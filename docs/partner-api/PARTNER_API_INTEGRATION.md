# 🚀 Guide d'Intégration Partner API - SAGA ID

## Vue d'ensemble

Ce guide vous explique **comment envoyer des requêtes** à l'API Partner de SAGA ID pour vérifier l'identité d'un citoyen, incluant le **chiffrement des données** pour une sécurité maximale.

---

## 📋 Prérequis

Avant de commencer, vous devez avoir :

1. ✅ **Client ID** : Identifiant unique de votre application (UUID)
2. ✅ **Client Secret** : Clé secrète pour l'authentification
3. ✅ **App Key** : Clé unique pour le chiffrement des données (64 caractères)

**Obtention** : Ces identifiants sont fournis par l'équipe SAGA ID lors de l'approbation de votre application.

---

## 🔐 Authentification

L'API utilise **HTTP Basic Authentication**.

### Format du Header

```
Authorization: Basic {base64(client_id:client_secret)}
```

### Exemples par langage

#### PHP
```php
$clientId = 'votre-client-id-uuid';
$clientSecret = 'votre-client-secret';

$authHeader = 'Basic ' . base64_encode($clientId . ':' . $clientSecret);

// Utilisation avec cURL
$headers = [
    'Authorization: ' . $authHeader,
    'Content-Type: application/json',
    'Accept: application/json',
];
```

#### JavaScript/Node.js
```javascript
const clientId = 'votre-client-id-uuid';
const clientSecret = 'votre-client-secret';

const authHeader = 'Basic ' + Buffer.from(`${clientId}:${clientSecret}`).toString('base64');

// Utilisation avec fetch
const headers = {
    'Authorization': authHeader,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
};
```

#### Python
```python
import base64

client_id = 'votre-client-id-uuid'
client_secret = 'votre-client-secret'

auth_string = f"{client_id}:{client_secret}"
auth_header = 'Basic ' + base64.b64encode(auth_string.encode()).decode()

# Utilisation avec requests
headers = {
    'Authorization': auth_header,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
}
```

---

## 🔒 Chiffrement des Données (AES-256-CBC)

### Étape 1 : Dérivation de la Clé de Chiffrement

La clé de chiffrement est **unique à votre application** et dérivée de vos credentials :

```
derivedKey = sha256(client_secret + app_key)[0:32 bytes]
```

**Important** : Prenez les **32 premiers octets** du hash SHA-256, pas les 32 premiers caractères hexadécimaux.

### Étape 2 : Génération du Vecteur d'Initialisation (IV)

Un **IV aléatoire de 16 octets** doit être généré pour chaque requête.

### Étape 3 : Chiffrement

- **Algorithme** : AES-256-CBC
- **Clé** : Clé dérivée (32 octets)
- **IV** : 16 octets aléatoires
- **Padding** : PKCS#7

### Étape 4 : Format Final

```
IV_base64:ciphertext_base64
```

Exemple : `aGVsbG8xMjM0NTY3ODk=:dGVzdGRhdGExMjM0NTY3ODk=`

---

## 💻 Exemples de Code Complet

### PHP (Recommandé)

