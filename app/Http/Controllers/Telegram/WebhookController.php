<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramService;
use App\Services\Telegram\TelegramMenuService;
use App\Services\Telegram\TelegramSessionService;
use App\Services\Sagaloto\SagalotoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WebhookController extends Controller
{
    protected $telegramService;
    protected $menuService;
    protected $sessionService;
    protected $sagalotoApi;

    public function __construct(
        TelegramService $telegramService,
        TelegramMenuService $menuService,
        TelegramSessionService $sessionService,
        SagalotoApiService $sagalotoApi
    ) {
        $this->telegramService = $telegramService;
        $this->menuService = $menuService;
        $this->sessionService = $sessionService;
        $this->sagalotoApi = $sagalotoApi;
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
        $username = $message['from']['username'] ?? null;
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
                'username' => $username,
            ]);
            $this->telegramService->sendMessage($chatId, config('telegram.unauthorized_message'));
            return;
        }

        // Rate limiting
        if ($this->isRateLimited($userId)) {
            Log::warning('Telegram rate limit exceeded', ['user_id' => $userId]);
            return;
        }

        // Stocker le username dans le contexte de session
        if ($username) {
            $this->sessionService->updateContext($userId, ['telegram_username' => $username]);
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
        $username = $callbackQuery['from']['username'] ?? null;
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

        // Stocker le username dans le contexte de session
        if ($username) {
            $this->sessionService->updateContext($userId, ['telegram_username' => $username]);
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
        // Pour tout message texte (sauf commandes /), afficher le menu principal
        // Interaction uniquement par boutons
        $this->telegramService->sendMessage(
            $chatId,
            "Veuillez utiliser les boutons ci-dessous pour interagir avec le bot :"
        );
        $this->menuService->sendMainMenu($chatId);
        $this->sessionService->updateSession((string)$userId, 'main');
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

        // Navigation vers sous-menus rapports (périodes) -> demander les branches
        if (in_array($action, ['rapport_matin', 'rapport_apres_midi', 'rapport_soir'])) {
            // Récupérer le username depuis le contexte
            $session = $this->sessionService->getSession((string)$userId);
            $username = $session['context']['telegram_username'] ?? null;

            if (!$username) {
                $this->telegramService->sendMessage($chatId, "❌ Erreur: Username Telegram introuvable. Utilisez /start pour réinitialiser.");
                return;
            }

            // Extraire la période (matin, apres_midi, soir)
            $periode = str_replace('rapport_', '', $action);

            // Récupérer les branches depuis Sagaloto API
            $branchesData = $this->sagalotoApi->getUserBranches($username);

            if (!$branchesData || !$branchesData['success']) {
                $this->telegramService->sendMessage($chatId, "❌ Impossible de récupérer vos branches. Veuillez réessayer plus tard.");
                Log::error('Failed to fetch branches from Sagaloto', [
                    'username' => $username,
                    'periode' => $periode,
                ]);
                return;
            }

            $branches = $branchesData['data']['branches'] ?? [];

            if (empty($branches)) {
                $this->telegramService->sendMessage($chatId, "❌ Aucune branche disponible pour votre compte.");
                return;
            }

            // Stocker les branches et la période dans le contexte
            $this->sessionService->updateContext((string)$userId, [
                'branches' => $branches,
                'selected_periode' => $periode,
                'selected_type' => 'rapport',
            ]);

            // Afficher le menu de sélection des branches
            $this->sendBranchSelectionMenu($chatId, $branches, $messageId);
            $this->sessionService->updateSession((string)$userId, 'branch_selection');
            return;
        }

        // Navigation vers sous-menus tirages (périodes) -> demander les branches
        if (in_array($action, ['tirage_matin', 'tirage_apres_midi', 'tirage_soir'])) {
            // Récupérer le username depuis le contexte
            $session = $this->sessionService->getSession((string)$userId);
            $username = $session['context']['telegram_username'] ?? null;

            if (!$username) {
                $this->telegramService->sendMessage($chatId, "❌ Erreur: Username Telegram introuvable. Utilisez /start pour réinitialiser.");
                return;
            }

            // Extraire la période
            $periode = str_replace('tirage_', '', $action);

            // Récupérer les branches depuis Sagaloto API
            $branchesData = $this->sagalotoApi->getUserBranches($username);

            if (!$branchesData || !$branchesData['success']) {
                $this->telegramService->sendMessage($chatId, "❌ Impossible de récupérer vos branches. Veuillez réessayer plus tard.");
                Log::error('Failed to fetch branches from Sagaloto', [
                    'username' => $username,
                    'periode' => $periode,
                ]);
                return;
            }

            $branches = $branchesData['data']['branches'] ?? [];

            if (empty($branches)) {
                $this->telegramService->sendMessage($chatId, "❌ Aucune branche disponible pour votre compte.");
                return;
            }

            // Stocker les branches et la période dans le contexte
            $this->sessionService->updateContext((string)$userId, [
                'branches' => $branches,
                'selected_periode' => $periode,
                'selected_type' => 'tirage',
            ]);

            // Afficher le menu de sélection des branches
            $this->sendBranchSelectionMenu($chatId, $branches, $messageId);
            $this->sessionService->updateSession((string)$userId, 'branch_selection');
            return;
        }

        // Afficher l'aide
        if ($action === 'aide') {
            $this->menuService->sendHelp($chatId);
            return;
        }

        // Gestion de la sélection de branche
        if (strpos($action, 'branch_') === 0) {
            $branchId = (int) str_replace('branch_', '', $action);

            // Récupérer le contexte de session
            $session = $this->sessionService->getSession((string)$userId);
            $username = $session['context']['telegram_username'] ?? null;
            $periode = $session['context']['selected_periode'] ?? null;
            $type = $session['context']['selected_type'] ?? 'rapport';
            $branches = $session['context']['branches'] ?? [];

            if (!$username || !$periode) {
                $this->telegramService->sendMessage($chatId, "❌ Session expirée. Utilisez /menu pour recommencer.");
                return;
            }

            // Trouver la branche sélectionnée
            $selectedBranch = null;
            foreach ($branches as $branch) {
                if ($branch['id'] === $branchId) {
                    $selectedBranch = $branch;
                    break;
                }
            }

            if (!$selectedBranch) {
                $this->telegramService->sendMessage($chatId, "❌ Branche invalide. Utilisez /menu pour recommencer.");
                return;
            }

            // Stocker la branche sélectionnée
            $this->sessionService->updateContext((string)$userId, [
                'selected_branch_id' => $branchId,
                'selected_branch_name' => $selectedBranch['name'],
            ]);

            // Afficher le menu des tirages pour cette période
            $menuKey = $type . '_' . $periode;
            $this->menuService->sendMenu($chatId, $menuKey, $messageId);
            $this->sessionService->updateSession((string)$userId, $menuKey);
            return;
        }

        // Actions qui retournent des données depuis l'API Sagaloto
        if (strpos($action, 'ventes_') === 0) {
            // Gérer les ventes
            $session = $this->sessionService->getSession((string)$userId);
            $username = $session['context']['telegram_username'] ?? null;
            $branchId = $session['context']['selected_branch_id'] ?? null;
            $periode = str_replace('ventes_', '', $action); // jour ou semaine

            if (!$username || !$branchId) {
                $this->telegramService->sendMessage($chatId, "❌ Session expirée. Utilisez /menu pour recommencer.");
                return;
            }

            $ventesData = $this->sagalotoApi->getVentes($username, $branchId, $periode);

            if (!$ventesData || !$ventesData['success']) {
                $this->telegramService->sendMessage($chatId, "❌ Impossible de récupérer les statistiques de ventes.");
                return;
            }

            $response = $this->formatVentes($ventesData['data']['ventes']);
            $this->telegramService->sendMessage($chatId, $response);

            sleep(1);
            $this->telegramService->sendMessage($chatId, "Utilisez /menu pour revenir au menu principal.");
            return;
        }

        if (strpos($action, 'tirage_') === 0 || strpos($action, 'rapport_') === 0) {
            // Extraire le type (tirage ou rapport), la loterie et la période
            preg_match('/^(tirage|rapport)_([a-z_]+)_(matin|apres_midi|soir)$/', $action, $matches);

            if (count($matches) !== 4) {
                Log::warning('Invalid action format', ['action' => $action]);
                $this->telegramService->sendMessage($chatId, "❌ Action invalide.");
                return;
            }

            $type = $matches[1]; // tirage ou rapport
            $tirage = $matches[2]; // tennessee, texas, etc.
            $periode = $matches[3]; // matin, apres_midi, soir

            // Récupérer le contexte
            $session = $this->sessionService->getSession((string)$userId);
            $username = $session['context']['telegram_username'] ?? null;
            $branchId = $session['context']['selected_branch_id'] ?? null;

            if (!$username || !$branchId) {
                $this->telegramService->sendMessage($chatId, "❌ Session expirée. Utilisez /menu pour recommencer.");
                return;
            }

            // Appeler l'API Sagaloto
            $rapportData = $this->sagalotoApi->getRapport($username, $branchId, $periode, $tirage, $type);

            if (!$rapportData || !$rapportData['success']) {
                $this->telegramService->sendMessage($chatId, "❌ Impossible de récupérer les données. Veuillez réessayer.");
                Log::error('Failed to fetch rapport from Sagaloto', [
                    'username' => $username,
                    'branch_id' => $branchId,
                    'periode' => $periode,
                    'tirage' => $tirage,
                    'type' => $type,
                ]);
                return;
            }

            // Formater et envoyer la réponse
            if ($type === 'rapport') {
                $response = $this->formatRapport($rapportData['data']['rapport']);
            } else {
                $response = $this->formatTirage($rapportData['data']['tirage']);
            }

            $this->telegramService->sendMessage($chatId, $response);

            // Proposer de revenir au menu
            sleep(1);
            $this->telegramService->sendMessage($chatId, "Utilisez /menu pour revenir au menu principal.");
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

    /**
     * Afficher le menu de sélection des branches
     *
     * @param int $chatId
     * @param array $branches
     * @param int|null $messageId
     * @return void
     */
    protected function sendBranchSelectionMenu(int $chatId, array $branches, ?int $messageId = null)
    {
        $keyboard = [];
        $row = [];

        foreach ($branches as $branch) {
            $row[] = [
                'text' => $branch['name'],
                'callback_data' => 'branch_' . $branch['id']
            ];

            // 2 branches par ligne
            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        // Ajouter la dernière ligne si elle n'est pas vide
        if (!empty($row)) {
            $keyboard[] = $row;
        }

        // Ajouter le bouton de retour
        $keyboard[] = [
            ['text' => '🔙 Retour au menu', 'callback_data' => 'menu']
        ];

        $text = "🏢 *Sélectionnez votre branche*\n\nChoisissez la branche pour laquelle vous souhaitez consulter les informations:";

        if ($messageId) {
            $this->telegramService->editMessage($chatId, $messageId, $text, $keyboard);
        } else {
            $this->telegramService->sendInlineKeyboard($chatId, $text, $keyboard);
        }
    }

    /**
     * Formater un rapport pour l'affichage
     *
     * @param array $rapport
     * @return string
     */
    protected function formatRapport(array $rapport): string
    {
        $tirageInfo = $rapport['tirage_info'] ?? [];
        $branchInfo = $rapport['branch_info'] ?? [];
        $stats = $rapport['statistiques'] ?? [];
        $numeros = $rapport['numeros_gagnants'] ?? [];

        $message = "📊 *RAPPORT - " . strtoupper($tirageInfo['name'] ?? 'N/A') . "*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "🏢 *Branche:* " . ($branchInfo['name'] ?? 'N/A') . "\n";
        $message .= "📅 *Date:* " . ($tirageInfo['date'] ?? 'N/A') . "\n";
        $message .= "🕐 *Heure:* " . ($tirageInfo['heure'] ?? 'N/A') . "\n";
        $message .= "🌅 *Période:* " . ucfirst($tirageInfo['periode'] ?? 'N/A') . "\n\n";

        $message .= "🎯 *NUMÉROS GAGNANTS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        if (!empty($numeros)) {
            foreach ($numeros as $type => $numero) {
                $message .= "• " . ucfirst(str_replace('_', ' ', $type)) . ": *" . $numero . "*\n";
            }
        } else {
            $message .= "Aucun numéro disponible\n";
        }

        $message .= "\n💰 *STATISTIQUES*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💵 Ventes totales: *" . number_format($stats['total_ventes'] ?? 0, 2) . " HTG*\n";
        $message .= "🎟️ Tickets vendus: *" . number_format($stats['total_tickets'] ?? 0) . "*\n";
        $message .= "🎁 Gains payés: *" . number_format($stats['total_gains'] ?? 0, 2) . " HTG*\n";
        $message .= "📈 Bénéfice net: *" . number_format($stats['benefice_net'] ?? 0, 2) . " HTG*\n";
        $message .= "📊 Taux de retour: *" . number_format($stats['taux_retour'] ?? 0, 1) . "%*\n";

        // Afficher les tickets gagnants si disponibles
        if (!empty($rapport['tickets_gagnants'])) {
            $message .= "\n🏆 *TICKETS GAGNANTS*\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            foreach ($rapport['tickets_gagnants'] as $ticket) {
                $message .= "• *" . $ticket['numero'] . "* (" . $ticket['type'] . ")\n";
                $message .= "  Mise: " . number_format($ticket['montant_mise'], 2) . " HTG\n";
                $message .= "  Gain: " . number_format($ticket['montant_gain'], 2) . " HTG\n";
                $message .= "  Gagnants: " . $ticket['nombre_gagnants'] . "\n\n";
            }
        }

        return $message;
    }

    /**
     * Formater un tirage pour l'affichage
     *
     * @param array $tirage
     * @return string
     */
    protected function formatTirage(array $tirage): string
    {
        $tirageInfo = $tirage['tirage_info'] ?? [];
        $branchInfo = $tirage['branch_info'] ?? [];
        $numeros = $tirage['numeros_tires'] ?? [];
        $stats = $tirage['statistiques_rapides'] ?? [];

        $message = "🎲 *TIRAGE - " . strtoupper($tirageInfo['name'] ?? 'N/A') . "*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "🏢 *Branche:* " . ($branchInfo['name'] ?? 'N/A') . "\n";
        $message .= "📅 *Date:* " . ($tirageInfo['date'] ?? 'N/A') . "\n";
        $message .= "🕐 *Heure:* " . ($tirageInfo['heure'] ?? 'N/A') . "\n";
        $message .= "🌅 *Période:* " . ucfirst($tirageInfo['periode'] ?? 'N/A') . "\n";
        $message .= "📍 *Statut:* " . ucfirst($tirageInfo['statut'] ?? 'N/A') . "\n\n";

        $message .= "🎯 *NUMÉROS TIRÉS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        if (!empty($numeros)) {
            foreach ($numeros as $type => $numero) {
                $message .= "• " . ucfirst(str_replace('_', ' ', $type)) . ": *" . $numero . "*\n";
            }
        } else {
            $message .= "Aucun numéro disponible\n";
        }

        if (!empty($stats)) {
            $message .= "\n📊 *STATISTIQUES RAPIDES*\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "💵 Ventes totales: *" . number_format($stats['total_ventes'] ?? 0, 2) . " HTG*\n";
            $message .= "🎟️ Tickets vendus: *" . number_format($stats['total_tickets'] ?? 0) . "*\n";
        }

        return $message;
    }

    /**
     * Formater les ventes pour l'affichage
     *
     * @param array $ventes
     * @return string
     */
    protected function formatVentes(array $ventes): string
    {
        $branchInfo = $ventes['branch_info'] ?? [];
        $stats = $ventes['statistiques'] ?? [];
        $periode = ucfirst($ventes['periode'] ?? 'N/A');

        $message = "💰 *STATISTIQUES DE VENTES*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "🏢 *Branche:* " . ($branchInfo['name'] ?? 'N/A') . "\n";
        $message .= "📅 *Période:* " . $periode . "\n";
        $message .= "🗓️ *Du:* " . ($ventes['date_debut'] ?? 'N/A') . "\n";
        $message .= "🗓️ *Au:* " . ($ventes['date_fin'] ?? 'N/A') . "\n\n";

        $message .= "💵 *RÉSUMÉ*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "• Ventes totales: *" . number_format($stats['total_ventes'] ?? 0, 2) . " HTG*\n";
        $message .= "• Tickets vendus: *" . number_format($stats['total_tickets'] ?? 0) . "*\n";
        $message .= "• Gains payés: *" . number_format($stats['total_gains'] ?? 0, 2) . " HTG*\n";
        $message .= "• Bénéfice net: *" . number_format($stats['benefice_net'] ?? 0, 2) . " HTG*\n";
        $message .= "• Taux de retour: *" . number_format($stats['taux_retour'] ?? 0, 1) . "%*\n";

        // Afficher les ventes par tirage si disponibles
        if (!empty($ventes['ventes_par_tirage'])) {
            $message .= "\n📊 *VENTES PAR TIRAGE*\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            foreach ($ventes['ventes_par_tirage'] as $tirage) {
                $message .= "\n*" . $tirage['tirage'] . "* (" . ucfirst($tirage['periode']) . ")\n";
                $message .= "  • Ventes: " . number_format($tirage['ventes'], 2) . " HTG\n";
                $message .= "  • Tickets: " . number_format($tirage['tickets']) . "\n";
                $message .= "  • Gains: " . number_format($tirage['gains'], 2) . " HTG\n";
            }
        }

        // Afficher les top numéros si disponibles
        if (!empty($ventes['top_numeros'])) {
            $message .= "\n🔥 *TOP NUMÉROS*\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            foreach (array_slice($ventes['top_numeros'], 0, 5) as $top) {
                $message .= "• *" . $top['numero'] . "* (" . $top['type'] . ")\n";
                $message .= "  Fréquence: " . $top['frequence'] . " fois\n";
                $message .= "  Montant: " . number_format($top['montant_total'], 2) . " HTG\n";
            }
        }

        return $message;
    }
}
