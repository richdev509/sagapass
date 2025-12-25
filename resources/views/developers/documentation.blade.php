@extends('layouts.app')

@section('title', 'Documentation API')

@section('content')
<div class="container-fluid py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 150px;">
    <div class="container">
        <h1 class="text-white fw-bold mb-2">
            <i class="fas fa-book me-2"></i>
            Documentation SAGAPASS OAuth2
        </h1>
        <p class="text-white-50 mb-0">
            Intégrez "Connect with SAGAPASS" en quelques minutes
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        {{-- Sidebar --}}
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Table des matières</h6>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="#quickstart">
                            <i class="fas fa-rocket me-2"></i>Démarrage rapide
                        </a>
                        <a class="nav-link" href="#authentication">
                            <i class="fas fa-key me-2"></i>Authentification
                        </a>
                        <a class="nav-link" href="#scopes">
                            <i class="fas fa-shield-alt me-2"></i>Scopes
                        </a>
                        <a class="nav-link" href="#endpoints">
                            <i class="fas fa-server me-2"></i>Endpoints API
                        </a>
                        <a class="nav-link" href="#widget">
                            <i class="fas fa-puzzle-piece me-2"></i>Widget d'Intégration
                        </a>
                        <a class="nav-link" href="#examples">
                            <i class="fas fa-code me-2"></i>Exemples de code
                        </a>
                        <a class="nav-link" href="#errors">
                            <i class="fas fa-exclamation-triangle me-2"></i>Gestion des erreurs
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        {{-- Contenu --}}
        <div class="col-lg-9">
            {{-- Démarrage rapide --}}
            <section id="quickstart" class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-4">
                            <i class="fas fa-rocket me-2"></i>
                            Démarrage rapide
                        </h2>

                        <h5 class="fw-bold mt-4 mb-3">1. Créer une application</h5>
                        <p>Créez une application OAuth depuis votre <a href="{{ route('developers.dashboard') }}">Developer Dashboard</a>.</p>

                        <h5 class="fw-bold mt-4 mb-3">2. Obtenir les credentials</h5>
                        <p>Après approbation, récupérez votre <code>client_id</code> et <code>client_secret</code>.</p>

                        <h5 class="fw-bold mt-4 mb-3">3. Ajouter le bouton "Connect with SAGAPASS"</h5>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>&lt;a href="https://sagapass.com/oauth/authorize?client_id=YOUR_CLIENT_ID&redirect_uri=YOUR_REDIRECT_URI&response_type=code&scope=profile email&state=RANDOM_STRING" class="btn btn-primary"&gt;
    &lt;img src="/saga-id-icon.png" width="20" /&gt;
    Se connecter avec SAGAPASS
&lt;/a&gt;</code></pre>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">4. Gérer le callback</h5>
                        <p>L'utilisateur sera redirigé vers votre <code>redirect_uri</code> avec un code d'autorisation.</p>

                        <h5 class="fw-bold mt-4 mb-3">5. Échanger le code contre un token</h5>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>POST https://sagapass.com/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
&code=AUTHORIZATION_CODE
&redirect_uri=YOUR_REDIRECT_URI</code></pre>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">6. Utiliser le token</h5>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>GET https://sagapass.com/api/v1/user
Authorization: Bearer YOUR_ACCESS_TOKEN</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Authentification --}}
            <section id="authentication" class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-4">
                            <i class="fas fa-key me-2"></i>
                            Flux d'authentification OAuth2
                        </h2>

                        <h5 class="fw-bold mb-3">Authorization Code Flow</h5>
                        <p>SAGAPASS utilise le flux OAuth2 Authorization Code, le plus sécurisé pour les applications web.</p>

                        <div class="alert alert-info">
                            <strong><i class="fas fa-shield-check me-2"></i>PKCE recommandé</strong>
                            <p class="mb-0 small">
                                Pour les applications mobiles et SPA, utilisez PKCE (Proof Key for Code Exchange)
                                en ajoutant <code>code_challenge</code> et <code>code_challenge_method=S256</code>.
                            </p>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Étape 1: Redirection vers l'autorisation</h5>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>GET https://sagapass.com/oauth/authorize?
  client_id=YOUR_CLIENT_ID&
  redirect_uri=https://yourapp.com/callback&
  response_type=code&
  scope=profile email&
  state=RANDOM_STRING</code></pre>
                        </div>

                        <h6 class="fw-bold mt-3 mb-2">Paramètres requis:</h6>
                        <ul>
                            <li><code>client_id</code> - Votre Client ID</li>
                            <li><code>redirect_uri</code> - URI de callback (doit être whitelistée)</li>
                            <li><code>response_type</code> - Toujours <code>code</code></li>
                            <li><code>state</code> - Chaîne aléatoire (protection CSRF)</li>
                        </ul>

                        <h5 class="fw-bold mt-4 mb-3">Étape 2: Callback avec le code</h5>
                        <p>L'utilisateur approuve, vous recevez:</p>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>https://yourapp.com/callback?
  code=AUTHORIZATION_CODE&
  state=RANDOM_STRING</code></pre>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Étape 3: Échange du code</h5>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>POST https://sagapass.com/oauth/token

