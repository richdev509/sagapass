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

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $appStats['total_users'] }}</h3>
                    <small>Total utilisateurs</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $appStats['active_users'] }}</h3>
                    <small>Utilisateurs actifs</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $appStats['codes_generated'] }}</h3>
                    <small>Codes générés</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $appStats['codes_used'] }}</h3>
                    <small>Codes utilisés</small>
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
                        @if($application->user->developer)
                        <tr>
                            <th>Entreprise</th>
                            <td>{{ $application->user->developer->company_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Site web</th>
                            <td>
                                @if($application->user->developer->website)
                                    <a href="{{ $application->user->developer->website }}" target="_blank">
                                        {{ $application->user->developer->website }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Configuration OAuth -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-cog"></i> Configuration OAuth</span>
                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editConfigModal">
                        <i class="fas fa-edit me-1"></i> Modifier
                    </button>
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
                            <th>URLs callback</th>
                            <td>
                                @php
                                    $redirectUris = is_array($application->redirect_uris)
                                        ? $application->redirect_uris
                                        : json_decode($application->redirect_uris, true) ?? [];
                                @endphp
                                @forelse($redirectUris as $uri)
                                    <code class="d-block mb-1">{{ $uri }}</code>
                                @empty
                                    <span class="text-muted">Aucune</span>
                                @endforelse
                                <button type="button" class="btn btn-sm btn-outline-info mt-1" data-bs-toggle="modal" data-bs-target="#editConfigModal">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th>Scopes</th>
                            <td>
                                @php
                                    $scopes = $application->allowed_scopes ?? [];
                                @endphp
                                @forelse($scopes as $scope)
                                    <span class="badge bg-success me-1">{{ $scope }}</span>
                                @empty
                                    <span class="text-muted">Aucun</span>
                                @endforelse
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

    <!-- Gestion des Scopes -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-shield-alt"></i> Gestion des Scopes</span>
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addScopeModal">
                <i class="fas fa-plus me-1"></i>
                Ajouter un scope
            </button>
        </div>
        <div class="card-body">
            @php
                $allScopes = [
                    'profile' => [
                        'name' => 'Profil de base',
                        'description' => 'Nom, prénom et statut de vérification',
                        'icon' => 'user'
                    ],
                    'email' => [
                        'name' => 'Adresse email',
                        'description' => 'Email vérifié de l\'utilisateur',
                        'icon' => 'envelope'
                    ],
                    'phone' => [
                        'name' => 'Numéro de téléphone',
                        'description' => 'Numéro de téléphone vérifié',
                        'icon' => 'phone'
                    ],
                    'address' => [
                        'name' => 'Adresse postale',
                        'description' => 'Adresse de résidence complète',
                        'icon' => 'home'
                    ],
                    'birthdate' => [
                        'name' => 'Date de naissance',
                        'description' => 'Date de naissance de l\'utilisateur',
                        'icon' => 'birthday-cake'
                    ],
                    'photo' => [
                        'name' => 'Photo de profil',
                        'description' => 'Photo de profil de l\'utilisateur',
                        'icon' => 'camera'
                    ],
                    'documents' => [
                        'name' => 'Documents d\'identité',
                        'description' => 'Statut de vérification des documents',
                        'icon' => 'id-card'
                    ],
                ];
                $currentScopes = $application->allowed_scopes ?? [];
            @endphp

            <div class="row g-3">
                @foreach($allScopes as $scopeKey => $scopeInfo)
                    @php
                        $isGranted = in_array($scopeKey, $currentScopes);
                    @endphp
                    <div class="col-md-6">
                        <div class="card {{ $isGranted ? 'border-success' : 'border-secondary' }} h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-{{ $scopeInfo['icon'] }} {{ $isGranted ? 'text-success' : 'text-secondary' }} fa-lg me-3"></i>
                                        <div>
                                            <h6 class="mb-0">
                                                {{ $scopeInfo['name'] }}
                                                @if($isGranted)
                                                    <span class="badge bg-success ms-2">
                                                        <i class="fas fa-check"></i> Autorisé
                                                    </span>
                                                @endif
                                            </h6>
                                            <small class="text-muted">{{ $scopeInfo['description'] }}</small>
                                        </div>
                                    </div>
                                </div>
                                @if($isGranted)
                                    <form method="POST" action="{{ route('admin.oauth.remove-scope', [$application, $scopeKey]) }}" class="d-inline" onsubmit="return confirm('Retirer ce scope ? Les utilisateurs devront réautoriser l\'application.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Utilisateurs autorisés -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users"></i> Utilisateurs autorisés</span>
                <a href="{{ route('admin.oauth.users', $application) }}" class="btn btn-sm btn-primary">
                    Voir tous les utilisateurs
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($application->userAuthorizations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Scopes autorisés</th>
                                <th>Accordé le</th>
                                <th>Dernière utilisation</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($application->userAuthorizations->take(10) as $auth)
                                <tr>
                                    <td>
                                        {{ $auth->user->first_name }} {{ $auth->user->last_name }}<br>
                                        <small class="text-muted">{{ $auth->user->email }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $authScopes = is_array($auth->scopes)
                                                ? $auth->scopes
                                                : json_decode($auth->scopes, true) ?? [];
                                        @endphp
                                        @foreach($authScopes as $scope)
                                            <span class="badge bg-secondary">{{ $scope }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <small>{{ $auth->granted_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <small>
                                            @if($auth->last_used_at)
                                                {{ $auth->last_used_at->diffForHumans() }}
                                            @else
                                                Jamais
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        @if($auth->revoked_at)
                                            <span class="badge bg-danger">Révoquée</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($application->userAuthorizations->count() > 10)
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Affichage de 10 sur {{ $application->userAuthorizations->count() }} autorisations
                        </small>
                    </div>
                @endif
            @else
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i>
                    Aucun utilisateur n'a encore autorisé cette application.
                </div>
            @endif
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

<!-- Modal Ajouter un Scope -->
<div class="modal fade" id="addScopeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.oauth.add-scope', $application) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Ajouter un scope
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Sélectionnez un scope à ajouter à l'application <strong>{{ $application->name }}</strong></p>

                    @php
                        $allScopes = \App\Services\OAuthScopeService::AVAILABLE_SCOPES;
                        $currentScopes = $application->allowed_scopes ?? [];
                        $notGranted = array_diff_key($allScopes, array_flip($currentScopes));
                    @endphp

                    @if(empty($notGranted))
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Tous les scopes sont déjà autorisés pour cette application.
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="scope" class="form-label">Scope <span class="text-danger">*</span></label>
                            <select name="scope" id="scope" class="form-select" required>
                                <option value="">-- Sélectionnez un scope --</option>

                                @if(count(array_filter($notGranted, fn($s) => $s['category'] === 'standard')) > 0)
                                    <optgroup label="📋 Scopes Standard">
                                        @foreach($notGranted as $key => $data)
                                            @if($data['category'] === 'standard')
                                                <option value="{{ $key }}">{{ $data['name'] }} - {{ $data['description'] }}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if(count(array_filter($notGranted, fn($s) => $s['category'] === 'partner')) > 0)
                                    <optgroup label="🔐 Scopes Partenaire (Réservés)">
                                        @foreach($notGranted as $key => $data)
                                            @if($data['category'] === 'partner')
                                                <option value="{{ $key }}" style="background: #fff3cd;">
                                                    🔒 {{ $data['name'] }} - {{ $data['description'] }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note :</strong> Les utilisateurs ayant déjà autorisé cette application ne seront pas affectés.
                            Les nouveaux scopes ne seront demandés qu'aux nouveaux utilisateurs.
                        </div>

                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Scopes Partenaire :</strong> Les scopes préfixés par "partner:" sont réservés aux partenaires officiels approuvés.
                            Ils donnent des privilèges élevés et doivent être attribués avec prudence.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    @if(!empty($notGranted))
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Ajouter
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Config Modal --}}
<div class="modal fade" id="editConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.oauth.update-config', $application) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-cog me-2"></i>
                        Modifier la configuration OAuth
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="config_name" class="form-label">Nom de l'application <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="config_name" class="form-control"
                               value="{{ $application->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="config_description" class="form-label">Description</label>
                        <textarea name="description" id="config_description" class="form-control"
                                  rows="3">{{ $application->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="config_website" class="form-label">Site web</label>
                        <input type="url" name="website" id="config_website" class="form-control"
                               value="{{ $application->website }}" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label for="config_redirect_uris" class="form-label">URLs de callback <span class="text-danger">*</span></label>
                        <textarea name="redirect_uris" id="config_redirect_uris" class="form-control font-monospace"
                                  rows="4" required placeholder="Une URL par ligne">{{ implode("\n", $application->redirect_uris ?? []) }}</textarea>
                        <div class="form-text">Entrez une URL par ligne. Ex: <code>kaypa://auth/callback</code></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_trusted" value="0">
                            <input class="form-check-input" type="checkbox" name="is_trusted" id="config_is_trusted"
                                   value="1" {{ $application->is_trusted ? 'checked' : '' }}>
                            <label class="form-check-label" for="config_is_trusted">
                                <strong>Application de confiance</strong>
                                <br><small class="text-muted">Les applications de confiance peuvent ignorer l'écran de consentement</small>
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Le <strong>Client ID</strong> ne peut pas être modifié. Pour changer le <strong>Client Secret</strong>, utilisez le bouton "Régénérer".
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-save me-1"></i> Enregistrer
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
