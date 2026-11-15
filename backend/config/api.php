<?php

return [
    /*
    | Public website API (/api/v1). Successful GET responses may be cached by browsers / CDNs for this many
    | seconds and carry an ETag, so repeat requests get a cheap "304 Not Modified". 0 turns caching off.
    | Admin changes reach visitors at most this long after saving.
    */
    'cache_seconds' => (int) env('API_CACHE_SECONDS', 60),
];
