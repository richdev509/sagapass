@extends('admin.layouts.admin')

@section('title', 'Inscriptions Mobiles')
@section('page-title', 'Vérification Inscriptions Mobiles')
@section('page-subtitle', 'Gérer les inscriptions soumises via l\'application SAGA ID')

@section('content')

{{-- Alertes --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <a href="{{ route('admin.mobile-verification.index') }}" class="text-decoration-none">
            <div class="stat-card border-start border-warning border-4">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-details">
                    <h6>En Attente</h6>
                    <h3>{{ number_format($stats['pending']) }}</h3>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.mobile-verification.approved') }}" class="text-decoration-none">
            <div class="stat-card border-start border-success border-4">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-details">
                    <h6>Approuvées</h6>
                    <h3>{{ number_format($stats['approved']) }}</h3>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.mobile-verification.rejected') }}" class="text-decoration-none">
            <div class="stat-card border-start border-danger border-4">
                <div class="stat-icon orange" style="background: linear-gradient(135deg,#e52d27,#b31217)"><i class="fas fa-times-circle"></i></div>
                <div class="stat-details">
                    <h6>Rejetées</h6>
                    <h3>{{ number_format($stats['rejected']) }}</h3>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Onglets de navigation --}}
<div class="card mb-0">
    <div class="card-header d-flex align-items-center gap-3 flex-wrap">
        <a href="{{ route('admin.mobile-verification.index') }}"
           class="btn btn-sm {{ request()->routeIs('admin.mobile-verification.index') ? 'btn-warning' : 'btn-outline-warning' }}">
            <i class="fas fa-clock me-1"></i>En Attente
            @if($stats['pending'] > 0)
            <span class="badge bg-danger ms-1">{{ $stats['pending'] }}</span>
            @endif
        </a>
        <a href="{{ route('admin.mobile-verification.approved') }}"
           class="btn btn-sm {{ request()->routeIs('admin.mobile-verification.approved') ? 'btn-success' : 'btn-outline-success' }}">
            <i class="fas fa-check-circle me-1"></i>Approuvées
        </a>
        <a href="{{ route('admin.mobile-verification.rejected') }}"
           class="btn btn-sm {{ request()->routeIs('admin.mobile-verification.rejected') ? 'btn-danger' : 'btn-outline-danger' }}">
            <i class="fas fa-times-circle me-1"></i>Rejetées
        </a>
    </div>

    <div class="card-body p-0">
        @if($documents->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Citoyen</th>
                        <th>NIU</th>
                        <th>Téléphone</th>
                        <th>Date Inscription</th>
                        <th>Statut</th>
                        <th>Documents</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                    <tr>
                        <td><code>#{{ $doc->id }}</code></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm">
                                    <img src="{{ route('admin.mobile-verification.image', [$doc, 'selfie']) }}"
                                         alt="Selfie"
                                         class="rounded-circle"
                                         width="40" height="40"
                                         style="object-fit:cover;"
                                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($doc->user->first_name . '+' . $doc->user->last_name) }}&background=667eea&color=fff'">
                                </div>
                                <div>
                                    <strong>{{ $doc->user->first_name }} {{ $doc->user->last_name }}</strong><br>
                                    <small class="text-muted">{{ $doc->user->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($doc->user->niu)
                            <code>{{ $doc->user->niu }}</code>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $doc->user->phone ?? '—' }}</td>
                        <td>
                            {{ $doc->created_at->format('d/m/Y') }}<br>
                            <small class="text-muted">{{ $doc->created_at->format('H:i') }}</small>
                        </td>
                        <td>
                            @if($doc->status === 'pending')
                            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En attente</span>
                            @elseif($doc->status === 'approved')
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Approuvé</span>
                            @else
                            <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Rejeté</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <img src="{{ route('admin.mobile-verification.image', [$doc, 'id_card_front']) }}"
                                     alt="Recto" width="30" height="22"
                                     style="object-fit:cover;border-radius:4px;cursor:pointer;"
                                     title="Recto CNI"
                                     onerror="this.style.display='none'">
                                <img src="{{ route('admin.mobile-verification.image', [$doc, 'id_card_back']) }}"
                                     alt="Verso" width="30" height="22"
                                     style="object-fit:cover;border-radius:4px;cursor:pointer;"
                                     title="Verso CNI"
                                     onerror="this.style.display='none'">
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.mobile-verification.show', $doc) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Réviser
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-3 d-flex justify-content-between align-items-center border-top">
            <small class="text-muted">
                {{ $documents->firstItem() }} – {{ $documents->lastItem() }} sur {{ $documents->total() }} résultats
            </small>
            {{ $documents->links('pagination::bootstrap-5') }}
        </div>

        @else
        <div class="p-5 text-center text-muted">
            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
            <p class="mb-0">Aucune inscription dans cette catégorie</p>
        </div>
        @endif
    </div>
</div>
@endsection
