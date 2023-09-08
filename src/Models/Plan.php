<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Rinvex\Support\Traits\ValidatingTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Rinvex\Support\Traits\HasTranslations;
use Rinvex\Support\Traits\HasSlug;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\SlugOptions;
use Spatie\EloquentSortable\Sortable;

/**
 * Laravelcm\Subscriptions\Models\Plan.
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array $description
 * @property bool$is_active
 * @property float $price
 * @property float $signup_fee
 * @property string $currency
 * @property int $trial_period
 * @property string $trial_interval
 * @property int $invoice_period
 * @property string $invoice_interval
 * @property int $grace_period
 * @property string $grace_interval
 * @property int $prorate_day
 * @property int $prorate_period
 * @property int $prorate_extend_due
 * @property int $active_subscribers_limit
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravelcm\Subscriptions\Models\Feature[]      $features
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravelcm\Subscriptions\Models\Subscription[] $subscriptions
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan ordered($direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereActiveSubscribersLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereGraceInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereGracePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereInvoiceInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereInvoicePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereProrateDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereProrateExtendDue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereProratePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereSignupFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereTrialInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereTrialPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\Plan whereUpdatedAt($value)
 */
final class Plan extends Model implements Sortable
{
    use HasFactory;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;
    use SortableTrait;
    use ValidatingTrait;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'price',
        'signup_fee',
        'currency',
        'trial_period',
        'trial_interval',
        'invoice_period',
        'invoice_interval',
        'grace_period',
        'grace_interval',
        'prorate_day',
        'prorate_period',
        'prorate_extend_due',
        'active_subscribers_limit',
        'sort_order',
    ];

    protected $casts = [
        'slug' => 'string',
        'is_active' => 'boolean',
        'price' => 'float',
        'signup_fee' => 'float',
        'currency' => 'string',
        'trial_period' => 'integer',
        'trial_interval' => 'string',
        'invoice_period' => 'integer',
        'invoice_interval' => 'string',
        'grace_period' => 'integer',
        'grace_interval' => 'string',
        'prorate_day' => 'integer',
        'prorate_period' => 'integer',
        'prorate_extend_due' => 'integer',
        'active_subscribers_limit' => 'integer',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $observables = [
        'validating',
        'validated',
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

    /**
     * The sortable settings.
     *
     * @var array
     */
    public $sortable = [
        'order_column_name' => 'sort_order',
    ];

    /**
     * The default rules that the model will validate against.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Whether the model should throw a
     * ValidationException if it fails validation.
     *
     * @var bool
     */
    protected $throwValidationExceptions = true;

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('laravel-subscriptions.tables.plans'));
        $this->mergeRules([
            'slug' => 'required|alpha_dash|max:150|unique:'.config('laravel-subscriptions.tables.plans').',slug',
            'name' => 'required|string|strip_tags|max:150',
            'description' => 'nullable|string|max:32768',
            'is_active' => 'sometimes|boolean',
            'price' => 'required|numeric',
            'signup_fee' => 'required|numeric',
            'currency' => 'required|alpha|size:3',
            'trial_period' => 'sometimes|integer|max:100000',
            'trial_interval' => 'sometimes|in:hour,day,week,month',
            'invoice_period' => 'sometimes|integer|max:100000',
            'invoice_interval' => 'sometimes|in:hour,day,week,month',
            'grace_period' => 'sometimes|integer|max:100000',
            'grace_interval' => 'sometimes|in:hour,day,week,month',
            'sort_order' => 'nullable|integer|max:100000',
            'prorate_day' => 'nullable|integer|max:150',
            'prorate_period' => 'nullable|integer|max:150',
            'prorate_extend_due' => 'nullable|integer|max:150',
            'active_subscribers_limit' => 'nullable|integer|max:100000',
        ]);

        parent::__construct($attributes);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function (Plan $plan): void {
            $plan->features()->delete();
            $plan->planSubscriptions()->delete();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function features(): HasMany
    {
        return $this->hasMany(config('laravel-subscriptions.models.feature'));
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(config('laravel-subscriptions.models.subscription'));
    }

    public function isFree(): bool
    {
        return $this->price <= 0.00;
    }

    public function hasTrial(): bool
    {
        return $this->trial_period && $this->trial_interval;
    }

    public function hasGrace(): bool
    {
        return $this->grace_period && $this->grace_interval;
    }

    /**
     * Get plan feature by the given slug.
     *
     * @param string $featureSlug
     *
     * @return \Laravelcm\Subscriptions\Models\Feature|null
     */
    public function getFeatureBySlug(string $featureSlug): ?Feature
    {
        return $this->features()->where('slug', $featureSlug)->first();
    }

    public function activate(): self
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['is_active' => false]);

        return $this;
    }
}
