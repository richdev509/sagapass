<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devenir partenaire - SAGAPASS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0D6EFD;
            --primary-dark: #0a58ca;
            --secondary: #1A202C;
            --text-body: #4A5568;
            --text-muted: #718096;
            --border-color: #E2E8F0;
            --danger: #DC2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #F7FAFC;
            color: var(--secondary);
        }
        .wrap { max-width: 42rem; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        .brand { display: flex; align-items: center; gap: 0.5rem; font-weight: 800; font-size: 1.1rem; margin-bottom: 2rem; }
        .brand span { color: var(--primary); }
        h1 { font-size: 1.5rem; margin: 0 0 0.4rem; }
        .subtitle { color: var(--text-muted); margin: 0 0 2rem; font-size: 0.95rem; }
        .card { background: #fff; border: 1px solid var(--border-color); border-radius: 0.9rem; padding: 1.75rem; }
        .field { margin-bottom: 1.1rem; }
        label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; }
        input, textarea {
            width: 100%; padding: 0.65rem 0.8rem; border: 1px solid var(--border-color);
            border-radius: 0.5rem; font-family: inherit; font-size: 0.92rem; color: var(--secondary);
        }
        input:focus, textarea:focus { outline: 2px solid var(--primary); outline-offset: 1px; }
        textarea { resize: vertical; min-height: 5rem; }
        .hint { font-size: 0.78rem; color: var(--text-muted); margin-top: 0.3rem; }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 40rem) { .row2 { grid-template-columns: 1fr; } }
        .error { color: var(--danger); font-size: 0.8rem; margin-top: 0.3rem; }
        .btn {
            width: 100%; border: none; background: var(--primary); color: #fff;
            padding: 0.85rem; border-radius: 0.6rem; font-weight: 700; font-size: 1rem; cursor: pointer;
        }
        .btn:hover { background: var(--primary-dark); }
        .footer-note { text-align: center; font-size: 0.85rem; color: var(--text-muted); margin-top: 1.25rem; }
        .footer-note a { color: var(--primary); text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand"><span>SAGA</span>PASS &mdash; Partenaires</div>
        <h1>Devenir partenaire</h1>
        <p class="subtitle">Intégrez la vérification d'identité SagaPass à votre service. Un administrateur revoit chaque demande avant d'activer les identifiants API.</p>

        <div class="card">
            <form method="POST" action="{{ route('partner.apply.submit') }}">
                @csrf

                <div class="row2">
                    <div class="field">
                        <label for="company_name">Nom de l'entreprise</label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                        @error('company_name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="contact_name">Nom du contact</label>
                        <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required>
                        @error('contact_name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="row2">
                    <div class="field">
                        <label for="email">Email professionnel</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="phone">Téléphone (optionnel)</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                        @error('phone') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="field">
                    <label for="website">Site web de l'entreprise</label>
                    <input type="url" id="website" name="website" value="{{ old('website') }}" placeholder="https://" required>
                    @error('website') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="redirect_uri">URL de rappel (webhook)</label>
                    <input type="url" id="redirect_uri" name="redirect_uri" value="{{ old('redirect_uri') }}" placeholder="https://votre-service.com/webhooks/sagaid" required>
                    <p class="hint">L'URL sur laquelle SagaPass enverra les résultats de vérification.</p>
                    @error('redirect_uri') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="description">Décrivez votre besoin</label>
                    <textarea id="description" name="description" required>{{ old('description') }}</textarea>
                    @error('description') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="row2">
                    <div class="field">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                        @error('password') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirmer le mot de passe</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>

                <button type="submit" class="btn">Soumettre la demande</button>
            </form>
        </div>

        <p class="footer-note">Déjà partenaire ? <a href="{{ route('partner.login') }}">Connectez-vous</a></p>
    </div>
</body>
</html>
