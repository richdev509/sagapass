@extends('layouts.app')

@section('title', $application->name)

@section('content')
<div class="container py-5">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('developers.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('developers.applications.index') }}">Applications</a></li>
            <li class="breadcrumb-item active">{{ $application->name }}</li>
        </ol>
    </nav>

    {{-- Header avec actions --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="d-flex align-items-start">
            @if($application->logo_path)
                <img src="{{ asset('storage/' . $application->logo_path) }}"
                     alt="{{ $application->name }}"
                     class="rounded me-3"
                     style="width: 80px; height: 80px; object-fit: cover;">
            @else
                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center"
                     style="width: 80px; height: 80px;">
                    <i class="fas fa-cube fa-3x text-muted"></i>
                </div>
            @endif
            <div>
                <h2 class="fw-bold mb-1">{{ $application->name }}</h2>
                <div class="mb-2">
                    @if($application->status === 'approved')
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>Approuvée
                        </span>
                    @elseif($application->status === 'pending')
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-clock me-1"></i>En attente d'approbation
                        </span>
                    @elseif($application->status === 'rejected')
                        <span class="badge bg-danger">
                            <i class="fas fa-times-circle me-1"></i>Rejetée
                        </span>
                    @else
                        <span class="badge bg-secondary">
                            <i class="fas fa-ban me-1"></i>Suspendue
                        </span>
                    @endif

                    @if($application->is_trusted)
                        <span class="badge bg-primary ms-2">
                            <i class="fas fa-shield-check me-1"></i>Application Vérifiée
                        </span>
                    @endif
                </div>
                <p class="text-muted mb-0">
                    <i class="fas fa-globe me-2"></i>
                    <a href="{{ $application->website }}" target="_blank" class="text-decoration-none">
                        {{ $application->website }}
                    </a>
                </p>
            </div>
        </div>
        <div class="btn-group">
            <a href="{{ route('developers.applications.edit', $application) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit me-1"></i>Modifier
            </a>
            <a href="{{ route('developers.applications.stats', $application) }}" class="btn btn-outline-info">
                <i class="fas fa-chart-bar me-1"></i>Statistiques
            </a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash me-1"></i>Supprimer
            </button>
        </div>
    </div>

    {{-- Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Important !</strong> {{ session('warning') }}
            @if($showSecret && $newSecret)
                <div class="mt-3 p-3 bg-white rounded">
                    <strong>Nouveau Client Secret :</strong>
                    <div class="input-group mt-2">
                        <input type="text" class="form-control font-monospace" value="{{ $newSecret }}" readonly id="newSecretInput">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ $newSecret }}')">
                            <i class="fas fa-copy"></i> Copier
                        </button>
                    </div>
                </div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistiques --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h3 class="fw-bold mb-1">{{ $stats['active_users'] }}</h3>
                    <p class="text-muted mb-0 small">Utilisateurs actifs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-double fa-2x text-success mb-2"></i>
                    <h3 class="fw-bold mb-1">{{ $stats['total_authorizations'] }}</h3>
                    <p class="text-muted mb-0 small">Autorisations totales</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-code fa-2x text-info mb-2"></i>
                    <h3 class="fw-bold mb-1">{{ $stats['authorization_codes_issued'] }}</h3>
                    <p class="text-muted mb-0 small">Codes générés</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-key fa-2x text-warning mb-2"></i>
                    <h3 class="fw-bold mb-1">{{ $stats['authorization_codes_used'] }}</h3>
                    <p class="text-muted mb-0 small">Tokens émis</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Credentials OAuth --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-key me-2"></i>
                        Credentials OAuth
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Client ID --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Client ID</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace bg-light"
                                   value="{{ $application->client_id }}"
                                   readonly
                                   id="clientIdInput">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="copyToClipboard('{{ $application->client_id }}')">
                                <i class="fas fa-copy"></i> Copier
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            Utilisez ce Client ID pour identifier votre application
                        </small>
                    </div>

                    {{-- Client Secret --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Client Secret</label>
                        <div class="input-group">
                            <input type="password" class="form-control font-monospace bg-light"
                                   value="••••••••••••••••••••••••••••••••"
                                   readonly
                                   id="clientSecretInput">
                            <button class="btn btn-outline-secondary" type="button" disabled>
                                <i class="fas fa-eye-slash"></i> Masqué
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            Le Client Secret n'est affiché qu'une seule fois lors de la création ou régénération
                        </small>
                    </div>

                    {{-- Bouton régénérer --}}
                    <div class="alert alert-warning">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Régénération du Client Secret
                        </h6>
                        <p class="mb-3 small">
                            La régénération du Client Secret invalidera immédiatement l'ancien secret.
                            Toutes les applications utilisant l'ancien secret devront être mises à jour.
                        </p>
                        <form method="POST" action="{{ route('developers.applications.regenerate-secret', $application) }}"
                              onsubmit="return confirm('Êtes-vous sûr de vouloir régénérer le Client Secret ? L\'ancien secret ne fonctionnera plus.')">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fas fa-sync-alt me-1"></i>
                                Régénérer le Client Secret
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle me-2"></i>
                        Description
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $application->description }}</p>
                </div>
            </div>

            {{-- URIs de redirection --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-link me-2"></i>
                        URIs de redirection autorisées
                    </h5>
                </div>
                <div class="card-body">
                    @if(empty($application->redirect_uris))
                        <p class="text-muted mb-0">Aucune URI de redirection configurée</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($application->redirect_uris as $uri)
                                <li class="list-group-item px-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <code>{{ $uri }}</code>
                                </li>
                            @endforeach
                        </ul>
                        <small class="text-muted d-block mt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Seules ces URLs sont autorisées pour la redirection OAuth
                        </small>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Scopes autorisés --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-shield-alt me-2"></i>
                        Scopes autorisés
                    </h5>
                </div>
                <div class="card-body">
                    @if(empty($application->allowed_scopes))
                        <p class="text-muted mb-0 small">Aucun scope configuré</p>
                    @else
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($application->allowed_scopes as $scope)
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>{{ $scope }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Demandes de scopes en attente --}}
                    @if($application->scopeRequests->isNotEmpty())
                        <div class="alert alert-info small mb-3">
                            <i class="fas fa-clock me-1"></i>
                            Vous avez une demande de scopes en attente de révision
                            <div class="mt-2">
                                <strong>Scopes demandés :</strong>
                                @foreach($application->scopeRequests->first()->requested_scopes as $scope)
                                    <span class="badge bg-primary ms-1">{{ $scope }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Bouton demander scopes --}}
                    <button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#requestScopesModal">
                        <i class="fas fa-plus-circle me-1"></i>
                        Demander des scopes additionnels
                    </button>

                    <small class="text-muted d-block mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Les demandes de scopes sont examinées par l'équipe SAGAPASS
                    </small>
                </div>
            </div>

            {{-- Informations --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-calendar me-2"></i>
                        Informations
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Créée le</small>
                        <strong>{{ $application->created_at->format('d/m/Y à H:i') }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Dernière modification</small>
                        <strong>{{ $application->updated_at->format('d/m/Y à H:i') }}</strong>
                    </div>
                    @if($application->approved_at)
                        <div>
                            <small class="text-muted d-block mb-1">Approuvée le</small>
                            <strong>{{ $application->approved_at->format('d/m/Y à H:i') }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-link me-2"></i>
                        Liens rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('developers.documentation') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-book me-1"></i>
                            Documentation
                        </a>
                        <a href="{{ route('developers.applications.stats', $application) }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-chart-line me-1"></i>
                            Statistiques détaillées
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de suppression --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'application <strong>{{ $application->name }}</strong> ?</p>
                <div class="alert alert-danger mb-0">
                    <strong>Attention :</strong> Cette action est irréversible.
                    <ul class="mb-0 mt-2">
                        <li>Toutes les autorisations utilisateurs seront révoquées</li>
                        <li>Les tokens existants cesseront de fonctionner</li>
                        <li>Les statistiques seront perdues</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="{{ route('developers.applications.destroy', $application) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal de demande de scopes --}}
<div class="modal fade" id="requestScopesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('developers.applications.request-scopes', $application) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt me-2"></i>
                        Demander des scopes additionnels
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Afficher les erreurs de validation --}}
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-circle me-2"></i>Erreur de validation :</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="text-muted mb-4">
                        Sélectionnez les scopes dont votre application a besoin. Votre demande sera examinée par l'équipe SAGAPASS.
                    </p>

                    {{-- Scopes actuels --}}
                    @if(!empty($application->allowed_scopes))
                        <div class="alert alert-info mb-4">
                            <strong><i class="fas fa-check me-2"></i>Scopes actuellement autorisés :</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($application->allowed_scopes as $scope)
                                    <span class="badge bg-success">{{ $scope }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Scopes disponibles --}}
                    <label class="form-label fw-semibold">Scopes à demander</label>
                    <div class="list-group mb-4">
                        @php
                            $availableScopes = [
                                'profile' => [
                                    'name' => 'Profil de base',
                                    'description' => 'Nom, prénom et statut de vérification d\'identité',
                                    'icon' => 'user'
                                ],
                                'email' => [
                                    'name' => 'Adresse email',
                                    'description' => 'Adresse email vérifiée de l\'utilisateur',
                                    'icon' => 'envelope'
                                ],
                                'phone' => [
                                    'name' => 'Numéro de téléphone',
                                    'description' => 'Numéro de téléphone vérifié',
                                    'icon' => 'phone'
                                ],
                                'address' => [
                                    'name' => 'Adresse postale',
                                    'description' => 'Adresse de résidence complète',
                                    'icon' => 'home'
                                ],
                                'birthdate' => [
                                    'name' => 'Date de naissance',
                                    'description' => 'Date de naissance de l\'utilisateur',
                                    'icon' => 'birthday-cake'
                                ],
                                'photo' => [
                                    'name' => 'Photo de profil',
                                    'description' => 'Photo de profil de l\'utilisateur',
                                    'icon' => 'camera'
                                ],
                                'documents' => [
                                    'name' => 'Documents d\'identité',
                                    'description' => 'Vérifier que l\'identité a été confirmée (sans voir les documents)',
                                    'icon' => 'id-card'
                                ],
                            ];
                        @endphp

                        @foreach($availableScopes as $scopeKey => $scopeInfo)
                            @php
                                $isAlreadyGranted = in_array($scopeKey, $application->allowed_scopes ?? []);
                            @endphp
                            <label class="list-group-item {{ $isAlreadyGranted ? 'disabled' : '' }}">
                                <div class="d-flex align-items-start">
                                    <input class="form-check-input me-3 mt-1" type="checkbox"
                                           name="requested_scopes[]"
                                           value="{{ $scopeKey }}"
                                           {{ $isAlreadyGranted ? 'disabled checked' : '' }}>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-{{ $scopeInfo['icon'] }} me-2 text-primary"></i>
                                            <strong>{{ $scopeInfo['name'] }}</strong>
                                            @if($isAlreadyGranted)
                                                <span class="badge bg-success ms-2">Déjà autorisé</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $scopeInfo['description'] }}</small>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    {{-- Justification --}}
                    <div class="mb-3">
                        <label for="justification" class="form-label fw-semibold">
                            Justification <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control"
                                  id="justification"
                                  name="justification"
                                  rows="4"
                                  required
                                  minlength="50"
                                  maxlength="1000"
                                  placeholder="Expliquez pourquoi votre application a besoin de ces scopes additionnels... (minimum 50 caractères)"></textarea>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Minimum 50 caractères requis. Fournissez une justification détaillée pour accélérer le traitement de votre demande.
                        </small>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>À noter :</strong> Demander plus de scopes que nécessaire peut réduire le taux d'acceptation des utilisateurs.
                        Ne demandez que les informations strictement nécessaires au fonctionnement de votre application.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>
                        Envoyer la demande
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copié dans le presse-papiers !');
    });
}

