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
            background: linear-gradient(135deg, #0D6EFD 0%, #0a58ca 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .content {
            padding: 30px;
            text-align: center;
        }
        .code {
            display: inline-block;
            margin: 20px 0;
            padding: 16px 28px;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.3em;
            background: #EDF2F7;
            border-radius: 8px;
            color: #1A202C;
        }
        .footer-note {
            font-size: 13px;
            color: #718096;
            padding: 0 30px 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SAGAPASS Partenaires</h1>
        </div>
        <div class="content">
            <p>Voici votre code de connexion. Il est valide 10 minutes.</p>
            <div class="code">{{ $code }}</div>
            <p>Si vous n'avez pas demande cette connexion, ignorez cet email et changez votre mot de passe par securite.</p>
        </div>
        <div class="footer-note">
            SAGAPASS - Verification d'identite
        </div>
    </div>
</body>
</html>
