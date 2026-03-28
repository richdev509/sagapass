<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('sagapass-logo.png') }}">
    <title>Documentation Développeur — SAGAPASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0052ff;
            --primary-dark: #003bb8;
            --primary-light: #e6f0ff;
            --secondary: #1a202c;
            --text-dark: #1a202c;
            --text-body: #4a5568;
            --text-muted: #718096;
            --bg-light: #f7fafc;
            --bg-white: #ffffff;
            --border-color: #e2e8f0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-body);
            line-height: 1.7;
        }
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-dark);
            font-weight: 700;
        }
        .gradient-text {
            background: linear-gradient(90deg, var(--primary), #3182ce);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            font-weight: 600;
            padding: 12px 32px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 82, 255, 0.15);
        }
        .section {
            padding: 60px 0;
        }
        .section-title {
            font-size: clamp(2rem, 5vw, 2.5rem);
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--text-dark) !important;
        }
        .navbar-brand i {
            color: var(--primary);
        }
        .nav-link {
            color: var(--text-body);
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-link:hover {
            color: var(--text-dark);
        }
        .navbar .btn-primary {
            padding: 8px 24px;
        }

        /* Doc Header */
        .doc-header {
            background: var(--bg-white);
            padding: 80px 0;
            border-bottom: 1px solid var(--border-color);
        }

        /* Doc Layout */
        .doc-sidebar {
            position: sticky;
            top: 100px; /* height of navbar + some padding */
            height: calc(100vh - 120px);
            overflow-y: auto;
            padding-right: 1rem;
        }
        .doc-sidebar .nav-link {
            color: var(--text-muted);
            padding: 0.5rem 1rem;
            border-left: 2px solid transparent;
        }
        .doc-sidebar .nav-link:hover {
            color: var(--text-dark);
            background-color: var(--primary-light);
        }
        .doc-sidebar .nav-link.active {
            color: var(--primary);
            font-weight: 600;
            border-left-color: var(--primary);
        }
        .doc-content section {
            padding-top: 80px;
            margin-top: -80px;
        }
        .doc-content h2 {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        pre {
            background-color: var(--secondary);
            color: #e2e8f0;
            padding: 1.5rem;
            border-radius: 12px;
            font-family: 'SF Mono', 'Menlo', 'Monaco', 'Consolas', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
        }
        code {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.2em 0.4em;
            border-radius: 4px;
            font-size: 0.9em;
        }
        pre code {
            background: none;
            color: inherit;
            padding: 0;
            border-radius: 0;
        }

        /* Footer */
        .footer {
            padding: 80px 0 30px;
            background: var(--secondary);
            color: rgba(255,255,255,0.7);
        }
        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer a:hover {
            color: #fff;
        }
        .footer .footer-title {
            font-weight: 600;
            color: #fff;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .footer .copyright {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            margin-top: 3rem;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
        }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#doc-nav" data-bs-offset="100">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="{{ asset('sagapass-logo.png') }}" alt="SAGAPASS Logo" style="height: 30px; margin-right: 10px;"> SAGAPASS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('welcome') }}#features">Fonctionnalités</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('welcome') }}#how-it-works">Comment ça marche</a></li>
                    <li class="nav-item"><a class="nav-link active" href="{{ route('documentation') }}">Développeurs</a></li>
                </ul>
                <div class="d-flex gap-2">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-outline-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-link text-decoration-none" style="color: var(--text-body);">Connexion</a>
                            <a href="{{ route('register') }}" class="btn btn-primary">S'inscrire</a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <main class="doc-layout">
        <div class="doc-header">
            <div class="container">
                <h1 class="section-title">Documentation Développeur</h1>
                <p class="lead text-muted">Intégrez l'authentification SAGAPASS à votre application avec OAuth2.</p>
            </div>
        </div>

        <div class="container mt-5">
            <div class="row">
                <div class="col-lg-3">
                    <aside class="doc-sidebar">
                        <nav id="doc-nav" class="nav flex-column">
                            <a class="nav-link" href="#quickstart">Démarrage rapide</a>
                            <a class="nav-link" href="#authentication">Flux d'Authentification</a>
                            <a class="nav-link" href="#scopes">Scopes & Permissions</a>
                            <a class="nav-link" href="#endpoints">Endpoints API</a>
                            <a class="nav-link" href="#mobile">Intégration Mobile</a>
                            <a class="nav-link" href="#examples">Exemples de Code</a>
                            <a class="nav-link" href="#errors">Gestion des Erreurs</a>
                            <a class="nav-link" href="#security">Sécurité & Bonnes Pratiques</a>
                        </nav>
                    </aside>
                </div>

                <div class="col-lg-9 doc-content">
                    <section id="quickstart">
                        <h2><i class="fas fa-rocket text-primary me-2"></i>Démarrage rapide</h2>
                        <p>Suivez ces étapes pour intégrer SAGAPASS en moins de 5 minutes.</p>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">Créez une application OAuth depuis votre <a href="{{ route('developers.dashboard') }}">Developer Dashboard</a> pour obtenir un <code>client_id</code> et <code>client_secret</code>. Votre application devra être approuvée par l'équipe SAGAPASS avant utilisation.</li>
                            <li class="list-group-item">Redirigez vos utilisateurs vers <code>{{ url('/oauth/authorize') }}</code> avec les paramètres requis (client_id, redirect_uri, response_type=code, scope, state).</li>
                            <li class="list-group-item">Sur votre URL de callback, échangez le <code>code</code> d'autorisation reçu contre un <code>access_token</code> via <code>POST {{ url('/oauth/token') }}</code>.</li>
                            <li class="list-group-item">Utilisez l'<code>access_token</code> pour récupérer les informations utilisateur via <code>GET {{ url('/api/oauth/userinfo') }}</code>.</li>
                        </ol>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i><strong>Prérequis :</strong> Seuls les utilisateurs avec le statut <code>verified</code> peuvent autoriser les applications tierces.
                        </div>
                    </section>

                    <section id="authentication">
                        <h2><i class="fas fa-key text-primary me-2"></i>Flux d'Authentification</h2>
                        <p>SAGAPASS utilise le flux standard <strong>OAuth2 Authorization Code</strong>, qui est le plus sécurisé pour les applications web serveur.</p>

                        <h5 class="mt-4">Étape 1: Redirection vers l'autorisation</h5>
                        <p>Votre application doit rediriger l'utilisateur vers l'endpoint <code>/oauth/authorize</code>.</p>
                        <pre><code>GET {{ url('/oauth/authorize') }}?
    client_id=YOUR_CLIENT_ID
   &redirect_uri=YOUR_REDIRECT_URI
   &response_type=code
   &scope=profile+email
   &state=UNIQUE_RANDOM_STRING</code></pre>

                        <h5 class="mt-4">Étape 2: Callback avec le code</h5>
                        <p>Si l'utilisateur autorise votre application, il est redirigé vers votre <code>redirect_uri</code> avec un code d'autorisation.</p>
                        <pre><code>https://YOUR_REDIRECT_URI?
    code=AUTHORIZATION_CODE_FROM_SAGA
   &state=THE_SAME_RANDOM_STRING</code></pre>

                        <h5 class="mt-4">Étape 3: Échange du code contre un Access Token</h5>
                        <p>Votre serveur doit ensuite faire un appel <code>POST</code> à l'endpoint <code>/oauth/token</code> pour échanger le code contre un token.</p>
                        <pre><code>POST {{ url('/oauth/token') }}
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
&code=AUTHORIZATION_CODE_FROM_SAGA
&redirect_uri=YOUR_REDIRECT_URI
&code_verifier=OPTIONAL_PKCE_VERIFIER</code></pre>
                        <p class="mt-2">La réponse contiendra l'<code>access_token</code>:</p>
                        <pre><code>{
    "access_token": "1|eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "scope": "profile email"
}</code></pre>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-shield-alt me-2"></i><strong>PKCE (optionnel mais recommandé) :</strong> Pour plus de sécurité, utilisez PKCE en incluant <code>code_challenge</code> et <code>code_challenge_method=S256</code> lors de l'autorisation, puis <code>code_verifier</code> lors de l'échange du token.
                        </div>
                    </section>

                    <section id="scopes">
                        <h2><i class="fas fa-shield-alt text-primary me-2"></i>Scopes & Permissions</h2>
                        <p>Les scopes vous permettent de demander l'accès à des sous-ensembles spécifiques des données d'un utilisateur. Demandez uniquement les scopes dont vous avez besoin.</p>

                        <h5 class="mt-4">Scopes Standard (Tous développeurs)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Scope</th><th>Nom</th><th>Données accessibles</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>profile</code></td><td>Profil de base</td><td>Nom, prénom (first_name, last_name, full_name), photo, statut de vérification</td></tr>
                                    <tr><td><code>email</code></td><td>Adresse email</td><td>Adresse email et statut de vérification (email, email_verified)</td></tr>
                                    <tr><td><code>phone</code></td><td>Numéro de téléphone</td><td>Numéro de téléphone et statut de vérification</td></tr>
                                    <tr><td><code>address</code></td><td>Adresse postale</td><td>Adresse complète du citoyen</td></tr>
                                    <tr><td><code>birthdate</code></td><td>Date de naissance</td><td>Date de naissance (format: YYYY-MM-DD)</td></tr>
                                    <tr><td><code>photo</code></td><td>Photo de profil</td><td>URL de la photo de profil (photo_url)</td></tr>
                                    <tr><td><code>documents</code></td><td>Documents d'identité</td><td>NIU (Numéro d'Identification Unique) - Informations sur les documents vérifiés (sans images)</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4">Scopes Partenaires (Approbation requise)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Scope</th><th>Nom</th><th>Description</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>partner:create-citizen</code></td><td>Création de citoyens</td><td>Créer automatiquement des comptes citoyens SAGAPASS via API</td></tr>
                                    <tr><td><code>partner:verify-citizen</code></td><td>Vérification de citoyens</td><td>Vérifier le statut et les informations d'un citoyen</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i><strong>Note :</strong> Les scopes partenaires nécessitent une approbation spéciale de l'équipe SAGAPASS. Contactez-nous pour en faire la demande.
                        </div>
                    </section>

                    <section id="endpoints">
                        <h2><i class="fas fa-server text-primary me-2"></i>Endpoints API</h2>
                        <p>Une fois que vous avez un <code>access_token</code>, vous pouvez l'utiliser pour appeler nos endpoints API.</p>

                        <h5 class="mt-4">GET /api/oauth/userinfo</h5>
                        <p>Récupère les informations de l'utilisateur authentifié. Les données retournées dépendent des scopes autorisés.</p>
                        <pre><code>GET {{ url('/api/oauth/userinfo') }}
Authorization: Bearer YOUR_ACCESS_TOKEN</code></pre>
                        <p class="mt-2">Exemple de réponse avec scopes <code>profile email phone birthdate documents</code> :</p>
                        <pre><code>{
    "sub": "12345",
    "verified": true,
    "first_name": "Jean",
    "last_name": "Dupont",
    "full_name": "Jean Dupont",
    "email": "jean.dupont@example.com",
    "email_verified": true,
    "phone": "+1234567890",
    "birthdate": "1990-05-15",
    "address": "123 Rue Exemple, Ville, Pays",
    "photo_url": "{{ url('/storage/photos/user123.jpg') }}",
    "niu": "SA-2024-XXXXXXXX"
}</code></pre>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i> Le champ <code>sub</code> contient l'identifiant unique de l'utilisateur. Le champ <code>niu</code> (Numéro d'Identification Unique) est retourné si le scope <code>documents</code> est autorisé.
                        </div>

                        <h5 class="mt-4">POST /oauth/revoke</h5>
                        <p>Révoque un access token actif.</p>
                        <pre><code>POST {{ url('/oauth/revoke') }}
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
    "token": "YOUR_ACCESS_TOKEN"
}</code></pre>

                        <h5 class="mt-4">POST /oauth/introspect</h5>
                        <p>Vérifie la validité d'un token et récupère ses métadonnées (nécessite authentification client).</p>
                        <pre><code>POST {{ url('/oauth/introspect') }}
Content-Type: application/json

{
    "token": "TOKEN_TO_INTROSPECT",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET"
}</code></pre>
                        <p class="mt-2">Réponse si le token est valide :</p>
                        <pre><code>{
    "active": true,
    "scope": "profile email",
    "client_id": "your_client_id",
    "user_id": 12345,
    "exp": 1234567890
}</code></pre>
                    </section>

                    <section id="mobile">
                        <h2><i class="fas fa-puzzle-piece text-primary me-2"></i>Intégration Mobile (App-to-App)</h2>
                        <p>SAGAPASS propose également un flux d'authentification mobile optimisé via deep links pour intégrer l'authentification directement dans votre application mobile.</p>

                        <h5 class="mt-4">Flux Mobile (Deep Link)</h5>
                        <p>Votre application mobile peut initier le flux d'authentification en ouvrant un deep link vers l'application SAGAPASS :</p>
                        <pre><code>sagapass://oauth/authorize?client_id=YOUR_CLIENT_ID&redirect_uri=yourapp://callback&scope=profile,email&state=RANDOM_STATE</code></pre>

                        <h5 class="mt-4">Endpoints API Mobile</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Endpoint</th><th>Méthode</th><th>Description</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>/api/oauth/app-info</code></td><td>GET</td><td>Récupérer les informations de l'application pour afficher l'écran de consentement</td></tr>
                                    <tr><td><code>/api/oauth/mobile-authorize</code></td><td>POST</td><td>Approuver ou refuser l'autorisation (retourne le code d'autorisation)</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4">Exemple d'utilisation (React Native)</h5>
                        <pre><code>import { Linking } from 'react-native';

