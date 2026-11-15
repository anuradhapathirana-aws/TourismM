<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\TourCategory;
use Illuminate\Validation\Rule;

final class TourCategoryRules
{
    public const ICON_REGEX = '/^[a-z0-9-]+$/';

    /** @return array<string, list<mixed>> */
    public static function rules(?TourCategory $category = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80', Rule::unique('tour_categories', 'name')->ignore($category?->id)],
            'slug' => [...CommonRules::slug(100), Rule::unique('tour_categories', 'slug')->ignore($category?->id)],
            // Bootstrap Icons class suffix only, e.g. "umbrella" for bi-umbrella
            'icon' => ['nullable', 'string', 'max:60', 'regex:'.self::ICON_REGEX],
            'summary' => CommonRules::plainText(300),
            'is_active' => ['boolean'],
            'sort_order' => CommonRules::sortOrder(),
        ];
    }
}
