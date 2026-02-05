<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\MenuService;
use App\Services\WhatsApp\SessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebhookController extends Controller
{
    protected $whatsappService;
    protected $menuService;
    protected $sessionService;

    public function __construct(
        WhatsAppService $whatsappService,
        MenuService $menuService,
        SessionService $sessionService
    ) {
        $this->whatsappService = $whatsappService;
        $this->menuService = $menuService;
        $this->sessionService = $sessionService;
    }

    /**
     * Vérification du webhook (GET request de Meta)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub.mode');
        $token = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        // Vérifier que le mode est 'subscribe' et que le token correspond
        if ($mode === 'subscribe' && $token === config('whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Verification failed', 403);
    }

    /**
     * Réception des webhooks WhatsApp (POST request de Meta)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        try {
            // Vérifier la signature du webhook (sécurité)
            if (config('whatsapp.verify_signature') && !$this->verifySignature($request)) {
                Log::error('WhatsApp webhook signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();

            // Logger le payload entrant
            if (config('whatsapp.log_incoming_messages')) {
                Log::info('WhatsApp webhook received', ['payload' => $payload]);
            }

            // Vérifier que c'est un message WhatsApp
            if (!isset($payload['entry'][0]['changes'][0]['value'])) {
                return response()->json(['status' => 'ok'], 200);
            }

            $value = $payload['entry'][0]['changes'][0]['value'];

            // Traiter les messages
            if (isset($value['messages'][0])) {
                $this->processMessage($value['messages'][0], $value['metadata'] ?? []);
            }

            // Traiter les statuts (lecture, livraison, etc.)
            if (isset($value['statuses'][0])) {
                $this->processStatus($value['statuses'][0]);
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Traiter un message WhatsApp
     *
     * @param array $message
     * @param array $metadata
     * @return void
     */
    protected function processMessage(array $message, array $metadata)
    {
        $from = $message['from'] ?? null;
        $messageType = $message['type'] ?? null;
        $messageId = $message['id'] ?? null;

        if (!$from) {
            Log::warning('WhatsApp message without sender');
            return;
        }

        // Vérifier si le numéro est autorisé
        if (!$this->isAuthorized($from)) {
            Log::warning('Unauthorized WhatsApp number attempted access', ['from' => $from]);
            $this->whatsappService->sendTextMessage($from, config('whatsapp.unauthorized_message'));
            return;
        }

        // Rate limiting
        if ($this->isRateLimited($from)) {
            Log::warning('Rate limit exceeded', ['from' => $from]);
            return;
        }

        // Traiter selon le type de message
        switch ($messageType) {
            case 'text':
                $this->handleTextMessage($from, $message['text']['body'] ?? '');
                break;

            case 'interactive':
                $this->handleInteractiveResponse($from, $message['interactive'] ?? []);
                break;

            case 'button':
                $this->handleButtonResponse($from, $message['button'] ?? []);
                break;

            default:
                Log::info('Unsupported message type', [
                    'from' => $from,
                    'type' => $messageType,
                ]);
                $this->whatsappService->sendTextMessage(
                    $from,
                    "Désolé, je ne peux traiter que les messages texte pour le moment."
                );
        }

        // Logger l'activité
        $this->logActivity($from, $messageType, $message);
    }

    /**
     * Gérer un message texte
     *
     * @param string $from
     * @param string $text
     * @return void
     */
    protected function handleTextMessage(string $from, string $text)
    {
        $text = trim(strtolower($text));

        // Récupérer ou créer la session
        $session = $this->sessionService->getOrCreateSession($from);

        // Commandes globales
        if (in_array($text, ['salut', 'bonjour', 'hello', 'hi', 'menu', 'start'])) {
            // Envoyer le message de bienvenue
            $this->whatsappService->sendTextMessage($from, config('whatsapp.welcome_message'));

            // Envoyer le menu principal
            $this->menuService->sendMainMenu($from);

            // Mettre à jour la session
            $this->sessionService->updateSession($from, 'main');
            return;
        }

        if (in_array($text, ['aide', 'help', '?'])) {
            $this->whatsappService->sendTextMessage(
                $from,
                "ℹ️ *Aide Sagaloto Bot*\n\n" .
                "Commandes disponibles:\n" .
                "• *salut* - Menu principal\n" .
                "• *aide* - Afficher cette aide\n\n" .
                "Utilisez les boutons pour naviguer dans les menus."
            );
            return;
        }

        // Message non reconnu
        $this->whatsappService->sendTextMessage(
            $from,
            "Je n'ai pas compris votre message. Envoyez *salut* pour voir le menu principal ou *aide* pour l'aide."
        );
    }

    /**
     * Gérer une réponse interactive (boutons ou listes)
     *
     * @param string $from
     * @param array $interactive
     * @return void
     */
    protected function handleInteractiveResponse(string $from, array $interactive)
    {
        $type = $interactive['type'] ?? null;

        if ($type === 'button_reply') {
            $buttonId = $interactive['button_reply']['id'] ?? null;
            $this->handleMenuAction($from, $buttonId);
        } elseif ($type === 'list_reply') {
            $listId = $interactive['list_reply']['id'] ?? null;
            $this->handleMenuAction($from, $listId);
        }
    }

    /**
     * Gérer un bouton cliqué
     *
     * @param string $from
     * @param array $button
     * @return void
     */
    protected function handleButtonResponse(string $from, array $button)
    {
        $buttonId = $button['payload'] ?? null;
        $this->handleMenuAction($from, $buttonId);
    }

    /**
     * Gérer une action de menu
     *
     * @param string $from
     * @param string|null $actionId
     * @return void
     */
    protected function handleMenuAction(string $from, ?string $actionId)
    {
        if (!$actionId) {
            return;
        }

        // Retour au menu principal
        if ($actionId === 'retour') {
            $this->whatsappService->sendTextMessage($from, "Retour au menu principal...");
            $this->menuService->sendMainMenu($from);
            $this->sessionService->updateSession($from, 'main');
            return;
        }

        // Navigation vers sous-menus
        if ($actionId === 'rapports') {
            $this->menuService->sendMenu($from, 'rapports');
            $this->sessionService->updateSession($from, 'rapports');
            return;
        }

        if ($actionId === 'ventes') {
            $this->menuService->sendMenu($from, 'ventes');
            $this->sessionService->updateSession($from, 'ventes');
            return;
        }

        if ($actionId === 'tirages') {
            $this->menuService->sendMenu($from, 'tirages');
            $this->sessionService->updateSession($from, 'tirages');
            return;
        }

        // Actions qui retournent des données (réponses mockées)
        if (strpos($actionId, 'rapport_') === 0 ||
            strpos($actionId, 'ventes_') === 0 ||
            strpos($actionId, 'tirages_') === 0) {

            $response = $this->menuService->getMockResponse($actionId);
            $this->whatsappService->sendTextMessage($from, $response);

            // Proposer de revenir au menu
            sleep(1); // Petit délai pour meilleure UX
            $this->whatsappService->sendTextMessage(
                $from,
                "Envoyez *menu* pour revenir au menu principal."
            );
            return;
        }

        // Action non reconnue
        Log::warning('Unknown menu action', ['action' => $actionId, 'from' => $from]);
        $this->whatsappService->sendTextMessage(
            $from,
            "Action non reconnue. Envoyez *menu* pour recommencer."
        );
    }

    /**
     * Traiter un statut de message
     *
     * @param array $status
     * @return void
     */
    protected function processStatus(array $status)
    {
        $messageId = $status['id'] ?? null;
        $statusType = $status['status'] ?? null;

        Log::info('WhatsApp message status', [
            'message_id' => $messageId,
            'status' => $statusType,
        ]);

        // On peut tracker les statuts: sent, delivered, read, failed
        // Utile pour le monitoring et l'analytics
    }

    /**
     * Vérifier la signature du webhook
     *
     * @param Request $request
     * @return bool
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, config('whatsapp.app_secret'));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Vérifier si un numéro est autorisé
     *
     * @param string $phoneNumber
     * @return bool
     */
    protected function isAuthorized(string $phoneNumber): bool
    {
        $authorizedNumbers = config('whatsapp.authorized_numbers', []);

        // Si pas de whitelist configurée, autoriser tous (mode dev)
        if (empty($authorizedNumbers)) {
            return true;
        }

        // Normaliser le numéro (retirer espaces, tirets, etc.)
        $normalized = preg_replace('/[^0-9+]/', '', $phoneNumber);

        return in_array($normalized, $authorizedNumbers);
    }

    /**
     * Vérifier le rate limiting
     *
     * @param string $from
     * @return bool
     */
    protected function isRateLimited(string $from): bool
    {
        if (!config('whatsapp.enable_rate_limiting')) {
            return false;
        }

        $key = 'whatsapp_rate_limit:' . $from;
        $limit = config('whatsapp.rate_limit_per_minute', 10);

        $attempts = Cache::get($key, 0);

        if ($attempts >= $limit) {
            return true;
        }

        Cache::put($key, $attempts + 1, 60); // 60 secondes

        return false;
    }

    /**
     * Logger l'activité
     *
     * @param string $from
     * @param string $type
     * @param array $data
     * @return void
     */
    protected function logActivity(string $from, string $type, array $data)
    {
        if (!config('whatsapp.enable_logging')) {
            return;
        }

        Log::info('WhatsApp activity', [
            'phone_number' => $from,
            'message_type' => $type,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ]);

        // On pourrait aussi enregistrer dans la DB pour un audit trail complet
        // WhatsAppLog::create([...])
    }
}
