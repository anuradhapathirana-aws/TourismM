<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin City */
final class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'content' => $this->when($this->relationLoaded('publishedDestinations'), $this->description),
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'image' => $this->imageUrl('cover', 'card'),
            'district' => $this->district?->name,
            'province' => $this->district?->province?->name,
            'destinationsCount' => $this->whenCounted('publishedDestinations'),
            'destinations' => DestinationResource::collection($this->whenLoaded('publishedDestinations')),
        ];
    }
}
