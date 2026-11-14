<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<City> */
final class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'district_id' => fn () => District::query()->value('id')
                ?? District::create(['name' => 'Kandy', 'province_id' => Province::create(['name' => 'Central'])->id])->id,
            'name' => fake()->unique()->city(),
            'summary' => fake()->sentence(),
            'latitude' => fake()->randomFloat(5, 6.0, 9.5),
            'longitude' => fake()->randomFloat(5, 79.9, 81.8),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
