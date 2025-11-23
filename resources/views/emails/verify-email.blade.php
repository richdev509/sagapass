@component('mail::message')
# Bienvenue sur SAGAPASS, {{ $userName }} !

Merci de vous être inscrit. Pour finaliser votre compte et accéder à toutes les fonctionnalités, veuillez vérifier votre adresse email en cliquant sur le bouton ci-dessous.

@component('mail::button', ['url' => $verificationUrl, 'color' => 'success'])
✓ Vérifier mon email
@endcomponent

**Ce lien est valide pendant 60 minutes.**

Si vous n'avez pas créé de compte, aucune action n'est requise.

---

Cordialement,
**L'équipe SAGAPASS**

@component('mail::subcopy')
Si vous avez des difficultés à cliquer sur le bouton "Vérifier mon email", copiez et collez l'URL ci-dessous dans votre navigateur :

[{{ $verificationUrl }}]({{ $verificationUrl }})
@endcomponent
@endcomponent
