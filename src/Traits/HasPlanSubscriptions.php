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
    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $localKey
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    abstract public function morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null): MorphMany;

    protected static function bootHasSubscriptions(): void
    {
        static::deleted(function ($plan): void {
            $plan->planSubscriptions()->delete();
        });
    }

    /**
     * The subscriber may have many plan subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function planSubscriptions(): MorphMany
    {
        return $this->morphMany(config('laravel-subscriptions.models.subscription'), 'subscriber', 'subscriber_type', 'subscriber_id');
    }

    public function activePlanSubscriptions(): Collection
    {
        return $this->planSubscriptions->reject->inactive();
    }

    /**
     * Get a plan subscription by slug.
     *
     * @param string $subscriptionSlug
     *
     * @return \Laravelcm\Subscriptions\Models\Subscription|null
     */
    public function planSubscription(string $subscriptionSlug): ?Subscription
    {
        return $this->planSubscriptions()->where('slug', $subscriptionSlug)->first();
    }

    /**
     * Get subscribed plans.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function subscribedPlans(): Collection
    {
        $planIds = $this->planSubscriptions->reject
            ->inactive()
            ->pluck('plan_id')
            ->unique();

        return app('laravelcm.subscriptions.models.plan')->whereIn('id', $planIds)->get();
    }

    /**
     * Check if the subscriber subscribed to the given plan.
     *
     * @param int $planId
     *
     * @return bool
     */
    public function subscribedTo(int $planId): bool
    {
        $subscription = $this->planSubscriptions()
            ->where('plan_id', $planId)
            ->first();

        return $subscription && $subscription->active();
    }

    /**
     * Subscribe subscriber to a new plan.
     *
     * @param string $subscription
     * @param \Laravelcm\Subscriptions\Models\Plan $plan
     * @param \Carbon\Carbon|null $startDate
     *
     * @return \Laravelcm\Subscriptions\Models\Subscription
     */
    public function newPlanSubscription(string $subscription, Plan $plan, ?Carbon $startDate = null): Subscription
    {
        $trial = new Period($plan->trial_interval, $plan->trial_period, $startDate ?? Carbon::now());
        $period = new Period($plan->invoice_interval, $plan->invoice_period, $trial->getEndDate());

        return $this->planSubscriptions()->create([
            'name' => $subscription,
            'plan_id' => $plan->getKey(),
            'trial_ends_at' => $trial->getEndDate(),
            'starts_at' => $period->getStartDate(),
            'ends_at' => $period->getEndDate(),
        ]);
    }
}