{
  "grant_type": "authorization_code",
  "client_id": "YOUR_CLIENT_ID",
  "client_secret": "YOUR_CLIENT_SECRET",
  "code": "AUTHORIZATION_CODE",
  "redirect_uri": "https://yourapp.com/callback"
}</code></pre>
                        </div>

                        <h6 class="fw-bold mt-3 mb-2">Réponse:</h6>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "profile email"
}</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Scopes --}}
            <section id="scopes" class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-4">
                            <i class="fas fa-shield-alt me-2"></i>
                            Scopes disponibles
                        </h2>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Scope</th>
                                        <th>Données accessibles</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>profile</code></td>
                                        <td>first_name, last_name, verification_status, account_level, video_status</td>
                                        <td>Informations de profil de base</td>
                                    </tr>
                                    <tr>
                                        <td><code>email</code></td>
                                        <td>email, email_verified_at</td>
                                        <td>Adresse email vérifiée</td>
                                    </tr>
                                    <tr>
                                        <td><code>phone</code></td>
                                        <td>phone</td>
                                        <td>Numéro de téléphone</td>
                                    </tr>
                                    <tr>
                                        <td><code>address</code></td>
                                        <td>address</td>
                                        <td>Adresse postale</td>
                                    </tr>
                                    <tr>
                                        <td><code>birthdate</code></td>
                                        <td>date_of_birth</td>
                                        <td>Date de naissance</td>
                                    </tr>
                                    <tr>
                                        <td><code>photo</code></td>
                                        <td>profile_photo_path, profile_photo_url</td>
                                        <td>Photo de profil</td>
                                    </tr>
                                    <tr>
                                        <td><code>documents</code></td>
                                        <td>document_type, card_number, verified_at</td>
                                        <td>Documents d'identité vérifiés (uniquement pour comptes Verified)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Note importante</strong>
                            <p class="mb-0 small">
                                Demandez uniquement les scopes dont vous avez réellement besoin.
                                Les utilisateurs sont plus susceptibles d'approuver des demandes minimales.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Endpoints API --}}
            <section id="endpoints" class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-4">
                            <i class="fas fa-server me-2"></i>
                            Endpoints API
                        </h2>

                        <h5 class="fw-bold mb-3">GET /api/v1/user</h5>
                        <p>Récupère le profil de l'utilisateur authentifié.</p>
                        <div class="bg-light p-3 rounded mb-3">
                            <pre class="mb-0"><code>GET https://sagapass.com/api/v1/user
