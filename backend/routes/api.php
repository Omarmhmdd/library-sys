<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api (default). Protected routes use auth:sanctum.
| Single controller: ApiController (auth + books).
|
*/

Route::get('/ping', fn () => response()->json(['pong' => true]));

// Auth (public)
Route::post('/auth/register', [ApiController::class, 'register']);
Route::post('/auth/login', [ApiController::class, 'login']);
Route::get('/auth/{provider}/redirect', [ApiController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [ApiController::class, 'handleProviderCallback']);

// Auth + Books (protected)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [ApiController::class, 'logout']);
    Route::get('/auth/me', [ApiController::class, 'me']);

    Route::get('/books', [ApiController::class, 'booksIndex']);
    Route::get('/books/{id}', [ApiController::class, 'booksShow']);
    Route::get('/books/{id}/similar', [ApiController::class, 'similarBooks']);
    Route::post('/books/{id}/borrow', [ApiController::class, 'borrow']);
    Route::post('/books/{id}/return', [ApiController::class, 'returnBook']);
    Route::get('/borrowals', [ApiController::class, 'myBorrowals']);
    Route::middleware('role:admin,librarian')->group(function (): void {
        Route::post('/books', [ApiController::class, 'booksStore']);
        Route::put('/books/{id}', [ApiController::class, 'booksUpdate']);
        Route::delete('/books/{id}', [ApiController::class, 'booksDestroy']);
    });
    Route::post('/ai/ask', [ApiController::class, 'aiAsk']);
    Route::post('/ai/suggest-metadata', [ApiController::class, 'aiSuggestMetadata']);
});
