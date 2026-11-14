<?php

use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\DestinationController;
use App\Http\Controllers\Api\V1\PlaceTypeController;
use App\Http\Controllers\Api\V1\ThemeController;
use Illuminate\Support\Facades\Route;

/*
| Public, read-only API for the React website. Prefix: /api/v1 (bootstrap/app.php).
| Rate limited by the "public-api" limiter (60 req/min per IP). Only published / active data.
*/
Route::get('themes', [ThemeController::class, 'index']);
Route::get('place-types', [PlaceTypeController::class, 'index']);

Route::get('cities', [CityController::class, 'index']);
Route::get('cities/{slug}', [CityController::class, 'show'])->where('slug', '[a-z0-9-]+');

Route::get('destinations', [DestinationController::class, 'index']);
Route::get('destinations/{slug}', [DestinationController::class, 'show'])->where('slug', '[a-z0-9-]+');
