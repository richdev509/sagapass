<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification envoyée - SAGAPASS</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --primary: #0D6EFD; --secondary: #1A202C; --success: #16A34A; }
        html, body { margin:0; height:100%; background: var(--secondary); font-family:'Inter',sans-serif; color:#fff; }
        .wrap { min-height:100dvh; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:2rem; gap:1rem; }
        .check { width:4.5rem; height:4.5rem; border-radius:50%; background: rgba(22,163,74,0.15); display:flex; align-items:center; justify-content:center; }
        .check i { font-size:2rem; color: var(--success); }
        h1 { font-size:1.2rem; margin:0; }
        p { margin:0; color:rgba(255,255,255,0.65); font-size:0.9rem; max-width:22rem; }
        .brand { position:absolute; top:1.5rem; left:1.5rem; font-weight:800; }
        .brand i { color: var(--primary); }
    </style>
</head>
<body>
    <div class="brand"><i class="fa-solid fa-shield-halved"></i> SAGAPASS</div>
    <div class="wrap">
        <div class="check"><i class="fa-solid fa-check"></i></div>
        <h1>Vérification envoyée</h1>
        <p>Votre selfie a bien été reçu et est en cours d'analyse. Vous pouvez fermer cette page et retourner sur votre ordinateur — la suite se passe automatiquement là-bas.</p>
    </div>
</body>
</html>
