<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ThemeSide;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Theme> */
final class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    public function definition(): array
    {
        return ['title' => fake()->unique()->words(2, true), 'side' => ThemeSide::Left, 'is_active' => true];
    }
}
