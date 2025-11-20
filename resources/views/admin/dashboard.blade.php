@extends('admin.layouts.admin')

@section('title', 'Tableau de Bord')
@section('page-title', 'Tableau de Bord')
@section('page-subtitle', 'Vue d\'ensemble des statistiques et activités')

@section('content')
<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h6>Total Utilisateurs</h6>
                <h3>{{ number_format($stats['total_users']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h6>Documents Vérifiés</h6>
                <h3>{{ number_format($stats['verified_documents']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h6>En Attente</h6>
                <h3>{{ number_format($stats['pending_documents']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-details">
                <h6>Nouveaux Utilisateurs</h6>
                <h3>{{ number_format($stats['new_users_today']) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Documents by Type -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-file-alt me-2"></i>Documents en Attente de Vérification</span>
                @can('verify-documents', 'admin')
                <a href="{{ route('admin.verification.index') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye me-1"></i>Voir Tout
                </a>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($pending_documents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Type Document</th>
                                <th>Numéro</th>
                                <th>Date Soumission</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pending_documents as $document)
                            <tr>
                                <td>
                                    <strong>{{ $document->user->first_name }} {{ $document->user->last_name }}</strong><br>
                                    <small class="text-muted">{{ $document->user->email }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $document->document_type === 'cni' ? 'Carte Nationale' : 'Passeport' }}
                                    </span>
                                </td>
                                <td><code>{{ $document->document_number }}</code></td>
                                <td>{{ $document->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @can('verify-documents', 'admin')
                                    <a href="{{ route('admin.verification.show', $document) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Vérifier
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
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Aucun document en attente de vérification</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2"></i>Statistiques Documents
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Cartes Nationales</span>
                        <strong>{{ number_format($stats['cni_documents']) }}</strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: {{ $stats['total_documents'] > 0 ? ($stats['cni_documents'] / $stats['total_documents'] * 100) : 0 }}%"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Passeports</span>
                        <strong>{{ number_format($stats['passport_documents']) }}</strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: {{ $stats['total_documents'] > 0 ? ($stats['passport_documents'] / $stats['total_documents'] * 100) : 0 }}%"></div>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-success"><i class="fas fa-check-circle"></i> Vérifiés</span>
                        <strong>{{ number_format($stats['verified_documents']) }}</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-warning"><i class="fas fa-clock"></i> En Attente</span>
                        <strong>{{ number_format($stats['pending_documents']) }}</strong>
                    </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-danger"><i class="fas fa-times-circle"></i> Rejetés</span>
                        <strong>{{ number_format($stats['rejected_documents']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities and New Users -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history me-2"></i>Activités Récentes</span>
                @can('view-audit-logs', 'admin')
                <a href="{{ route('admin.audit-logs') }}" class="btn btn-sm btn-outline-secondary">
                    Voir Tout
                </a>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($recent_activities->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($recent_activities as $activity)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $activity->admin->name }}</strong>
                                <p class="mb-1 text-muted small">{{ $activity->description }}</p>
                            </div>
                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-4 text-center text-muted">
                    <p>Aucune activité récente</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus me-2"></i>Nouveaux Utilisateurs
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
                            </div>
                            <div class="text-end">
                                @if($user->email_verified_at)
                                <span class="badge bg-success mb-1"><i class="fas fa-check"></i> Vérifié</span>
                                @else
                                <span class="badge bg-warning mb-1"><i class="fas fa-clock"></i> Non vérifié</span>
                                @endif
                                <br>
                                <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-4 text-center text-muted">
                    <p>Aucun nouvel utilisateur</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