```php
<?php

/**
 * Fonction de chiffrement AES-256-CBC
 */
function encryptPayload($data, $clientSecret, $appKey) {
    // 1. Dériver la clé (32 premiers octets du hash)
    $derivedKey = substr(hash('sha256', $clientSecret . $appKey, true), 0, 32);
    
    // 2. Générer un IV aléatoire (16 octets)
    $iv = openssl_random_pseudo_bytes(16);
    
    // 3. Convertir les données en JSON
    $jsonData = json_encode($data);
    
    // 4. Chiffrer avec AES-256-CBC
    $ciphertext = openssl_encrypt(
        $jsonData,
        'AES-256-CBC',
        $derivedKey,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    if ($ciphertext === false) {
        throw new Exception('Échec du chiffrement');
    }
    
    // 5. Format : IV:ciphertext (en base64)
    return base64_encode($iv) . ':' . base64_encode($ciphertext);
}

/**
 * Fonction d'envoi de requête
 */
function verifyIdentity($citizenData) {
    $clientId = 'votre-client-id-uuid';
    $clientSecret = 'votre-client-secret';
    $appKey = 'votre-app-key-64-chars';
    $apiUrl = 'https://api.sagapass.ht/api/partner/v1/verify';
    
    // Données du citoyen
    $payload = [
        'first_name'    => $citizenData['first_name'],
        'last_name'     => $citizenData['last_name'],
        'date_of_birth' => $citizenData['date_of_birth'], // Format: YYYY-MM-DD
        'niu'           => $citizenData['niu'] ?? null,    // 10 chiffres
        'email'         => $citizenData['email'] ?? null,
        'phone'         => $citizenData['phone'] ?? null,  // Max 20 chars
        'reference'     => $citizenData['reference'] ?? null, // Votre référence interne
    ];
    
    // Chiffrer le payload
    $encryptedData = encryptPayload($payload, $clientSecret, $appKey);
    
    // Préparer la requête
    $requestData = [
        'encrypted' => true,
        'data'      => $encryptedData,
    ];
    
    // Headers d'authentification
    $headers = [
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    // Envoyer la requête
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Important en production
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response'  => json_decode($response, true),
    ];
}

// Exemple d'utilisation
$result = verifyIdentity([
    'first_name'    => 'Jean',
    'last_name'     => 'Pierre',
    'date_of_birth' => '1990-05-15',
    'niu'           => '9005151234',
    'email'         => 'jean.pierre@example.ht',
    'phone'         => '+50937123456',
    'reference'     => 'ORDER_12345',
]);

if ($result['http_code'] === 200 && $result['response']['verified'] === true) {
    echo "✅ Identité vérifiée !";
} else {
    echo "❌ Vérification échouée : " . $result['response']['message'];
}
```

---

### JavaScript/Node.js

```javascript
const crypto = require('crypto');
const axios = require('axios');

/**
 * Fonction de chiffrement AES-256-CBC
 */
function encryptPayload(data, clientSecret, appKey) {
    // 1. Dériver la clé (32 premiers octets du hash)
    const hash = crypto.createHash('sha256').update(clientSecret + appKey).digest();
    const derivedKey = hash.slice(0, 32);
    
    // 2. Générer un IV aléatoire (16 octets)
    const iv = crypto.randomBytes(16);
    
    // 3. Convertir les données en JSON
    const jsonData = JSON.stringify(data);
    
    // 4. Chiffrer avec AES-256-CBC
    const cipher = crypto.createCipheriv('aes-256-cbc', derivedKey, iv);
    let encrypted = cipher.update(jsonData, 'utf8', 'base64');
    encrypted += cipher.final('base64');
    
    // 5. Format : IV:ciphertext (en base64)
    const ivBase64 = iv.toString('base64');
    return `${ivBase64}:${encrypted}`;
}

/**
 * Fonction d'envoi de requête
 */
async function verifyIdentity(citizenData) {
    const clientId = 'votre-client-id-uuid';
    const clientSecret = 'votre-client-secret';
    const appKey = 'votre-app-key-64-chars';
    const apiUrl = 'https://api.sagapass.ht/api/partner/v1/verify';
    
    // Données du citoyen
    const payload = {
        first_name: citizenData.first_name,
        last_name: citizenData.last_name,
        date_of_birth: citizenData.date_of_birth, // Format: YYYY-MM-DD
        niu: citizenData.niu || null,             // 10 chiffres
        email: citizenData.email || null,
        phone: citizenData.phone || null,         // Max 20 chars
        reference: citizenData.reference || null, // Votre référence interne
    };
    
    // Chiffrer le payload
    const encryptedData = encryptPayload(payload, clientSecret, appKey);
    
    // Préparer la requête
    const requestData = {
        encrypted: true,
        data: encryptedData,
    };
    
    // Headers d'authentification
    const authHeader = 'Basic ' + Buffer.from(`${clientId}:${clientSecret}`).toString('base64');
    
    try {
        const response = await axios.post(apiUrl, requestData, {
            headers: {
                'Authorization': authHeader,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });
        
        return {
            http_code: response.status,
            response: response.data,
        };
    } catch (error) {
        return {
            http_code: error.response?.status || 500,
            response: error.response?.data || { error: error.message },
        };
    }
}

// Exemple d'utilisation
(async () => {
    const result = await verifyIdentity({
        first_name: 'Jean',
        last_name: 'Pierre',
        date_of_birth: '1990-05-15',
        niu: '9005151234',
        email: 'jean.pierre@example.ht',
        phone: '+50937123456',
        reference: 'ORDER_12345',
    });
    
    if (result.http_code === 200 && result.response.verified === true) {
        console.log('✅ Identité vérifiée !');
    } else {
        console.log('❌ Vérification échouée :', result.response.message);
    }
})();
```

