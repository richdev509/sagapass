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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
        .warning-icon {
            text-align: center;
            margin: 20px 0;
            font-size: 48px;
        }
        .reason-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .reason-box h3 {
            margin-top: 0;
            color: #856404;
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
            <h1>❌ Application Rejetée</h1>
        </div>

        <div class="content">
            <div class="warning-icon">⚠️</div>

            <p>Bonjour,</p>

            <p>Nous regrettons de vous informer que votre application OAuth <strong>{{ $applicationName }}</strong> a été rejetée par notre équipe de vérification.</p>

            <div class="reason-box">
                <h3>📋 Raison du rejet</h3>
                <p style="margin: 0;">{{ $reason }}</p>
            </div>

            <p><strong>Que faire maintenant ?</strong></p>
            <ul>
                <li>Prenez connaissance de la raison du rejet ci-dessus</li>
                <li>Modifiez votre application pour corriger les problèmes identifiés</li>
                <li>Soumettez à nouveau votre application pour révision</li>
                <li>Consultez notre guide des bonnes pratiques</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ $editUrl }}" class="button">Modifier mon application</a>
            </div>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; font-size: 14px; color: #6c757d;">
                💡 <strong>Conseils :</strong><br>
                - Assurez-vous que votre application respecte nos conditions d'utilisation<br>
                - Vérifiez que les URLs de redirection sont correctes et sécurisées (HTTPS)<br>
                - Fournissez une description claire et complète de votre application<br>
                - N'hésitez pas à nous contacter si vous avez des questions
            </p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé par SAGAPASS<br>
            En cas de questions, contactez-nous à support@sagapass.com</p>
        </div>
    </div>
</body>
</html>