const loginWithSagapass = async () => {
    const authUrl = 'sagapass://oauth/authorize?' +
        'client_id=YOUR_CLIENT_ID&' +
        'redirect_uri=yourapp://callback&' +
        'scope=profile,email&' +
        'state=' + generateRandomState();

    await Linking.openURL(authUrl);
};

// Gérer le callback
Linking.addEventListener('url', (event) => {
    const { url } = event;
    // Extraire le code d'autorisation de l'URL
    const code = extractCodeFromUrl(url);
    // Échanger contre un access_token
    exchangeCodeForToken(code);
});</code></pre>

                        <div class="alert alert-info mt-3">
                            <i class="fas fa-mobile-alt me-2"></i><strong>Note :</strong> Le flux mobile nécessite que l'utilisateur ait l'application SAGAPASS installée. Pour plus de détails sur l'intégration mobile, consultez notre guide dédié.
                        </div>
                    </section>

                    <section id="examples">
                        <h2><i class="fas fa-code text-primary me-2"></i>Exemples de Code</h2>
                        <p>Voici des exemples complets pour les plateformes populaires.</p>

                        <ul class="nav nav-tabs" id="code-tabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#php-example">PHP (Laravel)</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#js-example">Node.js (Express)</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#python-example">Python (Flask)</button></li>
                        </ul>
                        <div class="tab-content border border-top-0 p-3 rounded-bottom">
                            <div class="tab-pane fade show active" id="php-example">
                                <pre><code>// Étape 1: Rediriger vers l'autorisation
