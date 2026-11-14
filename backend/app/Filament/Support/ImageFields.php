<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\Validation\CommonRules;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

/** Image upload fields with the shared file rules (type, size, min dimensions; no SVG). */
final class ImageFields
{
    public static function single(string $collection, string $label = 'Cover image'): SpatieMediaLibraryFileUpload
    {
        $c = config('locations.images');

        return SpatieMediaLibraryFileUpload::make($collection)
            ->label($label)
            ->collection($collection)
            ->image()
            ->imageEditor()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize($c['max_kb'])
            ->rules([CommonRules::image()])
            ->tip('JPG, PNG or WebP · max '.($c['max_kb'] / 1024)." MB · at least {$c['min_width']}×{$c['min_height']} px")
            ->conversion('thumb');
    }

    public static function gallery(string $collection = 'gallery'): SpatieMediaLibraryFileUpload
    {
        $c = config('locations.images');

        return SpatieMediaLibraryFileUpload::make($collection)
            ->label('Gallery')
            ->collection($collection)
            ->multiple()
            ->reorderable()
            ->maxFiles($c['gallery_max'])
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize($c['max_kb'])
            ->rules([CommonRules::image()])
            ->panelLayout('grid')
            ->tip("Up to {$c['gallery_max']} photos. Drag to reorder. JPG, PNG or WebP · max ".($c['max_kb'] / 1024)." MB · at least {$c['min_width']}×{$c['min_height']} px")
            ->conversion('thumb');
    }
}
