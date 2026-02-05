<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration WhatsApp Cloud API (Meta)
    | Utilisé pour le bot WhatsApp Sagaloto Admin
    |
    */

    // Token d'accès permanent pour l'API WhatsApp
    'api_token' => env('WHATSAPP_API_TOKEN'),

    // ID du numéro de téléphone WhatsApp Business
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    // ID du compte WhatsApp Business
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

    // Secret de l'application (pour validation webhook)
    'app_secret' => env('WHATSAPP_APP_SECRET'),

    // Token de vérification webhook
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'sagaloto_secure_token_2026'),

    // URL de base de l'API WhatsApp
    'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com/v18.0'),

    /*
    |--------------------------------------------------------------------------
    | Sécurité
    |--------------------------------------------------------------------------
    */

    // Activer la vérification de signature
    'verify_signature' => env('WHATSAPP_VERIFY_SIGNATURE', true),

    // Numéros WhatsApp autorisés (admins Sagaloto)
    // Format: +509XXXXXXXX
    'authorized_numbers' => array_filter(explode(',', env('WHATSAPP_AUTHORIZED_NUMBERS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    */

    // Durée de vie de la session (en minutes)
    'session_lifetime' => env('WHATSAPP_SESSION_LIFETIME', 15),

    // Activer le nettoyage automatique des sessions expirées
    'auto_cleanup_sessions' => env('WHATSAPP_AUTO_CLEANUP_SESSIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Messages & Menus
    |--------------------------------------------------------------------------
    */

    // Message de bienvenue
    'welcome_message' => "👋 Bonjour Admin Sagaloto!\n\nJe suis votre assistant virtuel. Comment puis-je vous aider aujourd'hui?",

    // Message pour numéro non autorisé
    'unauthorized_message' => "🚫 Désolé, votre numéro n'est pas autorisé à utiliser ce service.\n\nContactez l'administrateur système.",

    // Message d'erreur générique
    'error_message' => "❌ Une erreur s'est produite. Veuillez réessayer dans quelques instants.",

    // Message session expirée
    'session_expired_message' => "⏱️ Votre session a expiré. Envoyez un message pour recommencer.",

    /*
    |--------------------------------------------------------------------------
    | Menus Interactifs (MVP - Mockés)
    |--------------------------------------------------------------------------
    */

    'menus' => [
        'main' => [
            'type' => 'button',
            'text' => "Choisissez une option:",
            'buttons' => [
                [
                    'id' => 'rapports',
                    'title' => '📊 Rapports',
                ],
                [
                    'id' => 'ventes',
                    'title' => '💰 Ventes',
                ],
                [
                    'id' => 'tirages',
                    'title' => '🧾 Tirages',
                ],
            ],
        ],

        'rapports' => [
            'type' => 'list',
            'text' => "Sélectionnez un type de rapport:",
            'button_text' => 'Voir rapports',
            'sections' => [
                [
                    'title' => 'Rapports',
                    'rows' => [
                        [
                            'id' => 'rapport_jour',
                            'title' => 'Rapport journalier',
                            'description' => "Rapport d'aujourd'hui",
                        ],
                        [
                            'id' => 'rapport_semaine',
                            'title' => 'Rapport hebdomadaire',
                            'description' => 'Rapport de la semaine',
                        ],
                        [
                            'id' => 'rapport_mois',
                            'title' => 'Rapport mensuel',
                            'description' => 'Rapport du mois',
                        ],
                        [
                            'id' => 'retour',
                            'title' => '⬅️ Retour',
                            'description' => 'Menu principal',
                        ],
                    ],
                ],
            ],
        ],

        'ventes' => [
            'type' => 'button',
            'text' => "Résumé des ventes:",
            'buttons' => [
                [
                    'id' => 'ventes_jour',
                    'title' => "📅 Aujourd'hui",
                ],
                [
                    'id' => 'ventes_semaine',
                    'title' => '📆 Cette semaine',
                ],
                [
                    'id' => 'retour',
                    'title' => '⬅️ Retour',
                ],
            ],
        ],

        'tirages' => [
            'type' => 'button',
            'text' => "Historique des tirages:",
            'buttons' => [
                [
                    'id' => 'tirages_recent',
                    'title' => '🎰 Récents',
                ],
                [
                    'id' => 'tirages_gagnants',
                    'title' => '🏆 Gagnants',
                ],
                [
                    'id' => 'retour',
                    'title' => '⬅️ Retour',
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
    'enable_logging' => env('WHATSAPP_ENABLE_LOGGING', true),

    // Niveau de logging (debug, info, warning, error)
    'log_level' => env('WHATSAPP_LOG_LEVEL', 'info'),

    // Logger les messages entrants
    'log_incoming_messages' => env('WHATSAPP_LOG_INCOMING', true),

    // Logger les messages sortants
    'log_outgoing_messages' => env('WHATSAPP_LOG_OUTGOING', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    // Nombre maximum de messages par minute par utilisateur
    'rate_limit_per_minute' => env('WHATSAPP_RATE_LIMIT', 10),

    // Activer le rate limiting
    'enable_rate_limiting' => env('WHATSAPP_ENABLE_RATE_LIMITING', true),

    /*
    |--------------------------------------------------------------------------
    | Timeouts & Retries
    |--------------------------------------------------------------------------
    */

    // Timeout pour les appels API (en secondes)
    'api_timeout' => env('WHATSAPP_API_TIMEOUT', 30),

    // Nombre de tentatives en cas d'échec
    'retry_attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),

    // Délai entre les tentatives (en secondes)
    'retry_delay' => env('WHATSAPP_RETRY_DELAY', 2),

];
