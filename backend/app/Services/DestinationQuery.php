<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Public read queries for destination points, with all relations eager loaded (no N+1). */
final class DestinationQuery
{
    /** Upper bound for the map payload. */
    public const MAX_RESULTS = 1000;

    /** @param array{theme?: ?string, city?: ?string, type?: ?string, featured?: ?bool} $filters */
    public function list(array $filters = []): Collection
    {
        return $this->base()
            ->when($filters['theme'] ?? null, fn (Builder $q, string $slug) => $q->whereHas(
                'themes', fn (Builder $t) => $t->where('slug', $slug)->where('is_active', true)
            ))
            ->when($filters['city'] ?? null, fn (Builder $q, string $slug) => $q->whereHas(
                'city', fn (Builder $c) => $c->where('slug', $slug)
            ))
            ->when($filters['type'] ?? null, fn (Builder $q, string $slug) => $q->whereHas(
                'placeType', fn (Builder $t) => $t->where('slug', $slug)
            ))
            ->when($filters['featured'] ?? false, fn (Builder $q) => $q->where('is_featured', true))
            ->ordered()
            ->limit(self::MAX_RESULTS)
            ->get();
    }

    public function findBySlug(string $slug): ?Destination
    {
        return $this->base()
            ->with(['city.district.province'])
            ->where('slug', $slug)
            ->first();
    }

    private function base(): Builder
    {
        return Destination::query()
            ->public()
            ->with([
                'city:id,name,slug,district_id',
                'placeType:id,name,slug,icon',
                'themes' => fn ($q) => $q->select('themes.id', 'themes.slug')->where('is_active', true),
                'media',
            ]);
    }
}
