<?php

declare(strict_types=1);

namespace Database\Seeders;

use Spatie\MediaLibrary\HasMedia;
use Throwable;

/**
 * Downloads placeholder photos (Unsplash) for demo content, only when SEED_IMAGES=true in .env.
 * Failures are reported and skipped; seeding never aborts because of the network.
 */
final class DemoImages
{
    public static function attach(HasMedia $model, string $collection, ?string $photoId, int $width = 1200): void
    {
        if (! $photoId || ! config('locations.seed_images') || $model->getFirstMedia($collection)) {
            return;
        }

        $url = "https://images.unsplash.com/{$photoId}?auto=format&fit=crop&w={$width}&q=80&fm=jpg";

        try {
            $model->addMediaFromUrl($url, 'image/jpeg', 'image/png', 'image/webp')
                ->usingFileName(str($photoId)->slug().'.jpg')
                ->toMediaCollection($collection);
        } catch (Throwable $e) {
            fwrite(STDERR, "  ! image skipped ({$photoId}): {$e->getMessage()}\n");
        }
    }
}
