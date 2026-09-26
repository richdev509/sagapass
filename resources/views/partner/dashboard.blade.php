<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tableau de bord partenaire - SAGAPASS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0D6EFD; --primary-dark: #0a58ca; --secondary: #1A202C;
            --text-body: #4A5568; --text-muted: #718096; --border-color: #E2E8F0;
            --success: #16A34A; --danger: #DC2626; --warning: #D97706;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: #F7FAFC; color: var(--secondary); }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; background: #fff; border-bottom: 1px solid var(--border-color); }
        .brand { display: flex; align-items: center; gap: 0.5rem; font-weight: 800; font-size: 1.05rem; }
        .brand span { color: var(--primary); }
        .topbar a { color: var(--text-muted); text-decoration: none; font-size: 0.88rem; font-weight: 600; margin-left: 1.25rem; }
        .topbar a:hover { color: var(--primary); }
        .wrap { max-width: 44rem; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        .flash { background: #EAF6EC; border: 1px solid var(--success); color: #14532D; padding: 0.85rem 1rem; border-radius: 0.6rem; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .reveal { background: #FFFBEB; border: 1px solid var(--warning); color: #78350F; padding: 1rem; border-radius: 0.6rem; margin-bottom: 1.25rem; }
        .reveal code { display: block; background: #1A202C; color: #FBBF24; padding: 0.7rem 0.9rem; border-radius: 0.5rem; margin-top: 0.5rem; word-break: break-all; font-size: 0.88rem; }
        .card { background: #fff; border: 1px solid var(--border-color); border-radius: 0.9rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.05rem; margin: 0 0 1rem; }
        .badge { display: inline-block; padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; }
        .badge.pending { background: #FEF3C7; color: #92400E; }
        .badge.approved { background: #DCFCE7; color: #166534; }
        .badge.rejected, .badge.suspended { background: #FEE2E2; color: #991B1B; }
        .kv { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        .kv:last-child { border-bottom: none; }
        .kv .label { color: var(--text-muted); }
        .kv code { background: #EDF2F7; padding: 0.15rem 0.5rem; border-radius: 0.4rem; font-size: 0.85rem; }
        .field { margin-bottom: 1rem; }
        label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; }
        input, textarea {
            width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color);
            border-radius: 0.5rem; font-family: inherit; font-size: 0.9rem; color: var(--secondary);
        }
        textarea { resize: vertical; min-height: 4.5rem; }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 40rem) { .row2 { grid-template-columns: 1fr; } }
        .btn { border: none; background: var(--primary); color: #fff; padding: 0.6rem 1.1rem; border-radius: 0.55rem; font-weight: 700; font-size: 0.88rem; cursor: pointer; }
        .btn:hover { background: var(--primary-dark); }
        .btn-outline { background: none; border: 1px solid var(--border-color); color: var(--secondary); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .actions { display: flex; gap: 0.6rem; flex-wrap: wrap; margin-top: 0.75rem; }
        .doc-link { display: block; margin-top: 0.25rem; color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .muted { color: var(--text-muted); font-size: 0.88rem; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand"><span>SAGA</span>PASS - Partenaires</div>
        <div>
            <a href="{{ route('partner.docs.index') }}">Documentation</a>
            <form method="POST" action="{{ route('partner.logout') }}" style="display:inline">
                @csrf
                <a href="#" onclick="event.preventDefault(); this.closest('form').submit();">Déconnexion</a>
            </form>
        </div>
    </div>

    <div class="wrap">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if (session('revealed_value'))
            <div class="reveal">
                <strong>Nouveau {{ session('revealed_label') }} généré</strong> - copiez-le maintenant, il ne sera plus jamais affiché en clair.
                <code>{{ session('revealed_value') }}</code>
            </div>
        @endif

        <div class="card">
            <h2>Statut de votre demande</h2>
            @if ($application)
                <span class="badge {{ $application->status }}">{{ $application->status }}</span>
                @if ($application->status === 'pending')
                    <p class="muted" style="margin-top:0.75rem;">Un administrateur doit approuver votre demande avant que les identifiants API ne soient actifs.</p>
                @elseif ($application->status === 'rejected')
                    <p class="muted" style="margin-top:0.75rem;">Votre demande a été refusée. Contactez-nous si vous pensez qu'il s'agit d'une erreur.</p>
                @elseif ($application->status === 'suspended')
                    <p class="muted" style="margin-top:0.75rem;">Votre accès est actuellement suspendu.</p>
                @endif
            @else
                <p class="muted">Aucune demande associée à ce compte.</p>
            @endif
        </div>

        @if ($application && $application->isApproved())
            <div class="card">
                <h2>Identifiants API</h2>
                <div class="kv">
                    <span class="label">client_id</span>
                    <code>{{ $application->client_id }}</code>
                </div>
                <div class="kv">
                    <span class="label">client_secret</span>
                    <code>••••••••••••••••</code>
                </div>
                <div class="kv">
                    <span class="label">webhook_secret</span>
                    <code>••••••••••••••••</code>
                </div>
                <p class="muted" style="margin-top:0.75rem;">Les secrets ne sont jamais réaffichés - régénérez-les si vous les avez perdus (l'ancien cesse immédiatement de fonctionner).</p>
                <div class="actions">
                    <form method="POST" action="{{ route('partner.dashboard.regenerate-client-secret') }}" onsubmit="return confirm('Régénérer le client_secret ? L\'ancien cessera immédiatement de fonctionner.');">
                        @csrf
                        <button type="submit" class="btn btn-outline">Régénérer client_secret</button>
                    </form>
                    <form method="POST" action="{{ route('partner.dashboard.regenerate-webhook-secret') }}" onsubmit="return confirm('Régénérer le webhook_secret ?');">
                        @csrf
                        <button type="submit" class="btn btn-outline">Régénérer webhook_secret</button>
                    </form>
                </div>
                <a class="doc-link" href="{{ route('partner.docs.show', 'VERIFICATION_SESSIONS_GUIDE') }}">Voir le guide d'intégration &rarr;</a>
            </div>
        @endif

        <div class="card">
            <h2>Profil de l'entreprise</h2>
            <form method="POST" action="{{ route('partner.dashboard.update') }}">
                @csrf
                @method('PATCH')

                <div class="row2">
                    <div class="field">
                        <label for="company_name">Nom de l'entreprise</label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $account->company_name) }}" required>
                    </div>
                    <div class="field">
                        <label for="contact_name">Nom du contact</label>
                        <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $account->contact_name) }}" required>
                    </div>
                </div>

                <div class="row2">
                    <div class="field">
                        <label>Email</label>
                        <input type="email" value="{{ $account->email }}" disabled>
                    </div>
                    <div class="field">
                        <label for="phone">Téléphone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $account->phone) }}">
                    </div>
                </div>

                <div class="field">
                    <label for="website">Site web</label>
                    <input type="url" id="website" name="website" value="{{ old('website', $application?->website) }}" required>
                </div>

                <div class="field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required>{{ old('description', $application?->description) }}</textarea>
                </div>

                <button type="submit" class="btn">Enregistrer</button>
            </form>
        </div>
    </div>
</body>
</html>
