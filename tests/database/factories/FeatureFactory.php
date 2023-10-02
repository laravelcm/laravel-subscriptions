<?php

declare(strict_types=1);

namespace Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Models\Feature;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        return [
            'name' => 'listings',
            'value' => 50,
            'sort_order' => 1,
        ];
    }
}
