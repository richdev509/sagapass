<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .maintenance-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 3rem;
            max-width: 600px;
            text-align: center;
        }

        .maintenance-icon {
            font-size: 5rem;
            color: #667eea;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .maintenance-title {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .maintenance-message {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .countdown {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 10px;
            margin: 2rem 0;
        }

        .countdown-text {
            color: #4b5563;
            font-weight: 600;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 2rem;
        }

        .contact-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .contact-link:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>

        <h1 class="maintenance-title">
            Maintenance en Cours
        </h1>

        <div class="maintenance-message">
            {{ $message ?? 'Le système est actuellement en maintenance. Nous serons de retour bientôt.' }}
        </div>

        <div class="countdown">
            <p class="countdown-text mb-0">
                <i class="fas fa-clock me-2"></i>
                Nous serons de retour dans quelques minutes
            </p>
        </div>

        <p class="text-muted mt-4">
            <i class="fas fa-info-circle me-2"></i>
            Cette maintenance est nécessaire pour améliorer votre expérience
        </p>

        <hr class="my-4">

        <p class="mb-2">
            <strong>Besoin d'aide urgente ?</strong>
        </p>
        <a href="{{ \App\Models\SystemSetting::getWhatsAppLink() }}"
           target="_blank"
           class="contact-link">
            <i class="fab fa-whatsapp me-2"></i>
            Contactez le support WhatsApp
        </a>

        <div class="mt-4">
            <small class="text-muted">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
            </small>
        </div>
    </div>

    <script>
        // Auto-refresh toutes les 30 secondes
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
