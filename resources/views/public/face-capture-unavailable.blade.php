<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lien indisponible - SAGAPASS</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --primary: #0D6EFD; --secondary: #1A202C; }
        html, body { margin:0; height:100%; background: var(--secondary); font-family:'Inter',sans-serif; color:#fff; }
        .wrap { min-height:100dvh; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:2rem; gap:1rem; }
        i.icon { font-size:2.75rem; color:#F59E0B; }
        h1 { font-size:1.2rem; margin:0; }
        p { margin:0; color:rgba(255,255,255,0.65); font-size:0.9rem; max-width:22rem; }
        .brand { position:absolute; top:1.5rem; left:1.5rem; font-weight:800; }
        .brand i { color: var(--primary); }
    </style>
</head>
<body>
    <div class="brand"><i class="fa-solid fa-shield-halved"></i> SAGAPASS</div>
    <div class="wrap">
        <i class="fa-solid fa-link-slash icon"></i>
        <h1>
            @if($reason === 'expired')
                Ce lien de vérification a expiré
            @elseif($reason === 'already_used')
                Ce lien a déjà été utilisé
            @else
                Lien de vérification introuvable
            @endif
        </h1>
        <p>
            @if($reason === 'expired')
                Retournez sur votre ordinateur et relancez votre demande de vérification.
            @elseif($reason === 'already_used')
                Votre vérification a déjà été soumise. Vous pouvez fermer cette page.
            @else
                Vérifiez que vous avez bien scanné le dernier QR code affiché, ou relancez votre demande.
            @endif
        </p>
    </div>
</body>
</html>
