@extends('layouts.app')

@section('title', 'Developer Dashboard')

@section('content')
<div class="container-fluid py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 200px;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="text-white fw-bold mb-2">
                    <i class="fas fa-code me-2"></i>
                    Developer Dashboard
                </h1>
                <p class="text-white-50 mb-0">
                    Gérez vos applications OAuth et intégrez "Connect with SAGAPASS"
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('developers.applications.create') }}" class="btn btn-light btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nouvelle Application
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    {{-- Statistiques --}}
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-cube fa-3x text-primary"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $stats['total_applications'] }}</h3>
                    <p class="text-muted mb-0">Applications</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $stats['approved_applications'] }}</h3>
                    <p class="text-muted mb-0">Approuvées</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-clock fa-3x text-warning"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $stats['pending_applications'] }}</h3>
                    <p class="text-muted mb-0">En attente</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-users fa-3x text-info"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $stats['total_users'] }}</h3>
                    <p class="text-muted mb-0">Utilisateurs connectés</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-book fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-2">Documentation</h5>
                            <p class="text-muted mb-3">
                                Guides complets, exemples de code et références API
                            </p>
                            <a href="{{ route('developers.documentation') }}" class="btn btn-sm btn-outline-primary">
                                Voir la documentation <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="fas fa-play-circle fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-2">Démarrage rapide</h5>
                            <p class="text-muted mb-3">
                                Intégrez SAGAPASS en quelques minutes
                            </p>
                            <a href="{{ route('developers.documentation') }}#quickstart" class="btn btn-sm btn-outline-success">
                                Commencer <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="fas fa-life-ring fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-2">Support</h5>
                            <p class="text-muted mb-3">
                                Besoin d'aide ? Contactez notre équipe
                            </p>
                            <a href="mailto:developers@saga-id.sn" class="btn btn-sm btn-outline-info">
                                Contacter le support <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mes Applications --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-list me-2"></i>
                    Mes Applications
                </h5>
                <a href="{{ route('developers.applications.index') }}" class="btn btn-sm btn-outline-primary">
                    Voir toutes <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($applications->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-cube fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune application créée</h5>
                    <p class="text-muted mb-4">
                        Créez votre première application OAuth pour commencer à utiliser SAGAPASS
                    </p>
                    <a href="{{ route('developers.applications.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Créer une application
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Client ID</th>
                                <th>Statut</th>
                                <th>Utilisateurs</th>
                                <th>Créée le</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications->take(5) as $app)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($app->logo_path)
                                                <img src="{{ asset('storage/' . $app->logo_path) }}"
                                                     alt="{{ $app->name }}"
                                                     class="rounded me-2"
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-cube text-muted"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-semibold">{{ $app->name }}</div>
                                                <small class="text-muted">{{ Str::limit($app->description, 40) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code class="small">{{ Str::limit($app->client_id, 20) }}</code>
                                    </td>
                                    <td>
                                        @if($app->status === 'approved')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Approuvée
                                            </span>
                                        @elseif($app->status === 'pending')
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>En attente
                                            </span>
                                        @elseif($app->status === 'rejected')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i>Rejetée
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-ban me-1"></i>Suspendue
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <i class="fas fa-users text-muted me-1"></i>
                                        {{ $app->userAuthorizations()->whereNull('revoked_at')->count() }}
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $app->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('developers.applications.show', $app) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('developers.applications.stats', $app) }}"
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
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
@endsection
