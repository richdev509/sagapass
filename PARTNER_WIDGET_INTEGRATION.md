# Guide d'Intégration du Widget SAGAPASS Partner

## 📋 Table des Matières
1. [Introduction](#introduction)
2. [Prérequis](#prérequis)
3. [Installation Rapide](#installation-rapide)
4. [Configuration](#configuration)
5. [Utilisation](#utilisation)
6. [Référence API](#référence-api)
7. [Exemples Complets](#exemples-complets)
8. [Sécurité](#sécurité)
9. [Dépannage](#dépannage)

---

## 🎯 Introduction

Le **Widget SAGAPASS Partner** permet aux partenaires (comme KAYPA) de vérifier l'identité de leurs clients existants et de créer automatiquement des comptes SAGAPASS **sans rediriger l'utilisateur vers un autre site**.

### Fonctionnalités
- ✅ Popup intégrée (le client reste sur votre site)
- ✅ Capture photo + vidéo automatique
- ✅ Création de compte SAGAPASS en temps réel
- ✅ Email automatique avec identifiants
- ✅ Callbacks JavaScript pour intégration transparente
- ✅ Compatible tous navigateurs modernes

---

## 🔧 Prérequis

### 1. Obtenir un Token Partner API

Vous devez avoir une **OAuth Application** enregistrée avec le scope `partner:create-citizen`.

**Étapes :**
1. Connexion à votre compte développeur SAGAPASS
2. Créer une nouvelle OAuth Application
3. Cocher le scope `partner:create-citizen`
4. Récupérer votre `Client ID` et `Client Secret`
5. Générer un token d'accès :

```bash
curl -X POST https://sagapass.com/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET",
    "scope": "partner:create-citizen"
  }'
```

**Réponse :**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJh...",
  "token_type": "Bearer",
  "expires_in": 31536000
}
```

⚠️ **Gardez votre token secret !** Ne le divulguez jamais dans le code frontend.

---

## 🚀 Installation Rapide

### Option 1 : Widget JavaScript Simple (Recommandé)

Ajoutez simplement le script dans votre page HTML :

```html
<!DOCTYPE html>
<html>
<head>
    <title>Vérification Identité</title>
</head>
<body>
    <h1>Vérifier l'identité de votre client</h1>
    <button onclick="startVerification()">Vérifier avec SAGAPASS</button>

    <!-- Charger le Widget SAGAPASS -->
    <script src="https://sagapass.com/js/widget.js"></script>

    <script>
        function startVerification() {
            SagaPass.verify({
                partnerId: 'YOUR_OAUTH_CLIENT_ID', // Votre Client ID
                email: 'client@example.com',
                firstName: 'John',
                lastName: 'Doe',
                callbackUrl: 'https://yoursite.com/success',
                
                onSuccess: function(data) {
                    console.log('✅ Citoyen créé:', data.citizenId);
                    alert('Vérification réussie ! Email: ' + data.email);
                },
                
                onError: function(error) {
                    console.error('❌ Erreur:', error);
                    alert('Erreur lors de la vérification: ' + error);
                },
                
                onCancel: function() {
                    console.log('⚠️ Popup fermée par l\'utilisateur');
                }
            });
        }
    </script>
</body>
</html>
```

### Option 2 : Widget avec URL Pré-Générée (Backend Sécurisé)

Si vous préférez générer l'URL côté serveur (plus sécurisé), utilisez l'API :

**Backend (PHP) :**
```php
<?php
$accessToken = 'YOUR_ACCESS_TOKEN'; // Token stocké côté serveur

$response = file_get_contents('https://sagapass.com/api/partner/v1/widget/generate-token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ],
        'content' => json_encode([
            'partner_id' => 'YOUR_CLIENT_ID',
            'email' => 'client@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'callback_url' => 'https://yoursite.com/success'
        ])
    ]
]));

$data = json_decode($response, true);
$widgetUrl = $data['widget_url']; // URL sécurisée avec token

echo json_encode(['widget_url' => $widgetUrl]);
```

**Frontend :**
```javascript
fetch('/api/get-widget-url', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        email: 'client@example.com',
        first_name: 'John',
        last_name: 'Doe'
    })
})
.then(res => res.json())
.then(data => {
    // Ouvrir le widget avec l'URL sécurisée
    window.open(data.widget_url, 'SagaPassVerification', 'width=650,height=750');
});
```

---

## ⚙️ Configuration

### Paramètres du Widget

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `partnerId` | string | ✅ | Votre OAuth Client ID |
| `email` | string | ✅ | Email du client à vérifier |
| `firstName` | string | ✅ | Prénom du client |
| `lastName` | string | ✅ | Nom du client |
| `callbackUrl` | string | ❌ | URL de redirection après succès (optionnel) |
| `onSuccess` | function | ❌ | Callback appelé en cas de succès |
| `onError` | function | ❌ | Callback appelé en cas d'erreur |
| `onCancel` | function | ❌ | Callback appelé si popup fermée manuellement |

---

## 💡 Utilisation

### Exemple Complet avec Intégration React

```jsx
import React, { useState } from 'react';

function VerificationButton({ customer }) {
    const [status, setStatus] = useState('');

    const handleVerification = () => {
        // Charger le script si nécessaire
        if (!window.SagaPass) {
            const script = document.createElement('script');
            script.src = 'https://sagapass.com/js/widget.js';
            script.onload = () => startVerification();
            document.head.appendChild(script);
        } else {
            startVerification();
        }
    };

    const startVerification = () => {
        window.SagaPass.verify({
            partnerId: process.env.REACT_APP_SAGAPASS_CLIENT_ID,
            email: customer.email,
            firstName: customer.firstName,
            lastName: customer.lastName,
            
            onSuccess: (data) => {
                setStatus('Vérification réussie !');
                // Sauvegarder l'ID du citoyen
                saveToDatabase(customer.id, data.citizenId);
            },
            
            onError: (error) => {
                setStatus('Erreur: ' + error);
            }
        });
    };

    const saveToDatabase = async (customerId, citizenId) => {
        await fetch('/api/customers/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                customer_id: customerId, 
                sagapass_citizen_id: citizenId 
            })
        });
    };

    return (
        <div>
            <button onClick={handleVerification}>
                🔒 Vérifier avec SAGAPASS
            </button>
            {status && <p>{status}</p>}
        </div>
    );
}

