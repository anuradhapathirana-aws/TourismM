<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJourneyRequest;
use App\Services\InquiryIntake;
use Illuminate\Http\JsonResponse;

final class JourneyRequestController extends Controller
{
    /** POST /journey-requests → 201 { data: { reference } } · 422 { errors } · 429 when over the limit. */
    public function store(StoreJourneyRequest $request, InquiryIntake $intake): JsonResponse
    {
        // Honeypot filled in: a bot. Answer like a success so it learns nothing, but save nothing.
        if (filled($request->input('website'))) {
            return response()->json(['data' => ['reference' => null]], 201);
        }

        $inquiry = $intake->fromWebsite($request->validated(), $request->ip(), $request->userAgent());

        return response()->json(['data' => ['reference' => $inquiry->reference]], 201);
    }
}
