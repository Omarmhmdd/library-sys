<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api (default). Protected routes use auth:sanctum.
|
*/

Route::get('/ping', fn () => response()->json(['pong' => true]));

Route::middleware('auth:sanctum')->group(function (): void {
    // Books, checkout, AI endpoints will be added in feature branches.
});
