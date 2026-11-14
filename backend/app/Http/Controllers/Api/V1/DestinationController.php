<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DestinationResource;
use App\Services\DestinationQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class DestinationController extends Controller
{
    public function __construct(private readonly DestinationQuery $destinations) {}

    /** GET /destinations?theme=beaches&city=ella&type=waterfall&featured=1 */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'theme' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'city' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9-]+$/'],
            'type' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/'],
            'featured' => ['nullable', 'boolean'],
        ]);

        return DestinationResource::collection($this->destinations->list([
            ...$filters,
            'featured' => $request->boolean('featured'),
        ]));
    }

    public function show(string $slug): DestinationResource
    {
        $destination = $this->destinations->findBySlug($slug) ?? abort(404, 'Destination not found.');

        return (new DestinationResource($destination))->detailed();
    }
}
