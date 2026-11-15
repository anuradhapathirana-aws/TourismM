<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Enums\Meal;
use App\Enums\TourPace;
use App\Enums\TourStatus;
use App\Models\Tour;
use Closure;
use Illuminate\Validation\Rule;

/**
 * Tour, itinerary day and price-row rules. Used by the admin form (via FormRules::for), tests and any
 * future write API. Publishing a tour needs at least one day, one price row and a cover image.
 */
final class TourRules
{
    public const MAX_DAYS = 30;

    public const MAX_POINTS_PER_DAY = 15;

    public const MAX_ACTIVITIES_PER_DAY = 10;

    public const MAX_PRICE_ROWS = 10;

    public const MAX_PAX = 50;

    public const LIST_ITEM_MAX = 150;

    /** @return array<string, list<mixed>> */
    public static function rules(?Tour $tour = null, mixed $status = null): array
    {
        $published = ($status instanceof TourStatus ? $status : TourStatus::tryFrom((string) $status)) === TourStatus::Published;

        return [
            'tour_category_id' => ['required', 'integer', Rule::exists('tour_categories', 'id')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'min:3', 'max:150', Rule::unique('tours', 'title')->ignore($tour?->id)],
            'slug' => [...CommonRules::slug(170), Rule::unique('tours', 'slug')->ignore($tour?->id)],
            'summary' => ['required', 'string', 'min:10', 'max:300'],
            'description' => CommonRules::richText(),
            'highlights' => ['nullable', 'array', 'max:12'],
            'highlights.*' => ['string', 'max:'.self::LIST_ITEM_MAX],
            'inclusions' => ['nullable', 'array', 'max:20'],
            'inclusions.*' => ['string', 'max:'.self::LIST_ITEM_MAX],
            'exclusions' => ['nullable', 'array', 'max:20'],
            'exclusions.*' => ['string', 'max:'.self::LIST_ITEM_MAX],
            'pace' => ['required', Rule::enum(TourPace::class)],
            'group_min' => ['required', 'integer', 'between:1,'.self::MAX_PAX],
            'group_max' => ['nullable', 'integer', 'between:1,'.self::MAX_PAX, 'gte:group_min'],
            'start_point' => CommonRules::plainText(120),
            'end_point' => CommonRules::plainText(120),
            'best_months' => ['nullable', 'array', 'max:12'],
            'best_months.*' => ['integer', 'distinct', 'between:1,12'],
            'status' => ['required', Rule::enum(TourStatus::class)],
            'is_featured' => ['boolean'],
            'sort_order' => CommonRules::sortOrder(),
            'meta_title' => CommonRules::plainText(70),
            'meta_description' => CommonRules::plainText(160),
            'days' => ['required', 'array', 'min:1', 'max:'.self::MAX_DAYS],
            'prices' => [$published ? 'required' : 'nullable', 'array', $published ? 'min:1' : 'min:0', 'max:'.self::MAX_PRICE_ROWS, self::pricesDoNotOverlap()],
        ];
    }

    /** One itinerary day (repeater item). @return array<string, list<mixed>> */
    public static function dayRules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'description' => CommonRules::plainText(2000),
            'destination_ids' => ['nullable', 'array', 'max:'.self::MAX_POINTS_PER_DAY],
            'destination_ids.*' => ['integer', 'distinct', Rule::exists('destinations', 'id')->whereNull('deleted_at')],
            'overnight_city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->whereNull('deleted_at')],
            'hotel' => CommonRules::plainText(150),
            'meals' => ['nullable', 'array'],
            'meals.*' => [Rule::enum(Meal::class)],
            'activity_ids' => ['nullable', 'array', 'max:'.self::MAX_ACTIVITIES_PER_DAY],
            'activity_ids.*' => ['integer', 'distinct', Rule::exists('activities', 'id')->whereNull('deleted_at')],
            'distance_km' => ['nullable', 'integer', 'between:0,1000'],
            'drive_hours' => ['nullable', 'numeric', 'between:0,24'],
        ];
    }

    /** One group-size price row (repeater item). @return array<string, list<mixed>> */
    public static function priceRules(): array
    {
        return [
            'pax_from' => ['required', 'integer', 'between:1,'.self::MAX_PAX],
            'pax_to' => ['nullable', 'integer', 'between:1,'.self::MAX_PAX],
            'vehicle' => CommonRules::plainText(80),
            'price_per_person' => ['required', 'numeric', 'between:1,100000'],
        ];
    }

    /** Group-size ranges must be valid (to ≥ from) and must not overlap (1–2 and 2–4 both cover 2 travellers). */
    public static function pricesDoNotOverlap(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $ranges = collect(is_array($value) ? $value : [])
                ->filter(fn ($row) => is_array($row) && is_numeric($row['pax_from'] ?? null))
                ->map(fn (array $row) => [
                    (int) $row['pax_from'],
                    filled($row['pax_to'] ?? null) ? (int) $row['pax_to'] : self::MAX_PAX,
                ])
                ->sortBy(0)
                ->values();

            foreach ($ranges as $i => [$from, $to]) {
                if ($to < $from) {
                    $fail("A price row says {$from} to {$to} travellers. The “to” number must be at least the “from” number.");

                    return;
                }
                if ($i > 0 && $from <= $ranges[$i - 1][1]) {
                    $fail('Group sizes overlap. Each number of travellers must appear in only one price row (e.g. 1–2, 3–4, 5+).');

                    return;
                }
            }
        };
    }
}
