<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Shared image conversions for every model with photos (cities, destinations, themes, later tours/guides).
 * Conversions are WebP re-encodes, so EXIF/GPS metadata from the camera is not carried into served files.
 */
trait InteractsWithImages
{
    use InteractsWithMedia;

    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 400, 300)->format('webp')->nonQueued();
        $this->addMediaConversion('card')->fit(Fit::Max, 800, 800)->format('webp')->nonQueued();
        $this->addMediaConversion('large')->fit(Fit::Max, 1600, 1600)->format('webp');
    }

    /** URL of a conversion, falling back to the original while a queued conversion is pending. */
    public function imageUrl(string $collection, string $conversion = 'card'): ?string
    {
        $media = $this->getFirstMedia($collection);

        return $media ? $this->mediaUrl($media, $conversion) : null;
    }

    public function mediaUrl(Media $media, string $conversion = 'card'): string
    {
        return $media->hasGeneratedConversion($conversion)
            ? $media->getFullUrl($conversion)
            : $media->getFullUrl();
    }
}
