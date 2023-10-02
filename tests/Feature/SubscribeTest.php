<?php

declare(strict_types=1);

use Tests\Models\Plan;
use Tests\Models\User;

beforeEach(function (): void {
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
})->group('subscribe');

it('a user can subscribe to a plan', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->subscribedTo($this->plan->id))
        ->toBeTrue()
        ->and($this->user->subscribedPlans()->count())
        ->toBe(1);
})->group('subscribe');

it('user can have a monthly active subscription plan', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->planSubscription('main')->active())
        ->toBeTrue()
        ->and($this->user->planSubscription('main')->ends_at->toDateString())
        ->toBe(\Carbon\Carbon::now()->addMonth()->addDays($this->plan->trial_period)->toDateString());
})->group('subscribe');

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
})->group('subscribe');

it('user can cancel a subscription', function (): void {
    $this->user->newPlanSubscription('main', $this->plan);

    expect($this->user->subscribedTo($this->plan->id))
        ->toBeTrue();

    $this->user->planSubscription('main')->cancel(true);

    expect($this->user->planSubscription('main')->canceled())
        ->toBeTrue();
})->group('subscribe');
