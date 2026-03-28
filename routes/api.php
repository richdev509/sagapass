<?php

use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\OAuth\OAuthController;
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

// ============================================
// OAUTH MOBILE ENDPOINTS (App-to-App flow)
// ============================================
Route::middleware(['auth:sanctum', 'throttle:30,1'])->prefix('oauth')->group(function () {
    // L'app SAGA ID récupère les infos de l'app tierce pour afficher le consent
    Route::get('/app-info', [OAuthController::class, 'getAppInfo']);

    // L'app SAGA ID envoie le consentement de l'utilisateur
    Route::post('/mobile-authorize', [OAuthController::class, 'mobileAuthorize']);
});

// ============================================
// OAUTH USERINFO (Token from third-party app)
// ============================================
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('oauth')->group(function () {
    Route::get('/userinfo', [OAuthController::class, 'userInfo']);
});
