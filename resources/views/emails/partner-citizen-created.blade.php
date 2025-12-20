<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .credentials { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
        .credentials p { margin: 10px 0; }
        .credentials strong { color: #667eea; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; color: #777; font-size: 12px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Bienvenue sur SAGAPASS !</h1>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>

            <p>Un compte SAGAPASS a été créé pour vous via notre partenaire <strong>{{ strtoupper($partnerId) }}</strong>.</p>

            <div class="credentials">
                <h3>📧 Vos identifiants de connexion :</h3>
                <p><strong>Email :</strong> {{ $user->email }}</p>
                <p><strong>Mot de passe temporaire :</strong> <code style="background: #f0f0f0; padding: 5px 10px; border-radius: 5px;">{{ $password }}</code></p>
            </div>

            <div class="warning">
                <strong>⚠️ Important :</strong> Pour des raisons de sécurité, nous vous recommandons fortement de changer ce mot de passe dès votre première connexion.
            </div>

            <h3>📋 Prochaines étapes :</h3>
            <ol>
                <li><strong>Votre vidéo de vérification</strong> est en cours d'examen par notre équipe</li>
                <li>Vous recevrez un email une fois votre compte validé</li>
                <li>Vous pourrez alors accéder à tous nos services partenaires</li>
            </ol>

            <div style="text-align: center;">
                <a href="{{ url('/login') }}" class="button">Se connecter à SAGAPASS</a>
            </div>

            <h3>🔐 Qu'est-ce que SAGAPASS ?</h3>
            <p>SAGAPASS est votre identité numérique sécurisée qui vous permet de :</p>
            <ul>
                <li>✅ Vérifier votre identité en ligne de manière simple et sécurisée</li>
                <li>✅ Accéder à de nombreux services partenaires sans créer plusieurs comptes</li>
                <li>✅ Contrôler vos données personnelles et qui y a accès</li>
                <li>✅ Obtenir votre badge numérique de citoyen vérifié</li>
            </ul>

            <div class="footer">
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                <p>Si vous n'avez pas demandé la création de ce compte, veuillez nous contacter immédiatement.</p>
                <p>&copy; {{ date('Y') }} SAGAPASS - Tous droits réservés</p>
                <p>
                    <a href="{{ url('/') }}">Site web</a> |
                    <a href="{{ url('/support') }}">Support</a> |
                    <a href="{{ url('/privacy') }}">Confidentialité</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
