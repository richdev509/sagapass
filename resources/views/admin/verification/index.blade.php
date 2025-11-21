@extends('admin.layouts.admin')

@section('title', 'Vérification Documents')
@section('page-title', 'Vérification des Documents')
@section('page-subtitle', 'Examiner et valider les documents soumis')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-file-check me-2"></i>Documents en Attente de Vérification</h5>
    </div>

    <!-- Filtres Avancés -->
    <div class="card-body border-bottom bg-light">
        <form method="GET" action="{{ route('admin.verification.index') }}" id="filterForm">
            <div class="row g-3">
                <!-- Recherche globale -->
                <div class="col-md-4">
                    <label for="search" class="form-label small fw-semibold">
                        <i class="fas fa-search me-1"></i>Recherche Globale
                    </label>
                    <input type="text"
                           name="search"
                           id="search"
                           class="form-control"
                           placeholder="Nom, email, numéro document..."
                           value="{{ request('search') }}">
                    <small class="text-muted">Rechercher par nom, email ou numéro</small>
                </div>

                <!-- Type de document -->
                <div class="col-md-3">
                    <label for="document_type" class="form-label small fw-semibold">
                        <i class="fas fa-file-alt me-1"></i>Type de Document
                    </label>
                    <select name="document_type" id="document_type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="cni" {{ request('document_type') === 'cni' ? 'selected' : '' }}>
                            Carte Nationale
                        </option>
                        <option value="passport" {{ request('document_type') === 'passport' ? 'selected' : '' }}>
                            Passeport
                        </option>
                    </select>
                </div>

                <!-- Date de soumission (du) -->
                <div class="col-md-2">
                    <label for="date_from" class="form-label small fw-semibold">
                        <i class="fas fa-calendar-alt me-1"></i>Soumis Du
                    </label>
                    <input type="date"
                           name="date_from"
                           id="date_from"
                           class="form-control"
                           value="{{ request('date_from') }}">
                </div>

                <!-- Date de soumission (au) -->
                <div class="col-md-2">
                    <label for="date_to" class="form-label small fw-semibold">
                        <i class="fas fa-calendar-alt me-1"></i>Au
                    </label>
                    <input type="date"
                           name="date_to"
                           id="date_to"
                           class="form-control"
                           value="{{ request('date_to') }}">
                </div>

                <!-- Bouton Rechercher -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>

            <!-- Options de tri -->
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label for="sort_by" class="form-label small fw-semibold">
                        <i class="fas fa-sort me-1"></i>Trier Par
                    </label>
                    <select name="sort_by" id="sort_by" class="form-select">
                        <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>
                            Date de soumission
                        </option>
                        <option value="user_name" {{ request('sort_by') === 'user_name' ? 'selected' : '' }}>
                            Nom de l'utilisateur
                        </option>
                        <option value="document_number" {{ request('sort_by') === 'document_number' ? 'selected' : '' }}>
                            Numéro de document
                        </option>
                        <option value="expiry_date" {{ request('sort_by') === 'expiry_date' ? 'selected' : '' }}>
                            Date d'expiration
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="sort_order" class="form-label small fw-semibold">
                        <i class="fas fa-sort-amount-down me-1"></i>Ordre
                    </label>
                    <select name="sort_order" id="sort_order" class="form-select">
                        <option value="desc" {{ request('sort_order', 'desc') === 'desc' ? 'selected' : '' }}>
                            Décroissant (↓)
                        </option>
                        <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>
                            Croissant (↑)
                        </option>
                    </select>
                </div>

                <!-- Boutons d'action -->
                <div class="col-md-5 d-flex align-items-end justify-content-end gap-2">
                    @if(request()->hasAny(['search', 'document_type', 'date_from', 'date_to', 'sort_by', 'sort_order']))
                    <a href="{{ route('admin.verification.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Réinitialiser les Filtres
                    </a>
                    @endif

                    <div class="text-muted small">
                        <strong>{{ $documents->total() }}</strong> document(s) trouvé(s)
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        @if($documents->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Utilisateur</th>
                        <th width="15%">Type Document</th>
                        <th width="15%">Numéro</th>
                        <th width="15%">Date Délivrance</th>
                        <th width="15%">Date Expiration</th>
                        <th width="10%">Soumis</th>
                        <th width="5%" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                    <tr>
                        <td><strong>#{{ $document->id }}</strong></td>
                        <td>
                            <div>
                                <strong>{{ $document->user->first_name }} {{ $document->user->last_name }}</strong>
                            </div>
                            <small class="text-muted">{{ $document->user->email }}</small>
                        </td>
                        <td>
                            @if($document->document_type === 'cni')
                            <span class="badge bg-primary">
                                <i class="fas fa-id-card me-1"></i>Carte Nationale
                            </span>
                            @else
                            <span class="badge bg-info">
                                <i class="fas fa-passport me-1"></i>Passeport
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($document->document_type === 'cni' && $document->card_number)
                                <div><small class="text-muted">N° Carte:</small> <code class="text-primary">{{ $document->card_number }}</code></div>
                            @endif
                            <div><small class="text-muted">N° Doc:</small> <code class="text-dark">{{ $document->document_number }}</code></div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($document->expiry_date)->format('d/m/Y') }}
                            @if(\Carbon\Carbon::parse($document->expiry_date)->isPast())
                            <span class="badge bg-danger ms-1">Expiré</span>
                            @elseif(\Carbon\Carbon::parse($document->expiry_date)->diffInDays(now()) < 30)
                            <span class="badge bg-warning ms-1">Expire bientôt</span>
                            @endif
                        </td>
                        <td>
                            <small>{{ $document->created_at->format('d/m/Y') }}</small><br>
                            <small class="text-muted">{{ $document->created_at->diffForHumans() }}</small>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.verification.show', $document) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
        <div class="card-footer bg-white">
            {{ $documents->links() }}
        </div>
        @endif
        @else
        <div class="p-5 text-center">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun document en attente</h5>
            <p class="text-muted">Tous les documents ont été traités</p>
        </div>
        @endif
    </div>
</div>
@endsection