$authUrl = '{{ url('/oauth/authorize') }}?' . http_build_query([
    'client_id' => config('services.sagapass.client_id'),
    'redirect_uri' => route('auth.callback'),
    'response_type' => 'code',
    'scope' => 'profile email phone',
    'state' => session('oauth_state', Str::random(40)),
]);
return redirect($authUrl);

// Étape 2: Gérer le callback et échanger le code
$response = Http::asForm()->post('{{ url('/oauth/token') }}', [
    'grant_type' => 'authorization_code',
    'client_id' => config('services.sagapass.client_id'),
    'client_secret' => config('services.sagapass.client_secret'),
    'redirect_uri' => route('auth.callback'),
    'code' => $request->code,
]);
$accessToken = $response->json('access_token');

// Étape 3: Récupérer les infos utilisateur
$userResponse = Http::withToken($accessToken)
    ->get('{{ url('/api/oauth/userinfo') }}');
$sagaUser = $userResponse->json();

// Exemple de résultat:
// [
//     'sub' => '12345',
//     'verified' => true,
//     'first_name' => 'Jean',
//     'last_name' => 'Dupont',
//     'full_name' => 'Jean Dupont',
//     'email' => 'jean@example.com',
//     'phone' => '+1234567890'
// ]</code></pre>
                            </div>
                            <div class="tab-pane fade" id="js-example">
                                <pre><code>// Étape 1: Rediriger vers l'autorisation
