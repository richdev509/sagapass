@extends('admin.layouts.admin')

@section('title', 'Logs d\'Audit')
@section('page-title', 'Logs d\'Audit')
@section('page-subtitle', 'Historique des actions administratives')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-history me-2"></i>Journal des Activités</span>
        <div class="d-flex gap-2">
            <!-- Filters -->
            <form method="GET" action="{{ route('admin.audit-logs') }}" class="d-flex gap-2">
                <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Toutes les actions</option>
                    <option value="document_verified" {{ request('action') === 'document_verified' ? 'selected' : '' }}>Documents Vérifiés</option>
                    <option value="document_rejected" {{ request('action') === 'document_rejected' ? 'selected' : '' }}>Documents Rejetés</option>
                    <option value="admin_created" {{ request('action') === 'admin_created' ? 'selected' : '' }}>Admin Créé</option>
                    <option value="admin_updated" {{ request('action') === 'admin_updated' ? 'selected' : '' }}>Admin Modifié</option>
                    <option value="admin_deleted" {{ request('action') === 'admin_deleted' ? 'selected' : '' }}>Admin Supprimé</option>
                    <option value="admin_status_changed" {{ request('action') === 'admin_status_changed' ? 'selected' : '' }}>Statut Changé</option>
                </select>

                @if(request('action'))
                <a href="{{ route('admin.audit-logs') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
                @endif
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        @if($logs->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Administrateur</th>
                        <th width="15%">Action</th>
                        <th width="30%">Description</th>
                        <th width="15%">Utilisateur Concerné</th>
                        <th width="10%">IP</th>
                        <th width="10%">Date/Heure</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td><strong>#{{ $log->id }}</strong></td>
                        <td>
                            <strong>{{ $log->admin->name }}</strong><br>
                            <small class="text-muted">{{ $log->admin->email }}</small>
                        </td>
                        <td>
                            @php
                            $badgeClass = match($log->action) {
                                'document_verified' => 'bg-success',
                                'document_rejected' => 'bg-danger',
                                'admin_created' => 'bg-primary',
                                'admin_updated' => 'bg-info',
                                'admin_deleted' => 'bg-danger',
                                'admin_status_changed' => 'bg-warning',
                                default => 'bg-secondary'
                            };

                            $actionIcon = match($log->action) {
                                'document_verified' => 'fa-check-circle',
                                'document_rejected' => 'fa-times-circle',
                                'admin_created' => 'fa-user-plus',
                                'admin_updated' => 'fa-user-edit',
                                'admin_deleted' => 'fa-user-times',
                                'admin_status_changed' => 'fa-toggle-on',
                                default => 'fa-circle'
                            };

                            $actionLabel = match($log->action) {
                                'document_verified' => 'Document Vérifié',
                                'document_rejected' => 'Document Rejeté',
                                'admin_created' => 'Admin Créé',
                                'admin_updated' => 'Admin Modifié',
                                'admin_deleted' => 'Admin Supprimé',
                                'admin_status_changed' => 'Statut Modifié',
                                default => $log->action
                            };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                <i class="fas {{ $actionIcon }} me-1"></i>{{ $actionLabel }}
                            </span>
                        </td>
                        <td>
                            <small>{{ $log->description }}</small>
                        </td>
                        <td>
                            @if($log->user)
                            <strong>{{ $log->user->first_name }} {{ $log->user->last_name }}</strong><br>
                            <small class="text-muted">{{ $log->user->email }}</small>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><code>{{ $log->ip_address }}</code></td>
                        <td>
                            <small>{{ $log->created_at->format('d/m/Y') }}</small><br>
                            <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="card-footer bg-white">
            {{ $logs->appends(request()->query())->links() }}
        </div>
        @endif
        @else
        <div class="p-5 text-center">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune activité enregistrée</h5>
            <p class="text-muted">Les actions administratives apparaîtront ici</p>
        </div>
        @endif
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mt-3">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h6>Documents Vérifiés</h6>
                <h3>{{ number_format($stats['verified'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-details">
                <h6>Documents Rejetés</h6>
                <h3>{{ number_format($stats['rejected'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-details">
                <h6>Admins Créés</h6>
                <h3>{{ number_format($stats['admins_created'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-history"></i>
            </div>
            <div class="stat-details">
                <h6>Total Actions</h6>
                <h3>{{ number_format($stats['total'] ?? 0) }}</h3>
            </div>
        </div>
    </div>
</div>
@endsection