Authorization: Bearer YOUR_ACCESS_TOKEN</code></pre>
                        </div>

                        <div class="alert alert-info mb-3">
                            <strong><i class="fas fa-info-circle me-2"></i>Niveaux de compte SAGAPASS</strong>
                            <ul class="mb-0 small">
                                <li><strong>pending</strong> - Compte de base (email seulement)</li>
                                <li><strong>basic</strong> - Vidéo de vérification approuvée</li>
                                <li><strong>verified</strong> - Document d'identité approuvé (accès complet)</li>
                            </ul>
                        </div>

                        <h6 class="fw-bold mb-2">Réponse (avec scopes: profile, email, phone, address, birthdate, photo):</h6>
                        <div class="bg-light p-3 rounded mb-4">
                            <pre class="mb-0"><code>{
  "first_name": "Jean",
  "last_name": "Dupont",
  "account_level": "verified",
  "verification_level": "document",
  "verification_status": "verified",
  "video_status": "approved",
  "video_verified_at": "2025-01-12",
  "verified_at": "2025-01-15",
  "is_verified": true,
  "email": "jean@example.com",
  "email_verified_at": "2025-01-10",
  "phone": "221771234567",
  "address": "123 Rue de la Paix, 75001 Paris",
  "date_of_birth": "1990-05-15",
  "profile_photo_path": "profile-photos/abc123.jpg",
  "profile_photo_url": "https://sagapass.com/storage/profile-photos/abc123.jpg"
}</code></pre>
                        </div>

                        <h6 class="fw-bold mb-2">Champs disponibles selon les scopes:</h6>
                        <ul class="small">
                            <li><code>profile</code> - first_name, last_name, account_level, verification_level, verification_status, video_status, is_verified</li>
                            <li><code>email</code> - email, email_verified_at</li>
                            <li><code>phone</code> - phone</li>
                            <li><code>address</code> - address</li>
                            <li><code>birthdate</code> - date_of_birth</li>
                            <li><code>photo</code> - profile_photo_path, profile_photo_url</li>
                        </ul>

                        <h5 class="fw-bold mt-4 mb-3">GET /api/v1/user/documents</h5>
                        <p>Informations de vérification d'identité (nécessite scope <code>documents</code>).</p>
                        <div class="bg-light p-3 rounded mb-3">
                            <pre class="mb-0"><code>GET https://sagapass.com/api/v1/user/documents
Authorization: Bearer YOUR_ACCESS_TOKEN</code></pre>
                        </div>

                        <div class="alert alert-warning mb-3">
                            <strong><i class="fas fa-lock me-2"></i>Accès progressif</strong>
                            <p class="mb-0 small">
                                La réponse varie selon le niveau du compte. Les comptes "pending" et "basic"
                                reçoivent un message <code>upgrade_required</code> avec les étapes à suivre.
                            </p>
                        </div>

                        <h6 class="fw-bold mb-2">Réponse - Compte "pending" (email seulement):</h6>
                        <div class="bg-light p-3 rounded mb-4">
                            <pre class="mb-0"><code>{
  "account": {
    "level": "pending",
    "has_video": false,
    "has_document": false
  },
  "upgrade_required": {
    "message": "Votre compte nécessite une mise à niveau pour accéder à vos documents.",
    "next_step": "video_verification",
    "requirements": [
      "Enregistrez une vidéo selfie pour obtenir un badge de base",
      "Soumettez un document d'identité pour la vérification complète"
    ]
  }
}</code></pre>
                        </div>

                        <h6 class="fw-bold mb-2">Réponse - Compte "basic" (vidéo approuvée):</h6>
                        <div class="bg-light p-3 rounded mb-4">
                            <pre class="mb-0"><code>{
  "account": {
    "level": "basic",
    "has_video": true,
    "has_document": false,
    "video_verified_at": "2025-01-12T10:30:00.000000Z"
  },
  "upgrade_required": {
    "message": "Soumettez un document d'identité pour débloquer toutes les fonctionnalités.",
    "next_step": "document_verification",
    "requirements": [
      "Téléchargez une pièce d'identité officielle (CNI, Passeport, etc.)",
      "Le document sera vérifié par notre équipe sous 24-48h"
    ]
  }
}</code></pre>
                        </div>

                        <h6 class="fw-bold mb-2">Réponse - Compte "verified" (document approuvé):</h6>
                        <div class="bg-light p-3 rounded mb-4">
                            <pre class="mb-0"><code>{
  "account": {
    "level": "verified",
    "has_video": true,
    "has_document": true,
    "video_verified_at": "2025-01-12T10:30:00.000000Z"
  },
  "document": {
    "verified": true,
    "document_type": "passport",
    "card_number": "****5678",
    "issue_date": "2020-01-15",
    "expiry_date": "2030-01-15",
    "verified_at": "2025-01-15 14:30:00"
  }
}</code></pre>
                        </div>

                        <h6 class="fw-bold mb-2">Notes importantes:</h6>
                        <ul class="small">
                            <li><code>card_number</code> - Numéro du document masqué (NUI pour CNI, numéro de passeport)</li>
                            <li><code>level</code> - pending (email), basic (vidéo), verified (document)</li>
                            <li><code>upgrade_required</code> - Présent uniquement si le niveau est insuffisant</li>
                            <li><code>document</code> - Présent uniquement pour les comptes "verified"</li>
                        </ul>

                        <h5 class="fw-bold mt-4 mb-3">POST /oauth/revoke</h5>
                        <p>Révoquer un access token.</p>
                        <div class="bg-light p-3 rounded mb-3">
                            <pre class="mb-0"><code>POST https://sagapass.com/oauth/revoke