const authUrl = '{{ url('/oauth/authorize') }}?' + new URLSearchParams({
    client_id: process.env.SAGAPASS_CLIENT_ID,
    redirect_uri: 'http://localhost:3000/callback',
    response_type: 'code',
    scope: 'profile email phone',
    state: generateRandomState(),
});
window.location.href = authUrl;

// Étape 2: Callback - Échanger le code (côté serveur)
const tokenResponse = await axios.post('{{ url('/oauth/token') }}',
    new URLSearchParams({
        grant_type: 'authorization_code',
        client_id: process.env.SAGAPASS_CLIENT_ID,
        client_secret: process.env.SAGAPASS_CLIENT_SECRET,
        redirect_uri: 'http://localhost:3000/callback',
        code: req.query.code,
    })
);
const accessToken = tokenResponse.data.access_token;

// Étape 3: Récupérer les infos utilisateur
const userResponse = await axios.get('{{ url('/api/oauth/userinfo') }}', {
    headers: { 'Authorization': `Bearer ${accessToken}` }
});
const sagaUser = userResponse.data;
console.log('Utilisateur connecté:', sagaUser);</code></pre>
                            </div>
                            <div class="tab-pane fade" id="python-example">
                                <pre><code># Étape 1: Rediriger vers l'autorisation