export default VerificationButton;
```

### Exemple avec Laravel Blade

**Contrôleur Laravel :**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CustomerVerificationController extends Controller
{
    public function showVerificationPage($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        return view('verify-customer', [
            'customer' => $customer,
            'sagapass_client_id' => config('services.sagapass.client_id')
        ]);
    }

    public function generateWidgetUrl(Request $request)
    {
        $response = Http::withToken(config('services.sagapass.access_token'))
            ->post('https://sagapass.com/api/partner/v1/widget/generate-token', [
                'partner_id' => config('services.sagapass.client_id'),
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'callback_url' => route('verification.success')
            ]);

        return response()->json($response->json());
    }

    public function handleSuccess(Request $request)
    {
        // Sauvegarder l'ID citoyen SAGAPASS
        $customer = Customer::where('email', $request->email)->first();
        $customer->update([
            'sagapass_citizen_id' => $request->citizen_id,
            'identity_verified_at' => now()
        ]);

        return view('verification-success', ['customer' => $customer]);
    }
}
```

**Vue Blade (resources/views/verify-customer.blade.php) :**
```html
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Vérification Identité - {{ $customer->full_name }}</h1>
    
    <div class="card">
        <div class="card-body">
            <p><strong>Email :</strong> {{ $customer->email }}</p>
            <p><strong>Téléphone :</strong> {{ $customer->phone }}</p>
            
            <button id="verifyBtn" class="btn btn-primary">
                🔒 Vérifier avec SAGAPASS
            </button>

            <div id="status" class="alert mt-3" style="display:none;"></div>
        </div>
    </div>
</div>

<script src="https://sagapass.com/js/widget.js"></script>
<script>
    document.getElementById('verifyBtn').addEventListener('click', function() {
        SagaPass.verify({
            partnerId: '{{ $sagapass_client_id }}',
            email: '{{ $customer->email }}',
            firstName: '{{ $customer->first_name }}',
            lastName: '{{ $customer->last_name }}',
            callbackUrl: '{{ route("verification.success") }}',
            
            onSuccess: function(data) {
                const statusDiv = document.getElementById('status');
                statusDiv.className = 'alert alert-success mt-3';
                statusDiv.textContent = '✅ Vérification réussie ! ID Citoyen: ' + data.citizenId;
                statusDiv.style.display = 'block';

                // Envoyer à votre backend
                fetch('/api/customers/save-verification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        customer_id: {{ $customer->id }},
                        citizen_id: data.citizenId
                    })
                });
            },
            
            onError: function(error) {
                const statusDiv = document.getElementById('status');
                statusDiv.className = 'alert alert-danger mt-3';
                statusDiv.textContent = '❌ Erreur: ' + error;
                statusDiv.style.display = 'block';
            }
        });
    });
</script>
@endsection
```

