<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravelcm\Subscriptions\Models\Subscription;
use Laravelcm\Subscriptions\Models\Plan;
use Laravelcm\Subscriptions\Services\Period;

trait HasPlanSubscriptions
{
    protected static function bootHasSubscriptions(): void
    {
        static::deleted(function ($plan): void {
            $plan->planSubscriptions()->delete();
        });
    }

    /**
     * The subscriber may have many plan subscriptions.
     *
     * @return MorphMany
     */
    public function planSubscriptions(): MorphMany
    {
        return $this->morphMany(
            related: config('laravel-subscriptions.models.subscription'),
            name: 'subscriber',
            type: 'subscriber_type',
            id: 'subscriber_id'
        );
    }

    public function activePlanSubscriptions(): Collection
    {
        return $this->planSubscriptions->reject->inactive();
    }

    public function planSubscription(string $subscriptionSlug): ?Subscription
    {
        return $this->planSubscriptions()->where('slug', $subscriptionSlug)->first();
    }

    public function subscribedPlans(): Collection
    {
        $planIds = $this->planSubscriptions->reject
            ->inactive()
            ->pluck('plan_id')
            ->unique();

        return tap(new (config('laravel-subscriptions.models.plan')))->whereIn('id', $planIds)->get();
    }

    public function subscribedTo(int $planId): bool
    {
        $subscription = $this->planSubscriptions()
            ->where('plan_id', $planId)
            ->first();

        return $subscription && $subscription->active();
    }

    public function newPlanSubscription(string $subscription, Plan $plan, ?Carbon $startDate = null): Subscription
    {
        $trial = new Period(
            interval: $plan->trial_interval,
            count: $plan->trial_period,
            start: $startDate ?? Carbon::now()
        );
        $period = new Period(
            interval: $plan->invoice_interval,
            count: $plan->invoice_period,
            start: $trial->getEndDate()
        );

        return $this->planSubscriptions()->create([
            'name' => $subscription,
            'plan_id' => $plan->getKey(),
            'trial_ends_at' => $trial->getEndDate(),
            'starts_at' => $period->getStartDate(),
            'ends_at' => $period->getEndDate(),
        ]);
    }
}
