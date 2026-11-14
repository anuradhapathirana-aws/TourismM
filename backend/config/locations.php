<?php

return [

    /*
    | Coordinates accepted for cities and destination points. Matches the frontend map projection
    | (frontend/src/utils/geo.js → MAP_BOUNDS); points outside are not drawn on the website.
    */
    'bounds' => [
        'lat_min' => 5.85,
        'lat_max' => 9.95,
        'lng_min' => 79.5,
        'lng_max' => 82.0,
    ],

    'images' => [
        'max_kb' => 5120,        // 5 MB per image
        'min_width' => 800,
        'min_height' => 500,
        'gallery_max' => 12,
    ],

    // Demo seeder downloads Unsplash placeholder photos when true (needs internet).
    'seed_images' => (bool) env('SEED_IMAGES', false),

];
