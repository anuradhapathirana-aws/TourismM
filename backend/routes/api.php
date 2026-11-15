<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\DestinationController;
use App\Http\Controllers\Api\V1\JourneyRequestController;
use App\Http\Controllers\Api\V1\PlaceTypeController;
use App\Http\Controllers\Api\V1\ThemeController;
use App\Http\Controllers\Api\V1\TourCategoryController;
use App\Http\Controllers\Api\V1\TourController;
use Illuminate\Support\Facades\Route;

/*
| Public API for the React website. Prefix: /api/v1 (bootstrap/app.php). Read-only except POST /journey-requests.
| Rate limited by the "public-api" limiter (60 req/min per IP). Only published / active data.
*/
Route::get('themes', [ThemeController::class, 'index']);
Route::get('activities', [ActivityController::class, 'index']);
Route::get('place-types', [PlaceTypeController::class, 'index']);
Route::get('tour-categories', [TourCategoryController::class, 'index']);

Route::get('tours', [TourController::class, 'index']);
Route::get('tours/{slug}', [TourController::class, 'show'])->where('slug', '[a-z0-9-]+');

Route::get('cities', [CityController::class, 'index']);
Route::get('cities/{slug}', [CityController::class, 'show'])->where('slug', '[a-z0-9-]+');

Route::get('destinations', [DestinationController::class, 'index']);
Route::get('destinations/{slug}', [DestinationController::class, 'show'])->where('slug', '[a-z0-9-]+');

/*
| The only write endpoint: "Build your own journey" requests from the website.
| Extra per-IP limit (config/inquiries.php → rate_limit) on top of the general API limit.
*/
Route::post('journey-requests', [JourneyRequestController::class, 'store'])->middleware('throttle:journey-requests');
