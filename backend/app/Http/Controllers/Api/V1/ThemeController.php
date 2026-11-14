<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ThemeResource;
use App\Models\Theme;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ThemeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ThemeResource::collection(Theme::query()->active()->with('media')->ordered()->get());
    }
}
