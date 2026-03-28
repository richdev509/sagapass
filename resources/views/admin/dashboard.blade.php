@extends('admin.layouts.admin')

@section('title', 'Tableau de Bord')
@section('page-title', 'Tableau de Bord')
@section('page-subtitle', 'Vue d\'ensemble des statistiques SAGA ID')

@section('content')

{{-- ============================================================ --}}
{{--  STATS PRINCIPALES                                            --}}
{{-- ============================================================ --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-details">
                <h6>Total Citoyens</h6>
                <h3>{{ number_format($stats['total_users']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-mobile-alt"></i></div>
            <div class="stat-details">
                <h6>Inscriptions en Attente</h6>
                <h3>{{ number_format($stats['mobile_pending']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-details">
                <h6>Inscriptions ApprouvÃ©es</h6>
                <h3>{{ number_format($stats['mobile_approved']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-user-plus"></i></div>
            <div class="stat-details">
                <h6>Nouveaux Aujourd'hui</h6>
                <h3>{{ number_format($stats['new_users_today']) }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{--  INDICATEURS SUPPLÃ‰MENTAIRES                                  --}}
{{-- ============================================================ --}}
<div class="row g-4 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
            <div class="h4 fw-bold text-warning mb-1">{{ number_format($stats['users_pending']) }}</div>
            <small class="text-muted">Utilisateurs en attente</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
            <div class="h4 fw-bold text-success mb-1">{{ number_format($stats['users_verified']) }}</div>
            <small class="text-muted">Utilisateurs vÃ©rifiÃ©s</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
            <div class="h4 fw-bold text-danger mb-1">{{ number_format($stats['mobile_rejected']) }}</div>
            <small class="text-muted">Inscriptions rejetÃ©es</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
            <div class="h4 fw-bold text-info mb-1">{{ number_format($stats['new_users_week']) }}</div>
            <small class="text-muted">Inscrits cette semaine</small>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{--  INSCRIPTIONS MOBILES EN ATTENTE                              --}}
{{-- ============================================================ --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-mobile-alt me-2 text-warning"></i>
                    Inscriptions Mobiles en Attente
                    @if($stats['mobile_pending'] > 0)
                    <span class="badge bg-danger ms-2">{{ $stats['mobile_pending'] }}</span>
                    @endif
                </span>
                @can('verify-documents', 'admin')
                <a href="{{ route('admin.mobile-verification.index') }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-eye me-1"></i>Voir Tout
                </a>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($pending_mobile->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Citoyen</th>
                                <th>NIU</th>
                                <th>TÃ©lÃ©phone</th>
                                <th>Date Inscription</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pending_mobile as $doc)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ route('admin.mobile-verification.image', [$doc, 'selfie']) }}"
                                             alt="Selfie"
                                             class="rounded-circle"
                                             width="38" height="38"
                                             style="object-fit:cover;"
                                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($doc->user->first_name . '+' . $doc->user->last_name) }}&background=667eea&color=fff'">
                                        <div>
                                            <strong>{{ $doc->user->first_name }} {{ $doc->user->last_name }}</strong><br>
                                            <small class="text-muted">{{ $doc->user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><code>{{ $doc->user->niu ?? 'â€”' }}</code></td>
                                <td>{{ $doc->user->phone ?? 'â€”' }}</td>
                                <td>
                                    {{ $doc->created_at->format('d/m/Y') }}
                                    <br><small class="text-muted">{{ $doc->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @can('verify-documents', 'admin')
                                    <a href="{{ route('admin.mobile-verification.show', $doc) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>RÃ©viser
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                    <p class="mb-0">Aucune inscription en attente â€” tout est Ã  jour !</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{--  ACTIVITÃ‰S RÃ‰CENTES + NOUVEAUX UTILISATEURS                   --}}
{{-- ============================================================ --}}
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history me-2"></i>ActivitÃ©s RÃ©centes</span>
                @can('view-audit-logs', 'admin')
                <a href="{{ route('admin.audit-logs') }}" class="btn btn-sm btn-outline-secondary">Voir Tout</a>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($recent_activities->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($recent_activities as $activity)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $activity->admin->name ?? 'SystÃ¨me' }}</strong>
                                <p class="mb-1 text-muted small">{{ $activity->description }}</p>
                            </div>
                            <small class="text-muted text-nowrap ms-2">{{ $activity->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-4 text-center text-muted"><p>Aucune activitÃ© rÃ©cente</p></div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-plus me-2"></i>Nouveaux Citoyens</span>
                @can('view-users', 'admin')
                <a href="{{ route('admin.citizens.index') }}" class="btn btn-sm btn-outline-secondary">Voir Tout</a>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($new_users->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($new_users as $user)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                                <p class="mb-0 text-muted small">{{ $user->email }}</p>
                                @if($user->niu)
                                <small class="text-muted">NIU : <code>{{ $user->niu }}</code></small>
                                @endif
                            </div>
                            <div class="text-end">
                                @if($user->verification_status === 'verified')
                                <span class="badge bg-success mb-1"><i class="fas fa-check"></i> VÃ©rifiÃ©</span>
                                @elseif($user->verification_status === 'pending')
                                <span class="badge bg-warning text-dark mb-1"><i class="fas fa-clock"></i> En attente</span>
                                @else
                                <span class="badge bg-danger mb-1"><i class="fas fa-times"></i> RejetÃ©</span>
                                @endif
                                <br>
                                <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-4 text-center text-muted"><p>Aucun nouvel utilisateur</p></div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
