@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-cog text-primary me-3"></i>API SAGAPASS
                </h1>
                <p class="lead text-muted">Intégrez l'authentification SAGAPASS dans vos applications</p>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Pour accéder à la documentation complète de l'API, vous devez créer un compte développeur.
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h3><i class="fas fa-rocket text-primary me-2"></i>OAuth 2.0</h3>
                            <p class="text-muted">
                                Intégrez l'authentification SAGAPASS avec le protocole OAuth 2.0 standard.
                            </p>
                            <ul>
                                <li>Authorization Code Flow</li>
                                <li>Refresh Tokens</li>
                                <li>Scopes personnalisables</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h3><i class="fas fa-lock text-primary me-2"></i>Sécurité</h3>
                            <p class="text-muted">
                                Des standards de sécurité de niveau entreprise pour protéger vos utilisateurs.
                            </p>
                            <ul>
                                <li>HTTPS obligatoire</li>
                                <li>Rate limiting</li>
                                <li>Tokens chiffrés</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-4">Endpoints principaux</h2>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Méthode</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>/oauth/authorize</code></td>
                                    <td><span class="badge bg-primary">GET</span></td>
                                    <td>Demande d'autorisation</td>
                                </tr>
                                <tr>
                                    <td><code>/oauth/token</code></td>
                                    <td><span class="badge bg-success">POST</span></td>
                                    <td>Obtenir un access token</td>
                                </tr>
                                <tr>
                                    <td><code>/api/user</code></td>
                                    <td><span class="badge bg-primary">GET</span></td>
                                    <td>Informations utilisateur</td>
                                </tr>
                                <tr>
                                    <td><code>/oauth/revoke</code></td>
                                    <td><span class="badge bg-danger">POST</span></td>
                                    <td>Révoquer un token</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-5 text-center">
                    <h2 class="h3 mb-3">Prêt à commencer ?</h2>
                    <p class="mb-4">
                        Créez votre compte développeur pour obtenir vos clés API et accéder à la documentation complète.
                    </p>
                    <a href="{{ route('developers.register') }}" class="btn btn-light btn-lg me-2">
                        <i class="fas fa-user-plus me-2"></i>Créer un compte développeur
                    </a>
                    <a href="{{ route('developers.documentation') }}" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-book me-2"></i>Documentation complète
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
