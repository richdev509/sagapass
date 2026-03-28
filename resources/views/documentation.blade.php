@extends('layouts.app')

@section('title', 'Documentation Développeur - SAGAPASS')

@push('styles')
<style>
    .doc-sidebar {
        position: sticky;
        top: 100px;
        height: calc(100vh - 120px);
        overflow-y: auto;
        padding-right: 1.5rem;
        border-right: 1px solid var(--border-color);
    }
    .doc-sidebar .nav-link {
        color: var(--text-muted);
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-left: 3px solid transparent;
    }
    .doc-sidebar .nav-link:hover {
        color: var(--text-dark);
        background-color: var(--bg-light);
    }
    .doc-sidebar .nav-link.active {
        color: var(--primary);
        font-weight: 600;
        border-left-color: var(--primary);
    }
    .doc-content h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-top: 3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    .doc-content h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
    }
    .doc-content p, .doc-content li {
        color: var(--text-body);
        font-size: 1rem;
        line-height: 1.8;
    }
    .doc-content code {
        background-color: var(--primary-light);
        color: var(--primary-dark);
        padding: 0.2em 0.4em;
        border-radius: 4px;
        font-size: 0.9em;
    }
    .doc-content pre {
        background: var(--secondary);
        color: #e2e8f0;
        padding: 1.5rem;
        border-radius: 8px;
        font-family: 'SF Mono', 'Menlo', 'Monaco', 'Consolas', monospace;
        font-size: 0.9rem;
        white-space: pre-wrap;
    }
    .doc-content .alert {
        border-radius: 8px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid mt-5">
    <div class="row">
        <!-- Sidebar -->
        <nav id="doc-sidebar" class="col-lg-3 col-xl-2 d-none d-lg-block doc-sidebar">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="#introduction">Introduction</a></li>
                <li class="nav-item"><a class="nav-link" href="#authentication">Flux d'Authentification</a></li>
                <li class="nav-item"><a class="nav-link" href="#endpoints">Points d'Accès (Endpoints)</a></li>
                <li class="nav-item"><a class="nav-link" href="#scopes">Scopes</a></li>
                <li class="nav-item"><a class="nav-link" href="#tokens">Gestion des Tokens</a></li>
                <li class="nav-item"><a class="nav-link" href="#user-info">Récupérer les infos utilisateur</a></li>
                <li class="nav-item"><a class="nav-link" href="#errors">Gestion des Erreurs</a></li>
                <li class="nav-item"><a class="nav-link" href="#security">Sécurité & Bonnes Pratiques</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="col-lg-9 col-xl-10 ms-sm-auto px-md-4 doc-content">
            <div class="pb-5">
                <div class="text-center text-lg-start">
                    <h1 class="display-4 fw-bold">Documentation Développeur</h1>
                    <p class="lead text-muted">Intégrez l'authentification SAGAPASS à votre application en suivant notre guide complet.</p>
                </div>

                <section id="introduction">
                    <h2><i class="fas fa-rocket me-2 text-primary"></i>Introduction</h2>
                    <p>Bienvenue sur la documentation de l'API SAGAPASS. Notre objectif est de vous fournir tous les outils nécessaires pour intégrer une authentification OAuth2 robuste, sécurisée et simple pour vos utilisateurs.</p>
                    <p>SAGAPASS utilise le standard <strong>OAuth 2.0</strong> avec le flux <a href="https://oauth.net/2/grant-types/authorization-code/" target="_blank">Authorization Code Grant</a>, complété par la spécification <strong>PKCE</strong> (Proof Key for Code Exchange) pour une sécurité accrue, notamment pour les applications mobiles et les Single-Page Applications (SPA).</p>
                </section>

                <section id="authentication">
                    <h2><i class="fas fa-project-diagram me-2 text-primary"></i>Flux d'Authentification</h2>
                    <p>Le processus d'authentification se déroule en plusieurs étapes :</p>
                    <ol>
                        <li><strong>Redirection de l'utilisateur :</strong> Votre application redirige l'utilisateur vers le point d'accès <code>/oauth/authorize</code> de SAGAPASS avec les paramètres de votre application (client_id, redirect_uri, etc.).</li>
                        <li><strong>Consentement de l'utilisateur :</strong> L'utilisateur se connecte sur SAGAPASS et autorise votre application à accéder aux informations demandées (définies par les scopes).</li>
                        <li><strong>Réception du code d'autorisation :</strong> SAGAPASS redirige l'utilisateur vers votre <code>redirect_uri</code> avec un code d'autorisation (<code>code</code>) à usage unique.</li>
                        <li><strong>Échange du code contre un Access Token :</strong> Votre serveur, de manière sécurisée, échange ce code d'autorisation contre un <code>access_token</code> en appelant le point d'accès <code>/oauth/token</code>.</li>
                        <li><strong>Appels API :</strong> Avec l'<code>access_token</code>, votre application peut maintenant appeler les points d'accès protégés de l'API SAGAPASS (par exemple, pour récupérer les informations de l'utilisateur).</li>
                    </ol>
                </section>

                <section id="endpoints">
                    <h2><i class="fas fa-network-wired me-2 text-primary"></i>Points d'Accès (Endpoints)</h2>
                    <h3>Point d'accès d'autorisation</h3>
                    <p>C'est le point de départ du flux. Vous devez rediriger vos utilisateurs vers cette URL.</p>
                    <pre><code>GET {{ config('app.url') }}/oauth/authorize</code></pre>

                    <h3>Point d'accès de Token</h3>
                    <p>Utilisé par votre serveur pour échanger un code d'autorisation contre un access token.</p>
                    <pre><code>POST {{ config('app.url') }}/oauth/token</code></pre>

                    <h3>Point d'accès d'informations utilisateur</h3>
                    <p>Une fois que vous avez un access token valide, vous pouvez l'utiliser pour récupérer les informations de l'utilisateur.</p>
                    <pre><code>GET {{ config('app.url') }}/api/oauth/userinfo</code></pre>
                </section>

                <section id="scopes">
                    <h2><i class="fas fa-user-shield me-2 text-primary"></i>Scopes</h2>
                    <p>Les scopes permettent de limiter l'accès d'une application aux données d'un utilisateur. Vous devez spécifier les scopes dont vous avez besoin lors de la demande d'autorisation.</p>
                    <ul>
                        <li><code>profile</code>: Accès aux informations de base du profil (nom, email).</li>
                        <li><code>email</code>: Accès à l'adresse email de l'utilisateur.</li>
                        <li><code>openid</code>: Requis pour la compatibilité OpenID Connect.</li>
                        <li><code>kyc.status</code>: Permet de savoir si l'identité de l'utilisateur a été vérifiée (KYC).</li>
                    </ul>
                </section>

                <section id="tokens">
                    <h2><i class="fas fa-key me-2 text-primary"></i>Gestion des Tokens</h2>
                    <p>Lorsque vous échangez le code d'autorisation, l'API vous retourne un <code>access_token</code> et un <code>refresh_token</code>.</p>
                    <ul>
                        <li><strong>Access Token :</strong> Il a une durée de vie courte (généralement 1 heure). Il doit être inclus dans l'en-tête <code>Authorization</code> de chaque requête API : <code>Authorization: Bearer VOTRE_ACCESS_TOKEN</code>.</li>
                        <li><strong>Refresh Token :</strong> Il a une durée de vie plus longue et est utilisé pour obtenir un nouvel access token lorsque l'actuel a expiré, sans que l'utilisateur ait besoin de se reconnecter.</li>
                    </ul>
                </section>

                <section id="user-info">
                    <h2><i class="fas fa-user-circle me-2 text-primary"></i>Récupérer les infos utilisateur</h2>
                    <p>Pour obtenir les informations de l'utilisateur connecté, effectuez une requête GET vers le point d'accès <code>userinfo</code> avec l'access token.</p>
                    <pre>
