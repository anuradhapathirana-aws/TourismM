<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\PlaceType;
use Illuminate\Validation\Rule;

final class PlaceTypeRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(?PlaceType $type = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:60', Rule::unique('place_types', 'name')->ignore($type?->id)],
            'slug' => [...CommonRules::slug(80), Rule::unique('place_types', 'slug')->ignore($type?->id)],
            // Bootstrap Icons class suffix only, e.g. "water" for bi-water
            'icon' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'is_active' => ['boolean'],
            'sort_order' => CommonRules::sortOrder(),
        ];
    }
}
