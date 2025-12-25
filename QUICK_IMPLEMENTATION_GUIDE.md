# 🚀 Guide d'Implémentation Rapide - Widget SAGAPASS

## Pour les Développeurs Partenaires (ex: KAYPA)

---

## 📋 Scénarios d'Implémentation

### Scénario 1 : Site Web Simple (HTML/PHP)
### Scénario 2 : Application Laravel
### Scénario 3 : Application React/Vue
### Scénario 4 : API Backend avec Frontend Séparé

---

## 🎯 Scénario 1 : Site Web Simple (HTML/PHP)

### Étape 1 : Obtenir vos Identifiants SAGAPASS

1. Connectez-vous sur https://sagapass.com/developer
2. Créez une nouvelle OAuth Application
3. Nom : "KAYPA Identity Verification"
4. Cochez le scope : **`partner:create-citizen`**
5. Notez vos identifiants :
   - **Client ID** : `9d7f8a2b-4c1e-...`
   - **Client Secret** : `secret_xyz123...`

### Étape 2 : Générer un Token d'Accès

Créez un fichier `get-token.php` :

```php
<?php
// get-token.php - À EXÉCUTER UNE SEULE FOIS

$clientId = '9d7f8a2b-4c1e-...';  // Votre Client ID
$clientSecret = 'secret_xyz123...'; // Votre Client Secret

$ch = curl_init('https://sagapass.com/oauth/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'scope' => 'partner:create-citizen'
    ])
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "Votre Token d'Accès :\n";
echo $data['access_token'] . "\n\n";
echo "⚠️ SAUVEGARDEZ CE TOKEN DANS UN FICHIER SÉCURISÉ !\n";
echo "Valide pendant 1 an\n";
?>
```

Exécutez : `php get-token.php`

**Sauvegardez le token dans un fichier sécurisé (hors du dossier public) :**

```php
// config.php
<?php
define('SAGAPASS_CLIENT_ID', '9d7f8a2b-4c1e-...');
define('SAGAPASS_TOKEN', 'eyJ0eXAiOiJKV1QiLCJh...');
?>
```

### Étape 3 : Créer la Page de Vérification

**fichier : `verify-client.php`**

