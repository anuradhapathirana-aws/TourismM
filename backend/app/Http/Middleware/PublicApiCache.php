<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public, read-only API: short shared caching + ETag on successful GETs (config/api.php → cache_seconds).
 * The API holds only published content, so "public" caching never exposes anything private.
 * Errors (404/422/429) are never cached.
 */
final class PublicApiCache
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $seconds = (int) config('api.cache_seconds', 60);

        if ($seconds <= 0 || ! $request->isMethodCacheable() || $response->getStatusCode() !== Response::HTTP_OK) {
            return $response;
        }

        $response->setPublic();
        $response->setMaxAge($seconds);
        $response->setEtag(hash('xxh128', (string) $response->getContent()));
        $response->isNotModified($request); // matching If-None-Match → 304 with an empty body

        return $response;
    }
}
