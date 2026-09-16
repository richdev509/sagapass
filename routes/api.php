<?php

use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\Mobile\MobileAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================
// MOBILE APP ROUTES (PUBLIC)
// ============================================
Route::prefix('mobile')->group(function () {
    // Registration
    Route::post('/register/send-otp', [MobileAuthController::class, 'sendRegistrationOtp']);
    Route::post('/register/verify-otp', [MobileAuthController::class, 'verifyRegistrationOtp']);
    Route::post('/register/complete', [MobileAuthController::class, 'completeRegistration']);
    Route::post('/register/check-phone', [MobileAuthController::class, 'checkPhoneNumber']);
    Route::post('/register/check-niu', [MobileAuthController::class, 'checkNiu']);

    // Login
    Route::post('/login/send-otp', [MobileAuthController::class, 'sendLoginOtp']);
    Route::post('/login/verify-otp', [MobileAuthController::class, 'verifyLoginOtp']);
});

// ============================================
// PROTECTED ROUTES (Require Authentication)
// ============================================
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // User Profile
    Route::get('/user/profile', [UserApiController::class, 'profile']);
    Route::get('/user/documents', [UserApiController::class, 'documents']);
    Route::post('/user/resubmit-documents', [UserApiController::class, 'resubmitDocuments']);
});
