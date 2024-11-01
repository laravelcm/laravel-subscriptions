<?php

declare(strict_types=1);

use Tests\Models\Feature;
use Tests\Models\Plan;

beforeEach(function (): void {
    $this->plan = Plan::factory()->create();
});

it('can create a plan', function (): void {
    expect(Plan::count())->toBe(1);
});

it('a plan can have many features', function (): void {
    expect($this->plan->features()->count())->toBe(0);

    Feature::factory()->create(['plan_id' => $this->plan->id]);
    Feature::factory()->create([
        'name' => 'pictures_per_listing',
        'value' => 10,
        'sort_order' => 5,
        'plan_id' => $this->plan->id,
    ]);

    expect($this->plan->features()->count())->toBe(2);
});

it('a plan can be free with trial period', function (): void {
    $this->plan->update([
        'price' => 0,
        'signup_fee' => 0,
    ]);

    expect($this->plan->isFree())
        ->toBeTrue()
        ->and($this->plan->hasTrial())
        ->toBeTrue();
});

it('a plan can have a grace period', function (): void {
    $this->plan->update([
        'grace_period' => 7,
        'grace_interval' => 'day',
    ]);

    expect($this->plan->hasGrace())
        ->toBeTrue();
});
