<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TourCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TourCategory> */
final class TourCategoryFactory extends Factory
{
    protected $model = TourCategory::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true).' tours', 'is_active' => true];
    }
}
