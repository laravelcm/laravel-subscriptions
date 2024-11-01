<?php

declare(strict_types=1);

use Laravelcm\Subscriptions\Models\Subscription;
use Spatie\TestTime\TestTime;
use Tests\Models\Feature;
use Tests\Models\Plan;
use Tests\Models\User;

beforeEach(function (): void {
    TestTime::freeze('Y-m-d H:i:s', '2024-11-01 00:00:01');

    $this->user = User::factory()->create();
    $this->plan = Plan::factory()->create();
});

it('User model implement subscription methods', function (): void {
    expect($this->user)
        ->toHaveMethods([
            'activePlanSubscriptions',
            'planSubscription',
            'planSubscriptions',
            'newPlanSubscription',
            'subscribedPlans',
            'subscribedTo',
        ]);
});

it('a user can subscribe to a plan', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->subscribedTo($this->plan->id))
        ->toBeTrue()
        ->and($this->user->subscribedPlans()->count())
        ->toBe(1);
});

it('user can have a monthly active subscription plan', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->planSubscription('main')->active())
        ->toBeTrue()
        ->and($this->user->planSubscription('main')->ends_at->toDateString())
        ->toBe(Carbon\Carbon::now()->addMonth()->addDays($this->plan->trial_period)->toDateString());
});

it('user can change plan', function (): void {
    $plan = Plan::factory()->create([
        'name' => 'Premium plan',
        'description' => 'Premium plan description',
        'price' => 25.50,
        'signup_fee' => 10.99,
    ]);

    $this->user->newPlanSubscription('main', $this->plan);

    $this->user->planSubscription('main')->changePlan($plan);

    expect($this->user->subscribedTo($plan->id))
        ->toBeTrue();
});

it('user can cancel a subscription', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->subscribedTo($this->plan->id))
        ->toBeTrue();

    $this->user->planSubscription('main')->cancel(true);

    expect($this->user->planSubscription('main')->canceled())
        ->toBeTrue();
});

it('feature can be used by a user in a plan', function (): void {
    Feature::factory(['plan_id' => $this->plan->id])->create();
    Feature::factory([
        'name' => 'view_podcast',
        'value' => 2,
        'plan_id' => $this->plan->id,
    ])->create();

    expect($this->plan->features()->count())
        ->toBe(2);

    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->planSubscription('main'))
        ->toBeInstanceOf(Subscription::class)
        ->and($this->user->planSubscription('main')->active())
        ->toBeTrue()
        ->and($this->user->planSubscription('main')->canUseFeature('view-podcast'));
});

it('feature can be decrease for a user after usage on subscription', function (): void {
    Feature::factory([
        'name' => 'view_podcast',
        'value' => 2,
        'plan_id' => $this->plan->id,
    ])->create();

    $this->user->newPlanSubscription('main', $this->plan);
    $this->user->planSubscription('main')->recordFeatureUsage('view-podcast');

    expect($this->user->planSubscription('main')->getFeatureUsage('view-podcast'))
        ->toBe(1)
        ->and($this->user->planSubscription('main')->getFeatureRemainings('view-podcast'))
        ->toBe(1);
});

it('feature can reduce for a user on a subscription', function (): void {
    Feature::factory([
        'name' => 'deploy sites',
        'value' => 5,
        'plan_id' => $this->plan->id,
    ])->create();
    $featureSlug = 'deploy-sites';

    $this->user->newPlanSubscription('main', $this->plan);
    $this->user->planSubscription('main')->recordFeatureUsage($featureSlug, 2);
    $this->user->planSubscription('main')->reduceFeatureUsage($featureSlug);

    expect($this->user->planSubscription('main')->getFeatureUsage($featureSlug))
        ->toBe(1)
        ->and($this->user->planSubscription('main')->getFeatureRemainings($featureSlug))
        ->toBe(4);
});
