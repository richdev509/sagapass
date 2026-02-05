<?php

namespace App\Services\Telegram;

use Carbon\Carbon;

class TelegramMenuService
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Envoyer le menu principal
     *
     * @param int|string $chatId
     * @return void
     */
    public function sendMainMenu($chatId)
    {
        $menu = config('telegram.menus.main');

        $this->telegramService->sendMessageWithKeyboard(
            $chatId,
            $menu['text'],
            $menu['buttons']
        );
    }

    /**
     * Envoyer un menu spécifique
     *
     * @param int|string $chatId
     * @param string $menuName
     * @param int|null $messageId Pour éditer le message existant
     * @return void
     */
    public function sendMenu($chatId, string $menuName, ?int $messageId = null)
    {
        $menu = config("telegram.menus.{$menuName}");

        if (!$menu) {
            \Log::warning("Telegram menu not found: {$menuName}");
            $this->sendMainMenu($chatId);
            return;
        }

        if ($messageId) {
            // Éditer le message existant
            $this->telegramService->editMessageText(
                $chatId,
                $messageId,
                $menu['text'],
                $menu['buttons']
            );
        } else {
            // Envoyer un nouveau message
            $this->telegramService->sendMessageWithKeyboard(
                $chatId,
                $menu['text'],
                $menu['buttons']
            );
        }
    }

    /**
     * Obtenir une réponse mockée
     *
     * @param string $actionId
     * @return string
     */
    public function getMockResponse(string $actionId): string
    {
        $responses = config('telegram.mock_responses', []);

        if (!isset($responses[$actionId])) {
            return "Données non disponibles pour le moment.";
        }

        $response = $responses[$actionId];

        // Remplacer {date} par la date actuelle
        $response = str_replace('{date}', Carbon::now()->format('d/m/Y'), $response);

        return $response;
    }

    /**
     * Envoyer un message d'aide
     *
     * @param int|string $chatId
     * @return void
     */
    public function sendHelp($chatId)
    {
        $helpMessage = "ℹ️ *Aide Sagaloto Bot*\n\n" .
            "*Commandes disponibles:*\n" .
            "• /start - Afficher le menu principal\n" .
            "• /menu - Afficher le menu principal\n" .
            "• /aide - Afficher cette aide\n\n" .
            "*Navigation:*\n" .
            "• Utilisez les boutons pour naviguer\n" .
            "• Cliquez sur *⬅️ Retour* pour revenir\n\n" .
            "*Menus disponibles:*\n" .
            "📊 Rapports - Consulter les rapports d'activité\n" .
            "💰 Ventes - Voir les résumés de ventes\n" .
            "🧾 Tirages - Historique des tirages\n\n" .
            "Pour toute question, contactez l'administrateur système.";

        $this->telegramService->sendMessage($chatId, $helpMessage);
    }

    /**
     * Envoyer un message d'erreur
     *
     * @param int|string $chatId
     * @param string|null $customMessage
     * @return void
     */
    public function sendError($chatId, ?string $customMessage = null)
    {
        $message = $customMessage ?? config('telegram.error_message');
        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Envoyer une notification de session expirée
     *
     * @param int|string $chatId
     * @return void
     */
    public function sendSessionExpired($chatId)
    {
        $this->telegramService->sendMessage(
            $chatId,
            config('telegram.session_expired_message')
        );
    }

    /**
     * Envoyer un résumé quotidien
     *
     * @param int|string $chatId
     * @return void
     */
    public function sendDailySummary($chatId)
    {
        $summary = "📊 *Résumé Quotidien* - " . Carbon::now()->format('d/m/Y') . "\n\n";

        // Phase 1: Données mockées
        $summary .= "💰 Ventes: 125,000 HTG\n";
        $summary .= "🎫 Tickets: 45\n";
        $summary .= "🏆 Gains: 35,000 HTG\n";
        $summary .= "📈 Bénéfice: 90,000 HTG\n\n";
        $summary .= "✅ Système opérationnel\n";
        $summary .= "🔔 Aucune alerte";

        $this->telegramService->sendMessage($chatId, $summary);
    }

    /**
     * Envoyer une alerte importante
     *
     * @param int|string $chatId
     * @param string $alertMessage
     * @param string $priority (low, medium, high, critical)
     * @return void
     */
    public function sendAlert($chatId, string $alertMessage, string $priority = 'medium')
    {
        $emojis = [
            'low' => 'ℹ️',
            'medium' => '⚠️',
            'high' => '🚨',
            'critical' => '🔴',
        ];

        $emoji = $emojis[$priority] ?? '⚠️';

        $message = "{$emoji} *ALERTE SAGALOTO*\n\n{$alertMessage}\n\n";
        $message .= "_" . Carbon::now()->format('d/m/Y H:i') . "_";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Broadcaster un message à tous les admins autorisés
     *
     * @param string $message
     * @param array|null $chatIds
     * @return void
     */
    public function broadcast(string $message, ?array $chatIds = null)
    {
        // Si pas de chat IDs spécifiés, utiliser la liste autorisée
        if (!$chatIds) {
            $chatIds = config('telegram.authorized_users', []);
        }

        foreach ($chatIds as $chatId) {
            try {
                $this->telegramService->sendMessage($chatId, $message);

                // Petit délai pour éviter le rate limiting
                usleep(500000); // 0.5 seconde

            } catch (\Exception $e) {
                \Log::error('Telegram broadcast error', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
