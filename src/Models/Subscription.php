<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
 * Laravelcm\Subscriptions\Models\Subscription.
 *
 * @property int $id
 * @property int $subscriber_id
 * @property string $subscriber_type
 * @property int $plan_id
 * @property string $slug
 * @property array $title
 * @property array $description
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $cancels_at
 * @property Carbon|null $canceled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Plan $plan
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravelcm\Subscriptions\Models\SubscriptionUsage[] $usage
 * @property-read Model $subscriber
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription byPlanId($planId)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription findEndedPeriod()
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription findEndedTrial()
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription findEndingPeriod($dayRange = 3)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription findEndingTrial($dayRange = 3)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription ofSubscriber(\Illuminate\Database\Eloquent\Model $subscriber)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereCanceledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereCancelsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereSubscriberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Subscription whereSubscriberType($value)
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
        'cancels_at',
        'canceled_at',
    ];

    protected $casts = [
        'subscriber_id' => 'integer',
        'subscriber_type' => 'string',
        'plan_id' => 'integer',
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

    public function getTable(): string
    {
        return config('laravel-subscriptions.tables.subscriptions');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if ( ! $model->starts_at || ! $model->ends_at) {
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
            ->saveSlugsTo('slug');
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
        $this->canceled_at = Carbon::now();

        if ($immediately) {
            $this->ends_at = $this->canceled_at;
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
        $this->plan_id = $plan->getKey();
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
            $subscription->canceled_at = null;
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

        $this->starts_at = $period->getStartDate();
        $this->ends_at = $period->getEndDate();

        return $this;
    }

    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true): SubscriptionUsage
    {
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();

        $usage = $this->usage()->firstOrNew([
            'subscription_id' => $this->getKey(),
            'feature_id' => $feature->getKey(),
        ]);

        if ($feature->resettable_period) {
            // Set expiration date when the usage record is new or doesn't have one.
            if ($usage->valid_until === null) {
                // Set date from subscription creation date so the reset
                // period match the period specified by the subscription's plan.
                $usage->valid_until = $feature->getResetDate($this->created_at);
            } elseif ($usage->expired()) {
                // If the usage record has been expired, let's assign
                // a new expiration date and reset the uses to zero.
                $usage->valid_until = $feature->getResetDate($usage->valid_until);
                $usage->used = 0;
            }
        }

        $usage->used = $incremental ? $usage->used + $uses : $uses;

        $usage->save();

        return $usage;
    }

    public function reduceFeatureUsage(string $featureSlug, int $uses = 1): ?SubscriptionUsage
    {
        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        if ($usage === null) {
            return null;
        }

        $usage->used = max($usage->used - $uses, 0);

        $usage->save();

        return $usage;
    }

    /**
     * Determine if the feature can be used.
     */
    public function canUseFeature(string $featureSlug): bool
    {
        $featureValue = $this->getFeatureValue($featureSlug);
        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        if ($featureValue === 'true') {
            return true;
        }

        // If the feature value is zero, let's return false since
        // there's no uses available. (useful to disable countable features)
        if ( ! $usage || $usage->expired() || $featureValue === null || $featureValue === '0' || $featureValue === 'false') {
            return false;
        }

        // Check for available uses
        return $this->getFeatureRemainings($featureSlug) > 0;
    }

    /**
     * Get how many times the feature has been used.
     */
    public function getFeatureUsage(string $featureSlug): int
    {
        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        return ( ! $usage || $usage->expired()) ? 0 : $usage->used;
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