from urllib.parse import urlencode
auth_params = {
    'client_id': os.getenv('SAGAPASS_CLIENT_ID'),
    'redirect_uri': 'http://localhost:5000/callback',
    'response_type': 'code',
    'scope': 'profile email phone',
    'state': generate_random_state(),
}
auth_url = '{{ url('/oauth/authorize') }}?' + urlencode(auth_params)
return redirect(auth_url)

# Étape 2: Callback - Échanger le code
import requests
token_response = requests.post('{{ url('/oauth/token') }}', data={
    'grant_type': 'authorization_code',
    'client_id': os.getenv('SAGAPASS_CLIENT_ID'),
    'client_secret': os.getenv('SAGAPASS_CLIENT_SECRET'),
    'redirect_uri': 'http://localhost:5000/callback',
    'code': request.args.get('code'),
}).json()
access_token = token_response['access_token']

# Étape 3: Récupérer les infos utilisateur
user_response = requests.get('{{ url('/api/oauth/userinfo') }}',
    headers={'Authorization': f'Bearer {access_token}'}
).json()
print(f"Utilisateur connecté: {user_response['full_name']}")</code></pre>
                            </div>
                        </div>
                    </section>

                    <section id="errors">
                        <h2><i class="fas fa-exclamation-triangle text-primary me-2"></i>Gestion des Erreurs</h2>
                        <p>Une bonne gestion des erreurs est cruciale pour une intégration robuste. SAGAPASS retourne des codes d'erreur OAuth2 standard.</p>

                        <h5 class="mt-4">Erreurs lors de l'autorisation</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Code d'erreur</th><th>Description</th><th>Action recommandée</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>invalid_client</code></td><td>Application inconnue ou client_id invalide.</td><td>Vérifiez votre client_id depuis le Developer Dashboard.</td></tr>
                                    <tr><td><code>unauthorized_client</code></td><td>Application non approuvée ou désactivée.</td><td>Attendez l'approbation de votre application par l'équipe SAGAPASS.</td></tr>
                                    <tr><td><code>invalid_redirect_uri</code></td><td>L'URI de redirection ne correspond pas à celles enregistrées.</td><td>Vérifiez que l'URI est exactement la même que celle configurée dans votre application.</td></tr>
                                    <tr><td><code>invalid_scope</code></td><td>Un ou plusieurs scopes demandés sont invalides.</td><td>Vérifiez que tous les scopes demandés sont autorisés pour votre application.</td></tr>
                                    <tr><td><code>access_denied</code></td><td>L'utilisateur a refusé d'autoriser votre application.</td><td>Affichez un message informatif à l'utilisateur.</td></tr>
                                    <tr><td><code>user_not_verified</code></td><td>L'utilisateur n'a pas encore vérifié son identité.</td><td>Informez l'utilisateur qu'il doit compléter la vérification d'identité SAGAPASS.</td></tr>
                                    <tr><td><code>email_mismatch</code></td><td>L'email fourni ne correspond pas au compte connecté (flux mobile).</td><td>Vérifiez que l'email transmis correspond bien à l'utilisateur.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4">Erreurs lors de l'échange du token</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Code d'erreur</th><th>Description</th><th>Action recommandée</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>invalid_client</code></td><td>Identifiants client invalides (client_id/secret).</td><td>Vérifiez vos credentials d'application.</td></tr>
                                    <tr><td><code>invalid_grant</code></td><td>Le code d'autorisation est invalide, expiré ou a déjà été utilisé.</td><td>Redirigez l'utilisateur pour recommencer le flux d'autorisation. Les codes expirent après 60 secondes.</td></tr>
                                    <tr><td><code>invalid_request</code></td><td>La requête est malformée ou il manque des paramètres requis.</td><td>Vérifiez que tous les paramètres requis sont présents (grant_type, client_id, client_secret, code, redirect_uri).</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4">Exemple de gestion d'erreur</h5>
                        <pre><code>// PHP - Gestion des erreurs
