<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CityResource;
use App\Models\City;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CityController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $cities = City::query()
            ->active()
            ->with(['district.province', 'media'])
            ->withCount('publishedDestinations')
            ->ordered()
            ->get();

        return CityResource::collection($cities);
    }

    public function show(string $slug): CityResource
    {
        $city = City::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'district.province',
                'media',
                'publishedDestinations' => fn ($q) => $q->ordered()->with([
                    'city:id,name,slug',
                    'placeType:id,name,slug,icon',
                    'themes' => fn ($t) => $t->select('themes.id', 'themes.slug')->where('is_active', true),
                    'media',
                ]),
            ])
            ->withCount('publishedDestinations')
            ->first() ?? abort(404, 'City not found.');

        return new CityResource($city);
    }
}
