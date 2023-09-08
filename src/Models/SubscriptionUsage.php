<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Rinvex\Support\Traits\ValidatingTrait;

/**
 * Laravelcm\Subscriptions\Models\SubscriptionUsage.
 *
 * @property int $id
 * @property int $subscription_id
 * @property int $feature_id
 * @property int $used
 * @property \Carbon\Carbon|null $valid_until
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \Laravelcm\Subscriptions\Models\Feature $feature
 * @property-read \Laravelcm\Subscriptions\Models\Subscription $subscription
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage byFeatureSlug($featureSlug)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage whereFeatureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage whereUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Laravelcm\Subscriptions\Models\SubscriptionUsage whereValidUntil($value)
 *
 */
final class SubscriptionUsage extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ValidatingTrait;

    protected $fillable = [
        'subscription_id',
        'feature_id',
        'used',
        'valid_until',
    ];

    protected $casts = [
        'subscription_id' => 'integer',
        'feature_id' => 'integer',
        'used' => 'integer',
        'valid_until' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $observables = [
        'validating',
        'validated',
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
        $this->setTable(config('laravel-subscriptions.tables.subscription_usage'));
        $this->mergeRules([
            'subscription_id' => 'required|integer|exists:'.config('laravel-subscriptions.tables.subscriptions').',id',
            'feature_id' => 'required|integer|exists:'.config('laravel-subscriptions.tables.features').',id',
            'used' => 'required|integer',
            'valid_until' => 'nullable|date',
        ]);

        parent::__construct($attributes);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(config('laravel-subscriptions.models.feature'), 'feature_id', 'id', 'feature');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(config('laravel-subscriptions.models.subscription'), 'subscription_id', 'id', 'subscription');
    }

    public function scopeByFeatureSlug(Builder $builder, string $featureSlug): Builder
    {
        $feature = app('laravel-subscriptions.models.feature')->where('slug', $featureSlug)->first();

        return $builder->where('feature_id', $feature ? $feature->getKey() : null);
    }

    public function expired(): bool
    {
        if (null === $this->valid_until) {
            return false;
        }

        return Carbon::now()->gte($this->valid_until);
    }
}
