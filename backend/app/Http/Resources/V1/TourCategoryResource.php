<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\TourCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TourCategory */
final class TourCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'label' => $this->label(),
            'icon' => $this->icon,
            'summary' => $this->summary,
            'image' => $this->imageUrl('image', 'card'),
            'thumb' => $this->imageUrl('image', 'thumb'),
            'toursCount' => (int) ($this->tours_count ?? 0),
        ];
    }
}
