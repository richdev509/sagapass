<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MenuService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Envoyer le menu principal
     *
     * @param string $to
     * @return void
     */
    public function sendMainMenu(string $to)
    {
        $menu = config('whatsapp.menus.main');

        if ($menu['type'] === 'button') {
            $this->whatsappService->sendButtonMessage(
                $to,
                $menu['text'],
                $menu['buttons']
            );
        }
    }

    /**
     * Envoyer un menu spécifique
     *
     * @param string $to
     * @param string $menuName
     * @return void
     */
    public function sendMenu(string $to, string $menuName)
    {
        $menu = config("whatsapp.menus.{$menuName}");

        if (!$menu) {
            Log::warning("Menu not found: {$menuName}");
            $this->sendMainMenu($to);
            return;
        }

        if ($menu['type'] === 'button') {
            $this->whatsappService->sendButtonMessage(
                $to,
                $menu['text'],
                $menu['buttons']
            );
        } elseif ($menu['type'] === 'list') {
            $this->whatsappService->sendListMessage(
                $to,
                $menu['text'],
                $menu['button_text'],
                $menu['sections']
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
        $responses = config('whatsapp.mock_responses', []);

        if (!isset($responses[$actionId])) {
            return "Données non disponibles pour le moment.";
        }

        $response = $responses[$actionId];

        // Remplacer {date} par la date actuelle
        $response = str_replace('{date}', Carbon::now()->format('d/m/Y'), $response);

        return $response;
    }

    /**
     * Envoyer un rapport (pour la phase 2 avec Sagaloto API)
     *
     * @param string $to
     * @param string $reportType
     * @return void
     */
    public function sendReport(string $to, string $reportType)
    {
        // Phase 1 (MVP): Réponse mockée
        $response = $this->getMockResponse($reportType);
        $this->whatsappService->sendTextMessage($to, $response);

        // Phase 2: Intégration réelle avec Sagaloto API
        // $data = app(SagalotoApiService::class)->getReport($reportType);
        // $response = $this->formatReport($data);
        // $this->whatsappService->sendTextMessage($to, $response);

        // Optionnel: Envoyer aussi en PDF
        // $pdfUrl = $this->generatePdfReport($data);
        // $this->whatsappService->sendDocument($to, $pdfUrl, "Rapport_{$reportType}.pdf");
    }

    /**
     * Formater un rapport (pour phase 2)
     *
     * @param array $data
     * @return string
     */
    protected function formatReport(array $data): string
    {
        // À implémenter en phase 2
        // Transformer les données de l'API Sagaloto en message WhatsApp formaté
        return "Rapport non disponible";
    }

    /**
     * Générer un PDF de rapport (pour phase 2)
     *
     * @param array $data
     * @return string URL du PDF
     */
    protected function generatePdfReport(array $data): string
    {
        // À implémenter en phase 2
        // Utiliser une librairie comme DomPDF ou Snappy
        // Générer le PDF et le stocker
        // Retourner l'URL publique
        return "";
    }

    /**
     * Envoyer un message d'aide
     *
     * @param string $to
     * @return void
     */
    public function sendHelp(string $to)
    {
        $helpMessage = "ℹ️ *Aide Sagaloto Bot*\n\n" .
            "*Commandes disponibles:*\n" .
            "• *salut* ou *menu* - Afficher le menu principal\n" .
            "• *aide* ou *help* - Afficher cette aide\n\n" .
            "*Navigation:*\n" .
            "• Utilisez les boutons pour naviguer\n" .
            "• Cliquez sur *⬅️ Retour* pour revenir\n\n" .
            "*Menus disponibles:*\n" .
            "📊 Rapports - Consulter les rapports d'activité\n" .
            "💰 Ventes - Voir les résumés de ventes\n" .
            "🧾 Tirages - Historique des tirages\n\n" .
            "Pour toute question, contactez l'administrateur système.";

        $this->whatsappService->sendTextMessage($to, $helpMessage);
    }

    /**
     * Envoyer un message d'erreur
     *
     * @param string $to
     * @param string|null $customMessage
     * @return void
     */
    public function sendError(string $to, ?string $customMessage = null)
    {
        $message = $customMessage ?? config('whatsapp.error_message');
        $this->whatsappService->sendTextMessage($to, $message);
    }

    /**
     * Envoyer une notification de session expirée
     *
     * @param string $to
     * @return void
     */
    public function sendSessionExpired(string $to)
    {
        $this->whatsappService->sendTextMessage(
            $to,
            config('whatsapp.session_expired_message')
        );
    }

    /**
     * Construire un menu dynamique basé sur les permissions (Phase 2)
     *
     * @param string $to
     * @param array $permissions
     * @return void
     */
    public function sendDynamicMenu(string $to, array $permissions)
    {
        // Phase 2: Menu personnalisé selon les permissions de l'admin
        // Pour l'instant, tous ont accès à tout
        $this->sendMainMenu($to);
    }

    /**
     * Envoyer un résumé quotidien (peut être schedulé)
     *
     * @param string $to
     * @return void
     */
    public function sendDailySummary(string $to)
    {
        $summary = "📊 *Résumé Quotidien* - " . Carbon::now()->format('d/m/Y') . "\n\n";

        // Phase 1: Données mockées
        $summary .= "💰 Ventes: 125,000 HTG\n";
        $summary .= "🎫 Tickets: 45\n";
        $summary .= "🏆 Gains: 35,000 HTG\n";
        $summary .= "📈 Bénéfice: 90,000 HTG\n\n";
        $summary .= "✅ Système opérationnel\n";
        $summary .= "🔔 Aucune alerte";

        // Phase 2: Données réelles de Sagaloto API
        // $data = app(SagalotoApiService::class)->getDailySummary();
        // $summary = $this->formatDailySummary($data);

        $this->whatsappService->sendTextMessage($to, $summary);
    }

    /**
     * Envoyer une alerte importante
     *
     * @param string $to
     * @param string $alertMessage
     * @param string $priority (low, medium, high, critical)
     * @return void
     */
    public function sendAlert(string $to, string $alertMessage, string $priority = 'medium')
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

        $this->whatsappService->sendTextMessage($to, $message);
    }

    /**
     * Broadcaster un message à tous les admins autorisés (Phase 2)
     *
     * @param string $message
     * @param array|null $phoneNumbers
     * @return void
     */
    public function broadcast(string $message, ?array $phoneNumbers = null)
    {
        // Si pas de numéros spécifiés, utiliser la whitelist
        if (!$phoneNumbers) {
            $phoneNumbers = config('whatsapp.authorized_numbers', []);
        }

        foreach ($phoneNumbers as $number) {
            try {
                $this->whatsappService->sendTextMessage($number, $message);

                // Petit délai pour éviter le rate limiting
                usleep(500000); // 0.5 seconde

            } catch (\Exception $e) {
                Log::error('Broadcast error', [
                    'number' => $number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
