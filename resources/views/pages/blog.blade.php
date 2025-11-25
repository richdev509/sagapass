@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-newspaper text-primary me-3"></i>Blog SAGAPASS
                </h1>
                <p class="lead text-muted">Actualités, conseils et nouveautés</p>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Bientôt disponible !</strong> Notre blog est en cours de construction.
                Inscrivez-vous pour être notifié de nos prochains articles.
            </div>

            <div class="row g-4">
                <!-- Article 1 -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="badge bg-primary">Sécurité</span>
                                <span class="badge bg-secondary">{{ date('d M Y') }}</span>
                            </div>
                            <h3 class="h4">Comment sécuriser votre identité numérique</h3>
                            <p class="text-muted">
                                Découvrez les meilleures pratiques pour protéger votre compte SAGAPASS
                                et vos données personnelles en ligne.
                            </p>
                            <a href="#" class="btn btn-outline-primary btn-sm disabled">
                                <i class="fas fa-book-reader me-1"></i>Lire plus (bientôt)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Article 2 -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="badge bg-success">Nouveautés</span>
                                <span class="badge bg-secondary">{{ date('d M Y') }}</span>
                            </div>
                            <h3 class="h4">Lancement de l'authentification à deux facteurs</h3>
                            <p class="text-muted">
                                Renforcez la sécurité de votre compte avec notre nouvelle fonctionnalité
                                d'authentification à deux facteurs (2FA).
                            </p>
                            <a href="#" class="btn btn-outline-primary btn-sm disabled">
                                <i class="fas fa-book-reader me-1"></i>Lire plus (bientôt)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Article 3 -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="badge bg-info">Guide</span>
                                <span class="badge bg-secondary">{{ date('d M Y') }}</span>
                            </div>
                            <h3 class="h4">Guide complet : Créer votre SAGAPASS en 3 étapes</h3>
                            <p class="text-muted">
                                Un tutoriel détaillé pour vous accompagner dans la création de votre
                                compte SAGAPASS Basic en quelques minutes.
                            </p>
                            <a href="#" class="btn btn-outline-primary btn-sm disabled">
                                <i class="fas fa-book-reader me-1"></i>Lire plus (bientôt)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Article 4 -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="badge bg-warning text-dark">Développeurs</span>
                                <span class="badge bg-secondary">{{ date('d M Y') }}</span>
                            </div>
                            <h3 class="h4">API SAGAPASS : Intégrez l'authentification en 5 minutes</h3>
                            <p class="text-muted">
                                Documentation complète pour les développeurs souhaitant intégrer
                                SAGAPASS OAuth2 dans leurs applications.
                            </p>
                            <a href="#" class="btn btn-outline-primary btn-sm disabled">
                                <i class="fas fa-book-reader me-1"></i>Lire plus (bientôt)
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-5 text-center">
                    <h2 class="h3 mb-3">Restez informé</h2>
                    <p class="mb-4">
                        Inscrivez-vous à notre newsletter pour recevoir les dernières actualités et conseils.
                    </p>
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="email" class="form-control" placeholder="Votre email" disabled>
                                <button class="btn btn-light" type="button" disabled>
                                    <i class="fas fa-paper-plane me-1"></i>S'inscrire (bientôt)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
