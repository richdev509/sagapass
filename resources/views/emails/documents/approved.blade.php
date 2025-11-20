@component('mail::message')
# ✅ Document Vérifié avec Succès

Bonjour **{{ $userName }}**,

Nous avons le plaisir de vous informer que votre document a été **vérifié et approuvé** par notre équipe.

## 📄 Détails du Document

- **Type** : {{ $documentType }}
- **Numéro** : {{ $documentNumber }}
- **Date de vérification** : {{ $verifiedAt }}

Votre identité numérique est maintenant active et vous pouvez l'utiliser pour vous connecter aux services partenaires.

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'success'])
Voir mon Tableau de Bord
@endcomponent

**Que faire maintenant ?**
- Accédez aux services gouvernementaux en ligne
- Utilisez votre identité vérifiée pour des transactions sécurisées
- Gérez vos autorisations d'accès aux applications tierces

---

💡 **Conseil de sécurité** : Ne partagez jamais vos identifiants de connexion avec qui que ce soit.

Cordialement,
**L'équipe SAGAPASS**

@component('mail::subcopy')
Si vous avez des questions, contactez notre support à support@sagapass.com
@endcomponent
@endcomponent