Authorization: Bearer YOUR_ACCESS_TOKEN

{
  "token": "YOUR_ACCESS_TOKEN"
}</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Exemples de code --}}
            <section id="examples" class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-4">
                            <i class="fas fa-code me-2"></i>
                            Exemples de code
                        </h2>

                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#php">PHP</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#javascript">JavaScript</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#python">Python</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="php">
                                <h5 class="fw-bold mb-3">PHP (Laravel)</h5>
                                <div class="bg-dark text-light p-3 rounded">
                                    <pre class="mb-0"><code class="language-php">// routes/web.php
Route::get('/auth/saga-id', function () {
    $query = http_build_query([
        'client_id' => config('services.saga_id.client_id'),
        'redirect_uri' => route('auth.saga-id.callback'),
        'response_type' => 'code',
        'scope' => 'profile email',
        'state' => Str::random(40),
    ]);

    return redirect('https://sagapass.com/oauth/authorize?' . $query);
});

Route::get('/auth/saga-id/callback', function (Request $request) {
    $response = Http::asForm()->post('https://sagapass.com/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => config('services.saga_id.client_id'),
        'client_secret' => config('services.saga_id.client_secret'),
        'code' => $request->code,
        'redirect_uri' => route('auth.saga-id.callback'),
    ]);

    $token = $response->json()['access_token'];

    $user = Http::withToken($token)
        ->get('https://sagapass.com/api/v1/user')
        ->json();

    // Créer ou mettre à jour l'utilisateur dans votre base
    $localUser = User::updateOrCreate(
        ['email' => $user['email']],
        [
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'saga_id_verified' => $user['is_verified'],
        ]
    );

    Auth::login($localUser);

    return redirect('/dashboard');
});</code></pre>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="javascript">
                                <h5 class="fw-bold mb-3">JavaScript (Node.js/Express)</h5>
                                <div class="bg-dark text-light p-3 rounded">
                                    <pre class="mb-0"><code class="language-javascript">const axios = require('axios');

app.get('/auth/saga-id', (req, res) => {
  const params = new URLSearchParams({
    client_id: process.env.SAGA_ID_CLIENT_ID,
    redirect_uri: 'http://localhost:3000/auth/callback',
    response_type: 'code',
    scope: 'profile email',
    state: generateRandomString(40)
  });

  res.redirect(`https://sagapass.com/oauth/authorize?${params}`);
});

app.get('/auth/callback', async (req, res) => {
  const { code } = req.query;

  const tokenResponse = await axios.post('https://sagapass.com/oauth/token', {
    grant_type: 'authorization_code',
    client_id: process.env.SAGA_ID_CLIENT_ID,
    client_secret: process.env.SAGA_ID_CLIENT_SECRET,
    code: code,
    redirect_uri: 'http://localhost:3000/auth/callback'
  });

  const { access_token } = tokenResponse.data;

  const userResponse = await axios.get('https://sagapass.com/api/v1/user', {
    headers: { Authorization: `Bearer ${access_token}` }
  });

  req.session.user = userResponse.data;
  res.redirect('/dashboard');
});</code></pre>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="python">
                                <h5 class="fw-bold mb-3">Python (Flask)</h5>
                                <div class="bg-dark text-light p-3 rounded">
                                    <pre class="mb-0"><code class="language-python">import requests