```php
<?php
require_once 'config.php';

// Simuler un client KAYPA (normalement récupéré de votre base de données)
$client = [
    'id' => 123,
    'email' => 'john.doe@example.com',
    'prenom' => 'John',
    'nom' => 'Doe'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Identité - KAYPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3>🔒 Vérification d'Identité KAYPA</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Client :</strong> <?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?><br>
                    <strong>Email :</strong> <?= htmlspecialchars($client['email']) ?>
                </div>

                <p>Pour finaliser votre dossier, nous devons vérifier votre identité via notre partenaire sécurisé SAGAPASS.</p>

                <div class="alert alert-warning">
                    <strong>⏱️ Durée :</strong> 2-3 minutes<br>
                    <strong>📸 Requis :</strong> Webcam pour photo + vidéo
                </div>

                <button id="verifyBtn" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-shield-check"></i> Démarrer la Vérification
                </button>

                <!-- Zone de résultat -->
                <div id="result" class="mt-4" style="display:none;"></div>
            </div>
        </div>
    </div>

    <!-- ÉTAPE IMPORTANTE : Charger le Widget SAGAPASS -->
    <script src="https://sagapass.com/js/widget.js"></script>

    <script>
        document.getElementById('verifyBtn').addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Ouverture du widget...';

            // Lancer la vérification SAGAPASS
            SagaPass.verify({
                // Votre Client ID OAuth
                partnerId: '<?= SAGAPASS_CLIENT_ID ?>',
                
                // Données du client
                email: '<?= $client['email'] ?>',
                firstName: '<?= $client['prenom'] ?>',
                lastName: '<?= $client['nom'] ?>',
                
                // URL de retour (optionnel)
                callbackUrl: 'https://kaypa.com/verification-success',

                // Callback en cas de succès
                onSuccess: function(data) {
                    console.log('✅ Vérification réussie !', data);
                    
                    // Afficher le résultat
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-success">
                            <h4>✅ Vérification Réussie !</h4>
                            <p><strong>ID Citoyen SAGAPASS :</strong> ${data.citizenId}</p>
                            <p><strong>Email :</strong> ${data.email}</p>
                            <p class="mb-0">Un email avec les identifiants a été envoyé au client.</p>
                        </div>
                    `;
                    document.getElementById('result').style.display = 'block';

                    // Sauvegarder dans votre base de données
                    saveCitizenId(<?= $client['id'] ?>, data.citizenId);
                },

                // Callback en cas d'erreur
                onError: function(error) {
                    console.error('❌ Erreur:', error);
                    
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-danger">
                            <h4>❌ Erreur</h4>
                            <p>${error}</p>
                        </div>
                    `;
                    document.getElementById('result').style.display = 'block';
                    
                    document.getElementById('verifyBtn').disabled = false;
                    document.getElementById('verifyBtn').innerHTML = 'Réessayer la Vérification';
                },

                // Callback si l'utilisateur ferme la popup
                onCancel: function() {
                    console.log('⚠️ Vérification annulée');
                    document.getElementById('verifyBtn').disabled = false;
                    document.getElementById('verifyBtn').innerHTML = 'Démarrer la Vérification';
                }
            });
        });

        // Fonction pour sauvegarder l'ID citoyen dans votre base
        function saveCitizenId(clientId, citizenId) {
            fetch('save-citizen-id.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    client_id: clientId,
                    sagapass_citizen_id: citizenId
                })
            })
            .then(response => response.json())
            .then(data => console.log('💾 Sauvegarde réussie:', data))
            .catch(error => console.error('❌ Erreur sauvegarde:', error));
        }
    </script>
</body>
</html>
```

### Étape 4 : Sauvegarder l'ID Citoyen

**fichier : `save-citizen-id.php`**

```php
<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$clientId = $data['client_id'];
$citizenId = $data['sagapass_citizen_id'];

// Connexion à votre base de données
$db = new PDO('mysql:host=localhost;dbname=kaypa', 'username', 'password');

// Sauvegarder l'ID citoyen
$stmt = $db->prepare("
    UPDATE clients 
    SET sagapass_citizen_id = :citizen_id,
        identity_verified_at = NOW()
    WHERE id = :client_id
");

$stmt->execute([
    ':citizen_id' => $citizenId,
    ':client_id' => $clientId
]);

echo json_encode([
    'success' => true,
    'message' => 'ID citoyen sauvegardé avec succès'
]);
?>
```

---

## 🎯 Scénario 2 : Application Laravel

### Étape 1 : Configuration

**fichier : `config/services.php`**

```php
return [
    // ... autres services

    'sagapass' => [
        'client_id' => env('SAGAPASS_CLIENT_ID'),
        'client_secret' => env('SAGAPASS_CLIENT_SECRET'),
        'access_token' => env('SAGAPASS_ACCESS_TOKEN'),
        'base_url' => env('SAGAPASS_BASE_URL', 'https://sagapass.com'),
    ],
];
```

**fichier : `.env`**

```env
SAGAPASS_CLIENT_ID=9d7f8a2b-4c1e-...
SAGAPASS_CLIENT_SECRET=secret_xyz123...
SAGAPASS_ACCESS_TOKEN=eyJ0eXAiOiJKV1QiLCJh...
```

### Étape 2 : Migration Base de Données

```bash
php artisan make:migration add_sagapass_fields_to_customers
```

```php
<?php
// database/migrations/2025_01_19_create_sagapass_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('sagapass_citizen_id')->nullable()->after('email');
            $table->timestamp('identity_verified_at')->nullable()->after('sagapass_citizen_id');
            
            $table->index('sagapass_citizen_id');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['sagapass_citizen_id', 'identity_verified_at']);
        });
    }
};
```

Exécuter : `php artisan migrate`

### Étape 3 : Service SAGAPASS

```bash
php artisan make:service SagaPassService
```

```php
<?php
// app/Services/SagaPassService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SagaPassService
{
    protected $clientId;
    protected $accessToken;
    protected $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.sagapass.client_id');
        $this->accessToken = config('services.sagapass.access_token');
        $this->baseUrl = config('services.sagapass.base_url');
    }

    /**
     * Vérifier le statut d'une vérification
     */
    public function checkVerification($email)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get("{$this->baseUrl}/api/partner/v1/check-verification", [
                    'email' => $email
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SAGAPASS Check Verification Error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtenir les détails d'un citoyen par référence
     */
    public function getCitizenDetails($reference)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get("{$this->baseUrl}/api/partner/v1/citizen/{$reference}");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SAGAPASS Get Citizen Error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

### Étape 4 : Contrôleur

```php
<?php
// app/Http/Controllers/CustomerVerificationController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\SagaPassService;
use Illuminate\Http\Request;

class CustomerVerificationController extends Controller
{
    protected $sagapass;

    public function __construct(SagaPassService $sagapass)
    {
        $this->sagapass = $sagapass;
    }

    /**
     * Afficher la page de vérification
     */
    public function show($customerId)
    {
        $customer = Customer::findOrFail($customerId);

        // Vérifier si déjà vérifié
        if ($customer->sagapass_citizen_id) {
            return redirect()->route('customers.show', $customer)
                ->with('info', 'Ce client est déjà vérifié sur SAGAPASS');
        }

        return view('customers.verify', [
            'customer' => $customer,
            'sagapass_client_id' => config('services.sagapass.client_id')
        ]);
    }

    /**
     * Sauvegarder l'ID citoyen après vérification réussie
     */
    public function saveCitizenId(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'citizen_id' => 'required|string'
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        
        $customer->update([
            'sagapass_citizen_id' => $request->citizen_id,
            'identity_verified_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Identité vérifiée avec succès'
        ]);
    }

    /**
     * Vérifier le statut d'un client
     */
    public function checkStatus($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        if (!$customer->email) {
            return response()->json(['error' => 'Email manquant'], 400);
        }

        $result = $this->sagapass->checkVerification($customer->email);
        
        return response()->json($result);
    }
}
```

### Étape 5 : Routes

```php
<?php
// routes/web.php

use App\Http\Controllers\CustomerVerificationController;

Route::prefix('customers')->group(function () {
    Route::get('{customer}/verify', [CustomerVerificationController::class, 'show'])
        ->name('customers.verify');
    
    Route::post('save-citizen-id', [CustomerVerificationController::class, 'saveCitizenId'])
        ->name('customers.save-citizen-id');
    
    Route::get('{customer}/check-status', [CustomerVerificationController::class, 'checkStatus'])
        ->name('customers.check-status');
});
```

### Étape 6 : Vue Blade

```blade
{{-- resources/views/customers/verify.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">🔒 Vérification d'Identité SAGAPASS</h3>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="bi bi-person"></i> Client</h5>
                        <p class="mb-1"><strong>Nom :</strong> {{ $customer->first_name }} {{ $customer->last_name }}</p>
                        <p class="mb-0"><strong>Email :</strong> {{ $customer->email }}</p>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="bi bi-info-circle"></i> Ce processus comprend :</h6>
                        <ul class="mb-0">
                            <li>Capture d'une photo du visage</li>
                            <li>Enregistrement d'une courte vidéo (15 secondes)</li>
                            <li>Création automatique d'un compte SAGAPASS</li>
                        </ul>
                    </div>

                    <button id="verifyBtn" class="btn btn-success btn-lg w-100 mb-3">
                        <i class="bi bi-shield-check"></i> Démarrer la Vérification
                    </button>

                    <div id="statusMessage" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://sagapass.com/js/widget.js"></script>
<script>
    document.getElementById('verifyBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Ouverture...';

        SagaPass.verify({
            partnerId: '{{ $sagapass_client_id }}',
            email: '{{ $customer->email }}',
            firstName: '{{ $customer->first_name }}',
            lastName: '{{ $customer->last_name }}',
            callbackUrl: '{{ route("customers.show", $customer) }}',

            onSuccess: function(data) {
                // Sauvegarder l'ID citoyen
                fetch('{{ route("customers.save-citizen-id") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        customer_id: {{ $customer->id }},
                        citizen_id: data.citizenId
                    })
                })
                .then(response => response.json())
                .then(result => {
                    const statusDiv = document.getElementById('statusMessage');
                    statusDiv.className = 'alert alert-success';
                    statusDiv.innerHTML = `
                        <h4>✅ Vérification Réussie !</h4>
                        <p><strong>ID Citoyen SAGAPASS :</strong> ${data.citizenId}</p>
                        <p class="mb-0">Un email avec les identifiants a été envoyé au client.</p>
                    `;
                    statusDiv.style.display = 'block';

                    // Redirection après 3 secondes
                    setTimeout(() => {
                        window.location.href = '{{ route("customers.show", $customer) }}';
                    }, 3000);
                });
            },

            onError: function(error) {
                const statusDiv = document.getElementById('statusMessage');
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = `<h4>❌ Erreur</h4><p>${error}</p>`;
                statusDiv.style.display = 'block';
                
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-shield-check"></i> Réessayer';
            },

            onCancel: function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-shield-check"></i> Démarrer la Vérification';
            }
        });
    });