---

### Python

```python
import base64
import hashlib
import json
from Crypto.Cipher import AES
from Crypto.Random import get_random_bytes
from Crypto.Util.Padding import pad
import requests

def encrypt_payload(data, client_secret, app_key):
    """
    Fonction de chiffrement AES-256-CBC
    """
    # 1. Dériver la clé (32 premiers octets du hash)
    hash_input = (client_secret + app_key).encode('utf-8')
    derived_key = hashlib.sha256(hash_input).digest()[:32]
    
    # 2. Générer un IV aléatoire (16 octets)
    iv = get_random_bytes(16)
    
    # 3. Convertir les données en JSON
    json_data = json.dumps(data).encode('utf-8')
    
    # 4. Chiffrer avec AES-256-CBC
    cipher = AES.new(derived_key, AES.MODE_CBC, iv)
    ciphertext = cipher.encrypt(pad(json_data, AES.block_size))
    
    # 5. Format : IV:ciphertext (en base64)
    iv_base64 = base64.b64encode(iv).decode('utf-8')
    ciphertext_base64 = base64.b64encode(ciphertext).decode('utf-8')
    
    return f"{iv_base64}:{ciphertext_base64}"

def verify_identity(citizen_data):
    """
    Fonction d'envoi de requête
    """
    client_id = 'votre-client-id-uuid'
    client_secret = 'votre-client-secret'
    app_key = 'votre-app-key-64-chars'
    api_url = 'https://api.sagapass.ht/api/partner/v1/verify'
    
    # Données du citoyen
    payload = {
        'first_name': citizen_data['first_name'],
        'last_name': citizen_data['last_name'],
        'date_of_birth': citizen_data['date_of_birth'],  # Format: YYYY-MM-DD
        'niu': citizen_data.get('niu'),                  # 10 chiffres
        'email': citizen_data.get('email'),
        'phone': citizen_data.get('phone'),              # Max 20 chars
        'reference': citizen_data.get('reference'),      # Votre référence interne
    }
    
    # Chiffrer le payload
    encrypted_data = encrypt_payload(payload, client_secret, app_key)
    
    # Préparer la requête
    request_data = {
        'encrypted': True,
        'data': encrypted_data,
    }
    
    # Headers d'authentification
    auth_string = f"{client_id}:{client_secret}"
    auth_header = 'Basic ' + base64.b64encode(auth_string.encode()).decode()
    
    headers = {
        'Authorization': auth_header,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    }
    
    # Envoyer la requête
    try:
        response = requests.post(api_url, json=request_data, headers=headers)
        return {
            'http_code': response.status_code,
            'response': response.json(),
        }
    except Exception as e:
        return {
            'http_code': 500,
            'response': {'error': str(e)},
        }

# Exemple d'utilisation
if __name__ == '__main__':
    result = verify_identity({
        'first_name': 'Jean',
        'last_name': 'Pierre',
        'date_of_birth': '1990-05-15',
        'niu': '9005151234',
        'email': 'jean.pierre@example.ht',
        'phone': '+50937123456',
        'reference': 'ORDER_12345',
    })
    
    if result['http_code'] == 200 and result['response'].get('verified') == True:
        print('✅ Identité vérifiée !')
    else:
        print(f"❌ Vérification échouée : {result['response'].get('message')}")
```