from flask import Flask, redirect, request, session

@app.route('/auth/saga-id')
def saga_id_login():
    params = {
        'client_id': os.getenv('SAGA_ID_CLIENT_ID'),
        'redirect_uri': 'http://localhost:5000/auth/callback',
        'response_type': 'code',
        'scope': 'profile email',
        'state': generate_random_string(40)
    }
    url = f"https://sagapass.com/oauth/authorize?{urlencode(params)}"
    return redirect(url)

@app.route('/auth/callback')
def saga_id_callback():
    code = request.args.get('code')

    token_response = requests.post('https://sagapass.com/oauth/token', data={
        'grant_type': 'authorization_code',
        'client_id': os.getenv('SAGA_ID_CLIENT_ID'),
        'client_secret': os.getenv('SAGA_ID_CLIENT_SECRET'),
        'code': code,
        'redirect_uri': 'http://localhost:5000/auth/callback'
    })

    access_token = token_response.json()['access_token']

    user_response = requests.get(
        'https://sagapass.com/api/v1/user',
        headers={'Authorization': f'Bearer {access_token}'}
    )

    session['user'] = user_response.json()
    return redirect('/dashboard')</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Widget d'Intégration --}}
            <section id="widget" class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-4">
                            <i class="fas fa-puzzle-piece me-2"></i>
                            Widget d'Intégration - Vérification d'Identité
                        </h2>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nouveau !</strong> Intégrez facilement un processus complet de vérification d'identité dans votre application.
                        </div>

                        <div class="alert alert-danger">
                            <h6 class="fw-bold"><i class="fas fa-lock me-2"></i>SÉCURITÉ - PRÉREQUIS OBLIGATOIRE</h6>
                            <p class="mb-2">Avant d'utiliser le widget, vous DEVEZ :</p>
                            <ol class="mb-0">
                                <li><strong>Créer un endpoint backend</strong> pour générer le token OAuth</li>
                                <li><strong>Obtenir un token</strong> via le flux <code>client_credentials</code> avec le scope <code>partner:create-citizen</code></li>
                                <li><strong>JAMAIS exposer</strong> votre <code>client_secret</code> dans le code JavaScript frontend</li>
                                <li>Le token doit être récupéré <strong>à chaque nouvelle vérification</strong> (durée de vie : 1 heure)</li>
                            </ol>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Fonctionnalités</h5>
                        <ul>
                            <li><i class="fas fa-check text-success me-2"></i>Capture de photo de profil</li>
                            <li><i class="fas fa-check text-success me-2"></i>Capture de document d'identité (recto et verso)</li>
                            <li><i class="fas fa-check text-success me-2"></i>Vidéo de vérification faciale (15 secondes)</li>
                            <li><i class="fas fa-check text-success me-2"></i>Switch caméra (avant/arrière) pour mobile</li>
                            <li><i class="fas fa-check text-success me-2"></i>Interface responsive et mobile-friendly</li>
                            <li><i class="fas fa-check text-success me-2"></i>Validation en temps réel des données</li>
                        </ul>

                        <h5 class="fw-bold mt-4 mb-3">🔐 Authentification Backend (Exemples complets)</h5>

                        <ul class="nav nav-tabs mb-3" id="backendTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#nodejs" type="button">Node.js</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#laravel" type="button">Laravel</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#django" type="button">Django</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#flutter" type="button">Flutter</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="backendTabsContent">
                            {{-- Node.js --}}
                            <div class="tab-pane fade show active" id="nodejs">
                                <h6 class="fw-bold mb-2">Backend (Express)</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <pre class="mb-0"><code>const express = require('express');
const axios = require('axios');
const app = express();

