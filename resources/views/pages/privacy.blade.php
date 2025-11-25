@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-user-shield text-primary me-3"></i>Politique de Confidentialité
                </h1>
                <p class="text-muted">Dernière mise à jour : {{ date('d F Y') }}</p>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">1. Introduction</h2>
                    <p>
                        Chez SAGAPASS, nous prenons très au sérieux la protection de vos données personnelles.
                        Cette politique de confidentialité explique comment nous collectons, utilisons, protégeons
                        et partageons vos informations lorsque vous utilisez nos services.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">2. Données collectées</h2>
                    <p>Nous collectons les types de données suivantes :</p>

                    <h5 class="mt-4">2.1 Informations d'identification</h5>
                    <ul>
                        <li>Nom et prénom</li>
                        <li>Adresse email</li>
                        <li>Numéro de téléphone</li>
                        <li>Date de naissance</li>
                        <li>Photo de profil</li>
                        <li>Vidéo de vérification</li>
                    </ul>

                    <h5 class="mt-4">2.2 Documents d'identité (compte vérifié uniquement)</h5>
                    <ul>
                        <li>Carte d'identité nationale (CIN)</li>
                        <li>Passeport</li>
                        <li>Permis de conduire</li>
                    </ul>

                    <h5 class="mt-4">2.3 Données techniques</h5>
                    <ul>
                        <li>Adresse IP</li>
                        <li>Type de navigateur</li>
                        <li>Système d'exploitation</li>
                        <li>Logs de connexion</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">3. Utilisation des données</h2>
                    <p>Nous utilisons vos données pour :</p>
                    <ul>
                        <li>Créer et gérer votre compte SAGAPASS</li>
                        <li>Vérifier votre identité</li>
                        <li>Fournir nos services d'authentification</li>
                        <li>Prévenir la fraude et assurer la sécurité</li>
                        <li>Communiquer avec vous</li>
                        <li>Améliorer nos services</li>
                        <li>Respecter nos obligations légales</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">4. Protection des données</h2>
                    <p>Nous mettons en œuvre les mesures de sécurité suivantes :</p>
                    <ul>
                        <li>Chiffrement SSL/TLS pour toutes les transmissions</li>
                        <li>Chiffrement AES-256 pour le stockage des données sensibles</li>
                        <li>Authentification à deux facteurs (2FA)</li>
                        <li>Audits de sécurité réguliers</li>
                        <li>Accès restreint aux données personnelles</li>
                        <li>Surveillance 24/7 des systèmes</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">5. Partage des données</h2>
                    <p>
                        <strong>Nous ne vendons jamais vos données personnelles.</strong> Votre confiance est notre priorité.
                    </p>
                    <p>Nous ne partageons vos données qu'avec :</p>
                    <ul>
                        <li><strong>Applications tierces :</strong> Uniquement avec votre consentement explicite lors de l'autorisation OAuth</li>
                        <li><strong>Prestataires de services :</strong> Pour l'hébergement, la sécurité, les emails (sous contrat de confidentialité strict)</li>
                        <li><strong>Autorités légales :</strong> Uniquement en cas d'obligation légale</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">6. Vos droits</h2>
                    <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                    <ul>
                        <li><strong>Droit d'accès :</strong> Obtenir une copie de vos données</li>
                        <li><strong>Droit de rectification :</strong> Corriger vos données inexactes</li>
                        <li><strong>Droit à l'effacement :</strong> Demander la suppression de vos données</li>
                        <li><strong>Droit à la portabilité :</strong> Recevoir vos données dans un format structuré</li>
                        <li><strong>Droit d'opposition :</strong> Vous opposer au traitement de vos données</li>
                        <li><strong>Droit de limitation :</strong> Limiter le traitement de vos données</li>
                    </ul>
                    <p class="mt-3">
                        Pour exercer ces droits, contactez-nous à :
                        <a href="mailto:sagapass@sagapass.com">sagapass@sagapass.com</a>
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">7. Cookies</h2>
                    <p>
                        Nous utilisons des cookies essentiels pour le fonctionnement de notre site.
                        Pour plus d'informations, consultez notre
                        <a href="{{ route('cookies') }}">politique de cookies</a>.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">8. Conservation des données</h2>
                    <p>Nous conservons vos données :</p>
                    <ul>
                        <li>Tant que votre compte est actif</li>
                        <li>Jusqu'à 30 jours après la demande de suppression de compte</li>
                        <li>Plus longtemps si requis par la loi (ex: obligations fiscales)</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">9. Modifications</h2>
                    <p>
                        Nous pouvons modifier cette politique de confidentialité. Vous serez notifié
                        de tout changement significatif par email ou via notre plateforme.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-4 text-center">
                    <h3 class="h4 mb-3">Des questions ?</h3>
                    <p class="mb-3">
                        Pour toute question concernant cette politique de confidentialité, contactez-nous.
                    </p>
                    <a href="{{ route('contact') }}" class="btn btn-light">
                        <i class="fas fa-envelope me-2"></i>Nous contacter
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
