<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Activity> */
final class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return ['name' => ucfirst(fake()->unique()->words(2, true)), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
