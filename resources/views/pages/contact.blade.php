@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-envelope text-primary me-3"></i>Contactez-nous
                </h1>
                <p class="lead text-muted">Nous sommes là pour vous aider</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                            <h5>Email</h5>
                            <p class="text-muted mb-0">
                                <a href="mailto:sagapass@sagapass.com" class="text-decoration-none">
                                    sagapass@sagapass.com
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <i class="fas fa-phone fa-3x text-primary mb-3"></i>
                            <h5>Téléphone</h5>
                            <p class="text-muted mb-0">
                                +509 XXXX-XXXX<br>
                                <small>(Lun-Ven 9h-17h)</small>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                            <h5>Adresse</h5>
                            <p class="text-muted mb-0">
                                Port-au-Prince<br>
                                Haïti
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-4">Envoyez-nous un message</h2>

                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="name" required>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="phone">
                            </div>

                            <div class="col-md-6">
                                <label for="subject" class="form-label">Sujet *</label>
                                <select class="form-select" id="subject" required>
                                    <option value="">Choisir...</option>
                                    <option value="support">Support technique</option>
                                    <option value="billing">Facturation</option>
                                    <option value="partnership">Partenariat</option>
                                    <option value="careers">Carrières</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" rows="5" required></textarea>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Le formulaire de contact est en cours de configuration.
                                    Pour le moment, veuillez nous écrire directement à
                                    <a href="mailto:sagapass@sagapass.com">sagapass@sagapass.com</a>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="button" class="btn btn-primary btn-lg" disabled>
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer (bientôt disponible)
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-4 text-center">
                    <h3 class="h4 mb-3">Support prioritaire</h3>
                    <p class="mb-3">
                        Besoin d'une assistance immédiate ? Connectez-vous à votre compte pour accéder
                        au support prioritaire et au chat en direct.
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
