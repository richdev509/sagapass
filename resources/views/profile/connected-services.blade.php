@extends('layouts.app')

@section('title', 'Services connectés')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- Header --}}
            <div class="mb-4">
                <a href="{{ route('profile.edit') }}" class="text-decoration-none text-muted mb-3 d-inline-block">
                    <i class="fas fa-arrow-left me-2"></i>Retour au profil
                </a>
                <h2 class="fw-bold mb-2">
                    <i class="fas fa-link me-2"></i>
                    Services connectés
                </h2>
                <p class="text-muted">
                    Gérez les applications qui ont accès à votre compte SAGAPASS
                </p>
            </div>

            {{-- Message de succès --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Info --}}
            <div class="alert alert-info mb-4">
                <h6 class="fw-bold mb-2">
                    <i class="fas fa-info-circle me-2"></i>
                    À propos des services connectés
                </h6>
                <p class="mb-0 small">
                    Ces applications ont accès à certaines de vos données SAGAPASS. Vous pouvez révoquer
                    leur accès à tout moment. Une fois révoqué, l'application ne pourra plus accéder
                    à vos informations et devra demander une nouvelle autorisation.
                </p>
            </div>

            {{-- Liste des services --}}
            @if($authorizations->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-unlink fa-4x text-muted mb-4"></i>
                        <h4 class="fw-bold mb-3">Aucun service connecté</h4>
                        <p class="text-muted mb-0">
                            Vous n'avez autorisé aucune application à accéder à votre compte SAGAPASS.<br>
                            Lorsque vous utiliserez "Connect with SAGAPASS" sur un site externe,<br>
                            il apparaîtra ici.
                        </p>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach($authorizations as $auth)
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    {{-- Header avec logo et nom --}}
                                    <div class="d-flex align-items-start mb-3">
                                        @if($auth->application->logo_path)
                                            <img src="{{ asset('storage/' . $auth->application->logo_path) }}"
                                                 alt="{{ $auth->application->name }}"
                                                 class="rounded me-3"
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center"
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-cube fa-2x text-muted"></i>
                                            </div>
                                        @endif
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-1">{{ $auth->application->name }}</h5>
                                            @if($auth->application->is_trusted)
                                                <span class="badge bg-success mb-2">
                                                    <i class="fas fa-shield-check me-1"></i>Vérifiée
                                                </span>
                                            @endif
                                            <p class="text-muted small mb-0">
                                                <i class="fas fa-globe me-1"></i>
                                                <a href="{{ $auth->application->website }}" target="_blank" class="text-decoration-none">
                                                    {{ parse_url($auth->application->website, PHP_URL_HOST) }}
                                                </a>
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Scopes autorisés --}}
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-2 fw-semibold">Données accessibles :</small>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($auth->scopes as $scope)
                                                @php
                                                    $scopeInfo = [
                                                        'profile' => ['name' => 'Profil', 'color' => 'primary'],
                                                        'email' => ['name' => 'Email', 'color' => 'info'],
                                                        'phone' => ['name' => 'Téléphone', 'color' => 'success'],
                                                        'address' => ['name' => 'Adresse', 'color' => 'warning'],
                                                        'documents' => ['name' => 'Documents', 'color' => 'danger'],
                                                    ];
                                                    $info = $scopeInfo[$scope] ?? ['name' => $scope, 'color' => 'secondary'];
                                                @endphp
                                                <span class="badge bg-{{ $info['color'] }} bg-opacity-10 text-{{ $info['color'] }}">
                                                    {{ $info['name'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Informations de connexion --}}
                                    <div class="border-top pt-3 mb-3">
                                        <div class="row g-2 small text-muted">
                                            <div class="col-6">
                                                <i class="fas fa-calendar-check me-1"></i>
                                                Autorisé le
                                                <div class="fw-semibold text-dark">{{ $auth->granted_at->format('d/m/Y') }}</div>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-clock me-1"></i>
                                                Dernière utilisation
                                                <div class="fw-semibold text-dark">{{ $auth->granted_at->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Bouton révoquer --}}
                                    <button type="button"
                                            class="btn btn-outline-danger w-100"
                                            data-bs-toggle="modal"
                                            data-bs-target="#revokeModal{{ $auth->id }}">
                                        <i class="fas fa-times-circle me-1"></i>
                                        Révoquer l'accès
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Modal de confirmation --}}
                        <div class="modal fade" id="revokeModal{{ $auth->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                            Révoquer l'accès
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>
                                            Êtes-vous sûr de vouloir révoquer l'accès de
                                            <strong>{{ $auth->application->name }}</strong> à votre compte SAGAPASS ?
                                        </p>
                                        <div class="alert alert-warning mb-0">
                                            <strong>Conséquences :</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>L'application ne pourra plus accéder à vos données</li>
                                                <li>Vous devrez vous reconnecter si vous utilisez à nouveau cette application</li>
                                                <li>Une nouvelle demande d'autorisation sera nécessaire</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Annuler
                                        </button>
                                        <form method="POST" action="{{ route('profile.revoke-service', $auth) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-times-circle me-1"></i>
                                                Révoquer définitivement
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Liens rapides --}}
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="fw-bold mb-1">
                                    <i class="fas fa-history me-2"></i>
                                    Voir l'historique complet
                                </h6>
                                <p class="text-muted mb-0 small">
                                    Consultez toutes vos autorisations passées, y compris celles que vous avez révoquées
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('profile.connection-history') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-1"></i>
                                    Voir l'historique
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
