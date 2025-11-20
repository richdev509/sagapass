@extends('admin.layouts.admin')

@section('title', 'Documents Rejetés')
@section('page-title', 'Documents Rejetés')
@section('page-subtitle', 'Statistiques et liste des rejets')

@section('content')
<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-details">
                <h6>Total Rejetés</h6>
                <h3>{{ $stats['total'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="stat-details">
                <h6>CNI Rejetées</h6>
                <h3>{{ $stats['cni'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-passport"></i>
            </div>
            <div class="stat-details">
                <h6>Passeports Rejetés</h6>
                <h3>{{ $stats['passport'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-details">
                <h6>Aujourd'hui</h6>
                <h3>{{ $stats['today'] }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Documents Rejetés</h5>
    </div>

    <!-- Filtres -->
    <div class="card-body border-bottom bg-light">
        <form method="GET" action="{{ route('admin.verification.rejected') }}">
            <div class="row g-3">
                <!-- Recherche -->
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

                <!-- Type -->
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

                <!-- Date du -->
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

                <!-- Date au -->
                <div class="col-md-2">
                    <label for="date_to" class="form-label small fw-semibold">Au</label>
                    <input type="date"
                           name="date_to"
                           id="date_to"
                           class="form-control"
                           value="{{ request('date_to') }}">
                </div>

                <!-- Boutons -->
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                    </button>

                    @if(request()->hasAny(['search', 'document_type', 'date_from', 'date_to']))
                    <a href="{{ route('admin.verification.rejected') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
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
                        <th width="4%">#</th>
                        <th width="18%">Citoyen</th>
                        <th width="10%">Type</th>
                        <th width="12%">Numéro</th>
                        <th width="13%">Rejeté par</th>
                        <th width="12%">Date Rejet</th>
                        <th width="25%">Raison</th>
                        <th width="6%" class="text-center">Action</th>
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
                                    <small>
                                        <i class="fas fa-user-shield me-1"></i>
                                        {{ $document->verifiedBy->name }}
                                    </small>
                                </div>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $document->verified_at->format('d/m/Y') }}</div>
                            <small class="text-muted">{{ $document->verified_at->format('H:i') }}</small>
                        </td>
                        <td>
                            <small class="text-danger">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                {{ Str::limit($document->rejection_reason, 50) }}
                            </small>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.verification.show', $document) }}"
                               class="btn btn-sm btn-outline-danger"
                               title="Voir détails">
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
            <h5 class="text-muted">Aucun document rejeté</h5>
            <p class="text-muted">Les documents rejetés apparaîtront ici</p>
        </div>
        @endif
    </div>
</div>
@endsection