---

## 📤 Structure de la Requête

### Endpoint

```
POST https://api.sagapass.ht/api/partner/v1/verify
```

### Headers

```
Authorization: Basic {base64(client_id:client_secret)}
Content-Type: application/json
Accept: application/json
```

### Body (avec chiffrement)

```json
{
  "encrypted": true,
  "data": "aGVsbG8xMjM0NTY3ODk=:dGVzdGRhdGExMjM0NTY3ODk="
}
```

### Body (sans chiffrement - DEV UNIQUEMENT)

```json
{
  "encrypted": false,
  "first_name": "Jean",
  "last_name": "Pierre",
  "date_of_birth": "1990-05-15",
  "niu": "9005151234",
  "email": "jean.pierre@example.ht",
  "phone": "+50937123456",
  "reference": "ORDER_12345"
}
```

---

## 📥 Réponses Possibles

### Succès - Compte Vérifié (HTTP 200)

```json
{
  "success": true,
  "verified": true,
  "status": "verified",
  "citizen_id": 123,
  "message": "Identité vérifiée avec succès.",
  "data": {
    "full_name": "Jean Pierre",
    "verification_date": "2026-03-15T10:30:00Z"
  }
}
```

### Succès - Compte Non Vérifié (HTTP 200)

```json
{
  "success": true,
  "verified": false,
  "status": "pending_verification",
  "citizen_id": 456,
  "message": "Ce citoyen a un compte Sagapass, mais la vérification d'identité n'est pas encore complète.",
  "action": "Invitez le citoyen à ouvrir l'application Sagapass pour finaliser sa vérification.",
  "app_links": {
    "android": "https://play.google.com/store/apps/details?id=ht.sagapass",
    "ios": "https://apps.apple.com/app/sagapass"
  }
}
```

### Compte Rejeté (HTTP 403)

```json
{
  "success": false,
  "verified": false,
  "error": "verification_rejected",
  "status": "rejected",
  "citizen_id": 789,
  "message": "Ce compte a été rejeté lors de la vérification d'identité.",
  "action": "L'utilisateur doit se connecter sur Sagapass avec son email pour corriger ses informations ou soumettre à nouveau ses documents de vérification.",
  "app_links": {
    "android": "https://play.google.com/store/apps/details?id=ht.sagapass",
    "ios": "https://apps.apple.com/app/sagapass"
  }
}
```

### Erreur - Authentification Invalide (HTTP 401)

```json
{
  "success": false,
  "error": "invalid_client",
  "message": "Authentification invalide. Vérifiez votre client_id et client_secret."
}
```

### Erreur - NIU en Double (HTTP 409)

```json
{
  "success": false,
  "error": "duplicate_niu",
  "message": "Ce NIU est déjà utilisé par un autre citoyen.",
  "conflict": {
    "field": "niu",
    "value": "9005151234"
  }
}
```

📖 **Voir toutes les réponses** : [PARTNER_API_RESPONSES.md](./PARTNER_API_RESPONSES.md)

---

## ✅ Checklist d'Intégration

### Phase 1 : Préparation

- [ ] Obtenir `client_id`, `client_secret`, `app_key` auprès de SAGA ID
- [ ] Configurer l'environnement de développement
- [ ] Installer les dépendances nécessaires (crypto, HTTP client)
- [ ] Lire la documentation complète

### Phase 2 : Développement

