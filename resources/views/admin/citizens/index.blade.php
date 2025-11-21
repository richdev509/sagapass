@extends('admin.layouts.admin')

@section('title', 'Gestion des Citoyens')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users"></i> Gestion des Citoyens
        </h1>
        @can('export-users', 'admin')
        <a href="{{ route('admin.citizens.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-download"></i> Exporter en CSV
        </a>
        @endcan
    </div>

    <!-- Messages Flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Citoyens
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ App\Models\User::count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Citoyens Vérifiés
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ App\Models\User::where('verification_status', 'verified')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Comptes Actifs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ App\Models\User::where('account_status', 'active')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Développeurs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ App\Models\User::where('is_developer', true)->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-code fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres de recherche -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-search"></i> Recherche et Filtres
            </h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.citizens.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="search" class="form-label">Recherche globale</label>
                        <input type="text" class="form-control" id="search" name="search"
                               value="{{ request('search') }}"
                               placeholder="Nom, email, téléphone, adresse...">
                        <small class="form-text text-muted">Recherchez par nom, email, téléphone ou adresse</small>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="verification_status" class="form-label">Statut Vérification</label>
                        <select class="form-select" id="verification_status" name="verification_status">
                            <option value="">Tous</option>
                            <option value="pending" {{ request('verification_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="verified" {{ request('verification_status') == 'verified' ? 'selected' : '' }}>Vérifié</option>
                            <option value="rejected" {{ request('verification_status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="account_status" class="form-label">Statut Compte</label>
                        <select class="form-select" id="account_status" name="account_status">
                            <option value="">Tous</option>
                            <option value="active" {{ request('account_status') == 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="suspended" {{ request('account_status') == 'suspended' ? 'selected' : '' }}>Suspendu</option>
                            <option value="inactive" {{ request('account_status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="email_verified" class="form-label">Email Vérifié</label>
                        <select class="form-select" id="email_verified" name="email_verified">
                            <option value="">Tous</option>
                            <option value="verified" {{ request('email_verified') == 'verified' ? 'selected' : '' }}>Vérifié</option>
                            <option value="unverified" {{ request('email_verified') == 'unverified' ? 'selected' : '' }}>Non vérifié</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="is_developer" class="form-label">Type</label>
                        <select class="form-select" id="is_developer" name="is_developer">
                            <option value="">Tous</option>
                            <option value="1" {{ request('is_developer') == '1' ? 'selected' : '' }}>Développeurs uniquement</option>
                            <option value="0" {{ request('is_developer') == '0' ? 'selected' : '' }}>Non-développeurs</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="sort_by" class="form-label">Trier par</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="created_at" {{ request('sort_by', 'created_at') == 'created_at' ? 'selected' : '' }}>Date d'inscription</option>
                            <option value="first_name" {{ request('sort_by') == 'first_name' ? 'selected' : '' }}>Prénom</option>
                            <option value="last_name" {{ request('sort_by') == 'last_name' ? 'selected' : '' }}>Nom</option>
                            <option value="email" {{ request('sort_by') == 'email' ? 'selected' : '' }}>Email</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="sort_order" class="form-label">Ordre</label>
                        <select class="form-select" id="sort_order" name="sort_order">
                            <option value="desc" {{ request('sort_order', 'desc') == 'desc' ? 'selected' : '' }}>Décroissant</option>
                            <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Croissant</option>
                        </select>
                    </div>

                    <div class="col-md-7 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        <a href="{{ route('admin.citizens.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des citoyens -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table"></i> Liste des Citoyens ({{ $citizens->total() }} résultats)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom Complet</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Statut Vérification</th>
                            <th>Statut Compte</th>
                            <th>Type</th>
                            <th>Date Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($citizens as $citizen)
                        <tr>
                            <td>{{ $citizen->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($citizen->profile_photo)
                                        <img src="{{ asset('storage/' . $citizen->profile_photo) }}"
                                             class="rounded-circle me-2" width="32" height="32" alt="Photo">
                                    @else
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
                                             style="width: 32px; height: 32px;">
                                            {{ strtoupper(substr($citizen->first_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span>{{ $citizen->first_name }} {{ $citizen->last_name }}</span>
                                </div>
                            </td>
                            <td>
                                {{ $citizen->email }}
                                @if($citizen->email_verified_at)
                                    <i class="fas fa-check-circle text-success" title="Email vérifié"></i>
                                @endif
                            </td>
                            <td>{{ $citizen->phone ?? '-' }}</td>
                            <td>
                                @php
                                    $badgeClass = match($citizen->verification_status) {
                                        'verified' => 'success',
                                        'pending' => 'warning',
                                        'rejected' => 'danger',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">
                                    {{ ucfirst($citizen->verification_status) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $statusBadge = match($citizen->account_status) {
                                        'active' => 'success',
                                        'suspended' => 'danger',
                                        'inactive' => 'secondary',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusBadge }}">
                                    {{ ucfirst($citizen->account_status) }}
                                </span>
                            </td>
                            <td>
                                @if($citizen->is_developer)
                                    <span class="badge bg-info">
                                        <i class="fas fa-code"></i> Développeur
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Citoyen</span>
                                @endif
                            </td>
                            <td>{{ $citizen->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    @can('view-user-details', 'admin')
                                    <a href="{{ route('admin.citizens.show', $citizen->id) }}"
                                       class="btn btn-sm btn-info" title="Voir le profil">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endcan

                                    @can('suspend-users', 'admin')
                                        @if($citizen->account_status !== 'suspended')
                                        <button type="button" class="btn btn-sm btn-warning"
                                                onclick="suspendCitizen({{ $citizen->id }})" title="Suspendre">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                        @endif
                                    @endcan

                                    @can('activate-users', 'admin')
                                        @if($citizen->account_status === 'suspended')
                                        <form action="{{ route('admin.citizens.activate', $citizen->id) }}"
                                              method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Activer">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="fas fa-inbox fa-3x"></i>
                                <p class="mt-2">Aucun citoyen trouvé</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $citizens->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal de suspension -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="suspendForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Suspendre le compte citoyen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Êtes-vous sûr de vouloir suspendre ce compte citoyen ?
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Raison de la suspension *</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required
                                  placeholder="Expliquez la raison de la suspension..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-pause"></i> Suspendre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function suspendCitizen(citizenId) {
    const modal = new bootstrap.Modal(document.getElementById('suspendModal'));
    const form = document.getElementById('suspendForm');
    form.action = `/admin/citizens/${citizenId}/suspend`;
    modal.show();
}
</script>
@endpush

@endsection
