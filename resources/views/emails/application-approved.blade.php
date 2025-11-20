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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .success-icon {
            text-align: center;
            margin: 20px 0;
            font-size: 48px;
        }
        .credentials-box {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credential-item {
            margin: 10px 0;
        }
        .credential-label {
            font-weight: bold;
            color: #667eea;
        }
        .credential-value {
            font-family: monospace;
            background: #fff;
            padding: 8px;
            border-radius: 4px;
            margin-top: 5px;
            word-break: break-all;
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
            <h1>✅ Application Approuvée</h1>
        </div>

        <div class="content">
            <div class="success-icon">🎉</div>

            <p>Bonjour,</p>

            <p>Nous avons le plaisir de vous informer que votre application OAuth <strong>{{ $applicationName }}</strong> a été approuvée par notre équipe.</p>

            <div class="credentials-box">
                <h3 style="margin-top: 0; color: #667eea;">🔑 Identifiants de l'application</h3>

                <div class="credential-item">
                    <div class="credential-label">Client ID</div>
                    <div class="credential-value">{{ $clientId }}</div>
                </div>

                <p style="margin-top: 15px; font-size: 14px; color: #6c757d;">
                    ⚠️ Votre Client Secret est disponible dans votre tableau de bord développeur. Conservez-le en lieu sûr.
                </p>
            </div>

            <p><strong>Que faire maintenant ?</strong></p>
            <ul>
                <li>Accédez à votre tableau de bord développeur</li>
                <li>Récupérez votre Client Secret (visible une seule fois à la création)</li>
                <li>Intégrez l'authentification SAGAPASS dans votre application</li>
                <li>Consultez notre documentation pour l'implémentation</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">Accéder au tableau de bord</a>
            </div>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                <strong>Date d'approbation :</strong> {{ $approvedAt->format('d/m/Y à H:i') }}
            </p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé par SAGAPASS<br>
            En cas de questions, contactez-nous à support@sagapass.com</p>
        </div>
    </div>
</body>
</html>