app.get('/api/get-sagapass-token', async (req, res) => {
    try {
        const response = await axios.post('{{ url('/oauth/token') }}',
            new URLSearchParams({
                grant_type: 'client_credentials',
                client_id: process.env.SAGAPASS_CLIENT_ID,
                client_secret: process.env.SAGAPASS_CLIENT_SECRET,
                scope: 'partner:create-citizen'
            }),
            { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
        );

        res.json({ success: true, token: response.data.access_token });
    } catch (error) {
        res.status(500).json({ success: false, error: 'Failed to get token' });
    }
});

app.listen(3000);</code></pre>
                                </div>

                                <h6 class="fw-bold mb-2">Frontend (HTML/JavaScript)</h6>
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0"><code>&lt;script src="{{ url('/js/widget.js') }}"&gt;&lt;/script&gt;
&lt;button onclick="startVerification()"&gt;Vérifier mon identité&lt;/button&gt;

&lt;script&gt;
async function startVerification() {
    const response = await fetch('/api/get-sagapass-token');
    const { token } = await response.json();

    SagaPass.verify({
        token: token,
        email: 'user@example.com',
        firstName: 'Jean',
        lastName: 'Dupont',

        onSuccess: function(data) {
            console.log('Succès:', data);
        },

        onError: function(error) {
            console.error('Erreur:', error);
        }
    });
}
&lt;/script&gt;</code></pre>
                                </div>
                            </div>

                            {{-- Laravel --}}
                            <div class="tab-pane fade" id="laravel">
                                <h6 class="fw-bold mb-2">Controller</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <pre class="mb-0"><code>namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class SagaPassController extends Controller
{
    public function getToken()
    {
        $response = Http::asForm()->post(config('sagapass.url') . '/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('sagapass.client_id'),
            'client_secret' => config('sagapass.client_secret'),
            'scope' => 'partner:create-citizen'
        ]);

        return response()->json([
            'success' => true,
            'token' => $response->json('access_token')
        ]);
    }
}</code></pre>
                                </div>

                                <h6 class="fw-bold mb-2">Routes (web.php)</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <pre class="mb-0"><code>Route::get('/api/get-sagapass-token', [SagaPassController::class, 'getToken']);</code></pre>
                                </div>

                                <h6 class="fw-bold mb-2">Blade View</h6>
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0"><code>&lt;script src="{{ url('/js/widget.js') }}"&gt;&lt;/script&gt;
&lt;button onclick="startVerification()"&gt;Vérifier mon identité&lt;/button&gt;

&lt;script&gt;
async function startVerification() {
    const response = await fetch('/api/get-sagapass-token');
    const { token } = await response.json();

    SagaPass.verify({
        token: token,
        email: '{{ $user->email }}',
        firstName: '{{ $user->first_name }}',
        lastName: '{{ $user->last_name }}'
    });
}
&lt;/script&gt;</code></pre>
                                </div>
                            </div>

                            {{-- Django --}}
                            <div class="tab-pane fade" id="django">
                                <h6 class="fw-bold mb-2">views.py</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <pre class="mb-0"><code>import requests
from django.http import JsonResponse
from django.conf import settings

def get_sagapass_token(request):
    response = requests.post(
        f"{settings.SAGAPASS_URL}/oauth/token",
        data={
            'grant_type': 'client_credentials',
            'client_id': settings.SAGAPASS_CLIENT_ID,
            'client_secret': settings.SAGAPASS_CLIENT_SECRET,
            'scope': 'partner:create-citizen'
        }
    )

    return JsonResponse({
        'success': True,
        'token': response.json()['access_token']
    })</code></pre>
                                </div>

                                <h6 class="fw-bold mb-2">Template</h6>
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0"><code>&lt;script src="{{ url('/js/widget.js') }}"&gt;&lt;/script&gt;
&lt;button onclick="startVerification()"&gt;Vérifier mon identité&lt;/button&gt;

&lt;script&gt;
async function startVerification() {
    const response = await fetch('/api/get-sagapass-token');
    const data = await response.json();

    SagaPass.verify({
        token: data.token,
        email: '{{ user.email }}',
        firstName: '{{ user.first_name }}',
        lastName: '{{ user.last_name }}'
    });
}
&lt;/script&gt;</code></pre>
                                </div>
                            </div>

                            {{-- Flutter --}}
                            <div class="tab-pane fade" id="flutter">
                                <h6 class="fw-bold mb-2">Service Backend</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <pre class="mb-0"><code>import 'package:http/http.dart' as http;

