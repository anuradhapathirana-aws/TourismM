<?php

return [

    'paths' => ['api/*'],

    // Public API is read-only
    'allowed_methods' => ['GET', 'OPTIONS'],

    // FRONTEND_URLS in .env (comma separated)
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URLS', 'http://localhost:5173'))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
