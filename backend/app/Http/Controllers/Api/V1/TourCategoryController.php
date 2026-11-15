<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TourStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TourCategoryResource;
use App\Models\TourCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TourCategoryController extends Controller
{
    /** GET /tour-categories: active categories; toursCount counts published tours only. */
    public function index(): AnonymousResourceCollection
    {
        return TourCategoryResource::collection(
            TourCategory::query()
                ->active()
                ->with('media')
                ->withCount(['tours' => fn (Builder $query) => $query->where('status', TourStatus::Published)])
                ->ordered()
                ->get()
        );
    }
}
