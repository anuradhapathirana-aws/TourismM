<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\PlaceType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PlaceType */
final class PlaceTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'icon' => $this->icon,
        ];
    }
}
