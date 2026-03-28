# API Mobile - Documentation

API Backend pour l'application mobile SAGA ID.

## Base URL

```
http://localhost:8000/api
```

En production : `https://votre-domaine.com/api`

## Endpoints

### 1. Inscription

#### 1.1 Envoyer code OTP
```http
POST /mobile/register/send-otp
Content-Type: application/json

{
  "email": "utilisateur@example.com"
}
```

**Réponse succès (200)**
```json
{
  "success": true,
  "message": "Code OTP envoyé à votre email."
}
```

**Réponse erreur (400)**
```json
{
  "success": false,
  "message": "Cette adresse email est déjà utilisée."
}
```

---

#### 1.2 Vérifier code OTP
```http
POST /mobile/register/verify-otp
Content-Type: application/json

{
  "email": "utilisateur@example.com",
  "otp": "123456"
}
```

**Réponse succès (200)**
```json
{
  "success": true,
  "message": "Email vérifié avec succès."
}
```

**Réponse erreur (400)**
```json
{
  "success": false,
  "message": "Code OTP incorrect. Tentatives restantes: 4"
}
```

---

#### 1.3 Compléter l'inscription
```http
POST /mobile/register/complete
Content-Type: application/json

{
  "first_name": "Jean",
  "last_name": "Dupont",
  "date_of_birth": "1990-01-15",
  "phone": "+50928123456",
  "niu": "1234567890",
  "email": "utilisateur@example.com",
  "id_card_front": "base64_encoded_image...",
  "id_card_back": "base64_encoded_image...",
  "selfie": "base64_encoded_image..."
}
```

**Réponse succès (201)**
```json
{
  "success": true,
  "message": "Inscription réussie. Votre compte est en cours de vérification.",
  "data": {
    "user": {
      "id": 1,
      "first_name": "Jean",
      "last_name": "Dupont",
      "email": "utilisateur@example.com",
      "phone": "+50928123456",
      "niu": "1234567890",
      "date_of_birth": "1990-01-15",
      "verification_status": "pending",
      "account_status": "active",
      "email_verified_at": "2026-03-21T12:00:00.000000Z",
      "created_at": "2026-03-21T12:00:00.000000Z"
    },
    "token": "1|abc123..."
  }
}
```

---

### 2. Connexion

#### 2.1 Envoyer code OTP pour connexion
```http
POST /mobile/login/send-otp
Content-Type: application/json

{
  "email": "utilisateur@example.com"
}
```

**Réponse succès (200)**
```json
{
  "success": true,
  "message": "Code OTP envoyé à votre email."
}
```

**Réponse erreur (404)**
```json
{
  "success": false,
  "message": "Aucun compte trouvé avec cette adresse email."
}
```

---

#### 2.2 Vérifier code OTP et se connecter
```http
POST /mobile/login/verify-otp
Content-Type: application/json

{
  "email": "utilisateur@example.com",
  "otp": "123456"
}
```

**Réponse succès (200)**
```json
{
  "success": true,
  "message": "Connexion réussie.",
  "data": {
    "user": {
      "id": 1,
      "first_name": "Jean",
      "last_name": "Dupont",
      "email": "utilisateur@example.com",
      "phone": "+50928123456",
      "niu": "1234567890",
      "date_of_birth": "1990-01-15",
      "verification_status": "pending",
      "account_status": "active",
      "email_verified_at": "2026-03-21T12:00:00.000000Z",
      "created_at": "2026-03-21T12:00:00.000000Z",
      "profile_picture": null
    },
    "token": "2|xyz789..."
  }
}
```

---

### 3. Profil Utilisateur (Protégé)

#### 3.1 Récupérer le profil
```http
GET /user/profile
Authorization: Bearer {token}
```

**Réponse succès (200)**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "Jean",
    "last_name": "Dupont",
    "email": "utilisateur@example.com",
    "phone": "+50928123456",
    "niu": "1234567890",
    "date_of_birth": "1990-01-15",
    "verification_status": "verified",
    "account_status": "active",
    "email_verified_at": "2026-03-21T12:00:00.000000Z",
    "created_at": "2026-03-21T12:00:00.000000Z",
    "profile_picture": "/storage/profiles/photo.jpg"
  }
}
```

---

## Validation

### Règles de validation

**Email**
- Format email valide
- Unique lors de l'inscription

**Téléphone**
- Format: +509XXXXXXXX (11 chiffres au total)
- Commence par +509

**NIU**
- Exactement 10 chiffres
- Numérique uniquement

**Date de naissance**
- Format: YYYY-MM-DD
- L'utilisateur doit avoir au moins 18 ans

**Nom et prénom**
- Minimum 2 caractères
- Maximum 50 caractères
- Lettres, espaces, tirets et apostrophes uniquement

**Code OTP**
- Exactement 6 chiffres
- Valide pendant 15 minutes
- Maximum 5 tentatives incorrectes

**Photos (base64)**
- Format base64 valide
- Types supportés: JPEG, PNG

---

## Rate Limiting

### Inscription OTP
- **Par IP**: 5 tentatives par heure
- **Par email**: 3 tentatives par heure

### Connexion OTP
- **Par IP**: 10 tentatives par heure

### API Protégées
- **Général**: 60 requêtes par minute

---

## Codes d'erreur

| Code | Description |
|------|-------------|
| 200 | Succès |
| 201 | Créé avec succès |
| 400 | Requête invalide |
| 401 | Non authentifié |
| 404 | Ressource non trouvée |
| 429 | Trop de requêtes (rate limit) |
| 500 | Erreur serveur |

---

## Statuts de vérification

| Statut | Description |
|--------|-------------|
| `pending` | Vérification en cours |
| `verified` | Compte vérifié |
| `rejected` | Vérification rejetée |

---

## Notes de sécurité

1. **HTTPS obligatoire** en production
2. Les tokens sont générés avec Laravel Sanctum
3. Les OTP sont stockés en cache avec expiration
4. Les photos sont stockées localement (non publiques)
5. Le NIU est un identifiant sensible (à protéger)

---

## Exemples d'intégration

### Flutter (Dart)

```dart
// Send OTP
final response = await http.post(
  Uri.parse('$baseUrl/mobile/register/send-otp'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({'email': email}),
);

// Complete registration with auth token
final response = await http.post(
  Uri.parse('$baseUrl/mobile/register/complete'),
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer $token',
  },
  body: jsonEncode(registrationData),
);
```

---

## Support

Pour toute question ou problème, contactez l'équipe de développement.

**Version**: 1.0  
**Dernière mise à jour**: 21 Mars 2026
