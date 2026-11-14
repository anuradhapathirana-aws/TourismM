<?php

return [

    /*
    | Private admin entrance. The Filament panel is served ONLY at /{path}.
    | It is never linked from the public website; use a long random value per environment.
    */
    'path' => env('ADMIN_PATH', 'lg-admin-'.substr(hash('sha256', (string) env('APP_KEY')), 0, 10)),

    /* Comma-separated origins allowed to call the public API (CORS). */
    'frontend_urls' => array_values(array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URLS', 'http://localhost:5173'))))),

];
