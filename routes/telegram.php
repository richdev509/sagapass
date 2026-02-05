<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Telegram\WebhookController;

/*
|--------------------------------------------------------------------------
| Telegram Bot Routes
|--------------------------------------------------------------------------
|
| Routes pour gérer le webhook Telegram et les routes de test.
| Le webhook reçoit tous les updates du bot Telegram (messages, boutons).
|
*/

// Webhook Telegram (PRODUCTION)
Route::post('/webhook', [WebhookController::class, 'handle'])
    ->name('telegram.webhook');

// Routes de test (à désactiver en production)
if (config('app.env') === 'local' || config('app.env') === 'development') {

    Route::get('/test', function () {
        return response()->json([
            'status' => 'Telegram routes OK',
            'timestamp' => now()->toIso8601String(),
            'bot_username' => config('telegram.bot_username'),
        ]);
    })->name('telegram.test');

    Route::get('/set-webhook', function () {
        $telegramService = app(\App\Services\Telegram\TelegramService::class);
        $webhookUrl = config('telegram.webhook_url');

        $result = $telegramService->setWebhook($webhookUrl);

        return response()->json([
            'webhook_url' => $webhookUrl,
            'result' => $result,
        ]);
    })->name('telegram.set-webhook');

    Route::get('/get-webhook-info', function () {
        $telegramService = app(\App\Services\Telegram\TelegramService::class);
        $token = config('telegram.bot_token');

        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://api.telegram.org/bot{$token}/getWebhookInfo");

        return response()->json([
            'webhook_info' => json_decode($response->getBody()->getContents(), true),
        ]);
    })->name('telegram.get-webhook-info');

    Route::get('/send-test', function () {
        $telegramService = app(\App\Services\Telegram\TelegramService::class);

        // Utiliser l'ID du premier utilisateur autorisé (si configuré)
        $authorizedUsers = config('telegram.authorized_users', []);

        if (empty($authorizedUsers)) {
            return response()->json(['error' => 'No authorized users configured'], 400);
        }

        $chatId = $authorizedUsers[0];

        $result = $telegramService->sendMessage(
            $chatId,
            "🧪 Message de test du bot Saga ID\n\nLe bot fonctionne correctement!"
        );

        return response()->json([
            'chat_id' => $chatId,
            'result' => $result,
        ]);
    })->name('telegram.send-test');
}
