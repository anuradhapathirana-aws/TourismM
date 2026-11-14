<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\District;
use Illuminate\Validation\Rule;

final class DistrictRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(?District $district = null): array
    {
        return [
            'province_id' => ['required', 'integer', Rule::exists('provinces', 'id')],
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('districts', 'name')->ignore($district?->id)],
            'slug' => [...CommonRules::slug(120), Rule::unique('districts', 'slug')->ignore($district?->id)],
        ];
    }
}
