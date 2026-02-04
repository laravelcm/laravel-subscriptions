<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravelcm\Subscriptions\Traits\HasSlug;
use Laravelcm\Subscriptions\Traits\HasTranslations;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read int|string $id
 * @property-read string $slug
 * @property-read array $name
 * @property-read array $description
 * @property-read bool $is_active
 * @property-read float $price
 * @property-read float $signup_fee
 * @property-read string $currency
 * @property-read int $trial_period
 * @property-read string $trial_interval
 * @property-read int $invoice_period
 * @property-read string $invoice_interval
 * @property-read int $grace_period
 * @property-read string $grace_interval
 * @property-read int $prorate_day
 * @property-read int $prorate_period
 * @property-read int $prorate_extend_due
 * @property-read int $active_subscribers_limit
 * @property-read int $sort_order
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read ?CarbonInterface $deleted_at
 * @property-read Collection<int, Feature> $features
 * @property-read Collection<int, Subscription> $subscriptions
 */
class Plan extends Model implements Sortable
{
    use HasFactory;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;
    use SortableTrait;

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
        'is_active' => 'boolean',
        'price' => 'float',
        'signup_fee' => 'float',
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

    public array $sortable = [
        'order_column_name' => 'sort_order',
    ];

    public function getTable(): string
    {
        return config('laravel-subscriptions.tables.plans');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function ($plan): void {
            $plan->features()->delete();
            $plan->subscriptions()->delete();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs();
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
