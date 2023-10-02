<?php

declare(strict_types=1);

namespace Tests\Database\Factories;

use Tests\Models\User;

final class UserFactory extends \Orchestra\Testbench\Factories\UserFactory
{
    public function modelName(): string
    {
        return User::class;
    }
}
