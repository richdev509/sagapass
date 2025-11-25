@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-life-ring text-primary me-3"></i>Support
                </h1>
                <p class="lead text-muted">Nous sommes là pour vous aider</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <i class="fas fa-book-open fa-3x text-primary mb-3"></i>
                            <h4>Documentation</h4>
                            <p class="text-muted">Consultez nos guides et tutoriels</p>
                            <a href="{{ route('documentation') }}" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-right me-1"></i>Voir la documentation
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                            <h4>Contactez-nous</h4>
                            <p class="text-muted">Envoyez-nous un message</p>
                            <a href="{{ route('contact') }}" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-right me-1"></i>Formulaire de contact
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-4"><i class="fas fa-question-circle text-primary me-2"></i>Questions fréquentes</h2>

                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Comment réinitialiser mon mot de passe ?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Cliquez sur "Mot de passe oublié ?" sur la page de connexion, entrez votre email
                                    et suivez les instructions reçues par email.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Mon compte est-il sécurisé ?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Oui, nous utilisons les dernières technologies de cryptage et vous pouvez activer
                                    l'authentification à deux facteurs pour une sécurité renforcée.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Combien de temps prend la vérification de mon document ?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    La vérification prend généralement entre 24 et 48 heures. Vous serez notifié par email
                                    dès que votre document sera validé.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Puis-je supprimer mon compte ?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Oui, vous pouvez demander la suppression de votre compte en nous contactant.
                                    Toutes vos données seront définitivement supprimées sous 30 jours.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h3 class="h5 mb-3"><i class="fas fa-clock text-primary me-2"></i>Horaires de support</h3>
                    <ul class="mb-0">
                        <li><strong>Lundi - Vendredi :</strong> 9h00 - 17h00 (HNE)</li>
                        <li><strong>Samedi :</strong> 10h00 - 14h00 (HNE)</li>
                        <li><strong>Dimanche :</strong> Fermé</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-4 text-center">
                    <h3 class="h4 mb-3">Support prioritaire</h3>
                    <p class="mb-3">
                        Les utilisateurs connectés bénéficient d'un support prioritaire avec temps de réponse garanti.
                    </p>
                    <a href="{{ route('login') }}" class="btn btn-light">
                        <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
