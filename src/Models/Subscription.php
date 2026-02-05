<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Services\Period;
use Laravelcm\Subscriptions\Traits\BelongsToPlan;
use Laravelcm\Subscriptions\Traits\HasSlug;
use Laravelcm\Subscriptions\Traits\HasTranslations;
use LogicException;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read int|string $id
 * @property-read string $subscriber_type
 * @property-read int|string $subscriber_id
 * @property-read string $slug
 * @property-read array $name
 * @property-read array $description
 * @property-read int|string $plan_id
 * @property-read ?CarbonInterface $trial_ends_at
 * @property-read ?CarbonInterface $starts_at
 * @property-read ?CarbonInterface $ends_at
 * @property-read ?CarbonInterface $cancels_at
 * @property-read ?CarbonInterface $canceled_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read ?CarbonInterface $deleted_at
 * @property-read Plan $plan
 * @property-read Collection<int, SubscriptionUsage> $usage
 * @property-read Model $subscriber
 */
class Subscription extends Model
{
    use BelongsToPlan;
    use HasFactory;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'subscriber_id',
        'subscriber_type',
        'plan_id',
        'slug',
        'name',
        'description',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
    ];

    protected $casts = [
        'subscriber_type' => 'string',
        'slug' => 'string',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'canceled_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = [
        'name',
        'description',
    ];

    public function getTable()
    {
        return config('laravel-subscriptions.tables.subscriptions');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (! $model->starts_at || ! $model->ends_at) {
                $model->setNewPeriod();
            }
        });

        static::deleted(function (self $subscription): void {
            $subscription->usage()->delete();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->extraScope(fn ($builder) => $builder
                ->where('subscriber_type', $this->subscriber_type)
                ->where('subscriber_id', $this->subscriber_id)
            );
    }

    public function subscriber(): MorphTo
    {
        return $this->morphTo('subscriber', 'subscriber_type', 'subscriber_id', 'id');
    }

    public function usage(): HasMany
    {
        return $this->hasMany(config('laravel-subscriptions.models.subscription_usage'));
    }

    public function active(): bool
    {
        return ! $this->ended() || $this->onTrial();
    }

    public function inactive(): bool
    {
        return ! $this->active();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && Carbon::now()->lt($this->trial_ends_at);
    }

    public function canceled(): bool
    {
        return $this->canceled_at && Carbon::now()->gte($this->canceled_at);
    }

    public function ended(): bool
    {
        return $this->ends_at && Carbon::now()->gte($this->ends_at);
    }

    public function cancel(bool $immediately = false): self
    {
        $this->fill(['canceled_at' => Carbon::now()]);

        if ($immediately) {
            $this->fill(['ends_at' => $this->canceled_at]);
        }

        $this->save();

        return $this;
    }

    public function changePlan(Plan $plan): self
    {
        // If plans does not have the same billing frequency
        // (e.g., invoice_interval and invoice_period) we will update
        // the billing dates starting today, and since we are basically creating
        // a new billing cycle, the usage data will be cleared.
        if ($this->plan->invoice_interval !== $plan->invoice_interval || $this->plan->invoice_period !== $plan->invoice_period) {
            $this->setNewPeriod($plan->invoice_interval, $plan->invoice_period);
            $this->usage()->delete();
        }

        // Attach new plan to subscription
        $this->fill(['plan_id' => $plan->getKey()]);

        // Free plans never expire
        if ($plan->isFree()) {
            $this->fill([
                'ends_at' => null,
                'trial_ends_at' => null,
            ]);
        }

        $this->save();

        return $this;
    }

    /**
     * Renew subscription period.
     *
     * @return $this
     *
     * @throws LogicException
     */
    public function renew(): self
    {
        if ($this->ended() && $this->canceled()) {
            throw new LogicException('Unable to renew canceled ended subscription.');
        }

        $subscription = $this;

        DB::transaction(function () use ($subscription): void {
            // Clear usage data
            $subscription->usage()->delete();

            // Renew period
            $subscription->setNewPeriod();
            $subscription->fill(['canceled_at' => null]);
            $subscription->save();
        });

        return $this;
    }

    /**
     * Get bookings of the given subscriber.
     */
    public function scopeOfSubscriber(Builder $builder, Model $subscriber): Builder
    {
        return $builder->where('subscriber_type', $subscriber->getMorphClass())
            ->where('subscriber_id', $subscriber->getKey());
    }

    /**
     * Scope subscriptions with ending trial.
     */
    public function scopeFindEndingTrial(Builder $builder, int $dayRange = 3): Builder
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        return $builder->whereBetween('trial_ends_at', [$from, $to]);
    }

    /**
     * Scope subscriptions with ended trial.
     */
    public function scopeFindEndedTrial(Builder $builder): Builder
    {
        return $builder->where('trial_ends_at', '<=', Carbon::now());
    }

    /**
     * Scope subscriptions with ending periods.
     */
    public function scopeFindEndingPeriod(Builder $builder, int $dayRange = 3): Builder
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        return $builder->whereBetween('ends_at', [$from, $to]);
    }

    /**
     * Scope subscriptions with ended periods.
     */
    public function scopeFindEndedPeriod(Builder $builder): Builder
    {
        return $builder->where('ends_at', '<=', Carbon::now());
    }

    /**
     * Scope all active subscriptions for a user.
     */
    public function scopeFindActive(Builder $builder): Builder
    {
        return $builder->where('ends_at', '>', Carbon::now());
    }

    /**
     * Set new subscription period.
     *
     * @return $this
     */
    protected function setNewPeriod(string $invoice_interval = '', ?int $invoice_period = null, ?Carbon $start = null): self
    {
        if (empty($invoice_interval)) {
            $invoice_interval = $this->plan->invoice_interval;
        }

        if (empty($invoice_period)) {
            $invoice_period = $this->plan->invoice_period;
        }

        $period = new Period(
            interval: $invoice_interval,
            count: $invoice_period,
            start: $start ?? Carbon::now()
        );

        $this->fill([
            'starts_at' => $period->getStartDate(),
            'ends_at' => $period->getEndDate(),
        ]);

        return $this;
    }

    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true): SubscriptionUsage
    {
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();

        /** @var SubscriptionUsage $usage */
        $usage = $this->usage()->firstOrNew([
            'subscription_id' => $this->getKey(),
            'feature_id' => $feature->getKey(),
        ]);

        if ($feature->resettable_period) {
            // Set expiration date when the usage record is new or doesn't have one.
            if ($usage->valid_until === null) {
                // Set date from subscription creation date so the reset
                // period match the period specified by the subscription's plan.
                $usage->fill(['valid_until' => $feature->getResetDate($this->created_at)]);
            } elseif ($usage->expired()) {
                // If the usage record has been expired, let's assign
                // a new expiration date and reset the uses to zero.
                $usage->fill([
                    'valid_until' => $feature->getResetDate($usage->valid_until),
                    'used' => 0,
                ]);
            }
        }

        $usage->fill(['used' => $incremental ? $usage->used + $uses : $uses]);

        $usage->save();

        return $usage;
    }

    public function reduceFeatureUsage(string $featureSlug, int $uses = 1): ?SubscriptionUsage
    {
        $usage = $this->usage()->byFeatureSlug($featureSlug, $this->plan_id)->first();

        if ($usage === null) {
            return null;
        }

        $usage->used = max($usage->used - $uses, 0);

        $usage->save();

        return $usage;
    }

    public function canUseFeature(string $featureSlug): bool
    {
        $featureValue = $this->getFeatureValue($featureSlug);

        if ($featureValue === null) {
            return false;
        }

        if ($featureValue === 'true') {
            return true;
        }

        if ($featureValue === 'false' || $featureValue === '0') {
            return false;
        }

        $usage = $this->usage()->byFeatureSlug($featureSlug, $this->plan_id)->first();

        if ($usage && $usage->expired()) {
            return false;
        }

        return $this->getFeatureRemainings($featureSlug) > 0;
    }

    /**
     * Get how many times the feature has been used.
     */
    public function getFeatureUsage(string $featureSlug): int
    {
        $usage = $this->usage()->byFeatureSlug($featureSlug, $this->plan_id)->first();

        return (! $usage || $usage->expired()) ? 0 : $usage->used;
    }

    /**
     * Get the available uses.
     */
    public function getFeatureRemainings(string $featureSlug): int
    {
        return $this->getFeatureValue($featureSlug) - $this->getFeatureUsage($featureSlug);
    }

    public function getFeatureValue(string $featureSlug): ?string
    {
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();

        return $feature->value ?? null;
    }
}
