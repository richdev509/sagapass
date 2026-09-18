@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-file-contract text-primary me-3"></i>Conditions Générales d'Utilisation
                </h1>
                <p class="text-muted">Dernière mise à jour : 18 septembre 2026</p>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                En utilisant SAGAPASS, vous acceptez ces conditions générales d'utilisation.
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">1. Objet</h2>
                    <p>
                        Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation
                        de la plateforme SAGAPASS, un service d'identité numérique fourni par Sagacetech.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">2. Acceptation des conditions</h2>
                    <p>
                        L'utilisation de nos services implique l'acceptation pleine et entière des présentes CGU.
                        Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser nos services.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">3. Description des services</h2>
                    <p>SAGAPASS propose :</p>
                    <ul>
                        <li>Un système d'identité numérique sécurisée</li>
                        <li>Une authentification unique (SSO) pour applications tierces</li>
                        <li>Un service de vérification d'identité</li>
                        <li>Une API OAuth 2.0 pour développeurs</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">4. Création de compte</h2>
                    <p>Pour utiliser SAGAPASS, vous devez :</p>
                    <ul>
                        <li>Être âgé d'au moins 18 ans</li>
                        <li>Fournir des informations exactes et à jour</li>
                        <li>Créer un mot de passe sécurisé</li>
                        <li>Ne pas usurper l'identité d'autrui</li>
                        <li>Maintenir la confidentialité de vos identifiants</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">5. Obligations de l'utilisateur</h2>
                    <p>Vous vous engagez à :</p>
                    <ul>
                        <li>Utiliser le service de manière légale et responsable</li>
                        <li>Ne pas tenter de contourner les mesures de sécurité</li>
                        <li>Ne pas partager votre compte avec des tiers</li>
                        <li>Signaler toute activité suspecte</li>
                        <li>Respecter les droits de propriété intellectuelle</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">6. Nos engagements</h2>
                    <p>
                        SAGAPASS s'engage à fournir un service de qualité et sécurisé. Nous faisons tout
                        notre possible pour assurer une disponibilité maximale de nos services.
                    </p>
                    <p>En cas de problème technique, notre équipe intervient rapidement pour rétablir le service.</p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">7. Propriété intellectuelle</h2>
                    <p>
                        Tous les éléments de la plateforme SAGAPASS (design, code, logo, contenus)
                        appartiennent à Sagacetech. Merci de respecter notre travail et de ne pas les reproduire
                        sans autorisation.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">8. Fermeture de compte</h2>
                    <p>
                        Nous nous réservons le droit de suspendre un compte en cas d'utilisation frauduleuse
                        ou contraire à nos valeurs.
                    </p>
                    <p>
                        Vous pouvez également fermer votre compte à tout moment depuis votre profil.
                        Vos données de compte seront supprimées dans un délai de 30 jours, à l'exception
                        des données de vérification d'identité décrites à l'article 9.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="verification-identite">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">9. Vérification d'identité et données biométriques</h2>
                    <p>
                        Lorsque vous passez une vérification d'identité avec SAGAPASS, y compris à la demande
                        d'un partenaire, nous collectons :
                    </p>
                    <ul>
                        <li>les photos de votre pièce d'identité (passeport, carte d'identification nationale ou permis de conduire) ;</li>
                        <li>des images de votre visage, prises en direct avec votre caméra ;</li>
                        <li>les informations lues sur votre pièce (nom, date de naissance, numéro de pièce) ;</li>
                        <li>une empreinte numérique de votre visage, qui est une donnée biométrique.</li>
                    </ul>
                    <p>
                        <strong>À quoi cela sert.</strong> Ces données servent à confirmer votre identité, à vérifier
                        que vous êtes bien présent devant la caméra, et à prévenir la fraude et l'usurpation d'identité.
                    </p>
                    <p>
                        <strong>Comparaison avec les autres vérifications.</strong> Pour détecter les fraudes, SAGAPASS
                        compare votre visage aux vérifications déjà effectuées sur sa plateforme, quel que soit le
                        partenaire concerné. Vous pouvez posséder une pièce de chaque type (un passeport, une carte
                        nationale et un permis), mais pas deux pièces du même type, ni une identité différente
                        avec le même visage. En cas de doute, un membre de notre équipe examine le dossier :
                        aucune décision définitive n'est prise automatiquement.
                    </p>
                    <p>
                        <strong>Ce que reçoivent les partenaires.</strong> SAGAPASS ne partage ni vos photos ni
                        l'empreinte de votre visage avec les partenaires, et ne leur révèle rien des vérifications
                        effectuées pour d'autres partenaires. Le partenaire à l'origine de votre demande reçoit
                        le résultat de la vérification et les informations lues sur votre pièce dont il a besoin
                        (par exemple votre nom, votre date de naissance et votre numéro de pièce).
                    </p>
                    <p>
                        <strong>Conservation.</strong> Pour protéger les utilisateurs contre l'usurpation d'identité,
                        les photos de vérification et l'empreinte du visage sont conservées sans limite de durée,
                        y compris après la fermeture de votre compte. L'empreinte est chiffrée et l'accès à ces
                        données est réservé aux personnes autorisées de SAGAPASS.
                    </p>
                    <p>
                        <strong>Vos droits.</strong> Vous pouvez demander l'accès, la correction ou l'effacement de
                        ces données en écrivant à
                        <a href="mailto:sagapass@sagapass.com">sagapass@sagapass.com</a>, sous réserve de nos
                        obligations légales et de la nécessité de prévenir la fraude.
                    </p>
                    <p class="mb-0">
                        <strong>Votre consentement.</strong> Avant toute capture, nous vous demandons de cocher une
                        case pour confirmer que vous avez lu ces conditions et que vous consentez à ce traitement.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">10. Évolution du service</h2>
                    <p>
                        Nous améliorons constamment SAGAPASS. Ces conditions peuvent être mises à jour
                        pour refléter les nouvelles fonctionnalités. Vous serez informé des changements
                        importants par email.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">11. Nous contacter</h2>
                    <p>
                        Des questions sur ces conditions ? Notre équipe est là pour vous aider !
                    </p>
                    <p>
                        <i class="fas fa-envelope text-primary me-2"></i>
                        <a href="mailto:sagapass@sagapass.com">sagapass@sagapass.com</a>
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-4 text-center">
                    <h3 class="h4 mb-3">Besoin de clarifications ?</h3>
                    <p class="mb-3">
                        N'hésitez pas à nous contacter pour toute question concernant nos CGU.
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
