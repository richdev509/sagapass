<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .alert-icon {
            text-align: center;
            margin: 20px 0;
            font-size: 48px;
        }
        .reason-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .reason-box h3 {
            margin-top: 0;
            color: #721c24;
        }
        .impact-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚫 Application Suspendue</h1>
        </div>

        <div class="content">
            <div class="alert-icon">⛔</div>

            <p>Bonjour,</p>

            <p>Nous vous informons que votre application OAuth <strong>{{ $applicationName }}</strong> a été suspendue par notre équipe de sécurité.</p>

            <div class="reason-box">
                <h3>📋 Raison de la suspension</h3>
                <p style="margin: 0;">{{ $reason }}</p>
            </div>

            <div class="impact-box">
                <h3 style="margin-top: 0; color: #856404;">⚠️ Impact de cette suspension</h3>
                <ul style="margin: 10px 0;">
                    <li>Toutes les autorisations utilisateurs ont été révoquées</li>
                    <li>Les utilisateurs ne peuvent plus se connecter via votre application</li>
                    <li>Toutes les tentatives d'authentification échoueront</li>
                    <li>Vos tokens d'accès existants sont invalidés</li>
                </ul>
            </div>

            <p><strong>Que faire maintenant ?</strong></p>
            <ul>
                <li>Prenez connaissance de la raison de la suspension</li>
                <li>Corrigez les problèmes identifiés dans votre application</li>
                <li>Contactez notre équipe support pour discuter de la réactivation</li>
                <li>Préparez les justificatifs nécessaires pour un appel</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">Voir mon tableau de bord</a>
            </div>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; font-size: 14px; color: #6c757d;">
                <strong>Contact support :</strong> {{ $supportEmail }}<br>
                Merci de mentionner le nom de votre application dans votre demande.
            </p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé par SAGAPASS<br>
            Cette action a été prise pour protéger nos utilisateurs et notre plateforme.</p>
        </div>
    </div>
</body>
</html>
