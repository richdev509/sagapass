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

    // URL du webhook Telegram
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),

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
                    ['text' => '📅 Rapport journalier', 'callback_data' => 'rapport_type_jour'],
                ],
                [
                    ['text' => '📆 Rapport hebdomadaire', 'callback_data' => 'rapport_type_semaine'],
                ],
                [
                    ['text' => '📊 Rapport mensuel', 'callback_data' => 'rapport_type_mois'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'retour'],
                ],
            ],
        ],

        // Sélection période pour rapports
        'rapport_periode' => [
            'text' => "⏰ *Choisissez la période*\n\nPour quel moment de la journée?",
            'buttons' => [
                [
                    ['text' => '🌅 Matin', 'callback_data' => 'periode_matin'],
                ],
                [
                    ['text' => '☀️ Après-midi', 'callback_data' => 'periode_apres_midi'],
                ],
                [
                    ['text' => '🌙 Soir', 'callback_data' => 'periode_soir'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'rapports'],
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
            'text' => "🧾 *Tirages*\n\nChoisissez la période:",
            'buttons' => [
                [
                    ['text' => '🌅 Matin', 'callback_data' => 'tirage_matin'],
                ],
                [
                    ['text' => '☀️ Après-midi', 'callback_data' => 'tirage_apres_midi'],
                ],
                [
                    ['text' => '🌙 Soir', 'callback_data' => 'tirage_soir'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'retour'],
                ],
            ],
        ],

        // Tirages Matin
        'tirage_matin' => [
            'text' => "🌅 *Tirages Matin*\n\nSélectionnez le tirage:",
            'buttons' => [
                [
                    ['text' => '🏴󠁵󠁳󠁴󠁮󠁿 Tennessee', 'callback_data' => 'tirage_tennessee_matin'],
                ],
                [
                    ['text' => '🏴󠁵󠁳󠁴󠁸󠁿 Texas', 'callback_data' => 'tirage_texas_matin'],
                ],
                [
                    ['text' => '🏴󠁵󠁳󠁧󠁡󠁿 Georgia', 'callback_data' => 'tirage_georgia_matin'],
                ],
                [
                    ['text' => '🏴󠁵󠁳󠁦󠁬󠁿 Florida', 'callback_data' => 'tirage_florida_matin'],
                ],
                [
                    ['text' => '🏴󠁵󠁳󠁮󠁹󠁿 New York', 'callback_data' => 'tirage_newyork_matin'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'tirages'],
                ],
            ],
        ],

        // Tirages Après-midi
        'tirage_apres_midi' => [
            'text' => "☀️ *Tirages Après-midi*\n\nSélectionnez le tirage:",
            'buttons' => [
                [
                    ['text' => '🏴󠁵󠁳󠁧󠁡󠁿 Georgia Après-midi', 'callback_data' => 'tirage_georgia_apres_midi'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'tirages'],
                ],
            ],
        ],

        // Tirages Soir
        'tirage_soir' => [
            'text' => "🌙 *Tirages Soir*\n\nSélectionnez le tirage:",
            'buttons' => [
                [
                    ['text' => '🏴󠁵󠁳󠁴󠁸󠁿 Texas Soir', 'callback_data' => 'tirage_texas_soir'],
                ],
                [
                    ['text' => '🏴󠁵󠁳󠁴󠁮󠁿 Tennessee Soir', 'callback_data' => 'tirage_tennessee_soir'],
                ],
                [
                    ['text' => '🏴󠁵󠁳󠁦󠁬󠁿 Florida Soir', 'callback_data' => 'tirage_florida_soir'],
                ],
                [
                    ['text' => '🏴󠁵󠁳󠁮󠁹󠁿 New York Soir', 'callback_data' => 'tirage_newyork_soir'],
                ],
                [
                    ['text' => '🏴󠁵󠁳󠁧󠁡󠁿 Georgia Night', 'callback_data' => 'tirage_georgia_night'],
                ],
                [
                    ['text' => '⬅️ Retour', 'callback_data' => 'tirages'],
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
        // Rapports par période - Matin
        'rapport_jour_matin' => "📊 *Rapport Journalier - Matin* 🌅\n{date}\n\n💰 Ventes: 45,000 HTG\n🎫 Tickets vendus: 18\n🏆 Gains distribués: 12,000 HTG\n📈 Bénéfice net: 33,000 HTG\n\n✅ Période: 6h - 12h",

        'rapport_jour_apres_midi' => "📊 *Rapport Journalier - Après-midi* ☀️\n{date}\n\n💰 Ventes: 50,000 HTG\n🎫 Tickets vendus: 20\n🏆 Gains distribués: 15,000 HTG\n📈 Bénéfice net: 35,000 HTG\n\n✅ Période: 12h - 18h",

        'rapport_jour_soir' => "📊 *Rapport Journalier - Soir* 🌙\n{date}\n\n💰 Ventes: 30,000 HTG\n🎫 Tickets vendus: 12\n🏆 Gains distribués: 8,000 HTG\n📈 Bénéfice net: 22,000 HTG\n\n✅ Période: 18h - 23h",

        'rapport_semaine_matin' => "📊 *Rapport Hebdomadaire - Matin* 🌅\n{date}\n\n💰 Ventes: 315,000 HTG\n🎫 Tickets vendus: 126\n🏆 Gains distribués: 84,000 HTG\n📈 Bénéfice net: 231,000 HTG\n\n📈 +8% vs semaine dernière",

        'rapport_semaine_apres_midi' => "📊 *Rapport Hebdomadaire - Après-midi* ☀️\n{date}\n\n💰 Ventes: 350,000 HTG\n🎫 Tickets vendus: 140\n🏆 Gains distribués: 105,000 HTG\n📈 Bénéfice net: 245,000 HTG\n\n📈 +15% vs semaine dernière",

        'rapport_semaine_soir' => "📊 *Rapport Hebdomadaire - Soir* 🌙\n{date}\n\n💰 Ventes: 210,000 HTG\n🎫 Tickets vendus: 84\n🏆 Gains distribués: 56,000 HTG\n📈 Bénéfice net: 154,000 HTG\n\n📈 +10% vs semaine dernière",

        'rapport_mois_matin' => "📊 *Rapport Mensuel - Matin* 🌅\n{date}\n\n💰 Ventes: 1,350,000 HTG\n🎫 Tickets vendus: 540\n🏆 Gains distribués: 378,000 HTG\n📈 Bénéfice net: 972,000 HTG\n\n📊 Excellente performance!",

        'rapport_mois_apres_midi' => "📊 *Rapport Mensuel - Après-midi* ☀️\n{date}\n\n💰 Ventes: 1,500,000 HTG\n🎫 Tickets vendus: 600\n🏆 Gains distribués: 420,000 HTG\n📈 Bénéfice net: 1,080,000 HTG\n\n📊 Meilleure période du mois!",

        'rapport_mois_soir' => "📊 *Rapport Mensuel - Soir* 🌙\n{date}\n\n💰 Ventes: 900,000 HTG\n🎫 Tickets vendus: 360\n🏆 Gains distribués: 252,000 HTG\n📈 Bénéfice net: 648,000 HTG\n\n📊 Bonne performance!",

        // Ventes
        'ventes_jour' => "💰 *Résumé Ventes - Aujourd'hui*\n\nMatin (6h-12h): 45,000 HTG\nAprès-midi (12h-18h): 50,000 HTG\nSoir (18h-23h): 30,000 HTG\n\n*Total:* 125,000 HTG",

        'ventes_semaine' => "💰 *Résumé Ventes - Cette Semaine*\n\nLundi: 115,000 HTG\nMardi: 125,000 HTG\nMercredi: 130,000 HTG\nJeudi: 135,000 HTG\nVendredi: 145,000 HTG\nSamedi: 120,000 HTG\nDimanche: 105,000 HTG\n\n*Total:* 875,000 HTG",

        // Tirages Matin
        'tirage_tennessee_matin' => "🎰 *Tennessee Matin* 🌅\n\n📅 {date} - 10:00\nNuméros gagnants: 08, 15, 23, 42, 67\n🏆 Gagnant: Ticket #TN-45892\n💰 Gain: 25,000 HTG",

        'tirage_texas_matin' => "🎰 *Texas Matin* 🌅\n\n📅 {date} - 10:30\nNuméros gagnants: 12, 19, 34, 55, 78\n🏆 Gagnant: Ticket #TX-45761\n💰 Gain: 30,000 HTG",

        'tirage_georgia_matin' => "🎰 *Georgia Matin* 🌅\n\n📅 {date} - 11:00\nNuméros gagnants: 03, 21, 38, 49, 81\n❌ Aucun gagnant\n💰 Jackpot reporté: 75,000 HTG",

        'tirage_florida_matin' => "🎰 *Florida Matin* 🌅\n\n📅 {date} - 11:30\nNuméros gagnants: 07, 14, 28, 51, 69\n🏆 Gagnant: Ticket #FL-45234\n💰 Gain: 20,000 HTG",

        'tirage_newyork_matin' => "🎰 *New York Matin* 🌅\n\n📅 {date} - 12:00\nNuméros gagnants: 05, 16, 32, 47, 88\n🏆 Gagnant: Ticket #NY-45123\n💰 Gain: 35,000 HTG",

        // Tirages Après-midi
        'tirage_georgia_apres_midi' => "🎰 *Georgia Après-midi* ☀️\n\n📅 {date} - 15:00\nNuméros gagnants: 11, 24, 36, 58, 79\n🏆 Gagnant: Ticket #GA-45678\n💰 Gain: 40,000 HTG",

        // Tirages Soir
        'tirage_texas_soir' => "🎰 *Texas Soir* 🌙\n\n📅 {date} - 19:00\nNuméros gagnants: 09, 22, 41, 63, 85\n🏆 Gagnant: Ticket #TX-45999\n💰 Gain: 45,000 HTG",

        'tirage_tennessee_soir' => "🎰 *Tennessee Soir* 🌙\n\n📅 {date} - 19:30\nNuméros gagnants: 04, 18, 35, 52, 76\n❌ Aucun gagnant\n💰 Jackpot reporté: 85,000 HTG",

        'tirage_florida_soir' => "🎰 *Florida Soir* 🌙\n\n📅 {date} - 20:00\nNuméros gagnants: 13, 27, 44, 61, 89\n🏆 Gagnant: Ticket #FL-45555\n💰 Gain: 50,000 HTG",

        'tirage_newyork_soir' => "🎰 *New York Soir* 🌙\n\n📅 {date} - 20:30\nNuméros gagnants: 06, 17, 39, 54, 82\n🏆 Gagnant: Ticket #NY-45321\n💰 Gain: 55,000 HTG",

        'tirage_georgia_night' => "🎰 *Georgia Night* 🌙\n\n📅 {date} - 21:00\nNuméros gagnants: 02, 20, 31, 48, 90\n🏆 Gagnant: Ticket #GA-45888\n💰 Gain: 60,000 HTG",
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
