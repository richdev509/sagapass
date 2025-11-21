@extends('admin.layouts.admin')

@section('title', 'Profil Citoyen - ' . $citizen->first_name . ' ' . $citizen->last_name)

@section('content')
<div class="container-fluid">
    <!-- Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.citizens.index') }}">Citoyens</a></li>
            <li class="breadcrumb-item active">{{ $citizen->first_name }} {{ $citizen->last_name }}</li>
        </ol>
    </nav>

    <!-- Messages Flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- En-tête du profil -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 text-center">
                    @if($citizen->profile_photo)
                        <img src="{{ asset('storage/' . $citizen->profile_photo) }}"
                             class="rounded-circle" width="120" height="120" alt="Photo de profil">
                    @else
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto"
                             style="width: 120px; height: 120px; font-size: 48px;">
                            {{ strtoupper(substr($citizen->first_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-7">
                    <h2 class="mb-2">{{ $citizen->first_name }} {{ $citizen->last_name }}</h2>
                    <p class="text-muted mb-1">
                        <i class="fas fa-envelope"></i> {{ $citizen->email }}
                        @if($citizen->email_verified_at)
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check-circle"></i> Email vérifié
                            </span>
                        @else
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-exclamation-circle"></i> Email non vérifié
                            </span>
                        @endif
                    </p>
                    @if($citizen->phone)
                    <p class="text-muted mb-1">
                        <i class="fas fa-phone"></i> {{ $citizen->phone }}
                    </p>
                    @endif
                    <p class="text-muted mb-1">
                        <i class="fas fa-calendar"></i> Inscrit le {{ $citizen->created_at->format('d/m/Y à H:i') }}
                        <small>(il y a {{ $stats['account_age_days'] }} jours)</small>
                    </p>
                    <div class="mt-3">
                        @php
                            $verificationBadge = match($citizen->verification_status) {
                                'verified' => ['bg' => 'success', 'icon' => 'check-circle', 'text' => 'Vérifié'],
                                'pending' => ['bg' => 'warning', 'icon' => 'clock', 'text' => 'En attente'],
                                'rejected' => ['bg' => 'danger', 'icon' => 'times-circle', 'text' => 'Rejeté'],
                                default => ['bg' => 'secondary', 'icon' => 'question-circle', 'text' => 'Inconnu']
                            };
                            $statusBadge = match($citizen->account_status) {
                                'active' => ['bg' => 'success', 'icon' => 'check-circle', 'text' => 'Actif'],
                                'suspended' => ['bg' => 'danger', 'icon' => 'pause', 'text' => 'Suspendu'],
                                'inactive' => ['bg' => 'secondary', 'icon' => 'ban', 'text' => 'Inactif'],
                                default => ['bg' => 'secondary', 'icon' => 'question-circle', 'text' => 'Inconnu']
                            };
                        @endphp
                        <span class="badge bg-{{ $verificationBadge['bg'] }} me-2">
                            <i class="fas fa-{{ $verificationBadge['icon'] }}"></i> {{ $verificationBadge['text'] }}
                        </span>
                        <span class="badge bg-{{ $statusBadge['bg'] }}">
                            <i class="fas fa-{{ $statusBadge['icon'] }}"></i> {{ $statusBadge['text'] }}
                        </span>
                        @if($citizen->is_developer)
                            <span class="badge bg-info">
                                <i class="fas fa-code"></i> Développeur
                            </span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    @can('edit-users', 'admin')
                    <button class="btn btn-primary btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    @endcan

                    @can('suspend-users', 'admin')
                        @if($citizen->account_status !== 'suspended')
                        <button class="btn btn-warning btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#suspendModal">
                            <i class="fas fa-pause"></i> Suspendre
                        </button>
                        @endif
                    @endcan

                    @can('activate-users', 'admin')
                        @if($citizen->account_status === 'suspended')
                        <form action="{{ route('admin.citizens.activate', $citizen->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm mb-2">
                                <i class="fas fa-play"></i> Activer
                            </button>
                        </form>
                        @endif
                    @endcan

                    @can('reset-user-password', 'admin')
                    <button class="btn btn-secondary btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                        <i class="fas fa-key"></i> Réinitialiser mot de passe
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Documents</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_documents'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Documents Vérifiés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['verified_documents'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">En Attente</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_documents'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Apps OAuth</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['oauth_apps_count'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Onglets de contenu -->
    <ul class="nav nav-tabs" id="citizenTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">
                <i class="fas fa-user"></i> Informations Personnelles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button">
                <i class="fas fa-file-alt"></i> Documents ({{ $stats['total_documents'] }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="oauth-tab" data-bs-toggle="tab" data-bs-target="#oauth" type="button">
                <i class="fas fa-shield-alt"></i> Autorisations OAuth ({{ $stats['oauth_apps_count'] }})
            </button>
        </li>
        @if($citizen->is_developer)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="developer-tab" data-bs-toggle="tab" data-bs-target="#developer" type="button">
                <i class="fas fa-code"></i> Informations Développeur
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button">
                <i class="fas fa-history"></i> Activité ({{ $activities->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="citizenTabContent">
        <!-- Onglet Informations Personnelles -->
        <div class="tab-pane fade show active" id="info" role="tabpanel">
            <div class="card shadow mt-3">
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">ID</th>
                            <td>{{ $citizen->id }}</td>
                        </tr>
                        <tr>
                            <th>Prénom</th>
                            <td>{{ $citizen->first_name }}</td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td>{{ $citizen->last_name }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>
                                {{ $citizen->email }}
                                @if($citizen->email_verified_at)
                                    <span class="badge bg-success ms-2">Vérifié le {{ $citizen->email_verified_at->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge bg-warning ms-2">Non vérifié</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Téléphone</th>
                            <td>{{ $citizen->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Date de naissance</th>
                            <td>
                                @if($citizen->date_of_birth)
                                    {{ \Carbon\Carbon::parse($citizen->date_of_birth)->format('d/m/Y') }}
                                    <small class="text-muted">({{ \Carbon\Carbon::parse($citizen->date_of_birth)->age }} ans)</small>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Adresse</th>
                            <td>{{ $citizen->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Statut de vérification</th>
                            <td>
                                <span class="badge bg-{{ $verificationBadge['bg'] }}">
                                    {{ $verificationBadge['text'] }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Statut du compte</th>
                            <td>
                                <span class="badge bg-{{ $statusBadge['bg'] }}">
                                    {{ $statusBadge['text'] }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Type de compte</th>
                            <td>
                                @if($citizen->is_developer)
                                    <span class="badge bg-info">Développeur</span>
                                @else
                                    <span class="badge bg-secondary">Citoyen</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Date d'inscription</th>
                            <td>{{ $citizen->created_at->format('d/m/Y à H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Dernière mise à jour</th>
                            <td>{{ $citizen->updated_at->format('d/m/Y à H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Onglet Documents -->
        <div class="tab-pane fade" id="documents" role="tabpanel">
            <div class="card shadow mt-3">
                <div class="card-body">
                    @if($citizen->documents->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Numéro</th>
                                        <th>Date d'upload</th>
                                        <th>Statut</th>
                                        <th>Vérifié par</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($citizen->documents as $document)
                                    <tr>
                                        <td>
                                            @if($document->document_type === 'cni')
                                                <span class="badge bg-primary"><i class="fas fa-id-card me-1"></i>CNI</span>
                                            @else
                                                <span class="badge bg-info"><i class="fas fa-passport me-1"></i>Passeport</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($document->document_type === 'cni' && $document->card_number)
                                                <div><small class="text-muted">N° Carte:</small> <code class="text-primary">{{ $document->card_number }}</code></div>
                                            @endif
                                            <div><small class="text-muted">N° Doc:</small> <code class="text-dark">{{ $document->document_number }}</code></div>
                                        </td>
                                        <td>{{ $document->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @php
                                                $docBadge = match($document->verification_status) {
                                                    'verified' => 'success',
                                                    'pending' => 'warning',
                                                    'rejected' => 'danger',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $docBadge }}">
                                                {{ ucfirst($document->verification_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($document->verifiedBy)
                                                {{ $document->verifiedBy->name }}
                                                <br><small class="text-muted">{{ $document->verified_at?->format('d/m/Y H:i') }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.verification.show', $document->id) }}"
                                               class="btn btn-sm btn-info" target="_blank">
                                                <i class="fas fa-eye"></i> Voir
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x"></i>
                            <p class="mt-2">Aucun document uploadé</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Onglet OAuth -->
        <div class="tab-pane fade" id="oauth" role="tabpanel">
            <div class="card shadow mt-3">
                <div class="card-body">
                    @if($citizen->oauthAuthorizations && $citizen->oauthAuthorizations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Application</th>
                                        <th>Autorisé le</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($citizen->oauthAuthorizations as $auth)
                                    <tr>
                                        <td>
                                            @if($auth->application)
                                                <strong>{{ $auth->application->application_name }}</strong>
                                                <br><small class="text-muted">{{ $auth->application->website_url }}</small>
                                            @else
                                                <span class="text-muted">Application supprimée</span>
                                            @endif
                                        </td>
                                        <td>{{ $auth->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($auth->revoked_at)
                                                <span class="badge bg-secondary">Révoqué le {{ $auth->revoked_at->format('d/m/Y') }}</span>
                                            @else
                                                <span class="badge bg-success">Actif</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-shield-alt fa-3x"></i>
                            <p class="mt-2">Aucune autorisation OAuth</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Onglet Développeur -->
        @if($citizen->is_developer)
        <div class="tab-pane fade" id="developer" role="tabpanel">
            <div class="card shadow mt-3">
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Nom de l'entreprise</th>
                            <td>{{ $citizen->company_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Site web</th>
                            <td>
                                @if($citizen->developer_website)
                                    <a href="{{ $citizen->developer_website }}" target="_blank">{{ $citizen->developer_website }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Bio/Description</th>
                            <td>{{ $citizen->developer_bio ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Statut développeur</th>
                            <td>
                                @if($citizen->developer_verified_at)
                                    <span class="badge bg-success">
                                        Vérifié le {{ \Carbon\Carbon::parse($citizen->developer_verified_at)->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="badge bg-warning">En attente de vérification</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Onglet Activité -->
        <div class="tab-pane fade" id="activity" role="tabpanel">
            <div class="card shadow mt-3">
                <div class="card-body">
                    @if($activities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Date/Heure</th>
                                        <th>Action</th>
                                        <th>Effectué par</th>
                                        <th>Description</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activities as $activity)
                                    <tr>
                                        <td>{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td>
                                            <code>{{ $activity->action }}</code>
                                        </td>
                                        <td>
                                            @if($activity->admin)
                                                {{ $activity->admin->name }}
                                            @else
                                                <span class="text-muted">Système</span>
                                            @endif
                                        </td>
                                        <td>{{ $activity->description }}</td>
                                        <td><small>{{ $activity->ip_address }}</small></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-history fa-3x"></i>
                            <p class="mt-2">Aucune activité enregistrée</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Édition -->
@can('edit-users', 'admin')
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.citizens.update', $citizen->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Modifier les informations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   value="{{ $citizen->first_name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   value="{{ $citizen->last_name }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ $citizen->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                               value="{{ $citizen->phone }}">
                    </div>
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label">Date de naissance</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                               value="{{ $citizen->date_of_birth }}">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Adresse</label>
                        <textarea class="form-control" id="address" name="address" rows="2">{{ $citizen->address }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- Modal Suspension -->
@can('suspend-users', 'admin')
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.citizens.suspend', $citizen->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Suspendre le compte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Êtes-vous sûr de vouloir suspendre ce compte ?
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Raison *</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-pause"></i> Suspendre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- Modal Réinitialiser mot de passe -->
@can('reset-user-password', 'admin')
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.citizens.reset-password', $citizen->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Réinitialiser le mot de passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password"
                               minlength="8" required>
                        <small class="form-text text-muted">Minimum 8 caractères</small>
                    </div>
                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Confirmer le mot de passe *</label>
                        <input type="password" class="form-control" id="new_password_confirmation"
                               name="new_password_confirmation" minlength="8" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection
