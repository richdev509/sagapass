<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiToken;
    protected $phoneNumberId;
    protected $apiBaseUrl;

    public function __construct()
    {
        $this->apiToken = config('whatsapp.api_token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->apiBaseUrl = config('whatsapp.api_base_url');
    }

    /**
     * Envoyer un message texte simple
     *
     * @param string $to Numéro du destinataire
     * @param string $message Texte du message
     * @return array|null
     */
    public function sendTextMessage(string $to, string $message): ?array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Envoyer un message avec des boutons interactifs
     *
     * @param string $to Numéro du destinataire
     * @param string $bodyText Texte du message
     * @param array $buttons Tableau de boutons [['id' => '...', 'title' => '...']]
     * @param string|null $headerText Texte d'en-tête optionnel
     * @param string|null $footerText Texte de pied de page optionnel
     * @return array|null
     */
    public function sendButtonMessage(
        string $to,
        string $bodyText,
        array $buttons,
        ?string $headerText = null,
        ?string $footerText = null
    ): ?array {
        // WhatsApp limite à 3 boutons maximum
        $buttons = array_slice($buttons, 0, 3);

        $interactive = [
            'type' => 'button',
            'body' => [
                'text' => $bodyText,
            ],
            'action' => [
                'buttons' => array_map(function ($button) {
                    return [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $button['id'],
                            'title' => substr($button['title'], 0, 20), // Max 20 caractères
                        ],
                    ];
                }, $buttons),
            ],
        ];

        if ($headerText) {
            $interactive['header'] = [
                'type' => 'text',
                'text' => $headerText,
            ];
        }

        if ($footerText) {
            $interactive['footer'] = [
                'text' => $footerText,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Envoyer un message avec une liste interactive
     *
     * @param string $to Numéro du destinataire
     * @param string $bodyText Texte du message
     * @param string $buttonText Texte du bouton de la liste
     * @param array $sections Sections de la liste
     * @param string|null $headerText Texte d'en-tête optionnel
     * @param string|null $footerText Texte de pied de page optionnel
     * @return array|null
     */
    public function sendListMessage(
        string $to,
        string $bodyText,
        string $buttonText,
        array $sections,
        ?string $headerText = null,
        ?string $footerText = null
    ): ?array {
        $interactive = [
            'type' => 'list',
            'body' => [
                'text' => $bodyText,
            ],
            'action' => [
                'button' => substr($buttonText, 0, 20), // Max 20 caractères
                'sections' => $sections,
            ],
        ];

        if ($headerText) {
            $interactive['header'] = [
                'type' => 'text',
                'text' => $headerText,
            ];
        }

        if ($footerText) {
            $interactive['footer'] = [
                'text' => $footerText,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Envoyer un document (PDF, etc.)
     *
     * @param string $to Numéro du destinataire
     * @param string $documentUrl URL du document
     * @param string|null $filename Nom du fichier
     * @param string|null $caption Légende du document
     * @return array|null
     */
    public function sendDocument(
        string $to,
        string $documentUrl,
        ?string $filename = null,
        ?string $caption = null
    ): ?array {
        $document = [
            'link' => $documentUrl,
        ];

        if ($filename) {
            $document['filename'] = $filename;
        }

        if ($caption) {
            $document['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => $document,
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Envoyer une image
     *
     * @param string $to Numéro du destinataire
     * @param string $imageUrl URL de l'image
     * @param string|null $caption Légende de l'image
     * @return array|null
     */
    public function sendImage(
        string $to,
        string $imageUrl,
        ?string $caption = null
    ): ?array {
        $image = [
            'link' => $imageUrl,
        ];

        if ($caption) {
            $image['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => $image,
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Marquer un message comme lu
     *
     * @param string $messageId ID du message à marquer
     * @return array|null
     */
    public function markAsRead(string $messageId): ?array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Envoyer un message via l'API WhatsApp
     *
     * @param array $payload
     * @return array|null
     */
    protected function sendMessage(array $payload): ?array
    {
        try {
            $url = "{$this->apiBaseUrl}/{$this->phoneNumberId}/messages";

            $response = Http::withToken($this->apiToken)
                ->timeout(config('whatsapp.api_timeout', 30))
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();

                // Logger le message envoyé
                if (config('whatsapp.log_outgoing_messages')) {
                    Log::info('WhatsApp message sent', [
                        'to' => $payload['to'] ?? null,
                        'type' => $payload['type'] ?? null,
                        'message_id' => $result['messages'][0]['id'] ?? null,
                    ]);
                }

                return $result;
            }

            // Erreur API
            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            // Retry si configuré
            if (config('whatsapp.retry_attempts', 0) > 0) {
                return $this->retryMessage($payload);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('WhatsApp send message exception', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return null;
        }
    }

    /**
     * Réessayer d'envoyer un message
     *
     * @param array $payload
     * @param int $attempt
     * @return array|null
     */
    protected function retryMessage(array $payload, int $attempt = 1): ?array
    {
        $maxAttempts = config('whatsapp.retry_attempts', 3);
        $retryDelay = config('whatsapp.retry_delay', 2);

        if ($attempt > $maxAttempts) {
            Log::error('WhatsApp message retry limit exceeded', [
                'payload' => $payload,
                'attempts' => $attempt,
            ]);
            return null;
        }

        sleep($retryDelay);

        Log::info('WhatsApp retrying message', [
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
        ]);

        try {
            $url = "{$this->apiBaseUrl}/{$this->phoneNumberId}/messages";

            $response = Http::withToken($this->apiToken)
                ->timeout(config('whatsapp.api_timeout', 30))
                ->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return $this->retryMessage($payload, $attempt + 1);

        } catch (\Exception $e) {
            Log::error('WhatsApp retry exception', [
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);

            return $this->retryMessage($payload, $attempt + 1);
        }
    }

    /**
     * Obtenir les informations du numéro WhatsApp Business
     *
     * @return array|null
     */
    public function getPhoneNumberInfo(): ?array
    {
        try {
            $url = "{$this->apiBaseUrl}/{$this->phoneNumberId}";

            $response = Http::withToken($this->apiToken)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('WhatsApp get phone info exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Vérifier la validité du token API
     *
     * @return bool
     */
    public function verifyToken(): bool
    {
        $info = $this->getPhoneNumberInfo();
        return $info !== null;
    }
}