- [ ] Implémenter la fonction de dérivation de clé
- [ ] Implémenter la fonction de chiffrement AES-256-CBC
- [ ] Implémenter la fonction d'authentification Basic Auth
- [ ] Implémenter la fonction d'envoi de requête HTTP
- [ ] Tester en environnement de développement (sans chiffrement)

### Phase 3 : Tests

- [ ] Tester le chiffrement avec des données factices
- [ ] Tester les cas de succès (vérifié, non vérifié, nouveau compte)
- [ ] Tester les cas d'erreur (auth invalide, validation, doublons)
- [ ] Tester le cas du compte rejeté (HTTP 403)
- [ ] Valider la gestion des timeouts et erreurs réseau

### Phase 4 : Production

- [ ] Activer le chiffrement obligatoire
- [ ] Configurer SSL/TLS (HTTPS uniquement)
- [ ] Implémenter la gestion des logs
- [ ] Implémenter la gestion des retry (en cas d'erreur temporaire)
- [ ] Monitorer les appels API
- [ ] Sécuriser le stockage des credentials (variables d'environnement, vault)

---

## 🔒 Bonnes Pratiques de Sécurité

### 1. Stockage des Credentials

❌ **NE JAMAIS** :
- Hardcoder les credentials dans le code
- Commiter les credentials dans Git
- Exposer les credentials dans les logs

✅ **À FAIRE** :
- Utiliser des variables d'environnement (`.env`)
- Utiliser un gestionnaire de secrets (AWS Secrets Manager, HashiCorp Vault)
- Restreindre l'accès aux credentials (principe du moindre privilège)

### 2. Chiffrement

✅ **Obligatoire en production** :
- Toujours chiffrer les données sensibles
- Générer un IV aléatoire unique pour chaque requête
- Ne jamais réutiliser le même IV

### 3. Communication

✅ **Toujours utiliser HTTPS** :
- Vérifier le certificat SSL (`CURLOPT_SSL_VERIFYPEER = true`)
- Utiliser TLS 1.2 minimum

### 4. Validation

✅ **Valider les données avant envoi** :
- Format de date : `YYYY-MM-DD`
- NIU : Exactement 10 chiffres
- Téléphone : Maximum 20 caractères
- Email : Format valide

---

## 🐛 Dépannage

### Erreur : "Impossible de décrypter les données"

**Causes possibles** :
- Mauvaise clé dérivée (vérifier `client_secret` et `app_key`)
- IV mal formaté
- Données corrompues

**Solution** :
```php
// Vérifier la dérivation de clé
$derivedKey = substr(hash('sha256', $clientSecret . $appKey, true), 0, 32);
echo bin2hex($derivedKey); // Afficher pour debug
```

### Erreur : "invalid_client" (HTTP 401)

**Causes possibles** :
- `client_id` incorrect
- `client_secret` incorrect
- Header Authorization mal formaté

**Solution** :
```php
// Vérifier le header
$header = base64_encode($clientId . ':' . $clientSecret);
echo $header; // Doit être une chaîne base64 valide
```

### Erreur : Timeout

**Solution** :
```php
// Augmenter le timeout
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 secondes
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 secondes pour connexion
```

---

## 📞 Support

### Documentation

- **Réponses API** : [PARTNER_API_RESPONSES.md](./PARTNER_API_RESPONSES.md)
- **Emails** : [PARTNER_EMAIL_GUIDE.md](./PARTNER_EMAIL_GUIDE.md)
- **Unicité** : [PARTNER_VERIFY_UNIQUENESS.md](./PARTNER_VERIFY_UNIQUENESS.md)
- **App Stores** : [APP_STORES_MANAGEMENT.md](./APP_STORES_MANAGEMENT.md)

### Contact

📧 Email : support@sagapass.ht  
🌐 Portal : https://developer.sagapass.ht  
📚 Docs : https://docs.sagapass.ht

---

## 📝 Exemple Complet d'Intégration

Voici un exemple complet d'intégration dans une application e-commerce :

```php
<?php

class SagapassVerification {
    private $clientId;
    private $clientSecret;
    private $appKey;
    private $apiUrl = 'https://api.sagapass.ht/api/partner/v1/verify';
    
    public function __construct($clientId, $clientSecret, $appKey) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->appKey = $appKey;
    }
    
    private function encryptPayload($data) {
        $derivedKey = substr(hash('sha256', $this->clientSecret . $this->appKey, true), 0, 32);
        $iv = openssl_random_pseudo_bytes(16);
        $jsonData = json_encode($data);
        $ciphertext = openssl_encrypt($jsonData, 'AES-256-CBC', $derivedKey, OPENSSL_RAW_DATA, $iv);
        
        if ($ciphertext === false) {
            throw new Exception('Échec du chiffrement');
        }
        
        return base64_encode($iv) . ':' . base64_encode($ciphertext);
    }
    
    public function verifyCustomer($customerData) {
        $payload = [
            'first_name'    => $customerData['first_name'],
            'last_name'     => $customerData['last_name'],
            'date_of_birth' => $customerData['date_of_birth'],
            'niu'           => $customerData['niu'] ?? null,
            'email'         => $customerData['email'] ?? null,
            'phone'         => $customerData['phone'] ?? null,
            'reference'     => 'ORDER_' . $customerData['order_id'],
        ];
        
        $encryptedData = $this->encryptPayload($payload);
        
        $requestData = [
            'encrypted' => true,
            'data'      => $encryptedData,
        ];
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erreur cURL : $error");
        }
        
        return [
            'http_code' => $httpCode,
            'response'  => json_decode($response, true),
        ];
    }
    
    public function processVerificationResult($result) {
        $httpCode = $result['http_code'];
        $data = $result['response'];
        
        switch ($httpCode) {
            case 200:
                if ($data['verified'] === true) {
                    return [
                        'status' => 'approved',
                        'message' => 'Identité vérifiée. Transaction autorisée.',
                    ];
                } else {
                    return [
                        'status' => 'pending',
                        'message' => 'Identité non vérifiée. Invitez le client à compléter sa vérification Sagapass.',
                        'action' => $data['action'] ?? null,
                        'app_links' => $data['app_links'] ?? null,
                    ];
                }
            
            case 403:
                return [
                    'status' => 'rejected',
                    'message' => 'Compte rejeté. Le client doit corriger ses informations sur Sagapass.',
                    'action' => $data['action'] ?? null,
                ];
            
            case 409:
                return [
                    'status' => 'conflict',
                    'message' => 'Données en conflit (NIU ou téléphone déjà utilisé).',
                    'error' => $data['error'] ?? null,
                ];
            
            case 401:
            case 422:
            default:
                return [
                    'status' => 'error',
                    'message' => $data['message'] ?? 'Erreur lors de la vérification',
                ];
        }
    }
}

// Utilisation
$sagapass = new SagapassVerification(
    getenv('SAGAPASS_CLIENT_ID'),
    getenv('SAGAPASS_CLIENT_SECRET'),
    getenv('SAGAPASS_APP_KEY')
);

try {
    $result = $sagapass->verifyCustomer([
        'first_name' => 'Jean',
        'last_name' => 'Pierre',
        'date_of_birth' => '1990-05-15',
        'niu' => '9005151234',
        'email' => 'jean.pierre@example.ht',
        'phone' => '+50937123456',
        'order_id' => '12345',
    ]);
    
    $decision = $sagapass->processVerificationResult($result);
    
    if ($decision['status'] === 'approved') {
        // Autoriser la transaction
        echo "✅ Transaction autorisée !";
    } else {
        // Refuser ou mettre en attente
        echo "⏳ " . $decision['message'];
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
```

---

**Dernière mise à jour** : 30 mars 2026  
**Version API** : v1  
**Auteur** : Équipe SAGA ID
