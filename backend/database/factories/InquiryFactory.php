<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\TourPace;
use App\Models\Guest;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Inquiry> */
final class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        $arrival = now()->addDays(fake()->numberBetween(20, 200))->startOfDay();

        return [
            'guest_id' => Guest::factory(),
            'type' => InquiryType::CustomTour,
            'source' => InquirySource::Website,
            'status' => InquiryStatus::New,
            'arrival_date' => $arrival,
            'departure_date' => $arrival->copy()->addDays(fake()->numberBetween(5, 14)),
            'arrival_airport' => 'cmb',
            'pace' => TourPace::Moderate,
            'men' => 1,
            'women' => 1,
            'children' => 0,
            'accommodation' => 'comfort',
            'rooms' => 1,
            'meal_plan' => 'bb',
            'vehicle' => 'recommend',
            'guide_language' => 'English',
            'notes' => fake()->sentence(),
        ];
    }

    public function status(InquiryStatus $status): self
    {
        return $this->state(['status' => $status]);
    }
}
