<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ActivityController extends Controller
{
    /** GET /activities: active activities in the admin's sort order (choices in "Build your own journey"). */
    public function index(): AnonymousResourceCollection
    {
        return ActivityResource::collection(Activity::query()->active()->ordered()->get(['id', 'slug', 'name']));
    }
}
