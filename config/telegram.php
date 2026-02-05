<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration Telegram Bot API
    | Utilisé pour le bot Telegram Sagaloto Admin
    |
    */

    // Token du bot Telegram (obtenu via @BotFather)
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    // URL de base de l'API Telegram
    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),

    // Nom d'utilisateur du bot (sans @)
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),

    /*
    |--------------------------------------------------------------------------
    | Sécurité
    |--------------------------------------------------------------------------
    */

    // Secret pour valider les webhooks
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    // IDs Telegram autorisés (admins Sagaloto)
    // Format: ID numérique Telegram
    'authorized_users' => array_filter(explode(',', env('TELEGRAM_AUTHORIZED_USERS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    */

    // Durée de vie de la session (en minutes)
    'session_lifetime' => env('TELEGRAM_SESSION_LIFETIME', 15),

    // Activer le nettoyage automatique des sessions expirées
    'auto_cleanup_sessions' => env('TELEGRAM_AUTO_CLEANUP_SESSIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Messages & Menus
    |--------------------------------------------------------------------------
    */

    // Message de bienvenue
    'welcome_message' => "👋 *Bienvenue Admin Sagaloto!*\n\nJe suis votre assistant virtuel. Comment puis-je vous aider aujourd'hui?\n\nUtilisez les boutons ci-dessous pour naviguer.",

    // Message pour utilisateur non autorisé
    'unauthorized_message' => "🚫 Désolé, vous n'êtes pas autorisé à utiliser ce bot.\n\nContactez l'administrateur système.",

    // Message d'erreur générique
    'error_message' => "❌ Une erreur s'est produite. Veuillez réessayer dans quelques instants.",

    // Message session expirée
    'session_expired_message' => "⏱️ Votre session a expiré. Utilisez /start pour recommencer.",

    /*
    |--------------------------------------------------------------------------
    | Menus Interactifs (MVP - Mockés)
    |--------------------------------------------------------------------------
    */

    'menus' => [
        'main' => [
            'text' => "📊 *Menu Principal*\n\nChoisissez une option:",
            'buttons' => [
                [
                    ['text' => '📊 Rapports', 'callback_data' => 'rapports'],
                    ['text' => '💰 Ventes', 'callback_data' => 'ventes'],
                ],
                [
                    ['text' => '🧾 Tirages', 'callback_data' => 'tirages'],
                    ['text' => 'ℹ️ Aide', 'callback_data' => 'aide'],
                ],
            ],
        ],

        'rapports' => [
            'text' => "📊 *Rapports*\n\nSélectionnez un type de rapport:",
            'buttons' => [
                [
                    ['text' => '📅 Rapport journalier', 'callback_data' => 'rapport_jour'],
                ],
                [
                    ['text' => '📆 Rapport hebdomadaire', 'callback_data' => 'rapport_semaine'],
                ],
                [
                    ['text' => '📊 Rapport mensuel', 'callback_data' => 'rapport_mois'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'retour'],
                ],
            ],
        ],

        'ventes' => [
            'text' => "💰 *Résumé des Ventes*\n\nChoisissez une période:",
            'buttons' => [
                [
                    ['text' => "📅 Aujourd'hui", 'callback_data' => 'ventes_jour'],
                    ['text' => '📆 Cette semaine', 'callback_data' => 'ventes_semaine'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'retour'],
                ],
            ],
        ],

        'tirages' => [
            'text' => "🧾 *Historique des Tirages*\n\nQue souhaitez-vous consulter?",
            'buttons' => [
                [
                    ['text' => '🎰 Tirages récents', 'callback_data' => 'tirages_recent'],
                    ['text' => '🏆 Gagnants', 'callback_data' => 'tirages_gagnants'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'retour'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Réponses Mockées (MVP)
    |--------------------------------------------------------------------------
    */

    'mock_responses' => [
        'rapport_jour' => "📊 *Rapport Journalier* - {date}\n\n💰 Ventes: 125,000 HTG\n🎫 Tickets vendus: 45\n🏆 Gains distribués: 35,000 HTG\n📈 Bénéfice net: 90,000 HTG\n\n✅ Tout fonctionne normalement",

        'rapport_semaine' => "📊 *Rapport Hebdomadaire* - {date}\n\n💰 Ventes totales: 875,000 HTG\n🎫 Tickets vendus: 315\n🏆 Gains distribués: 245,000 HTG\n📈 Bénéfice net: 630,000 HTG\n\n📈 +12% vs semaine dernière",

        'rapport_mois' => "📊 *Rapport Mensuel* - {date}\n\n💰 Ventes totales: 3,500,000 HTG\n🎫 Tickets vendus: 1,260\n🏆 Gains distribués: 980,000 HTG\n📈 Bénéfice net: 2,520,000 HTG\n\n📊 Meilleur mois de l'année!",

        'ventes_jour' => "💰 *Résumé Ventes - Aujourd'hui*\n\nMatin (6h-12h): 45,000 HTG\nAprès-midi (12h-18h): 50,000 HTG\nSoir (18h-23h): 30,000 HTG\n\n*Total:* 125,000 HTG",

        'ventes_semaine' => "💰 *Résumé Ventes - Cette Semaine*\n\nLundi: 115,000 HTG\nMardi: 125,000 HTG\nMercredi: 130,000 HTG\nJeudi: 135,000 HTG\nVendredi: 145,000 HTG\nSamedi: 120,000 HTG\nDimanche: 105,000 HTG\n\n*Total:* 875,000 HTG",

        'tirages_recent' => "🎰 *Tirages Récents*\n\n5 Février 2026 - 14h30\nNuméros: 12, 23, 45, 67, 89\n🏆 Gagnant: Ticket #45892\n\n5 Février 2026 - 10h00\nNuméros: 08, 15, 32, 44, 78\n🏆 Gagnant: Ticket #45761\n\n4 Février 2026 - 18h45\nNuméros: 03, 21, 38, 56, 91\n❌ Aucun gagnant",

        'tirages_gagnants' => "🏆 *Derniers Gagnants*\n\n💰 50,000 HTG - Ticket #45892\n📅 5 Février 2026 - 14h30\n\n💰 35,000 HTG - Ticket #45761\n📅 5 Février 2026 - 10h00\n\n💰 25,000 HTG - Ticket #45234\n📅 4 Février 2026 - 16h15",
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    // Activer le logging des messages
    'enable_logging' => env('TELEGRAM_ENABLE_LOGGING', true),

    // Niveau de logging (debug, info, warning, error)
    'log_level' => env('TELEGRAM_LOG_LEVEL', 'info'),

    // Logger les messages entrants
    'log_incoming_messages' => env('TELEGRAM_LOG_INCOMING', true),

    // Logger les messages sortants
    'log_outgoing_messages' => env('TELEGRAM_LOG_OUTGOING', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    // Nombre maximum de messages par minute par utilisateur
    'rate_limit_per_minute' => env('TELEGRAM_RATE_LIMIT', 20),

    // Activer le rate limiting
    'enable_rate_limiting' => env('TELEGRAM_ENABLE_RATE_LIMITING', true),

    /*
    |--------------------------------------------------------------------------
    | Timeouts & Retries
    |--------------------------------------------------------------------------
    */

    // Timeout pour les appels API (en secondes)
    'api_timeout' => env('TELEGRAM_API_TIMEOUT', 30),

    // Nombre de tentatives en cas d'échec
    'retry_attempts' => env('TELEGRAM_RETRY_ATTEMPTS', 3),

    // Délai entre les tentatives (en secondes)
    'retry_delay' => env('TELEGRAM_RETRY_DELAY', 2),

];
