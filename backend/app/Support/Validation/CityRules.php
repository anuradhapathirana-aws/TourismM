<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\City;
use Illuminate\Validation\Rule;

final class CityRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(?City $city = null, ?int $districtId = null): array
    {
        return [
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')],
            'name' => [
                'required', 'string', 'min:2', 'max:120',
                Rule::unique('cities', 'name')
                    ->where('district_id', $districtId ?? $city?->district_id)
                    ->ignore($city?->id),
            ],
            'slug' => [...CommonRules::slug(140), Rule::unique('cities', 'slug')->ignore($city?->id)],
            'summary' => CommonRules::plainText(300),
            'description' => CommonRules::richText(),
            'latitude' => CommonRules::latitude(),
            'longitude' => CommonRules::longitude(),
            'is_active' => ['boolean'],
            'sort_order' => CommonRules::sortOrder(),
            'meta_title' => CommonRules::plainText(70),
            'meta_description' => CommonRules::plainText(160),
        ];
    }
}
