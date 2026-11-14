<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlaceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlaceType> */
final class PlaceTypeFactory extends Factory
{
    protected $model = PlaceType::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->word().' type', 'icon' => 'geo', 'is_active' => true];
    }
}
