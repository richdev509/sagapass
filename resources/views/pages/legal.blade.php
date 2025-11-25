@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-balance-scale text-primary me-3"></i>Mentions Légales
                </h1>
                <p class="text-muted">Informations légales et réglementaires</p>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Éditeur du site</h2>
                    <p><strong>Raison sociale :</strong> Sagacetech</p>
                    <p><strong>Siège social :</strong> Port-au-Prince, Haïti</p>
                    <p><strong>Email :</strong> <a href="mailto:sagapass@sagapass.com">sagapass@sagapass.com</a></p>
                    <p><strong>Directeur de publication :</strong> Richardson</p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Hébergement</h2>
                    <p>Le site SAGAPASS est hébergé par :</p>
                    <p><strong>Hébergeur :</strong> [Nom de l'hébergeur]</p>
                    <p><strong>Adresse :</strong> [Adresse de l'hébergeur]</p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Propriété intellectuelle</h2>
                    <p>
                        L'ensemble du contenu de ce site (textes, images, vidéos, logos, icônes, design)
                        est la propriété de Sagacetech.
                    </p>
                    <p>
                        Merci de respecter notre travail ! Si vous souhaitez utiliser nos contenus,
                        contactez-nous pour en discuter.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Protection des données personnelles</h2>
                    <p>
                        SAGAPASS s'engage à protéger vos données personnelles conformément au RGPD et
                        aux lois applicables en Haïti.
                    </p>
                    <p>
                        Pour plus d'informations sur la collecte et le traitement de vos données,
                        consultez notre <a href="{{ route('privacy') }}">Politique de Confidentialité</a>.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Cookies</h2>
                    <p>
                        Ce site utilise des cookies pour améliorer l'expérience utilisateur et assurer
                        le bon fonctionnement de nos services.
                    </p>
                    <p>
                        Pour plus d'informations, consultez notre
                        <a href="{{ route('cookies') }}">Politique de Cookies</a>.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Limitation de responsabilité</h2>
                    <p>
                        Sagacetech met tout en œuvre pour assurer l'exactitude et la mise à jour des
                        informations diffusées sur ce site.
                    </p>
                    <p>
                        En cas de problème technique ou d'interruption de service, notre équipe travaille
                        rapidement pour rétablir la situation. Nous restons à votre écoute pour toute question.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Liens externes</h2>
                    <p>
                        Ce site peut contenir des liens vers d'autres sites. Nous ne contrôlons pas
                        le contenu de ces sites externes, alors naviguez en toute conscience !
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Informations complémentaires</h2>
                    <p>
                        SAGAPASS est un service en constante évolution. Nous travaillons chaque jour
                        pour améliorer votre expérience et garantir la sécurité de vos données.
                    </p>
                    <p>
                        <strong>Localisation :</strong> Port-au-Prince, Haïti
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-4 text-center">
                    <h3 class="h4 mb-3">Contact légal</h3>
                    <p class="mb-3">
                        Pour toute question d'ordre légal, contactez notre service juridique.
                    </p>
                    <a href="mailto:legal@sagapass.com" class="btn btn-light">
                        <i class="fas fa-envelope me-2"></i>legal@sagapass.com
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
