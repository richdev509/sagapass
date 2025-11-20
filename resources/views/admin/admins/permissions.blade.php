@extends('admin.layouts.admin')

@section('title', 'Gérer Permissions - ' . $admin->name)
@section('page-title', 'Gestion des Permissions')
@section('page-subtitle', 'Définir les permissions pour ' . $admin->name)

@section('content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">
                            <i class="fas fa-shield-alt me-2"></i>Permissions de {{ $admin->name }}
                        </h5>
                        <small class="text-muted">
                            Rôle:
                            @if($admin->hasRole('Super Admin', 'admin'))
                                <span class="badge bg-danger">Super Admin</span>
                            @elseif($admin->hasRole('Manager', 'admin'))
                                <span class="badge bg-warning">Manager</span>
                            @else
                                <span class="badge bg-info">Agent</span>
                            @endif
                        </small>
                    </div>
                    <a href="{{ route('admin.admins.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.admins.permissions.update', $admin) }}">
                @csrf
                @method('PATCH')

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Cochez les permissions que vous souhaitez accorder à cet administrateur.
                        Les permissions définissent ce que l'admin peut faire dans le système.
                    </div>

                    <!-- Liste des permissions -->
                    <div class="row">
                        @forelse($allPermissions as $permission)
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch p-3 border rounded {{ in_array($permission->name, $adminPermissions) ? 'bg-light' : '' }}">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permission->name }}"
                                       id="permission_{{ $permission->id }}"
                                       {{ in_array($permission->name, $adminPermissions) ? 'checked' : '' }}>
                                <label class="form-check-label ms-2" for="permission_{{ $permission->id }}">
                                    <div>
                                        <strong>
                                            @if($permission->name === 'verify-documents')
                                                <i class="fas fa-file-check text-primary me-1"></i>Vérifier Documents
                                            @elseif($permission->name === 'manage-admins')
                                                <i class="fas fa-users-cog text-danger me-1"></i>Gérer Administrateurs
                                            @elseif($permission->name === 'view-audit-logs')
                                                <i class="fas fa-history text-warning me-1"></i>Voir Logs d'Audit
                                            @else
                                                <i class="fas fa-lock me-1"></i>{{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                                            @endif
                                        </strong>
                                    </div>
                                    <small class="text-muted">
                                        @if($permission->name === 'verify-documents')
                                            Permet de vérifier, approuver ou rejeter les documents soumis par les citoyens
                                        @elseif($permission->name === 'manage-admins')
                                            Permet de créer, modifier et supprimer des administrateurs (réservé Super Admin)
                                        @elseif($permission->name === 'view-audit-logs')
                                            Permet de consulter l'historique complet des actions administratives
                                        @else
                                            {{ $permission->name }}
                                        @endif
                                    </small>
                                </label>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Aucune permission disponible dans le système.
                            </div>
                        </div>
                        @endforelse
                    </div>

                    <!-- Sélection rapide -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="mb-3"><i class="fas fa-magic me-2"></i>Sélection Rapide</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                <i class="fas fa-check-double me-1"></i>Tout Sélectionner
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                                <i class="fas fa-times me-1"></i>Tout Désélectionner
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="selectVerification()">
                                <i class="fas fa-file-check me-1"></i>Vérification Uniquement
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('admin.admins.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Enregistrer les Permissions
                    </button>
                </div>
            </form>
        </div>

        <!-- Résumé des permissions actuelles -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-list-check me-2"></i>Permissions Actuelles</h6>
            </div>
            <div class="card-body">
                @if(count($adminPermissions) > 0)
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($adminPermissions as $perm)
                            <span class="badge bg-success">
                                <i class="fas fa-check me-1"></i>{{ ucfirst(str_replace('-', ' ', $perm)) }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Aucune permission attribuée pour le moment.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function selectAll() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAll() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function selectVerification() {
    deselectAll();
    document.querySelectorAll('input[value="verify-documents"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}
</script>
@endpush
@endsection
