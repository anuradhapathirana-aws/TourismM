<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Field names match the React frontend contract (CLAUDE.md → API contract).
 * `description` = short plain summary (cards / map); `content` = rich HTML (detail endpoint only).
 *
 * @mixin Destination
 */
final class DestinationResource extends JsonResource
{
    /** Include detail-only fields (gallery, travel info, rich content). */
    public bool $detailed = false;

    public function detailed(): self
    {
        $this->detailed = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->placeType?->name,
            'categorySlug' => $this->placeType?->slug,
            'icon' => $this->placeType?->icon,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'themes' => $this->themes->pluck('slug')->values(),
            'featured' => $this->is_featured,
            'description' => $this->summary,
            'image' => $this->imageUrl('cover', 'card'),
            'thumb' => $this->imageUrl('cover', 'thumb'),
            'city' => [
                'id' => $this->city->id,
                'name' => $this->city->name,
                'slug' => $this->city->slug,
            ],
        ];

        if (! $this->detailed) {
            return $base;
        }

        return [
            ...$base,
            'content' => $this->description,
            'imageLarge' => $this->imageUrl('cover', 'large'),
            'gallery' => $this->getMedia('gallery')->map(fn ($m) => [
                'thumb' => $this->mediaUrl($m, 'thumb'),
                'large' => $this->mediaUrl($m, 'large'),
                'alt' => $m->getCustomProperty('alt', $this->name),
            ])->values(),
            'address' => $this->address,
            'openingHours' => $this->opening_hours,
            'entryFee' => $this->entry_fee,
            'bestTimeToVisit' => $this->best_time_to_visit,
            'visitDurationMinutes' => $this->visit_duration_minutes,
            'district' => $this->city->district?->name,
            'province' => $this->city->district?->province?->name,
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
        ];
    }
}
