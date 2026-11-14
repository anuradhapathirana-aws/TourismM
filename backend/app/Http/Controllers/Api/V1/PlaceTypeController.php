<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PlaceTypeResource;
use App\Models\PlaceType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PlaceTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PlaceTypeResource::collection(
            PlaceType::query()->active()->orderBy('sort_order')->orderBy('name')->get()
        );
    }
}
