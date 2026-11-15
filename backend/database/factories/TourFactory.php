<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TourPace;
use App\Enums\TourStatus;
use App\Models\Tour;
use App\Models\TourCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tour> */
final class TourFactory extends Factory
{
    protected $model = Tour::class;

    public function definition(): array
    {
        return [
            'tour_category_id' => TourCategory::factory(),
            'title' => fake()->unique()->numberBetween(2, 14).' Days '.ucfirst(fake()->unique()->words(2, true)),
            'summary' => fake()->sentence(12),
            'highlights' => ['Sigiriya at sunrise', 'Scenic train ride'],
            'inclusions' => ['Private vehicle & chauffeur-guide'],
            'pace' => TourPace::Moderate,
            'group_min' => 1,
            'status' => TourStatus::Draft,
        ];
    }

    public function published(): self
    {
        return $this->state(['status' => TourStatus::Published]);
    }

    /** @param  list<list<int>>  $pointsPerDay  destination ids visited on each day */
    public function withDays(array $pointsPerDay, array $prices = [[1, 2, 900], [3, null, 650]]): self
    {
        return $this->afterCreating(function (Tour $tour) use ($pointsPerDay, $prices): void {
            foreach ($pointsPerDay as $i => $points) {
                $day = $tour->days()->create(['day_number' => $i + 1, 'title' => 'Day '.($i + 1), 'description' => 'Sightseeing.']);
                $day->destinations()->sync(collect($points)->mapWithKeys(fn ($id, $n) => [$id => ['sort_order' => $n + 1]])->all());
            }
            foreach ($prices as [$from, $to, $price]) {
                $tour->prices()->create(['pax_from' => $from, 'pax_to' => $to, 'price_per_person' => $price]);
            }
        });
    }
}
