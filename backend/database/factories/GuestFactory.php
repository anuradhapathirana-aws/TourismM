<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Guest> */
final class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+44 7700 '.fake()->numerify('######'),
            'country' => fake()->randomElement(['United Kingdom', 'Germany', 'France', 'Australia', 'India']),
            'preferred_contact' => 'whatsapp',
        ];
    }
}