fetch('{{ config('app.url') }}/api/oauth/userinfo', {
    headers: {
        'Authorization': 'Bearer ' + accessToken,
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => console.log(data));</pre>
                    <p>La réponse sera un objet JSON contenant les informations de l'utilisateur correspondant aux scopes autorisés.</p>
                </section>

                <section id="errors">
                    <h2><i class="fas fa-exclamation-triangle me-2 text-primary"></i>Gestion des Erreurs</h2>
                    <p>L'API utilise les codes de statut HTTP standards pour indiquer le succès ou l'échec d'une requête. Une réponse d'erreur (4xx ou 5xx) inclura un corps JSON avec des détails sur l'erreur.</p>
                    <pre>
{
  "error": "invalid_request",
  "error_description": "The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed."
}</pre>
                </section>

                <section id="security">
                    <h2><i class="fas fa-shield-alt me-2 text-primary"></i>Sécurité & Bonnes Pratiques</h2>
                    <ul>
                        <li><strong>PKCE est obligatoire :</strong> Toutes les demandes d'autorisation doivent inclure les paramètres <code>code_challenge</code> et <code>code_challenge_method</code>.</li>
                        <li><strong>Stockage sécurisé des tokens :</strong> Ne stockez jamais les tokens côté client dans un stockage persistant (comme le localStorage). Pour les SPAs, utilisez la mémoire de l'application. Pour les applications web traditionnelles, stockez-les dans des sessions côté serveur.</li>
                        <li><strong>Validation du `state` :</strong> Utilisez le paramètre <code>state</code> pour prévenir les attaques CSRF. Générez une chaîne aléatoire avant la redirection et validez-la au retour.</li>
                    </ul>
                </section>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Active link scrolling
    const sidebar = document.getElementById('doc-sidebar');
    const sections = document.querySelectorAll('.doc-content section');

    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 120) {
                current = section.getAttribute('id');
            }
        });

        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(current)) {
                link.classList.add('active');
            }
        });
    });
</script>
@endpush