class SagaPassService {
  Future&lt;String&gt; getToken() async {
    final response = await http.post(
      Uri.parse('{{ url('/oauth/token') }}'),
      body: {
        'grant_type': 'client_credentials',
        'client_id': clientId,
        'client_secret': clientSecret,
        'scope': 'partner:create-citizen',
      },
    );

    return jsonDecode(response.body)['access_token'];
  }
}</code></pre>
                                </div>

                                <h6 class="fw-bold mb-2">WebView Widget</h6>
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0"><code>import 'package:webview_flutter/webview_flutter.dart';

class VerificationPage extends StatefulWidget {
  @override
  State&lt;VerificationPage&gt; createState() => _VerificationPageState();
}

class _VerificationPageState extends State&lt;VerificationPage&gt; {
  late WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _initializeWebView();
  }

  Future&lt;void&gt; _initializeWebView() async {
    final token = await SagaPassService().getToken();

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..runJavaScript('''
        SagaPass.verify({
          token: '$token',
          email: 'user@example.com',
          firstName: 'Jean',
          lastName: 'Dupont'
        });
      ''');
  }
}</code></pre>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Paramètres du Widget</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Paramètre</th>
                                        <th>Type</th>
                                        <th>Requis</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>token</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger">Oui</span></td>
                                        <td>Access token OAuth (client_credentials)</td>
                                    </tr>
                                    <tr>
                                        <td><code>email</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger">Oui</span></td>
                                        <td>Email de l'utilisateur</td>
                                    </tr>
                                    <tr>
                                        <td><code>firstName</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger">Oui</span></td>
                                        <td>Prénom de l'utilisateur</td>
                                    </tr>
                                    <tr>
                                        <td><code>lastName</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger">Oui</span></td>
                                        <td>Nom de famille</td>
                                    </tr>
                                    <tr>
                                        <td><code>callbackUrl</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-secondary">Non</span></td>
                                        <td>URL de redirection après succès</td>
                                    </tr>
                                    <tr>
                                        <td><code>onSuccess</code></td>
                                        <td>function</td>
                                        <td><span class="badge bg-secondary">Non</span></td>
                                        <td>Callback en cas de succès</td>
                                    </tr>
                                    <tr>
                                        <td><code>onError</code></td>
                                        <td>function</td>
                                        <td><span class="badge bg-secondary">Non</span></td>
                                        <td>Callback en cas d'erreur</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Flux de vérification (4 étapes)</h5>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">
                                <strong>Informations personnelles</strong> - Date de naissance (18+ requis), téléphone, adresse
                            </li>
                            <li class="list-group-item">
                                <strong>Photo de profil</strong> - Capture via webcam/caméra mobile
                            </li>
                            <li class="list-group-item">
                                <strong>Document d'identité (CNI)</strong>
                                <ul class="mt-2">
                                    <li>NINU (10 chiffres)</li>
                                    <li>Numéro de carte (9 caractères)</li>
                                    <li>Photo RECTO (caméra arrière par défaut)</li>
                                    <li>Photo VERSO (caméra arrière par défaut)</li>
                                    <li>Bouton Switch Caméra disponible</li>
                                </ul>
                            </li>
                            <li class="list-group-item">
                                <strong>Vidéo de vérification</strong> - 15 secondes d'enregistrement
                            </li>
                        </ol>

                        <h5 class="fw-bold mt-4 mb-3">Support Mobile (WebView)</h5>
                        <div class="alert alert-warning">
                            <i class="fas fa-mobile-alt me-2"></i>
                            <strong>Important pour les applications mobiles :</strong> Si vous intégrez le widget dans un WebView, vous devez configurer les permissions caméra/micro.
                        </div>

                        <h6 class="fw-bold mb-2">Android (WebView)</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            <pre class="mb-0"><code>// Dans votre Activity
WebView webView = findViewById(R.id.webview);
webSettings.setJavaScriptEnabled(true);
webSettings.setMediaPlaybackRequiresUserGesture(false);

webView.setWebChromeClient(new WebChromeClient() {
    @Override
    public void onPermissionRequest(PermissionRequest request) {
        request.grant(request.getResources());
    }
});

