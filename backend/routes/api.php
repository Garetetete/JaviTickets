<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1  (prefijo "api/v1" definido en bootstrap/app.php)
|--------------------------------------------------------------------------
| Estructura por audiencias (ver docs/specs/api/endpoints.md):
|   - Públicas / consumidas por la tienda  (auth:api_client, JWT cliente)
|   - Validación en puerta                 (auth:gate, JWT usuario)
|   - Panel admin                          (auth:admin, JWT usuario)
| Los endpoints se irán montando por fase; de momento solo el health check.
*/

Route::get('/up', fn () => response()->json([
    'status' => 'ok',
    'service' => config('app.name'),
    'time' => now()->toIso8601String(),
]));
