@php
    // Redirection automatique vers la nouvelle inscription avec vérification email
    header('Location: ' . route('register.basic.email-request'));
    exit;
@endphp
