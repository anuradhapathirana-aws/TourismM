<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\Meal;
use App\Enums\TourStatus;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Tour;
use App\Models\TourDay;
use App\Models\TourPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Field names match the React frontend contract (CLAUDE.md → API contract):
 * list = { id, slug, title, category, duration, price, featured, image, summary, highlights[], itinerary[{title,details}], inclusions[] }.
 * The detail endpoint adds the full day-by-day plan, price table, gallery and practical info.
 *
 * @mixin Tour
 */
final class TourResource extends JsonResource
{
    public bool $detailed = false;

    public function detailed(): self
    {
        $this->detailed = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $days = $this->days;
        $minPrice = $this->prices->min('price_per_person');

        $base = [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category?->name,
            'categorySlug' => $this->category?->slug,
            'categoryLabel' => $this->category?->label(),
            'duration' => Tour::durationLabel($days->count()),
            'days' => $days->count(),
            'price' => $minPrice !== null ? (float) $minPrice : null,
            'currency' => config('tours.currency'),
            'featured' => $this->is_featured,
            'image' => $this->imageUrl('cover', 'card'),
            'thumb' => $this->imageUrl('cover', 'thumb'),
            'summary' => $this->summary,
            'highlights' => $this->highlights ?? [],
            'itinerary' => $days->map(fn (TourDay $day) => ['title' => $day->title, 'details' => $day->description])->values(),
            'inclusions' => $this->inclusions ?? [],
        ];

        if (! $this->detailed) {
            return $base;
        }

        return [
            ...$base,
            'content' => $this->description,
            'imageLarge' => $this->imageUrl('cover', 'large'),
            'exclusions' => $this->exclusions ?? [],
            'pace' => $this->pace?->value,
            'groupMin' => $this->group_min,
            'groupMax' => $this->group_max,
            'startPoint' => $this->start_point,
            'endPoint' => $this->end_point,
            'bestMonths' => $this->best_months ?? [],
            'prices' => $this->prices->map(fn (TourPrice $row) => [
                'paxFrom' => $row->pax_from,
                'paxTo' => $row->pax_to,
                'pax' => $row->paxLabel(),
                'vehicle' => $row->vehicle,
                'pricePerPerson' => (float) $row->price_per_person,
            ])->values(),
            'itinerary' => $days->map(fn (TourDay $day) => [
                'day' => $day->day_number,
                'title' => $day->title,
                'details' => $day->description,
                'overnight' => $day->overnightCity ? ['name' => $day->overnightCity->name, 'slug' => $day->overnightCity->slug] : null,
                'hotel' => $day->hotel,
                'meals' => collect($day->meals ?? [])->map(fn (string $m) => Meal::tryFrom($m)?->value)->filter()->values(),
                'activities' => $day->activities->map(fn (Activity $activity) => [
                    'id' => $activity->id,
                    'slug' => $activity->slug,
                    'name' => $activity->name,
                    'description' => $activity->description,
                    'image' => $activity->imageUrl('images', 'card'),
                    'thumb' => $activity->imageUrl('images', 'thumb'),
                ])->values(),
                'distanceKm' => $day->distance_km,
                'driveHours' => $day->drive_hours,
                'image' => $day->imageUrl('image', 'card'),
                'destinations' => $day->destinations->map(fn (Destination $point) => [
                    'id' => $point->id,
                    'slug' => $point->slug,
                    'name' => $point->name,
                    'lat' => $point->latitude,
                    'lng' => $point->longitude,
                    'category' => $point->placeType?->name,
                    'city' => $point->city?->name,
                    'description' => $point->summary,
                    'image' => $point->imageUrl('cover', 'card'),
                    'thumb' => $point->imageUrl('cover', 'thumb'),
                ])->values(),
            ])->values(),
            'gallery' => $this->getMedia('gallery')->map(fn ($m) => [
                'thumb' => $this->mediaUrl($m, 'thumb'),
                'large' => $this->mediaUrl($m, 'large'),
                'alt' => $m->getCustomProperty('alt', $this->title),
            ])->values(),
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
            'published' => $this->status === TourStatus::Published,
        ];
    }
}
