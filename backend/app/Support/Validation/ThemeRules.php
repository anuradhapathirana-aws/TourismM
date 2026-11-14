<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Enums\ThemeSide;
use App\Models\Theme;
use Illuminate\Validation\Rule;

final class ThemeRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(?Theme $theme = null): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:80', Rule::unique('themes', 'title')->ignore($theme?->id)],
            'slug' => [...CommonRules::slug(100), Rule::unique('themes', 'slug')->ignore($theme?->id)],
            'side' => ['required', Rule::enum(ThemeSide::class)],
            'is_active' => ['boolean'],
            'sort_order' => CommonRules::sortOrder(),
        ];
    }
}
