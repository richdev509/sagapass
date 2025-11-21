<?php

use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// OAuth2 Protected API Routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    // User profile endpoint
    Route::get('/user', [UserApiController::class, 'profile']);

    // User documents verification endpoint
    Route::get('/user/documents', [UserApiController::class, 'documents']);
});
