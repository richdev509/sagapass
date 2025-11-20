@extends('admin.layouts.admin')

@section('title', 'Gestion OAuth')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête avec statistiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">
                    <i class="fas fa-plug text-primary"></i>
                    Applications OAuth
                </h2>
                <a href="{{ route('admin.oauth.scope-requests') }}" class="btn btn-outline-primary">
                    <i class="fas fa-shield-alt me-1"></i>
                    Demandes de Scopes
                    @php
                        $pendingRequests = \App\Models\ScopeRequest::where('status', 'pending')->count();
                    @endphp
                    @if($pendingRequests > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $pendingRequests }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    <small>Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['pending'] }}</h3>
                    <small>En attente</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['approved'] }}</h3>
                    <small>Approuvées</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['rejected'] }}</h3>
                    <small>Rejetées</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['suspended'] }}</h3>
                    <small>Suspendues</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.oauth.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approuvées</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejetées</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspendues</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nom app ou email développeur..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trier par</label>
                    <select name="sort_by" class="form-select">
                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Date création</option>
                        <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Nom</option>
                        <option value="user_authorizations_count" {{ request('sort_by') == 'user_authorizations_count' ? 'selected' : '' }}>Utilisateurs</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Ordre</label>
                    <select name="sort_order" class="form-select">
                        <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>↓</option>
                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>↑</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.oauth.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des applications -->
    <div class="card">
        <div class="card-body">
            @if($applications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Développeur</th>
                                <th>Statut</th>
                                <th>Utilisateurs</th>
                                <th>Codes générés</th>
                                <th>Date création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $app)
                                <tr>
                                    <td>
                                        <strong>{{ $app->name }}</strong><br>
                                        <small class="text-muted">{{ Str::limit($app->description, 50) }}</small>
                                    </td>
                                    <td>
                                        {{ $app->user->first_name }} {{ $app->user->last_name }}<br>
                                        <small class="text-muted">{{ $app->user->email }}</small>
                                    </td>
                                    <td>
                                        @if($app->status === 'pending')
                                            <span class="badge bg-warning">En attente</span>
                                        @elseif($app->status === 'approved')
                                            <span class="badge bg-success">Approuvée</span>
                                        @elseif($app->status === 'rejected')
                                            <span class="badge bg-danger">Rejetée</span>
                                        @elseif($app->status === 'suspended')
                                            <span class="badge bg-secondary">Suspendue</span>
                                        @endif
                                    </td>
                                    <td>
                                        <i class="fas fa-users text-primary"></i>
                                        {{ $app->user_authorizations_count }}
                                    </td>
                                    <td>
                                        <i class="fas fa-key text-info"></i>
                                        {{ $app->authorization_codes_count }}
                                    </td>
                                    <td>
                                        <small>{{ $app->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.oauth.show', $app) }}"
                                           class="btn btn-sm btn-info"
                                           title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $applications->withQueryString()->links() }}
                </div>
            @else
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i>
                    Aucune application trouvée.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
