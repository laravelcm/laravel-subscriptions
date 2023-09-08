<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToPlan
{
    public function plan(): BelongsTo
    {
        return $this->belongsTo(config('laravel-subscriptions.models.plan'), 'plan_id', 'id', 'plan');
    }

    public function scopeByPlanId(Builder $builder, int $planId): Builder
    {
        return $builder->where('plan_id', $planId);
    }
}
