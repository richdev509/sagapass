@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-building text-primary me-3"></i>À propos de SAGAPASS
                </h1>
                <p class="lead text-muted">Votre passeport numérique pour un monde connecté</p>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h3 mb-4"><i class="fas fa-bullseye text-primary me-2"></i>Notre Mission</h2>
                    <p class="lead">
                        Rendre l'accès aux services numériques simple, rapide et sécurisé pour tous.
                    </p>
                    <p>
                        Nous croyons que chaque personne mérite une identité numérique fiable, sans
                        complications ni tracas. SAGAPASS, c'est votre clé universelle pour le monde digital !
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h3 mb-4"><i class="fas fa-eye text-primary me-2"></i>Notre Vision</h2>
                    <p>
                        Devenir LA référence de l'identité numérique en Haïti et dans la Caraïbe.
                        Un compte SAGAPASS = accès à tous vos services préférés en un clic !
                    </p>
                    <p>
                        Imaginez : plus besoin de créer 20 comptes différents, de retenir 20 mots de passe.
                        Avec SAGAPASS, une seule identité sécurisée suffit. 🚀
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h3 mb-4"><i class="fas fa-heart text-primary me-2"></i>Nos Valeurs</h2>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-shield-alt text-primary fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h4>Sécurité</h4>
                                    <p>Protection maximale de vos données personnelles avec les dernières technologies de cryptage.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-check text-primary fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h4>Confiance</h4>
                                    <p>Transparence totale sur l'utilisation de vos données et respect de votre vie privée.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-rocket text-primary fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h4>Innovation</h4>
                                    <p>Adoption continue des meilleures pratiques et technologies du secteur.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users text-primary fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h4>Accessibilité</h4>
                                    <p>Une plateforme simple et intuitive accessible à tous.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-5 text-center">
                    <h2 class="h3 mb-4">Rejoignez-nous</h2>
                    <p class="mb-4">
                        Faites partie de la révolution numérique. Créez votre SAGAPASS dès aujourd'hui et accédez
                        à des centaines de services en toute simplicité.
                    </p>
                    <a href="{{ route('register.basic.step1') }}" class="btn btn-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Créer mon compte
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
