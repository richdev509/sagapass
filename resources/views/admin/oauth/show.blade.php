@extends('admin.layouts.admin')

@section('title', 'Détails Application OAuth')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.oauth.index') }}" class="btn btn-sm btn-secondary mb-2">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <h2 class="mb-0">{{ $application->name }}</h2>
                    <p class="text-muted mb-0">{{ $application->description }}</p>
                </div>
                <div>
                    @if($application->status === 'pending')
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="fas fa-check"></i> Approuver
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fas fa-times"></i> Rejeter
                        </button>
                    @elseif($application->status === 'approved')
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#suspendModal">
                            <i class="fas fa-ban"></i> Suspendre
                        </button>
                    @elseif($application->status === 'suspended')
                        <form action="{{ route('admin.oauth.reactivate', $application) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success"
                                    onclick="return confirm('Êtes-vous sûr de vouloir réactiver cette application ?')">
                                <i class="fas fa-play"></i> Réactiver
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statut principal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Statut</strong><br>
                            @if($application->status === 'pending')
                                <span class="badge bg-warning fs-6">En attente</span>
                            @elseif($application->status === 'approved')
                                <span class="badge bg-success fs-6">Approuvée</span>
                            @elseif($application->status === 'rejected')
                                <span class="badge bg-danger fs-6">Rejetée</span>
                            @elseif($application->status === 'suspended')
                                <span class="badge bg-secondary fs-6">Suspendue</span>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <strong>Client ID</strong><br>
                            <code>{{ $application->client_id }}</code>
                        </div>
                        <div class="col-md-3">
                            <strong>Créée le</strong><br>
                            {{ $application->created_at->format('d/m/Y à H:i') }}
                        </div>
                        @if($application->approved_at)
                        <div class="col-md-3">
                            <strong>Approuvée le</strong><br>
                            {{ $application->approved_at->format('d/m/Y à H:i') }}<br>
                            <small class="text-muted">
                                par {{ $application->approver->name ?? 'N/A' }}
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations détaillées -->
    <div class="row">
        <!-- Développeur -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-user"></i> Développeur
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Nom</th>
                            <td>{{ $application->user->first_name }} {{ $application->user->last_name }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $application->user->email }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Configuration OAuth -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <span><i class="fas fa-cog"></i> Identifiants partenaire</span>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Client ID</th>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control font-monospace" value="{{ $application->client_id }}" readonly id="clientIdField">
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('clientIdField')" title="Copier">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Client Secret</th>
                            <td>
                                @if(session('new_secret'))
                                    <div class="alert alert-success p-2 mb-2">
                                        <small><strong><i class="fas fa-exclamation-triangle"></i> Nouveau secret généré !</strong></small>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control font-monospace" value="{{ session('new_secret') }}" readonly id="newSecretField">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('newSecretField')" title="Copier">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="input-group input-group-sm">
                                        <input type="password" class="form-control font-monospace" value="••••••••••••••••" readonly id="secretField">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleSecretBtn" onclick="toggleSecret({{ $application->id }})" title="Afficher/Masquer">
                                            <i class="fas fa-eye" id="secretEyeIcon"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('secretField')" title="Copier" id="copySecretBtn" style="display:none;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                @endif
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#regenerateSecretModal">
                                        <i class="fas fa-sync-alt me-1"></i> Régénérer
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>App Key <small class="text-muted">(Server-to-Server)</small></th>
                            <td>
                                @if(session('new_app_key'))
                                    <div class="alert alert-success p-2 mb-2">
                                        <small><strong><i class="fas fa-exclamation-triangle"></i> Nouvelle App Key générée !</strong></small>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control font-monospace" value="{{ session('new_app_key') }}" readonly id="newAppKeyField">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('newAppKeyField')" title="Copier">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="input-group input-group-sm">
                                        <input type="password" class="form-control font-monospace" value="••••••••••••••••" readonly id="appKeyField">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleAppKeyBtn" onclick="toggleAppKey({{ $application->id }})" title="Afficher/Masquer">
                                            <i class="fas fa-eye" id="appKeyEyeIcon"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('appKeyField')" title="Copier" id="copyAppKeyBtn" style="display:none;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                @endif
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#regenerateAppKeyModal">
                                        <i class="fas fa-sync-alt me-1"></i> Régénérer
                                    </button>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle"></i> Utilisée pour le chiffrement des vérifications d'identité Server-to-Server
                                    </small>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Site web</th>
                            <td>
                                @if($application->website)
                                    <a href="{{ $application->website }}" target="_blank" rel="noopener noreferrer">
                                        {{ $application->website }}
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Application de confiance</th>
                            <td>
                                @if($application->is_trusted)
                                    <span class="badge bg-success"><i class="fas fa-shield-alt"></i> Oui</span>
                                @else
                                    <span class="badge bg-secondary">Non</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Approbation -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.oauth.approve', $application) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Approuver l'application</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir approuver l'application <strong>{{ $application->name }}</strong> ?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Le développeur recevra un email de confirmation avec les identifiants OAuth.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approuver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rejet -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.oauth.reject', $application) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Rejeter l'application</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Veuillez indiquer la raison du rejet de l'application <strong>{{ $application->name }}</strong> :</p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Raison du rejet *</label>
                        <textarea name="rejection_reason" id="rejection_reason"
                                  class="form-control @error('rejection_reason') is-invalid @enderror"
                                  rows="4" required
                                  placeholder="Ex: Les URLs de redirection ne sont pas sécurisées (HTTPS requis)..."></textarea>
                        @error('rejection_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Le développeur recevra cette raison par email.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Rejeter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Suspension -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.oauth.suspend', $application) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Suspendre l'application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Veuillez indiquer la raison de la suspension de l'application <strong>{{ $application->name }}</strong> :</p>
                    <div class="mb-3">
                        <label for="suspension_reason" class="form-label">Raison de la suspension *</label>
                        <textarea name="suspension_reason" id="suspension_reason"
                                  class="form-control @error('suspension_reason') is-invalid @enderror"
                                  rows="4" required
                                  placeholder="Ex: Utilisation abusive détectée, violation des conditions d'utilisation..."></textarea>
                        @error('suspension_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="alert alert-danger">
                        <strong><i class="fas fa-exclamation-triangle"></i> Attention !</strong>
                        <ul class="mb-0 mt-2">
                            <li>Toutes les autorisations utilisateurs seront révoquées</li>
                            <li>L'application ne pourra plus authentifier d'utilisateurs</li>
                            <li>Le développeur recevra un email de notification</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-ban"></i> Suspendre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Regenerate Secret Modal --}}
<div class="modal fade" id="regenerateSecretModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.oauth.regenerate-secret', $application) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-sync-alt me-2"></i>
                        Régénérer le Client Secret
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i> Attention !</strong>
                        <ul class="mb-0 mt-2">
                            <li>L'ancien secret sera <strong>immédiatement invalidé</strong></li>
                            <li>L'application <strong>{{ $application->name }}</strong> ne pourra plus échanger de tokens jusqu'à mise à jour de son secret</li>
                            <li>Le nouveau secret sera affiché <strong>une seule fois</strong> — copiez-le immédiatement</li>
                        </ul>
                    </div>
                    <p>Êtes-vous sûr de vouloir régénérer le secret pour <strong>{{ $application->name }}</strong> ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i> Régénérer le secret
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Regenerate App Key Modal --}}
<div class="modal fade" id="regenerateAppKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.oauth.regenerate-app-key', $application) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-sync-alt me-2"></i>
                        Régénérer l'App Key
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i> Attention !</strong>
                        <ul class="mb-0 mt-2">
                            <li>L'ancienne app_key sera <strong>immédiatement invalidée</strong></li>
                            <li>Les vérifications d'identité Server-to-Server <strong>échoueront</strong> jusqu'à mise à jour de l'app_key</li>
                            <li>La nouvelle app_key sera affichée <strong>une seule fois</strong> — copiez-la immédiatement</li>
                            <li>Communiquez la nouvelle clé au développeur de manière sécurisée</li>
                        </ul>
                    </div>
                    <p>Êtes-vous sûr de vouloir régénérer l'app_key pour <strong>{{ $application->name }}</strong> ?</p>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-info-circle"></i> L'app_key est utilisée pour chiffrer les données dans les vérifications d'identité Server-to-Server (endpoint <code>/api/partner/v1/verify</code>).
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i> Régénérer l'app_key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    let secretVisible = false;

    function toggleSecret(appId) {
        const field = document.getElementById('secretField');
        const icon = document.getElementById('secretEyeIcon');
        const copyBtn = document.getElementById('copySecretBtn');
        const toggleBtn = document.getElementById('toggleSecretBtn');

        if (secretVisible) {
            field.type = 'password';
            field.value = '••••••••••••••••';
            icon.className = 'fas fa-eye';
            copyBtn.style.display = 'none';
            secretVisible = false;
        } else {
            toggleBtn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin';

            fetch(`{{ url('/admin/oauth') }}/${appId}/secret`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                field.type = 'text';
                field.value = data.secret;
                icon.className = 'fas fa-eye-slash';
                copyBtn.style.display = '';
                secretVisible = true;
            })
            .catch(() => {
                alert('Erreur lors du chargement du secret.');
                icon.className = 'fas fa-eye';
            })
            .finally(() => {
                toggleBtn.disabled = false;
            });
        }
    }

    let appKeyVisible = false;

    function toggleAppKey(appId) {
        const field = document.getElementById('appKeyField');
        const icon = document.getElementById('appKeyEyeIcon');
        const copyBtn = document.getElementById('copyAppKeyBtn');
        const toggleBtn = document.getElementById('toggleAppKeyBtn');

        if (appKeyVisible) {
            field.type = 'password';
            field.value = '••••••••••••••••';
            icon.className = 'fas fa-eye';
            copyBtn.style.display = 'none';
            appKeyVisible = false;
        } else {
            toggleBtn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin';

            fetch(`{{ url('/admin/oauth') }}/${appId}/app-key`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                field.type = 'text';
                field.value = data.app_key;
                icon.className = 'fas fa-eye-slash';
                copyBtn.style.display = '';
                appKeyVisible = true;
            })
            .catch(() => {
                alert('Erreur lors du chargement de l\'app_key.');
                icon.className = 'fas fa-eye';
            })
            .finally(() => {
                toggleBtn.disabled = false;
            });
        }
    }

    function copyToClipboard(fieldId) {
        const field = document.getElementById(fieldId);
        const value = field.value;
        navigator.clipboard.writeText(value).then(() => {
            const btn = field.nextElementSibling;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => { btn.innerHTML = originalHtml; }, 1500);
        });
    }
</script>
@endsection