// Compteur de caractères pour la justification
document.addEventListener('DOMContentLoaded', function() {
    // Rouvrir le modal si des erreurs de validation existent
    @if($errors->any())
        const modal = new bootstrap.Modal(document.getElementById('requestScopesModal'));
        modal.show();
    @endif

    const justificationTextarea = document.getElementById('justification');
    if (justificationTextarea) {
        const parentDiv = justificationTextarea.closest('.mb-3');
        const charCounter = document.createElement('small');
        charCounter.className = 'form-text text-muted d-block mt-1';
        charCounter.id = 'charCounter';

        function updateCounter() {
            const length = justificationTextarea.value.length;
            const remaining = 50 - length;

            if (length < 50) {
                charCounter.innerHTML = `<i class="fas fa-exclamation-triangle text-warning me-1"></i><span class="text-warning">${length}/50 caractères - encore ${remaining} caractères requis</span>`;
            } else if (length > 1000) {
                charCounter.innerHTML = `<i class="fas fa-times-circle text-danger me-1"></i><span class="text-danger">${length}/1000 caractères - ${length - 1000} caractères en trop !</span>`;
            } else {
                charCounter.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i><span class="text-success">${length}/1000 caractères</span>`;
            }
        }

        justificationTextarea.addEventListener('input', updateCounter);
        parentDiv.appendChild(charCounter);
        updateCounter();
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
