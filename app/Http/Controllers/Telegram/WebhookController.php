<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramService;
use App\Services\Telegram\TelegramMenuService;
use App\Services\Telegram\TelegramSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WebhookController extends Controller
{
    protected $telegramService;
    protected $menuService;
    protected $sessionService;

    public function __construct(
        TelegramService $telegramService,
        TelegramMenuService $menuService,
        TelegramSessionService $sessionService
    ) {
        $this->telegramService = $telegramService;
        $this->menuService = $menuService;
        $this->sessionService = $sessionService;
    }

    /**
     * Gérer les webhooks Telegram
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        try {
            // Vérifier le secret token (si configuré)
            $secretToken = config('telegram.webhook_secret');
            if ($secretToken && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secretToken) {
                Log::warning('Telegram webhook invalid secret token');
                return response()->json(['error' => 'Invalid secret token'], 403);
            }

            $update = $request->all();

            // Logger les updates entrants
            if (config('telegram.log_incoming_messages')) {
                Log::info('Telegram webhook received', ['update' => $update]);
            }

            // Traiter les messages
            if (isset($update['message'])) {
                $this->processMessage($update['message']);
            }

            // Traiter les callback queries (boutons cliqués)
            if (isset($update['callback_query'])) {
                $this->processCallbackQuery($update['callback_query']);
            }

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Traiter un message Telegram
     *
     * @param array $message
     * @return void
     */
    protected function processMessage(array $message)
    {
        $chatId = $message['chat']['id'] ?? null;
        $userId = $message['from']['id'] ?? null;
        $text = $message['text'] ?? null;
        $messageDate = $message['date'] ?? null;

        if (!$chatId || !$userId) {
            return;
        }

        // Ignorer les messages de plus de 5 minutes (éviter le retraitement)
        if ($messageDate) {
            $messageTime = Carbon::createFromTimestamp($messageDate);
            $ageInSeconds = Carbon::now()->diffInSeconds($messageTime);
            if ($ageInSeconds > 300) { // 5 minutes
                Log::info('Telegram: Ignoring old message', [
                    'user_id' => $userId,
                    'age_seconds' => $ageInSeconds,
                    'message_date' => $messageTime->toIso8601String()
                ]);
                return;
            }
        }

        // Vérifier si l'utilisateur est autorisé
        if (!$this->isAuthorized($userId)) {
            Log::warning('Unauthorized Telegram user attempted access', [
                'user_id' => $userId,
                'username' => $message['from']['username'] ?? null,
            ]);
            $this->telegramService->sendMessage($chatId, config('telegram.unauthorized_message'));
            return;
        }

        // Rate limiting
        if ($this->isRateLimited($userId)) {
            Log::warning('Telegram rate limit exceeded', ['user_id' => $userId]);
            return;
        }

        // Traiter les commandes
        if ($text && strpos($text, '/') === 0) {
            $this->handleCommand($chatId, $userId, $text);
            return;
        }

        // Message texte normal
        if ($text) {
            $this->handleTextMessage($chatId, $userId, $text);
        }

        // Logger l'activité
        $this->logActivity($userId, 'message', $message);
    }

    /**
     * Traiter un callback query (bouton cliqué)
     *
     * @param array $callbackQuery
     * @return void
     */
    protected function processCallbackQuery(array $callbackQuery)
    {
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $userId = $callbackQuery['from']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $data = $callbackQuery['data'] ?? null;
        $callbackQueryId = $callbackQuery['id'] ?? null;

        if (!$chatId || !$userId || !$data) {
            return;
        }

        // Déduplication: vérifier si ce callback a déjà été traité
        $cacheKey = 'telegram_callback_processed:' . $callbackQueryId;
        if (Cache::has($cacheKey)) {
            Log::info('Telegram: Ignoring duplicate callback query', [
                'callback_id' => $callbackQueryId,
                'user_id' => $userId
            ]);
            // Répondre quand même pour éviter le timeout Telegram
            if ($callbackQueryId) {
                $this->telegramService->answerCallbackQuery($callbackQueryId);
            }
            return;
        }

        // Marquer comme traité (expire après 5 minutes)
        Cache::put($cacheKey, true, 300);

        // Vérifier si l'utilisateur est autorisé
        if (!$this->isAuthorized($userId)) {
            $this->telegramService->answerCallbackQuery($callbackQueryId, "Accès non autorisé", true);
            return;
        }

        // Répondre au callback pour retirer l'indicateur de chargement
        $this->telegramService->answerCallbackQuery($callbackQueryId);

        // Traiter l'action
        $this->handleMenuAction($chatId, $userId, $data, $messageId);

        // Logger l'activité
        $this->logActivity($userId, 'callback_query', $callbackQuery);
    }

    /**
     * Gérer une commande Telegram
     *
     * @param int $chatId
     * @param int $userId
     * @param string $command
     * @return void
     */
    protected function handleCommand(int $chatId, int $userId, string $command)
    {
        $command = strtolower(trim($command));

        switch ($command) {
            case '/start':
            case '/menu':
                $this->telegramService->sendMessage($chatId, config('telegram.welcome_message'));
                $this->menuService->sendMainMenu($chatId);
                $this->sessionService->updateSession((string)$userId, 'main');
                break;

            case '/aide':
            case '/help':
                $this->menuService->sendHelp($chatId);
                break;

            default:
                $this->telegramService->sendMessage(
                    $chatId,
                    "Commande non reconnue. Utilisez /start pour voir le menu."
                );
        }
    }

    /**
     * Gérer un message texte
     *
     * @param int $chatId
     * @param int $userId
     * @param string $text
     * @return void
     */
    protected function handleTextMessage(int $chatId, int $userId, string $text)
    {
        $text = trim(strtolower($text));

        // Commandes texte sans /
        if (in_array($text, ['salut', 'bonjour', 'hello', 'hi', 'menu', 'start'])) {
            $this->telegramService->sendMessage($chatId, config('telegram.welcome_message'));
            $this->menuService->sendMainMenu($chatId);
            $this->sessionService->updateSession((string)$userId, 'main');
            return;
        }

        if (in_array($text, ['aide', 'help', '?'])) {
            $this->menuService->sendHelp($chatId);
            return;
        }

        // Message non reconnu
        $this->telegramService->sendMessage(
            $chatId,
            "Je n'ai pas compris. Utilisez /start pour voir le menu ou /aide pour l'aide."
        );
    }

    /**
     * Gérer une action de menu (bouton cliqué)
     *
     * @param int $chatId
     * @param int $userId
     * @param string $action
     * @param int|null $messageId
     * @return void
     */
    protected function handleMenuAction(int $chatId, int $userId, string $action, ?int $messageId = null)
    {
        // Retour au menu principal
        if ($action === 'retour') {
            $this->menuService->sendMenu($chatId, 'main', $messageId);
            $this->sessionService->updateSession((string)$userId, 'main');
            return;
        }

        // Navigation vers menus principaux
        if (in_array($action, ['ventes', 'tirages'])) {
            $this->menuService->sendMenu($chatId, $action, $messageId);
            $this->sessionService->updateSession((string)$userId, $action);
            return;
        }

        // Navigation menu Rapports -> afficher périodes
        if ($action === 'rapports') {
            $this->menuService->sendMenu($chatId, 'rapports', $messageId);
            $this->sessionService->updateSession((string)$userId, 'rapports');
            return;
        }

        // Navigation vers sous-menus rapports (périodes)
        if (in_array($action, ['rapport_matin', 'rapport_apres_midi', 'rapport_soir'])) {
            $this->menuService->sendMenu($chatId, $action, $messageId);
            $this->sessionService->updateSession((string)$userId, $action);
            return;
        }

        // Navigation vers sous-menus tirages (périodes)
        if (in_array($action, ['tirage_matin', 'tirage_apres_midi', 'tirage_soir'])) {
            $this->menuService->sendMenu($chatId, $action, $messageId);
            $this->sessionService->updateSession((string)$userId, $action);
            return;
        }

        // Afficher l'aide
        if ($action === 'aide') {
            $this->menuService->sendHelp($chatId);
            return;
        }

        // Actions qui retournent des données (réponses mockées)
        if (strpos($action, 'ventes_') === 0 ||
            strpos($action, 'tirage_') === 0 ||
            strpos($action, 'rapport_') === 0) {

            $response = $this->menuService->getMockResponse($action);
            $this->telegramService->sendMessage($chatId, $response);

            // Proposer de revenir au menu
            sleep(1);
            $this->telegramService->sendMessage(
                $chatId,
                "Utilisez /menu pour revenir au menu principal."
            );
            return;
        }

        // Action non reconnue
        Log::warning('Unknown Telegram menu action', ['action' => $action, 'user_id' => $userId]);
        $this->telegramService->sendMessage(
            $chatId,
            "Action non reconnue. Utilisez /menu pour recommencer."
        );
    }

    /**
     * Vérifier si un utilisateur est autorisé
     *
     * @param int $userId
     * @return bool
     */
    protected function isAuthorized(int $userId): bool
    {
        $authorizedUsers = config('telegram.authorized_users', []);

        // Si pas de whitelist configurée, autoriser tous (mode dev)
        if (empty($authorizedUsers)) {
            return true;
        }

        return in_array((string)$userId, $authorizedUsers);
    }

    /**
     * Vérifier le rate limiting
     *
     * @param int $userId
     * @return bool
     */
    protected function isRateLimited(int $userId): bool
    {
        if (!config('telegram.enable_rate_limiting')) {
            return false;
        }

        $key = 'telegram_rate_limit:' . $userId;
        $limit = config('telegram.rate_limit_per_minute', 20);

        $attempts = Cache::get($key, 0);

        if ($attempts >= $limit) {
            return true;
        }

        Cache::put($key, $attempts + 1, 60);

        return false;
    }

    /**
     * Logger l'activité
     *
     * @param int $userId
     * @param string $type
     * @param array $data
     * @return void
     */
    protected function logActivity(int $userId, string $type, array $data)
    {
        if (!config('telegram.enable_logging')) {
            return;
        }

        Log::info('Telegram activity', [
            'user_id' => $userId,
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ]);
    }
}
