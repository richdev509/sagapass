@extends('layouts.app')

@section('title', 'Mes Applications')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-cube me-2"></i>
                Mes Applications OAuth
            </h2>
            <p class="text-muted mb-0">Gérez vos applications et leurs credentials</p>
        </div>
        <a href="{{ route('developers.applications.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Nouvelle Application
        </a>
    </div>

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tous les statuts</option>
                        <option value="approved">Approuvées</option>
                        <option value="pending">En attente</option>
                        <option value="rejected">Rejetées</option>
                        <option value="suspended">Suspendues</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" placeholder="Rechercher une application..." id="searchInput">
                </div>
                <div class="col-md-3">
                    <a href="{{ route('developers.dashboard') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour au dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Liste des applications --}}
    @if($applications->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-cube fa-4x text-muted mb-4"></i>
                <h4 class="fw-bold mb-3">Aucune application</h4>
                <p class="text-muted mb-4">
                    Vous n'avez pas encore créé d'application OAuth.<br>
                    Créez-en une pour permettre aux utilisateurs de se connecter avec SAGAPASS.
                </p>
                <a href="{{ route('developers.applications.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Créer ma première application
                </a>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($applications as $app)
                <div class="col-md-6 col-lg-4 application-item" data-status="{{ $app->status }}">
                    <div class="card border-0 shadow-sm h-100 hover-card">
                        <div class="card-body">
                            {{-- Logo et nom --}}
                            <div class="d-flex align-items-start mb-3">
                                @if($app->logo_path)
                                    <img src="{{ asset('storage/' . $app->logo_path) }}"
                                         alt="{{ $app->name }}"
                                         class="rounded me-3"
                                         style="width: 60px; height: 60px; object-fit: cover;">
                                @else
                                    <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center"
                                         style="width: 60px; height: 60px;">
                                        <i class="fas fa-cube fa-2x text-muted"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1">{{ $app->name }}</h5>
                                    @if($app->is_trusted)
                                        <span class="badge bg-success mb-2">
                                            <i class="fas fa-shield-check me-1"></i>Vérifiée
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Description --}}
                            <p class="text-muted small mb-3">
                                {{ Str::limit($app->description, 80) }}
                            </p>

                            {{-- Statut --}}
                            <div class="mb-3">
                                @if($app->status === 'approved')
                                    <span class="badge bg-success w-100 py-2">
                                        <i class="fas fa-check-circle me-1"></i>Approuvée
                                    </span>
                                @elseif($app->status === 'pending')
                                    <span class="badge bg-warning text-dark w-100 py-2">
                                        <i class="fas fa-clock me-1"></i>En attente d'approbation
                                    </span>
                                @elseif($app->status === 'rejected')
                                    <span class="badge bg-danger w-100 py-2">
                                        <i class="fas fa-times-circle me-1"></i>Rejetée
                                    </span>
                                @else
                                    <span class="badge bg-secondary w-100 py-2">
                                        <i class="fas fa-ban me-1"></i>Suspendue
                                    </span>
                                @endif
                            </div>

                            {{-- Statistiques --}}
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="bg-light rounded p-2 text-center">
                                        <div class="fw-bold text-primary">
                                            {{ $app->userAuthorizations()->whereNull('revoked_at')->count() }}
                                        </div>
                                        <small class="text-muted">Utilisateurs actifs</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2 text-center">
                                        <div class="fw-bold text-info">
                                            {{ $app->authorizationCodes()->where('used', true)->count() }}
                                        </div>
                                        <small class="text-muted">Connexions</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Client ID --}}
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Client ID</small>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control font-monospace"
                                           value="{{ $app->client_id }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="copyToClipboard('{{ $app->client_id }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="d-grid gap-2">
                                <a href="{{ route('developers.applications.show', $app) }}"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>
                                    Voir les détails
                                </a>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('developers.applications.edit', $app) }}"
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-edit me-1"></i>Modifier
                                    </a>
                                    <a href="{{ route('developers.applications.stats', $app) }}"
                                       class="btn btn-outline-info">
                                        <i class="fas fa-chart-bar me-1"></i>Stats
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light border-top-0">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Créée {{ $app->created_at->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $applications->links() }}
        </div>
    @endif
</div>

<style>
    .hover-card {
        transition: all 0.3s ease;
    }
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
    }
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Client ID copié dans le presse-papiers !');
    });
}

// Filtre par statut
document.getElementById('statusFilter').addEventListener('change', function() {
    const status = this.value;
    const items = document.querySelectorAll('.application-item');

    items.forEach(item => {
        if (status === '' || item.dataset.status === status) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Recherche
document.getElementById('searchInput').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    const items = document.querySelectorAll('.application-item');

    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(search)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>
@endsection
