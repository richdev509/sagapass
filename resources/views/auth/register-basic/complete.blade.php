@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-check-circle"></i> Compte créé avec succès !</h4>
                </div>

                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>

                    <h3 class="text-success mb-4">
                        <i class="fas fa-user-check"></i> Bienvenue sur SagaPass !
                    </h3>

                    <div class="alert alert-info text-start">
                        <h5><i class="fas fa-info-circle"></i> Votre compte est en cours de vérification</h5>
                        <p class="mb-2">Nous avons bien reçu :</p>
                        <ul class="mb-0">
                            <li><i class="fas fa-check text-success"></i> Vos informations personnelles</li>
                            <li><i class="fas fa-check text-success"></i> Votre photo de profil</li>
                            <li><i class="fas fa-check text-success"></i> Votre vidéo de vérification</li>
                        </ul>
                    </div>

                    <div class="card bg-light mt-4">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-clock"></i> Prochaines étapes</h5>
                            <div class="text-start">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-primary rounded-circle" style="width: 30px; height: 30px; line-height: 30px;">1</span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <strong>Vérifiez votre email</strong>
                                        <p class="text-muted mb-0">Un email de vérification a été envoyé à <strong>{{ auth()->user()->email }}</strong></p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start mb-3">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-warning rounded-circle" style="width: 30px; height: 30px; line-height: 30px;">2</span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <strong>Validation de votre vidéo</strong>
                                        <p class="text-muted mb-0">Nos équipes vont vérifier votre vidéo sous 24-48 heures</p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-success rounded-circle" style="width: 30px; height: 30px; line-height: 30px;">3</span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <strong>Activation de votre compte</strong>
                                        <p class="text-muted mb-0">Vous recevrez un email dès que votre compte sera activé</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-4 text-start">
                        <h6><i class="fas fa-star"></i> Votre compte SagaPass Basic</h6>
                        <p class="mb-0">
                            Vous avez créé un compte <strong>Basic</strong>. Après validation de votre vidéo, 
                            vous pourrez passer à un compte <strong>Vérifié</strong> en ajoutant vos documents d'identité 
                            (CNI ou Passeport) pour accéder à tous les services.
                        </p>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-home"></i> Accéder à mon dashboard
                        </a>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            Vous n'avez pas reçu l'email ? 
                            <form method="POST" action="{{ route('verification.resend') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm p-0">Renvoyer l'email</button>
                            </form>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Informations supplémentaires -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-question-circle"></i> Questions fréquentes</h5>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Combien de temps prend la vérification ?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    La vérification de votre vidéo prend généralement entre 24 et 48 heures. 
                                    Vous recevrez un email dès que votre compte sera activé.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Que se passe-t-il si ma vidéo est rejetée ?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Si votre vidéo ne respecte pas les critères (visage non visible, pas de son, etc.), 
                                    vous recevrez un email avec la raison du rejet et pourrez soumettre une nouvelle vidéo 
                                    depuis votre profil.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Quelle est la différence entre Basic et Vérifié ?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <strong>SagaPass Basic :</strong> Accès limité aux services, avec photo et vidéo.<br>
                                    <strong>SagaPass Vérifié :</strong> Accès complet à tous les services après validation de vos documents d'identité (CNI/Passeport).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
