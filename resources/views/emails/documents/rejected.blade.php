@component('mail::message')
# ❌ Document Rejeté

Bonjour **{{ $userName }}**,

Malheureusement, nous ne pouvons pas approuver votre document pour le moment.

## 📄 Détails du Document

- **Type** : {{ $documentType }}
- **Numéro** : {{ $documentNumber }}
- **Date de rejet** : {{ $rejectedAt }}

## 🔍 Raison du Rejet

{{ $rejectionReason }}

---

## 🔄 Que faire maintenant ?

Vous pouvez soumettre un nouveau document en corrigeant le(s) problème(s) mentionné(s) ci-dessus.

**Conseils pour une nouvelle soumission réussie :**
- ✅ Assurez-vous que le document est lisible et de bonne qualité
- ✅ Vérifiez que toutes les informations sont visibles
- ✅ Le document ne doit pas être expiré
- ✅ Les photos doivent être bien éclairées sans reflets

@component('mail::button', ['url' => $resubmitUrl, 'color' => 'primary'])
Soumettre un Nouveau Document
@endcomponent

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'secondary'])
Voir mon Tableau de Bord
@endcomponent

---

Si vous avez besoin d'aide ou d'éclaircissements, n'hésitez pas à nous contacter.

Cordialement,
**L'équipe SAGAPASS**

@component('mail::subcopy')
Pour toute question, contactez notre support à support@sagapass.com
@endcomponent
@endcomponent
