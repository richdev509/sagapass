@extends('layouts.app')

@section('title', 'Autorisation - Connexion à ' . $application->name)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 text-center">
                    {{-- Logo de l'application --}}
                    @if($application->logo_path)
                        <img src="{{ asset('storage/' . $application->logo_path) }}"
                             alt="{{ $application->name }}"
                             class="mb-4"
                             style="max-width: 100px; border-radius: 15px;">
                    @else
                        <div class="mb-4">
                            <i class="fas fa-cube fa-4x text-primary"></i>
                        </div>
                    @endif

                    {{-- Titre --}}
                    <h3 class="fw-bold mb-2">{{ $application->name }}</h3>

                    {{-- Badge vérifié --}}
                    @if($application->is_trusted)
                        <div class="mb-3">
                            <span class="badge bg-success">
                                <i class="fas fa-shield-check me-1"></i>
                                Application Vérifiée
                            </span>
                        </div>
                    @endif

                    {{-- Site web --}}
                    <p class="text-muted mb-4">
                        <small>
                            <i class="fas fa-globe me-1"></i>
                            <a href="{{ $application->website }}" target="_blank" class="text-decoration-none">
                                {{ parse_url($application->website, PHP_URL_HOST) }}
                            </a>
                        </small>
                    </p>

                    <hr class="my-4">

                    {{-- Message principal --}}
                    <h5 class="mb-4">
                        <strong>{{ $application->name }}</strong> souhaite accéder à votre compte SAGAPASS
                    </h5>

                    {{-- Statut de vérification de l'utilisateur --}}
                    @if($user->isVerified())
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-check-circle me-2"></i>
                            Votre identité est <strong>vérifiée</strong>
                        </div>
                    @else
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Votre identité n'est <strong>pas encore vérifiée</strong>
                        </div>
                    @endif

                    {{-- Permissions demandées --}}
                    <div class="text-start mb-4">
                        <p class="fw-semibold mb-3">Cette application pourra :</p>
                        <ul class="list-unstyled">
                            @foreach($scopes as $scopeKey => $scopeInfo)
                                <li class="mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <i class="fas fa-{{ $scopeInfo['icon'] }} fa-lg text-primary"></i>
                                        </div>
                                        <div>
                                            <strong>{{ $scopeInfo['name'] }}</strong>
                                            <p class="text-muted mb-0 small">{{ $scopeInfo['description'] }}</p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Note importante --}}
                    <div class="alert alert-info text-start mb-4">
                        <small>
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note :</strong> Vous pouvez révoquer cet accès à tout moment depuis vos paramètres de compte.
                        </small>
                    </div>

                    {{-- Formulaire de décision --}}
                    <form method="POST" action="{{ route('oauth.authorize.decision') }}">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $params['client_id'] }}">
                        <input type="hidden" name="redirect_uri" value="{{ $params['redirect_uri'] }}">
                        <input type="hidden" name="scope" value="{{ $params['scope'] }}">
                        <input type="hidden" name="state" value="{{ $params['state'] }}">
                        <input type="hidden" name="code_challenge" value="{{ $params['code_challenge'] }}">
                        <input type="hidden" name="code_challenge_method" value="{{ $params['code_challenge_method'] }}">

                        <div class="d-grid gap-3">
                            {{-- Bouton Autoriser --}}
                            <button type="submit" name="action" value="approve" class="btn btn-lg btn-primary-custom">
                                <i class="fas fa-check me-2"></i>
                                Autoriser
                            </button>

                            {{-- Bouton Refuser --}}
                            <button type="submit" name="action" value="deny" class="btn btn-lg btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                Refuser
                            </button>
                        </div>
                    </form>

                    {{-- Lien d'aide --}}
                    <div class="mt-4">
                        <small class="text-muted">
                            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#helpModal">
                                <i class="fas fa-question-circle me-1"></i>
                                Qu'est-ce que cela signifie ?
                            </a>
                        </small>
                    </div>
                </div>
            </div>

            {{-- Informations sur SAGAPASS --}}
            <div class="text-center mt-4">
                <small class="text-muted">
                    Propulsé par <strong>SAGAPASS</strong> - Votre identité numérique sécurisée
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Modal d'aide --}}
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">
                    <i class="fas fa-question-circle me-2"></i>
                    Comprendre les autorisations OAuth
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold">Qu'est-ce que c'est ?</h6>
                <p>
                    Cette demande permet à <strong>{{ $application->name }}</strong> de vérifier votre identité
                    via SAGAPASS, sans que l'application n'ait accès à votre mot de passe.
                </p>

                <h6 class="fw-bold mt-3">Est-ce sécurisé ?</h6>
                <p>
                    Oui ! SAGAPASS utilise le protocole OAuth2, un standard international de sécurité.
                    L'application ne peut accéder qu'aux informations que vous autorisez.
                </p>

                <h6 class="fw-bold mt-3">Puis-je révoquer l'accès plus tard ?</h6>
                <p>
                    Absolument ! Vous pouvez à tout moment révoquer l'accès de cette application depuis
                    <a href="{{ route('profile.connected-services') }}">vos paramètres de compte</a>.
                </p>

                <h6 class="fw-bold mt-3">Que se passe-t-il si je refuse ?</h6>
                <p>
                    Vous serez redirigé vers <strong>{{ $application->name }}</strong> et aucune information
                    ne sera partagée. Vous ne pourrez pas utiliser SAGAPASS pour vous connecter à cette application.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        transition: all 0.3s;
    }
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
