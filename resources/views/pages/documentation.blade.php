@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-book text-primary me-3"></i>Documentation
                </h1>
                <p class="lead text-muted">Tout ce que vous devez savoir sur SAGAPASS</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-user-circle fa-4x text-primary mb-3"></i>
                            <h4>Pour les utilisateurs</h4>
                            <p class="text-muted">Guides et tutoriels pour créer et gérer votre compte SAGAPASS</p>
                            <a href="#users" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-right me-1"></i>Voir les guides
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-code fa-4x text-primary mb-3"></i>
                            <h4>Pour les développeurs</h4>
                            <p class="text-muted">Documentation API et intégration OAuth2</p>
                            <a href="{{ route('developers.documentation') }}" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-right me-1"></i>Documentation API
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-building fa-4x text-primary mb-3"></i>
                            <h4>Pour les entreprises</h4>
                            <p class="text-muted">Solutions d'authentification pour votre organisation</p>
                            <a href="#business" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-right me-1"></i>En savoir plus
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="h3 mb-4" id="users"><i class="fas fa-graduation-cap text-primary me-2"></i>Guides utilisateurs</h2>

            <div class="accordion mb-5" id="userGuides">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#guide1">
                            <i class="fas fa-user-plus text-primary me-2"></i>Comment créer mon compte SAGAPASS ?
                        </button>
                    </h2>
                    <div id="guide1" class="accordion-collapse collapse show" data-bs-parent="#userGuides">
                        <div class="accordion-body">
                            <ol>
                                <li><strong>Étape 1 :</strong> Remplissez vos informations personnelles (nom, prénom, email, mot de passe)</li>
                                <li><strong>Étape 2 :</strong> Prenez une photo de profil avec votre webcam</li>
                                <li><strong>Étape 3 :</strong> Enregistrez une vidéo de vérification (5 secondes)</li>
                                <li><strong>Confirmation :</strong> Vérifiez votre email et connectez-vous</li>
                            </ol>
                            <a href="{{ route('register.basic.step1') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-rocket me-1"></i>Commencer maintenant
                            </a>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guide2">
                            <i class="fas fa-shield-alt text-primary me-2"></i>Comment activer l'authentification à deux facteurs ?
                        </button>
                    </h2>
                    <div id="guide2" class="accordion-collapse collapse" data-bs-parent="#userGuides">
                        <div class="accordion-body">
                            <ol>
                                <li>Connectez-vous à votre compte</li>
                                <li>Allez dans <strong>Profil → Sécurité</strong></li>
                                <li>Activez l'authentification à deux facteurs (2FA)</li>
                                <li>Scannez le QR code avec Google Authenticator</li>
                                <li>Entrez le code de vérification</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guide3">
                            <i class="fas fa-file-upload text-primary me-2"></i>Comment passer en compte vérifié ?
                        </button>
                    </h2>
                    <div id="guide3" class="accordion-collapse collapse" data-bs-parent="#userGuides">
                        <div class="accordion-body">
                            <p>Pour passer de <strong>SAGAPASS Basic</strong> à <strong>SAGAPASS Vérifié</strong> :</p>
                            <ol>
                                <li>Connectez-vous à votre dashboard</li>
                                <li>Cliquez sur <strong>"Ajouter un document"</strong></li>
                                <li>Téléchargez une photo recto-verso de votre pièce d'identité</li>
                                <li>Attendez la validation par notre équipe (24-48h)</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guide4">
                            <i class="fas fa-link text-primary me-2"></i>Comment connecter une application à mon SAGAPASS ?
                        </button>
                    </h2>
                    <div id="guide4" class="accordion-collapse collapse" data-bs-parent="#userGuides">
                        <div class="accordion-body">
                            <ol>
                                <li>Sur l'application partenaire, cliquez sur <strong>"Se connecter avec SAGAPASS"</strong></li>
                                <li>Vous serez redirigé vers la page de connexion SAGAPASS</li>
                                <li>Connectez-vous avec vos identifiants</li>
                                <li>Autorisez l'accès aux informations demandées</li>
                                <li>Vous serez redirigé vers l'application avec votre compte connecté</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-5 text-center">
                    <h2 class="h3 mb-3">Besoin d'aide ?</h2>
                    <p class="mb-4">
                        Vous ne trouvez pas la réponse à votre question ? Contactez notre équipe support.
                    </p>
                    <a href="{{ route('support') }}" class="btn btn-light btn-lg me-2">
                        <i class="fas fa-life-ring me-2"></i>Support
                    </a>
                    <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>Contact
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