</script>
@endpush
```

---

## 🎯 Scénario 3 : Application React

### Installation

```bash
npm install axios
```

### Component React

```jsx
// components/SagaPassVerification.jsx

import React, { useState, useEffect } from 'react';
import axios from 'axios';

function SagaPassVerification({ customer }) {
    const [status, setStatus] = useState('');
    const [loading, setLoading] = useState(false);
    const [widgetLoaded, setWidgetLoaded] = useState(false);

    // Charger le script widget
    useEffect(() => {
        if (!window.SagaPass) {
            const script = document.createElement('script');
            script.src = 'https://sagapass.com/js/widget.js';
            script.async = true;
            script.onload = () => setWidgetLoaded(true);
            document.head.appendChild(script);
        } else {
            setWidgetLoaded(true);
        }
    }, []);

    const handleVerification = () => {
        if (!widgetLoaded) {
            alert('Widget en cours de chargement...');
            return;
        }

        setLoading(true);

        window.SagaPass.verify({
            partnerId: process.env.REACT_APP_SAGAPASS_CLIENT_ID,
            email: customer.email,
            firstName: customer.firstName,
            lastName: customer.lastName,
            
            onSuccess: async (data) => {
                console.log('✅ Vérification réussie:', data);
                
                // Sauvegarder dans votre backend
                try {
                    await axios.post('/api/customers/save-citizen-id', {
                        customer_id: customer.id,
                        citizen_id: data.citizenId
                    });

                    setStatus('success');
                    setLoading(false);
                } catch (error) {
                    console.error('Erreur sauvegarde:', error);
                    setStatus('error');
                    setLoading(false);
                }
            },

            onError: (error) => {
                console.error('❌ Erreur:', error);
                setStatus('error');
                setLoading(false);
            },

            onCancel: () => {
                setLoading(false);
            }
        });
    };

    return (
        <div className="card shadow">
            <div className="card-header bg-primary text-white">
                <h3>🔒 Vérification d'Identité</h3>
            </div>
            <div className="card-body">
                <div className="alert alert-info">
                    <strong>Client :</strong> {customer.firstName} {customer.lastName}<br />
                    <strong>Email :</strong> {customer.email}
                </div>

                {status === 'success' && (
                    <div className="alert alert-success">
                        ✅ Vérification réussie ! Un email a été envoyé au client.
                    </div>
                )}

                {status === 'error' && (
                    <div className="alert alert-danger">
                        ❌ Une erreur s'est produite. Veuillez réessayer.
                    </div>
                )}

                <button 
                    className="btn btn-success btn-lg w-100"
                    onClick={handleVerification}
                    disabled={loading || !widgetLoaded}
                >
                    {loading ? (
                        <span className="spinner-border spinner-border-sm"></span>
                    ) : (
                        '🔒 Démarrer la Vérification'
                    )}
                </button>
            </div>
        </div>
    );
}

export default SagaPassVerification;
```

### Utilisation

```jsx
// pages/CustomerPage.jsx

import SagaPassVerification from '../components/SagaPassVerification';

function CustomerPage() {
    const customer = {
        id: 123,
        email: 'john@example.com',
        firstName: 'John',
        lastName: 'Doe'
    };

    return (
        <div className="container mt-5">
            <SagaPassVerification customer={customer} />
        </div>
    );
}
```

---

## 📞 Support & Ressources

- **Documentation complète :** `PARTNER_WIDGET_INTEGRATION.md`
- **API Reference :** `API_DOCUMENTATION.md`
- **Support :** support@sagapass.com

---

**✅ Votre intégration est prête !** Le widget gère automatiquement :
- Capture photo/vidéo
- Création compte SAGAPASS
- Envoi email avec identifiants
- Callbacks de succès/erreur
