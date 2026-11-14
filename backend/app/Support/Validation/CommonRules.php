<?php

declare(strict_types=1);

namespace App\Support\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/** Reusable rule fragments shared by every module (admin forms, future write APIs, tests). */
final class CommonRules
{
    public const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** @return list<mixed> */
    public static function latitude(): array
    {
        $b = config('locations.bounds');

        return ['required', 'numeric', "between:{$b['lat_min']},{$b['lat_max']}"];
    }

    /** @return list<mixed> */
    public static function longitude(): array
    {
        $b = config('locations.bounds');

        return ['required', 'numeric', "between:{$b['lng_min']},{$b['lng_max']}"];
    }

    /** @return list<mixed> */
    public static function slug(int $max = 170): array
    {
        return ['required', 'string', "max:{$max}", 'regex:'.self::SLUG_REGEX];
    }

    /** @return list<mixed> */
    public static function plainText(int $max, bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', "max:{$max}"];
    }

    /** @return list<mixed> */
    public static function richText(int $max = 20000): array
    {
        return ['nullable', 'string', "max:{$max}"];
    }

    /** @return list<mixed> */
    public static function sortOrder(): array
    {
        return ['nullable', 'integer', 'between:0,65535'];
    }

    /** Uploaded image: real raster image (no SVG), size + dimension limits from config/locations.php. */
    public static function image(): File
    {
        $c = config('locations.images');

        return File::image()
            ->types(['jpg', 'jpeg', 'png', 'webp'])
            ->max($c['max_kb'])
            ->dimensions(Rule::dimensions()->minWidth($c['min_width'])->minHeight($c['min_height']));
    }
}
