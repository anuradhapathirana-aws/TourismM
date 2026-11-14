<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DestinationStatus;
use App\Models\City;
use App\Models\Destination;
use App\Models\PlaceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Destination> */
final class DestinationFactory extends Factory
{
    protected $model = Destination::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'place_type_id' => PlaceType::factory(),
            'name' => fake()->unique()->words(3, true),
            'summary' => fake()->sentence(10),
            'latitude' => fake()->randomFloat(5, 6.0, 9.5),
            'longitude' => fake()->randomFloat(5, 79.9, 81.8),
            'status' => DestinationStatus::Published,
            'is_featured' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => DestinationStatus::Draft]);
    }
}
