<?php

declare(strict_types=1);

use Tests\Models\User;

it('User model has plan subscription trait or implement subscription methods', function (): void {
    expect(User::factory()->create())
        ->toHaveMethods([
            'activePlanSubscriptions',
            'planSubscription',
            'planSubscriptions',
            'newPlanSubscription',
            'subscribedPlans',
            'subscribedTo',
        ]);
});
