<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;
    protected $apiBaseUrl;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        $this->apiBaseUrl = config('telegram.api_base_url');
    }

    /**
     * Envoyer un message texte simple
     *
     * @param int|string $chatId ID du chat Telegram
     * @param string $text Texte du message
     * @param array $options Options supplémentaires
     * @return array|null
     */
    public function sendMessage($chatId, string $text, array $options = []): ?array
    {
        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ], $options);

        return $this->callApi('sendMessage', $payload);
    }

    /**
     * Envoyer un message avec un clavier inline (boutons)
     *
     * @param int|string $chatId
     * @param string $text
     * @param array $buttons Tableau de boutons [[['text' => '...', 'callback_data' => '...']]]
     * @return array|null
     */
    public function sendMessageWithKeyboard($chatId, string $text, array $buttons): ?array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ];

        return $this->callApi('sendMessage', $payload);
    }

    /**
     * Modifier un message existant
     *
     * @param int|string $chatId
     * @param int $messageId
     * @param string $text
     * @param array $buttons
     * @return array|null
     */
    public function editMessageText($chatId, int $messageId, string $text, array $buttons = []): ?array
    {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if (!empty($buttons)) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $buttons,
            ]);
        }

        return $this->callApi('editMessageText', $payload);
    }

    /**
     * Répondre à un callback query (quand un bouton est cliqué)
     *
     * @param string $callbackQueryId
     * @param string|null $text
     * @param bool $showAlert
     * @return array|null
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): ?array
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
        ];

        if ($text) {
            $payload['text'] = $text;
            $payload['show_alert'] = $showAlert;
        }

        return $this->callApi('answerCallbackQuery', $payload);
    }

    /**
     * Envoyer un document (PDF, etc.)
     *
     * @param int|string $chatId
     * @param string $documentUrl URL du document
     * @param string|null $caption
     * @return array|null
     */
    public function sendDocument($chatId, string $documentUrl, ?string $caption = null): ?array
    {
        $payload = [
            'chat_id' => $chatId,
            'document' => $documentUrl,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
            $payload['parse_mode'] = 'Markdown';
        }

        return $this->callApi('sendDocument', $payload);
    }

    /**
     * Envoyer une photo
     *
     * @param int|string $chatId
     * @param string $photoUrl
     * @param string|null $caption
     * @return array|null
     */
    public function sendPhoto($chatId, string $photoUrl, ?string $caption = null): ?array
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
            $payload['parse_mode'] = 'Markdown';
        }

        return $this->callApi('sendPhoto', $payload);
    }

    /**
     * Définir le webhook
     *
     * @param string $url
     * @param string|null $secretToken
     * @return array|null
     */
    public function setWebhook(string $url, ?string $secretToken = null): ?array
    {
        $payload = [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ];

        if ($secretToken) {
            $payload['secret_token'] = $secretToken;
        }

        return $this->callApi('setWebhook', $payload);
    }

    /**
     * Obtenir les informations du webhook
     *
     * @return array|null
     */
    public function getWebhookInfo(): ?array
    {
        return $this->callApi('getWebhookInfo');
    }

    /**
     * Supprimer le webhook
     *
     * @return array|null
     */
    public function deleteWebhook(): ?array
    {
        return $this->callApi('deleteWebhook');
    }

    /**
     * Obtenir les informations du bot
     *
     * @return array|null
     */
    public function getMe(): ?array
    {
        return $this->callApi('getMe');
    }

    /**
     * Appeler l'API Telegram
     *
     * @param string $method
     * @param array $params
     * @return array|null
     */
    protected function callApi(string $method, array $params = []): ?array
    {
        try {
            $url = "{$this->apiBaseUrl}/bot{$this->botToken}/{$method}";

            $response = Http::timeout(config('telegram.api_timeout', 30))
                ->post($url, $params);

            if ($response->successful()) {
                $result = $response->json();

                if (config('telegram.log_outgoing_messages') && $method === 'sendMessage') {
                    Log::info('Telegram message sent', [
                        'chat_id' => $params['chat_id'] ?? null,
                        'method' => $method,
                    ]);
                }

                return $result;
            }

            Log::error('Telegram API error', [
                'method' => $method,
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params,
            ]);

            // Retry si configuré
            if (config('telegram.retry_attempts', 0) > 0) {
                return $this->retryApiCall($method, $params);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Telegram API exception', [
                'method' => $method,
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return null;
        }
    }

    /**
     * Réessayer un appel API
     *
     * @param string $method
     * @param array $params
     * @param int $attempt
     * @return array|null
     */
    protected function retryApiCall(string $method, array $params, int $attempt = 1): ?array
    {
        $maxAttempts = config('telegram.retry_attempts', 3);
        $retryDelay = config('telegram.retry_delay', 2);

        if ($attempt > $maxAttempts) {
            Log::error('Telegram API retry limit exceeded', [
                'method' => $method,
                'attempts' => $attempt,
            ]);
            return null;
        }

        sleep($retryDelay);

        Log::info('Telegram retrying API call', [
            'method' => $method,
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
        ]);

        try {
            $url = "{$this->apiBaseUrl}/bot{$this->botToken}/{$method}";

            $response = Http::timeout(config('telegram.api_timeout', 30))
                ->post($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return $this->retryApiCall($method, $params, $attempt + 1);

        } catch (\Exception $e) {
            Log::error('Telegram retry exception', [
                'method' => $method,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);

            return $this->retryApiCall($method, $params, $attempt + 1);
        }
    }

    /**
     * Vérifier la validité du token
     *
     * @return bool
     */
    public function verifyToken(): bool
    {
        $result = $this->getMe();
        return $result !== null && isset($result['ok']) && $result['ok'] === true;
    }
}
