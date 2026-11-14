<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Enums\DestinationStatus;
use App\Models\Destination;
use Illuminate\Validation\Rule;

final class DestinationRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(?Destination $destination = null, ?int $cityId = null): array
    {
        return [
            'city_id' => [
                'required', 'integer',
                Rule::exists('cities', 'id')->whereNull('deleted_at'),
            ],
            'place_type_id' => [
                'required', 'integer',
                Rule::exists('place_types', 'id')->where('is_active', true),
            ],
            'name' => [
                'required', 'string', 'min:2', 'max:150',
                Rule::unique('destinations', 'name')
                    ->where('city_id', $cityId ?? $destination?->city_id)
                    ->ignore($destination?->id),
            ],
            'slug' => [...CommonRules::slug(170), Rule::unique('destinations', 'slug')->ignore($destination?->id)],
            'summary' => ['required', 'string', 'min:10', 'max:300'],
            'description' => CommonRules::richText(),
            'latitude' => CommonRules::latitude(),
            'longitude' => CommonRules::longitude(),
            'address' => CommonRules::plainText(255),
            'opening_hours' => CommonRules::plainText(150),
            'entry_fee' => CommonRules::plainText(150),
            'best_time_to_visit' => CommonRules::plainText(150),
            'visit_duration_minutes' => ['nullable', 'integer', 'between:5,1440'],
            'status' => ['required', Rule::enum(DestinationStatus::class)],
            'is_featured' => ['boolean'],
            'sort_order' => CommonRules::sortOrder(),
            'themes' => ['nullable', 'array', 'max:6'],
            'themes.*' => ['integer', 'distinct', Rule::exists('themes', 'id')->whereNull('deleted_at')],
            'meta_title' => CommonRules::plainText(70),
            'meta_description' => CommonRules::plainText(160),
        ];
    }
}
