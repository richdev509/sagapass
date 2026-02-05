<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsApp\WebhookController;

/*
|--------------------------------------------------------------------------
| WhatsApp Routes
|--------------------------------------------------------------------------
|
| Routes pour l'intégration WhatsApp Cloud API
| Webhook pour la réception et l'envoi de messages
|
*/

// Webhook WhatsApp - Vérification (GET)
Route::get('/webhook', [WebhookController::class, 'verify'])
    ->name('whatsapp.webhook.verify');

// Webhook WhatsApp - Réception des messages (POST)
Route::post('/webhook', [WebhookController::class, 'handle'])
    ->name('whatsapp.webhook.handle');

// Routes de test (à retirer en production)
if (config('app.env') !== 'production') {

    // Debug: voir les paramètres reçus
    Route::get('/test/debug', function () {
        return response()->json([
            'query_params' => request()->query(),
            'all_params' => request()->all(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    })->name('whatsapp.test.debug');

    // Test d'envoi de message
    Route::get('/test/send', function () {
        $whatsapp = app(\App\Services\WhatsApp\WhatsAppService::class);

        $to = request('to');
        $message = request('message', 'Test message from Sagaloto');

        if (!$to) {
            return response()->json(['error' => 'Missing "to" parameter'], 400);
        }

        $result = $whatsapp->sendTextMessage($to, $message);

        return response()->json([
            'status' => 'sent',
            'result' => $result,
        ]);
    })->name('whatsapp.test.send');

    // Test du menu principal
    Route::get('/test/menu', function () {
        $menu = app(\App\Services\WhatsApp\MenuService::class);

        $to = request('to');

        if (!$to) {
            return response()->json(['error' => 'Missing "to" parameter'], 400);
        }

        $menu->sendMainMenu($to);

        return response()->json([
            'status' => 'menu_sent',
            'to' => $to,
        ]);
    })->name('whatsapp.test.menu');

    // Test de session
    Route::get('/test/session', function () {
        $session = app(\App\Services\WhatsApp\SessionService::class);

        $phoneNumber = request('phone', '+50938123456');

        $sessionData = $session->getOrCreateSession($phoneNumber);

        return response()->json([
            'session' => $sessionData,
            'ttl_minutes' => $session->getTimeToLive($phoneNumber),
        ]);
    })->name('whatsapp.test.session');

    // Vérifier le token API
    Route::get('/test/verify-token', function () {
        $whatsapp = app(\App\Services\WhatsApp\WhatsAppService::class);

        $valid = $whatsapp->verifyToken();
        $info = $whatsapp->getPhoneNumberInfo();

        return response()->json([
            'valid' => $valid,
            'info' => $info,
        ]);
    })->name('whatsapp.test.verify');
}
