<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ str_replace('_', ' ', $slug) }} - Documentation SAGAPASS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0D6EFD; --secondary: #1A202C; --text-body: #4A5568; --text-muted: #718096; --border-color: #E2E8F0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: #F7FAFC; color: var(--secondary); }
        .wrap { max-width: 46rem; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        .back { display: inline-block; margin-bottom: 1.5rem; color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
        .doc { background: #fff; border: 1px solid var(--border-color); border-radius: 0.9rem; padding: 2rem; line-height: 1.65; color: var(--text-body); }
        .doc h1, .doc h2, .doc h3 { color: var(--secondary); }
        .doc code { background: #EDF2F7; padding: 0.15rem 0.35rem; border-radius: 0.3rem; font-size: 0.88em; }
        .doc pre { background: #1A202C; color: #E2E8F0; padding: 1rem; border-radius: 0.6rem; overflow-x: auto; }
        .doc pre code { background: none; padding: 0; color: inherit; }
        .doc table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        .doc th, .doc td { border: 1px solid var(--border-color); padding: 0.5rem 0.7rem; text-align: left; font-size: 0.92rem; }
        .doc blockquote { border-left: 3px solid var(--primary); margin: 1rem 0; padding: 0.25rem 1rem; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="wrap">
        <a class="back" href="{{ route('partner.docs.index') }}">&larr; Tous les guides</a>
        <div class="doc">
            {!! $html !!}
        </div>
    </div>
</body>
</html>
