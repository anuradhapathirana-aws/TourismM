<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TourResource;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TourController extends Controller
{
    /** GET /tours?category=beach-tours&featured=1 */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'featured' => ['nullable', 'boolean'],
        ]);

        $tours = Tour::query()
            ->public()
            ->with(['category:id,name,slug', 'days:id,tour_id,day_number,title,description', 'prices:id,tour_id,price_per_person', 'media'])
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'category', fn (Builder $category) => $category->where('slug', $slug)
            ))
            ->when($request->boolean('featured'), fn (Builder $query) => $query->where('is_featured', true))
            ->orderByDesc('is_featured')
            ->ordered()
            ->limit(200)
            ->get();

        return TourResource::collection($tours);
    }

    public function show(string $slug): TourResource
    {
        $tour = Tour::query()
            ->public()
            ->with([
                'category:id,name,slug',
                'prices',
                'media',
                'days.overnightCity:id,name,slug',
                'days.media',
                'days.activities' => fn ($query) => $query->active()->with('media'), // inactive/deleted hidden
                // only points the public may see; order by visit order
                'days.destinations' => fn ($query) => $query->public()->with(['media', 'placeType:id,name', 'city:id,name']),
            ])
            ->where('slug', $slug)
            ->first() ?? abort(404, 'Tour not found.');

        return (new TourResource($tour))->detailed();
    }
}
