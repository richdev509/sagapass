# Guide d'intégration - Sessions de vérification (flux QR)

C'est le flux **recommandé** pour un nouveau partenaire qui veut vérifier
l'identité d'un utilisateur qui n'a pas de compte SagaPass. SagaPass capture
lui-même (sur sa propre page web, caméra en direct) la pièce d'identité et le
selfie de l'utilisateur, fait l'OCR + la vivacité + la correspondance
visage↔pièce, et notifie le résultat par webhook. Vous n'avez jamais besoin
de manipuler de photos vous-même.

> Pour l'ancien endpoint `POST /api/partner/v1/verify` (compare un nom/NIU à
> un compte SagaPass **déjà existant**, sans capture de photo), voir
> `PARTNER_API_INTEGRATION.md` - généralement pas ce qu'il vous faut si vos
> utilisateurs n'ont pas de compte SagaPass.

## 1. Identifiants

Après approbation de votre demande de partenariat (tableau de bord), vous
recevez :

- `client_id` / `client_secret` - authentification Basic sur les appels
  sortants vers l'API.
- `webhook_secret` - vérifie la signature des webhooks entrants. **Distinct**
  du `client_secret`, ne le confondez pas.

## 2. Créer une session de vérification

```
POST https://sagapass.com/api/partner/v1/verification-sessions
Auth: Basic (client_id, client_secret)
Body (JSON):
  document_type: "national_id" | "passport" | "drivers_license"
  partner_reference: string   (votre référence interne, unique)
  webhook_url: string          (votre URL publique qui recevra le résultat)
  partner_submitted_data: object  (infos déjà saisies par l'utilisateur - nom,
                                    date de naissance - pour recoupement contre
                                    l'OCR ; jamais de fichier/photo ici)

Réponse (201, success: true):
  session_token: string
  capture_url: string    (page publique SagaPass - redirigez l'utilisateur ici)
  expires_at: string (ISO 8601)
```

Redirigez l'utilisateur vers `capture_url`. SagaPass gère toute la capture
caméra (pièce + selfie + vivacité active) sur sa propre page.

## 3. Vérifier le statut (filet de sécurité)

```
GET https://sagapass.com/api/partner/v1/verification-sessions/{session_token}/status
Auth: Basic (client_id, client_secret)
```

À utiliser uniquement si le webhook tarde - le webhook reste le chemin
principal.

## 4. Recevoir le webhook de résultat

SagaPass POST le résultat sur `webhook_url`.

- Header `X-Saga-Signature`, valeur `sha256=<hmac>`.
- Calcul : `hash_hmac('sha256', <corps brut>, webhook_secret)`.
- Comparez en **temps constant** (`hash_equals`), jamais `===`.
- Événements : `verification.completed`, `verification.failed`,
  `verification.expired`, `kyc.expired`.
- Le payload contient `session_token` et/ou `kyc_id` - utilisez-les comme clé
  d'idempotence (le même événement peut être renvoyé plusieurs fois).
- Répondez vite (`200 {"received": true}`) et traitez en file d'attente -
  jamais en synchrone dans le contrôleur du webhook.

## 5. Champs disponibles selon le type de pièce

Tous les types renvoient au minimum `document_number`, `full_name`,
`date_of_birth`, `date_of_expiry`. Selon `document_type`, des champs
supplémentaires sont disponibles dans le résultat :

| Type | Champs supplémentaires |
|---|---|
| `national_id` | `sex`, `place_of_birth`, `date_of_issue` |
| `passport` | `sex`, `nationality`, `personal_number`, `mrz_line1`, `mrz_line2` |
| `drivers_license` | `sex`, `nif`, `address`, `blood_type`, `license_category`, `place_of_issue`, `date_of_issue` |

**Ne bloquez pas votre décision d'approbation sur un score de correspondance
visage (`face_match_score`) seul** - c'est un signal indicatif, pas un
verdict. La qualité des photos imprimées sur certaines pièces d'identité
varie beaucoup ; un score de correspondance faible n'est pas nécessairement
une fraude.

## 6. Pièce expirée

Une pièce dont `date_of_expiry` est dans le passé est **automatiquement
rejetée** (`verification.failed`, aucun KYC ID émis) - vous n'avez rien à
vérifier vous-même côté partenaire pour ce cas.

## 7. Revérifier un KYC ID durable

```
GET https://sagapass.com/api/partner/v1/kyc-identities/{kyc_id}/status
Auth: Basic (client_id, client_secret)
```

Permet de vérifier qu'une identité déjà validée est toujours dans sa période
de validité, sans repasser par toute la capture - utile avant une action
sensible côté partenaire.