---

## 📚 Référence API

### 1. `SagaPass.verify(options)`

Ouvre le popup de vérification.

**Paramètres :**
```javascript
{
    partnerId: string,        // Votre OAuth Client ID (requis)
    email: string,            // Email du client (requis)
    firstName: string,        // Prénom (requis)
    lastName: string,         // Nom (requis)
    callbackUrl: string,      // URL de redirection après succès (optionnel)
    onSuccess: function,      // Callback succès (optionnel)
    onError: function,        // Callback erreur (optionnel)
    onCancel: function        // Callback annulation (optionnel)
}
```

**Callback `onSuccess` :**
```javascript
function(data) {
    // data.citizenId - ID du citoyen créé
    // data.email - Email du citoyen
}
```

---

### 2. `SagaPass.generateWidgetToken(options)`

Génère une URL de widget sécurisée (à utiliser côté serveur).

**Paramètres :**
```javascript
{
    partnerId: string,
    accessToken: string,      // Token Bearer API
    email: string,
    firstName: string,
    lastName: string,
    callbackUrl: string,
    onSuccess: function,
    onError: function
}
```

**Callback `onSuccess` :**
```javascript
function(data) {
    // data.widget_url - URL complète du widget
    // data.token - Token temporaire
    // data.expires_at - Date d'expiration (15 minutes)
}
```

---

### 3. `SagaPass.checkVerification(options)`

Vérifie le statut d'une vérification.

**Paramètres :**
```javascript
{
    accessToken: string,
    verificationId: string,
    onSuccess: function,
    onError: function
}
```

---

## 🔒 Sécurité

### Bonnes Pratiques

1. **Ne jamais exposer votre Access Token dans le frontend**
   ```javascript
   ❌ MAUVAIS :
   const token = 'eyJ0eXAiOiJKV1QiLCJh...'; // Visible dans le code source
   
   ✅ BON :
   // Token stocké côté serveur, utilisé via API backend
   ```

2. **Valider les données côté serveur**
   ```php
   // Toujours vérifier que l'email existe dans votre base
   if (!Customer::where('email', $request->email)->exists()) {
       return response()->json(['error' => 'Client introuvable'], 404);
   }
   ```

3. **Utiliser HTTPS uniquement**
   ```javascript
   // Le widget force HTTPS automatiquement
   ```

4. **Implémenter un système de référence**
   ```php
   // Associer chaque vérification à votre référence interne
   'partner_reference' => 'KAYPA-CUSTOMER-' . $customer->id
   ```

---

## 🐞 Dépannage

### Problème : Popup Bloquée

**Solution :** Demander à l'utilisateur d'autoriser les popups.

```javascript
const popup = window.open(...);
if (!popup) {
    alert('Veuillez autoriser les popups pour ce site.');
}
```

---

### Problème : Erreur "Partner not found"

**Cause :** `partnerId` incorrect ou application OAuth inexistante.

**Solution :**
1. Vérifier le `Client ID` dans votre dashboard développeur
2. S'assurer que l'application a le scope `partner:create-citizen`

---

### Problème : Erreur "Invalid token"

**Cause :** Token expiré ou invalide.

**Solution :**
```php
// Régénérer un nouveau token
$response = Http::post('https://sagapass.com/oauth/token', [
    'grant_type' => 'client_credentials',
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'scope' => 'partner:create-citizen'
]);

$newToken = $response->json()['access_token'];
```

---

### Problème : Webcam Non Détectée

**Cause :** Permissions navigateur ou HTTPS manquant.

**Solution :**
1. S'assurer que le site utilise HTTPS (requis pour `getUserMedia`)
2. Vérifier que l'utilisateur a autorisé l'accès caméra
3. Tester sur un navigateur moderne (Chrome, Firefox, Safari)

---

## 📞 Support

- **Documentation complète :** https://sagapass.com/docs/partner-api
- **Support technique :** support@sagapass.com
- **Status API :** https://status.sagapass.com

---

## 📝 Changelog

### Version 1.0.0 (Janvier 2025)
- ✅ Lancement initial du Widget Partner
- ✅ Support photo + vidéo
- ✅ Callbacks JavaScript
- ✅ API REST complète
- ✅ Documentation intégration

---

**© 2025 SAGAPASS - Tous droits réservés**
