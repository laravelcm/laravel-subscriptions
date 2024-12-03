<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Laravelcm\Subscriptions\Services\Period;
use Laravelcm\Subscriptions\Traits\BelongsToPlan;
use Laravelcm\Subscriptions\Traits\HasSlug;
use Laravelcm\Subscriptions\Traits\HasTranslations;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read int|string $id
 * @property string $slug
 * @property array $title
 * @property array $description
 * @property string $value
 * @property int $resettable_period
 * @property string $resettable_interval
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Plan $plan
 * @property-read \Illuminate\Database\Eloquent\Collection|SubscriptionUsage[] $usages
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Feature byPlanId($planId)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature ordered($direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereResettableInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereResettablePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereValue($value)
 */
class Feature extends Model implements Sortable
{
    use BelongsToPlan;
    use HasFactory;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;
    use SortableTrait;

    protected $fillable = [
        'plan_id',
        'slug',
        'name',
        'description',
        'value',
        'resettable_period',
        'resettable_interval',
        'sort_order',
    ];

    protected $casts = [
        'slug' => 'string',
        'value' => 'string',
        'resettable_period' => 'integer',
        'resettable_interval' => 'string',
        'sort_order' => 'integer',
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
        return config('laravel-subscriptions.tables.features', 'features');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function (Feature $feature): void {
            $feature->usage()->delete();
        });

        static::creating(function (Feature $feature) {
            if (static::where('plan_id', $feature->plan_id)->where('slug', $feature->slug)->exists()) {
                throw new InvalidArgumentException('Each plan should only have one feature with the same slug');
            }
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->allowDuplicateSlugs()
            ->saveSlugsTo('slug');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(config('laravel-subscriptions.models.subscription_usage'));
    }

    public function getResetDate(?Carbon $dateFrom = null): Carbon
    {
        $period = new Period($this->resettable_interval, $this->resettable_period, $dateFrom ?? Carbon::now());

        return $period->getEndDate();
    }
}
