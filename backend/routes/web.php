<?php

use Illuminate\Support\Facades\Route;

/*
| The public website is the React app (frontend/). This Laravel app only serves:
|   - the private admin panel at /{ADMIN_PATH}  (registered by Filament)
|   - the read-only public API at /api/v1/*
| The root deliberately reveals nothing and never links or redirects to the admin.
*/
Route::get('/', fn () => response()->json(['name' => config('app.name'), 'api' => url('/api/v1')]));