try {
    $response = Http::asForm()->post('{{ url('/oauth/token') }}', [...]);

    if ($response->failed()) {
        $error = $response->json('error');
        $errorDescription = $response->json('error_description');

        switch ($error) {
            case 'invalid_grant':
                // Code expiré ou invalide - recommencer le flux
                return redirect()->route('login.sagapass');
            case 'invalid_client':
                // Problème de configuration
                Log::error('SAGAPASS OAuth error: Invalid client credentials');
                abort(500, 'Erreur de configuration OAuth');
            default:
                Log::error("SAGAPASS OAuth error: $error - $errorDescription");
                abort(500, 'Erreur lors de l\'authentification');
        }
    }

    $accessToken = $response->json('access_token');
    // Continuer...
} catch (Exception $e) {
    Log::error('SAGAPASS OAuth exception: ' . $e->getMessage());
    return redirect()->route('login')->with('error', 'Échec de l\'authentification');
}</code></pre>
                    </section>

                    <section id="security">
                        <h2><i class="fas fa-lock text-primary me-2"></i>Sécurité & Bonnes Pratiques</h2>
                        <p>Pour garantir la sécurité de votre intégration OAuth2, suivez ces recommandations importantes.</p>

                        <div class="alert alert-danger mt-4">
                            <h5><i class="fas fa-shield-alt me-2"></i>Règles de sécurité critiques</h5>
                            <ul class="mb-0">
                                <li><strong>Ne jamais exposer le <code>client_secret</code></strong> côté client (navigateur, application mobile). Utilisez-le uniquement côté serveur.</li>
                                <li><strong>Toujours valider le paramètre <code>state</code></strong> pour prévenir les attaques CSRF.</li>
                                <li><strong>Utiliser HTTPS</strong> en production pour toutes les communications OAuth.</li>
                                <li><strong>Ne pas stocker les tokens</strong> dans le localStorage ou sessionStorage - préférez les cookies HttpOnly.</li>
                            </ul>
                        </div>

                        <h5 class="mt-4">Protection CSRF avec le paramètre State</h5>
                        <pre><code>// PHP - Générer et valider le state
// Avant la redirection
$state = Str::random(40);
session(['oauth_state' => $state]);
$authUrl = '{{ url('/oauth/authorize') }}?' . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'profile email',
    'state' => $state,
]);

// Dans le callback
if ($request->state !== session('oauth_state')) {
    abort(403, 'État OAuth invalide - possible attaque CSRF');
}
session()->forget('oauth_state');</code></pre>

                        <h5 class="mt-4">Sécurité renforcée avec PKCE</h5>
                        <p>Pour les applications mobiles ou SPA, utilisez PKCE (Proof Key for Code Exchange) pour une sécurité renforcée :</p>
                        <pre><code>// JavaScript - Générer code_verifier et code_challenge
