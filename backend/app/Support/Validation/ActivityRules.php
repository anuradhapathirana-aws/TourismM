<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\Activity;
use Illuminate\Validation\Rule;

final class ActivityRules
{
    public const DESCRIPTION_MAX = 1000;

    /** @return array<string, list<mixed>> */
    public static function rules(?Activity $activity = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('activities', 'name')->ignore($activity?->id)],
            'slug' => [...CommonRules::slug(120), Rule::unique('activities', 'slug')->ignore($activity?->id)],
            'description' => CommonRules::plainText(self::DESCRIPTION_MAX),
            'is_active' => ['boolean'],
            'sort_order' => CommonRules::sortOrder(),
        ];
    }
}
