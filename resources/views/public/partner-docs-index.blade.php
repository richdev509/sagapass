<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentation partenaire - SAGAPASS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0D6EFD; --secondary: #1A202C; --text-muted: #718096; --border-color: #E2E8F0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: #F7FAFC; color: var(--secondary); }
        .wrap { max-width: 40rem; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        .brand { display: flex; align-items: center; gap: 0.5rem; font-weight: 800; font-size: 1.1rem; margin-bottom: 2rem; }
        .brand span { color: var(--primary); }
        h1 { font-size: 1.4rem; margin: 0 0 1.5rem; }
        ul { list-style: none; padding: 0; margin: 0; }
        li { border: 1px solid var(--border-color); border-radius: 0.6rem; margin-bottom: 0.75rem; background: #fff; }
        li a { display: block; padding: 1rem 1.1rem; color: var(--secondary); text-decoration: none; font-weight: 600; }
        li a:hover { color: var(--primary); }
        .back { display: inline-block; margin-top: 1.5rem; color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand"><span>SAGA</span>PASS &mdash; Documentation partenaire</div>
        <h1>Guides d'intégration</h1>
        <ul>
            @foreach ($slugs as $slug)
                <li><a href="{{ route('partner.docs.show', $slug) }}">{{ str_replace('_', ' ', $slug) }}</a></li>
            @endforeach
        </ul>
        <a class="back" href="{{ route('partner.dashboard') }}">&larr; Retour au tableau de bord</a>
    </div>
</body>
</html>