function generateCodeVerifier() {
    const array = new Uint8Array(32);
    crypto.getRandomValues(array);
    return base64UrlEncode(array);
}

async function generateCodeChallenge(verifier) {
    const encoder = new TextEncoder();
    const data = encoder.encode(verifier);
    const hash = await crypto.subtle.digest('SHA-256', data);
    return base64UrlEncode(new Uint8Array(hash));
}

const codeVerifier = generateCodeVerifier();
const codeChallenge = await generateCodeChallenge(codeVerifier);

// Stocker le code_verifier pour l'échange du token
sessionStorage.setItem('code_verifier', codeVerifier);

// Inclure dans l'URL d'autorisation
const authUrl = '{{ url('/oauth/authorize') }}?' + new URLSearchParams({
    client_id: clientId,
    redirect_uri: redirectUri,
    response_type: 'code',
    scope: 'profile email',
    state: state,
    code_challenge: codeChallenge,
    code_challenge_method: 'S256'
});</code></pre>

                        <h5 class="mt-4">Gestion sécurisée des tokens</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Recommandation</th><th>Raison</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td>Stocker les tokens côté serveur dans des sessions sécurisées</td><td>Évite l'exposition des tokens au JavaScript côté client</td></tr>
                                    <tr><td>Définir une durée de vie limitée pour les tokens</td><td>Limite l'impact d'un token compromis (SAGAPASS: 3600s)</td></tr>
                                    <tr><td>Implémenter une révocation de token</td><td>Permet aux utilisateurs de révoquer l'accès immédiatement</td></tr>
                                    <tr><td>Valider les redirect_uri strictement</td><td>Prévient les attaques par redirection</td></tr>
                                    <tr><td>Logger les échecs d'authentification</td><td>Détecte les tentatives d'intrusion</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-4">Tester votre intégration</h5>
                        <p>Avant de passer en production, testez votre implémentation OAuth :</p>
                        <ol>
                            <li><strong>Créez une application de test</strong> dans votre Developer Dashboard avec des URIs de redirection localhost.</li>
                            <li><strong>Testez le flux complet</strong> : autorisation → callback → échange du code → récupération des données utilisateur.</li>
                            <li><strong>Testez les scénarios d'erreur</strong> : refus de l'utilisateur, code expiré, credentials invalides.</li>
                            <li><strong>Vérifiez la validation du state</strong> pour vous assurer que la protection CSRF fonctionne.</li>
                            <li><strong>Testez la révocation de token</strong> pour confirmer que l'accès est correctement révoqué.</li>
                        </ol>

                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i><strong>Support & Assistance :</strong> Pour toute question sur l'intégration OAuth ou pour signaler un problème de sécurité, contactez notre équipe via le <a href="{{ route('developers.dashboard') }}" class="alert-link">Developer Dashboard</a> ou par email à <strong>developers@sagapass.com</strong>.
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <a class="navbar-brand mb-3 d-inline-block text-white" href="/">
                        <img src="{{ asset('sagapass-logo.png') }}" alt="SAGAPASS Logo" style="height: 30px; margin-right: 10px;"> SAGAPASS
                    </a>
                    <p>La plateforme d'identité numérique unifiée.</p>
                </div>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-4 col-6">
                            <h5 class="footer-title">Produit</h5>
                            <ul class="list-unstyled">
                                <li><a href="{{ route('welcome') }}#features">Fonctionnalités</a></li>
                                <li><a href="{{ route('welcome') }}#how-it-works">Comment ça marche</a></li>
                            </ul>
                        </div>
                        <div class="col-lg-4 col-6">
                            <h5 class="footer-title">Développeurs</h5>
                            <ul class="list-unstyled">
                                <li><a href="{{ route('documentation') }}">Documentation</a></li>
                                <li><a href="#">Statut du service</a></li>
                            </ul>
                        </div>
                        <div class="col-lg-4 col-6">
                            <h5 class="footer-title">Légal</h5>
                            <ul class="list-unstyled">
                                <li><a href="#">Confidentialité</a></li>
                                <li><a href="#">Conditions d'utilisation</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="copyright text-center text-md-start d-md-flex justify-content-between">
                <p>&copy; {{ date('Y') }} SAGAPASS. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>

