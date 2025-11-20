@extends('layouts.app')

@section('title', 'Tableau de Bord')

@section('content')
<div class="container">
    <!-- En-tête de bienvenue -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card gradient-bg text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h2 class="mb-1">Bienvenue, {{ $user->first_name }} {{ $user->last_name }} !</h2>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-envelope me-2"></i>{{ $user->email }}
                                @if($user->email_verified_at)
                                    <span class="badge bg-success ms-2">
                                        <i class="fas fa-check-circle"></i> Email vérifié
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark ms-2">
                                        <i class="fas fa-exclamation-triangle"></i> Email non vérifié
                                    </span>
                                @endif
                            </p>
                            @if($user->verification_status === 'verified')
                                <span class="badge bg-success mt-2">
                                    <i class="fas fa-check-circle"></i> Compte Vérifié
                                </span>
                            @elseif($user->verification_status === 'pending')
                                <span class="badge bg-warning text-dark mt-2">
                                    <i class="fas fa-clock"></i> Vérification en cours
                                </span>
                            @endif
                        </div>
                        @if($user->profile_photo)
                            <img src="{{ asset('storage/' . $user->profile_photo) }}"
                                 class="rounded-circle"
                                 style="width: 80px; height: 80px; object-fit: cover; border: 3px solid white;">
                        @else
                            <div class="rounded-circle bg-white d-flex align-items-center justify-content-center"
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x text-secondary"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$user->email_verified_at)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Action requise :</strong> Votre adresse email n'est pas encore vérifiée.
            <a href="{{ route('verification.notice') }}" class="alert-link">Cliquez ici pour vérifier votre email</a>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$user->phone || !$user->date_of_birth)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Profil incomplet :</strong> Vous devez compléter votre profil (téléphone et date de naissance) avant de pouvoir soumettre des documents.
            <a href="{{ route('profile.edit') }}" class="alert-link">Compléter mon profil maintenant</a>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-id-card fa-3x"></i>
                    </div>
                    <h3 class="mb-1">{{ $stats['documents'] }}</h3>
                    <p class="text-muted mb-0">Documents</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                    <h3 class="mb-1">{{ $stats['documents_verified'] }}</h3>
                    <p class="text-muted mb-0">Vérifiés</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-clock fa-3x"></i>
                    </div>
                    <h3 class="mb-1">{{ $stats['documents_pending'] }}</h3>
                    <p class="text-muted mb-0">En attente</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-plug fa-3x"></i>
                    </div>
                    <h3 class="mb-1">{{ $stats['connected_services'] }}</h3>
                    <p class="text-muted mb-0">Services connectés</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Derniers documents -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>Mes Documents
                    </h5>
                    @if($user->email_verified_at)
                        <a href="{{ route('documents.create') }}" class="btn btn-sm btn-primary-custom">
                            <i class="fas fa-plus"></i> Ajouter
                        </a>
                    @else
                        <button class="btn btn-sm btn-secondary" disabled title="Vérifiez votre email pour ajouter des documents">
                            <i class="fas fa-lock"></i> Ajouter
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if($recentDocuments->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                            <p>Aucun document pour le moment</p>
                            @if($user->email_verified_at)
                                <a href="{{ route('documents.create') }}" class="btn btn-primary-custom">
                                    Ajouter votre premier document
                                </a>
                            @else
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-lock me-2"></i>Vérifiez votre email pour ajouter des documents
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($recentDocuments as $doc)
                                <a href="{{ route('documents.show', $doc->id) }}"
                                   class="list-group-item list-group-item-action border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="fas fa-id-card me-2 text-primary"></i>
                                                {{ $doc->document_type === 'cni' ? 'Carte Nationale d\'Identité' : 'Passeport' }}
                                            </h6>
                                            <small class="text-muted">
                                                N° {{ $doc->document_number }} •
                                                Ajouté le {{ $doc->created_at->format('d/m/Y') }}
                                            </small>
                                        </div>
                                        @if($doc->verification_status === 'verified')
                                            <span class="badge badge-status-verified">
                                                <i class="fas fa-check"></i> Vérifié
                                            </span>
                                        @elseif($doc->verification_status === 'pending')
                                            <span class="badge badge-status-pending">
                                                <i class="fas fa-clock"></i> En attente
                                            </span>
                                        @else
                                            <span class="badge badge-status-rejected">
                                                <i class="fas fa-times"></i> Rejeté
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-3 text-center">
                            <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-primary">
                                Voir tous les documents
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Services connectés -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-link me-2"></i>Services Connectés
                    </h5>
                </div>
                <div class="card-body">
                    @if($recentConsents->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-unlink fa-3x mb-3 opacity-50"></i>
                            <p>Aucun service connecté</p>
                            <small>Vous n'avez pas encore autorisé d'applications tierces</small>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($recentConsents as $consent)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            @if($consent->application && $consent->application->logo_path)
                                                <img src="{{ asset('storage/' . $consent->application->logo_path) }}"
                                                     class="rounded me-3"
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-cube text-secondary"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <h6 class="mb-0">{{ $consent->application->name ?? 'Service inconnu' }}</h6>
                                                <small class="text-muted">
                                                    Connecté le {{ $consent->granted_at->format('d/m/Y') }}
                                                </small>
                                            </div>
                                        </div>
                                        @if($consent->revoked_at)
                                            <span class="badge bg-secondary">Révoqué</span>
                                        @else
                                            <span class="badge bg-success">Actif</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Actions Rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <a href="{{ route('documents.create') }}" class="text-decoration-none">
                                <div class="p-3 hover-shadow rounded">
                                    <i class="fas fa-upload fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-1">Ajouter un Document</h6>
                                    <small class="text-muted">Téléversez votre CNI ou passeport</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <a href="{{ route('profile.edit') }}" class="text-decoration-none">
                                <div class="p-3 hover-shadow rounded">
                                    <i class="fas fa-user-edit fa-2x text-success mb-2"></i>
                                    <h6 class="mb-1">Modifier mon Profil</h6>
                                    <small class="text-muted">Mettez à jour vos informations</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('documents.index') }}" class="text-decoration-none">
                                <div class="p-3 hover-shadow rounded">
                                    <i class="fas fa-folder-open fa-2x text-info mb-2"></i>
                                    <h6 class="mb-1">Mes Documents</h6>
                                    <small class="text-muted">Consultez tous vos documents</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .hover-shadow {
        transition: all 0.3s;
    }
    .hover-shadow:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
</style>
@endpush
