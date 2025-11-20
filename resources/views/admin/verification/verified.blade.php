@extends('admin.layouts.admin')

@section('title', 'Documents Vérifiés')
@section('page-title', 'Documents Vérifiés')
@section('page-subtitle', 'Historique des documents approuvés')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Documents Vérifiés</h5>
    </div>

    <!-- Filtres Avancés -->
    <div class="card-body border-bottom bg-light">
        <form method="GET" action="{{ route('admin.verification.verified') }}" id="filterForm">
            <div class="row g-3">
                <!-- Recherche globale -->
                <div class="col-md-4">
                    <label for="search" class="form-label small fw-semibold">
                        <i class="fas fa-search me-1"></i>Recherche
                    </label>
                    <input type="text"
                           name="search"
                           id="search"
                           class="form-control"
                           placeholder="Nom, email, téléphone, numéro..."
                           value="{{ request('search') }}">
                </div>

                <!-- Type de document -->
                <div class="col-md-2">
                    <label for="document_type" class="form-label small fw-semibold">
                        <i class="fas fa-file-alt me-1"></i>Type
                    </label>
                    <select name="document_type" id="document_type" class="form-select">
                        <option value="">Tous</option>
                        <option value="cni" {{ request('document_type') === 'cni' ? 'selected' : '' }}>CNI</option>
                        <option value="passport" {{ request('document_type') === 'passport' ? 'selected' : '' }}>Passeport</option>
                    </select>
                </div>

                <!-- Admin vérificateur -->
                <div class="col-md-3">
                    <label for="verified_by" class="form-label small fw-semibold">
                        <i class="fas fa-user-shield me-1"></i>Vérifié par
                    </label>
                    <select name="verified_by" id="verified_by" class="form-select">
                        <option value="">Tous les admins</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" {{ request('verified_by') == $admin->id ? 'selected' : '' }}>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date de vérification (du) -->
                <div class="col-md-2">
                    <label for="date_from" class="form-label small fw-semibold">
                        <i class="fas fa-calendar-alt me-1"></i>Du
                    </label>
                    <input type="date"
                           name="date_from"
                           id="date_from"
                           class="form-control"
                           value="{{ request('date_from') }}">
                </div>

                <!-- Date de vérification (au) -->
                <div class="col-md-1">
                    <label for="date_to" class="form-label small fw-semibold">Au</label>
                    <input type="date"
                           name="date_to"
                           id="date_to"
                           class="form-control"
                           value="{{ request('date_to') }}">
                </div>
            </div>

            <!-- Options de tri -->
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label for="sort_by" class="form-label small fw-semibold">
                        <i class="fas fa-sort me-1"></i>Trier Par
                    </label>
                    <select name="sort_by" id="sort_by" class="form-select">
                        <option value="verified_at" {{ request('sort_by', 'verified_at') === 'verified_at' ? 'selected' : '' }}>
                            Date de vérification
                        </option>
                        <option value="user_name" {{ request('sort_by') === 'user_name' ? 'selected' : '' }}>
                            Nom de l'utilisateur
                        </option>
                        <option value="document_number" {{ request('sort_by') === 'document_number' ? 'selected' : '' }}>
                            Numéro de document
                        </option>
                    </select>
                </div>

                <div class="col-md-2">
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
                <div class="col-md-7 d-flex align-items-end justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Filtrer
                    </button>

                    @if(request()->hasAny(['search', 'document_type', 'verified_by', 'date_from', 'date_to', 'sort_by', 'sort_order']))
                    <a href="{{ route('admin.verification.verified') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Réinitialiser
                    </a>
                    @endif

                    <div class="text-muted small">
                        <strong>{{ $documents->total() }}</strong> document(s)
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
                        <th width="20%">Citoyen</th>
                        <th width="12%">Type</th>
                        <th width="13%">Numéro</th>
                        <th width="15%">Vérifié par</th>
                        <th width="15%">Date Vérification</th>
                        <th width="10%">Temps Traitement</th>
                        <th width="10%" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                    <tr>
                        <td><strong>#{{ $document->id }}</strong></td>
                        <td>
                            <div class="mb-1">
                                <strong>{{ $document->user->first_name }} {{ $document->user->last_name }}</strong>
                            </div>
                            <small class="text-muted d-block">
                                <i class="fas fa-envelope me-1"></i>{{ $document->user->email }}
                            </small>
                            <small class="text-muted d-block">
                                <i class="fas fa-phone me-1"></i>{{ $document->user->phone ?? 'N/A' }}
                            </small>
                        </td>
                        <td>
                            @if($document->document_type === 'cni')
                            <span class="badge bg-primary">
                                <i class="fas fa-id-card me-1"></i>CNI
                            </span>
                            @else
                            <span class="badge bg-info">
                                <i class="fas fa-passport me-1"></i>Passeport
                            </span>
                            @endif
                        </td>
                        <td><code class="text-dark">{{ $document->document_number }}</code></td>
                        <td>
                            @if($document->verifiedBy)
                                <div class="mb-1">
                                    <i class="fas fa-user-shield me-1"></i>
                                    <strong>{{ $document->verifiedBy->name }}</strong>
                                </div>
                                <small>
                                    @if($document->verifiedBy->hasRole('Super Admin', 'admin'))
                                        <span class="badge bg-danger">Super Admin</span>
                                    @elseif($document->verifiedBy->hasRole('Manager', 'admin'))
                                        <span class="badge bg-warning">Manager</span>
                                    @else
                                        <span class="badge bg-info">Agent</span>
                                    @endif
                                </small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $document->verified_at->format('d/m/Y') }}</div>
                            <small class="text-muted">{{ $document->verified_at->format('H:i') }}</small>
                        </td>
                        <td>
                            @php
                                $processingTime = $document->created_at->diff($document->verified_at);
                            @endphp
                            <small>
                                @if($processingTime->d > 0)
                                    {{ $processingTime->d }}j
                                @endif
                                @if($processingTime->h > 0)
                                    {{ $processingTime->h }}h
                                @endif
                                @if($processingTime->i > 0)
                                    {{ $processingTime->i }}m
                                @endif
                            </small>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.verification.show', $document) }}"
                               class="btn btn-sm btn-primary"
                               title="Voir détails et historique">
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
            <h5 class="text-muted">Aucun document vérifié</h5>
            <p class="text-muted">Les documents vérifiés apparaîtront ici</p>
        </div>
        @endif
    </div>
</div>
@endsection
