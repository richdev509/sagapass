<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification SAGAPASS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            padding: 40px 30px;
        }
        .code-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .verification-code {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 8px;
            color: white;
            font-family: 'Courier New', monospace;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
        }
        p {
            line-height: 1.6;
            color: #333;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin: 8px 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 SAGAPASS</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Vérification de votre adresse email</p>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <p>Vous avez demandé à créer un compte SAGAPASS. Pour confirmer votre adresse email, veuillez utiliser le code de vérification ci-dessous :</p>

            <div class="code-box">
                <div class="verification-code">{{ $code }}</div>
            </div>

            <div class="info-box">
                <strong>ℹ️ Informations importantes :</strong>
                <ul>
                    <li>Ce code est valable pendant <strong>{{ $expiresInMinutes }} minutes</strong></li>
                    <li>Ne partagez jamais ce code avec qui que ce soit</li>
                    <li>Si vous n'avez pas demandé ce code, ignorez cet email</li>
                </ul>
            </div>

            <div class="warning-box">
                <strong>⚠️ Sécurité :</strong><br>
                SAGAPASS ne vous demandera JAMAIS votre code par téléphone ou par email. Ne communiquez ce code à personne.
            </div>

            <p style="margin-top: 30px;">
                Une fois votre email vérifié, vous pourrez continuer votre inscription et créer votre identité numérique sécurisée.
            </p>

            <p style="margin-top: 30px;">
                Cordialement,<br>
                <strong>L'équipe SAGAPASS</strong>
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0 0 10px 0;">
                © {{ date('Y') }} SAGAPASS - Votre Identité Numérique Sécurisée
            </p>
            <p style="margin: 0; font-size: 12px;">
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.
            </p>
        </div>
    </div>
</body>
</html>
