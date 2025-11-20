@extends('admin.layouts.admin')

@section('title', 'Demandes de Scopes')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-shield-alt me-2"></i>
                Demandes de Scopes OAuth
            </h2>
            <p class="text-muted mb-0">
                Gérez les demandes de scopes additionnels des développeurs
            </p>
        </div>
        <a href="{{ route('admin.oauth.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Retour aux applications
        </a>
    </div>

    {{-- Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approuvées</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejetées</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h3 class="fw-bold mb-1">{{ $scopeRequests->where('status', 'pending')->count() }}</h3>
                    <p class="text-muted mb-0 small">En attente</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h3 class="fw-bold mb-1">{{ $scopeRequests->where('status', 'approved')->count() }}</h3>
                    <p class="text-muted mb-0 small">Approuvées</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                    <h3 class="fw-bold mb-1">{{ $scopeRequests->where('status', 'rejected')->count() }}</h3>
                    <p class="text-muted mb-0 small">Rejetées</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Liste des demandes --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($scopeRequests->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune demande de scope</h5>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Développeur</th>
                                <th>Scopes demandés</th>
                                <th>Justification</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scopeRequests as $request)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($request->application->logo_path)
                                                <img src="{{ asset('storage/' . $request->application->logo_path) }}"
                                                     class="rounded me-2"
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-cube text-muted"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <a href="{{ route('admin.oauth.show', $request->application) }}" class="fw-semibold text-decoration-none">
                                                    {{ $request->application->name }}
                                                </a>
                                                <br>
                                                <small class="text-muted">{{ $request->application->website }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            {{ $request->application->user->first_name }} {{ $request->application->user->last_name }}
                                        </div>
                                        <small class="text-muted">{{ $request->application->user->email }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($request->requested_scopes as $scope)
                                                <span class="badge bg-primary">{{ $scope }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#justificationModal{{ $request->id }}">
                                            <i class="fas fa-eye me-1"></i>
                                            Voir
                                        </button>
                                    </td>
                                    <td>
                                        <div>{{ $request->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($request->status === 'pending')
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>En attente
                                            </span>
                                        @elseif($request->status === 'approved')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Approuvée
                                            </span>
                                            @if($request->reviewed_at)
                                                <div class="small text-muted mt-1">
                                                    {{ $request->reviewed_at->format('d/m/Y') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i>Rejetée
                                            </span>
                                            @if($request->reviewed_at)
                                                <div class="small text-muted mt-1">
                                                    {{ $request->reviewed_at->format('d/m/Y') }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($request->isPending())
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $request->id }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $request->id }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailsModal{{ $request->id }}">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $scopeRequests->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modals (en dehors de la boucle) --}}
    @foreach($scopeRequests as $request)
        {{-- Modal Justification --}}
        <div class="modal fade" id="justificationModal{{ $request->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Justification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">{{ $request->justification }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Approuver --}}
        @if($request->isPending())
            <div class="modal fade" id="approveModal{{ $request->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.oauth.approve-scope-request', $request) }}">
                            @csrf
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Approuver la demande
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>
                                    Approuver la demande de scopes pour <strong>{{ $request->application->name }}</strong> ?
                                </p>
                                <div class="alert alert-info">
                                    <strong>Scopes à ajouter :</strong>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @foreach($request->requested_scopes as $scope)
                                            <span class="badge bg-primary">{{ $scope }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Commentaire (optionnel)</label>
                                    <textarea name="admin_comment" class="form-control" rows="3" placeholder="Message au développeur..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-1"></i>
                                    Approuver
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Modal Rejeter --}}
            <div class="modal fade" id="rejectModal{{ $request->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.oauth.reject-scope-request', $request) }}">
                            @csrf
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-times-circle me-2"></i>
                                    Rejeter la demande
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>
                                    Rejeter la demande de scopes pour <strong>{{ $request->application->name }}</strong> ?
                                </p>
                                <div class="mb-3">
                                    <label class="form-label">Raison du rejet <span class="text-danger">*</span></label>
                                    <textarea name="admin_comment" class="form-control" rows="4" required placeholder="Expliquez pourquoi vous rejetez cette demande..."></textarea>
                                    <small class="form-text text-muted">Cette raison sera visible par le développeur</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times me-1"></i>
                                    Rejeter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            {{-- Modal Détails (pour demandes traitées) --}}
            <div class="modal fade" id="detailsModal{{ $request->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Détails de la demande</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <strong>Examinée par :</strong> {{ $request->reviewer ? $request->reviewer->name : 'N/A' }}
                            </div>
                            <div class="mb-3">
                                <strong>Date :</strong> {{ $request->reviewed_at ? $request->reviewed_at->format('d/m/Y à H:i') : 'N/A' }}
                            </div>
                            @if($request->admin_comment)
                                <div>
                                    <strong>Commentaire admin :</strong>
                                    <p class="mt-2 p-3 bg-light rounded">{{ $request->admin_comment }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

@endsection