// AndroidManifest.xml
&lt;uses-permission android:name="android.permission.CAMERA" /&gt;
&lt;uses-permission android:name="android.permission.RECORD_AUDIO" /&gt;</code></pre>
                        </div>

                        <h6 class="fw-bold mb-2">iOS (WKWebView)</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            <pre class="mb-0"><code>// Configuration WKWebView
let configuration = WKWebViewConfiguration()
configuration.allowsInlineMediaPlayback = true

// Info.plist
&lt;key&gt;NSCameraUsageDescription&lt;/key&gt;
&lt;string&gt;Accès caméra pour vérification d'identité&lt;/string&gt;
&lt;key&gt;NSMicrophoneUsageDescription&lt;/key&gt;
&lt;string&gt;Accès microphone pour vidéo&lt;/string&gt;</code></pre>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Vérifier le statut</h5>
                        <p>Après la vérification, interrogez l'API pour obtenir le statut :</p>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0"><code>GET {{ url('/api/partner/v1/check-verification') }}?email=user@example.com
Authorization: Bearer YOUR_ACCESS_TOKEN

// Réponse
{
    "success": true,
    "status": "pending",  // pending, approved, rejected
    "citizen": {
        "id": 123,
        "email": "user@example.com",
        "account_level": "pending"
    }
}</code></pre>
                        </div>

                        <div class="alert alert-success mt-4">
                            <i class="fas fa-book me-2"></i>
                            <strong>Documentation complète :</strong> Consultez le fichier <code>API_DOCUMENTATION.md</code> pour plus de détails et exemples avancés.
                        </div>
                    </div>
                </div>
            </section>

            {{-- Gestion des erreurs --}}
            <section id="errors" class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Gestion des erreurs
                        </h2>

                        <h5 class="fw-bold mb-3">Codes d'erreur OAuth</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Description</th>
                                        <th>Solution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>invalid_request</code></td>
                                        <td>Paramètres manquants ou invalides</td>
                                        <td>Vérifiez tous les paramètres requis</td>
                                    </tr>
                                    <tr>
                                        <td><code>unauthorized_client</code></td>
                                        <td>Application non approuvée</td>
                                        <td>Attendez l'approbation admin</td>
                                    </tr>
                                    <tr>
                                        <td><code>access_denied</code></td>
                                        <td>Utilisateur a refusé</td>
                                        <td>Réessayez plus tard</td>
                                    </tr>
                                    <tr>
                                        <td><code>invalid_scope</code></td>
                                        <td>Scope non autorisé</td>
                                        <td>Demandez uniquement les scopes approuvés</td>
                                    </tr>
                                    <tr>
                                        <td><code>invalid_grant</code></td>
                                        <td>Code expiré ou invalide</td>
                                        <td>Le code n'est valable que 10 minutes</td>
                                    </tr>
                                    <tr>
                                        <td><code>invalid_client</code></td>
                                        <td>Client secret incorrect</td>
                                        <td>Vérifiez votre client secret</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Codes HTTP API</h5>
                        <ul>
                            <li><code>200 OK</code> - Succès</li>
                            <li><code>400 Bad Request</code> - Paramètres invalides</li>
                            <li><code>401 Unauthorized</code> - Token invalide ou expiré</li>
                            <li><code>403 Forbidden</code> - Scope insuffisant</li>
                            <li><code>429 Too Many Requests</code> - Rate limit dépassé</li>
                        </ul>
                    </div>
                </div>
            </section>

            {{-- Support --}}
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-4 text-center">
                    <h4 class="fw-bold mb-3">
                        <i class="fas fa-life-ring me-2"></i>
                        Besoin d'aide ?
                    </h4>
                    <p class="mb-3">
                        Contactez notre équipe de support pour toute question technique
                    </p>
                    <a href="mailto:developers@sagapass.com" class="btn btn-primary">
                        <i class="fas fa-envelope me-2"></i>
                        developers@sagapass.com
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .nav-link {
        color: #6c757d;
        padding: 0.5rem 0;
        font-size: 0.9rem;
    }
    .nav-link:hover {
        color: #667eea;
    }
    pre code {
        font-size: 0.85rem;
        line-height: 1.5;
    }
</style>
@endsection

