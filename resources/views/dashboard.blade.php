@extends('layouts.app')

@section('title', 'Tableau de Bord')

@section('content')
<!-- Beta Banner -->
@include('components.beta-banner')

<div class="mobile-app-container">
    <!-- En-tête de bienvenue -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card gradient-bg text-white">
                <div class="card-body p-4">
                    <!-- Titre centré en haut -->
                    <h2 class="text-center mb-3">Bienvenue, {{ $user->first_name }} {{ $user->last_name }} !</h2>

                    <!-- Badges à gauche + Photo à droite -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <!-- Badge niveau de compte -->
                            <div class="badges-inline mb-2">
                                @if($user->account_level === 'verified')
                                    <span class="badge bg-success badge-compact">
                                        <i class="fas fa-shield-alt"></i> Verified
                                    </span>
                                @elseif($user->account_level === 'basic')
                                    <span class="badge bg-info badge-compact">
                                        <i class="fas fa-user"></i> Basic
                                    </span>
                                @else
                                    <span class="badge bg-secondary badge-compact">
                                        <i class="fas fa-hourglass-half"></i> Pending
                                    </span>
                                @endif

                                @if($user->verification_status === 'verified')
                                    <span class="badge bg-success badge-compact">
                                        <i class="fas fa-check-circle"></i> Complète
                                    </span>
                                @elseif($user->verification_status === 'pending')
                                    <span class="badge bg-warning text-dark badge-compact">
                                        <i class="fas fa-clock"></i> En cours
                                    </span>
                                @endif

                                @if($user->email_verified_at)
                                    <span class="badge bg-success badge-compact">
                                        <i class="fas fa-envelope"></i> Email ✓
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark badge-compact">
                                        <i class="fas fa-envelope"></i> Email ✗
                                    </span>
                                @endif
                            </div>

                            <!-- Email en dessous -->
                            <p class="mb-0 opacity-75 small" style="word-wrap: break-word;">
                                <i class="fas fa-envelope me-1"></i>{{ $user->email }}
                            </p>
                        </div>

                        <!-- Photo à droite -->
                        <div class="flex-shrink-0 ms-3">
                            @if($user->profile_photo)
                                <img src="{{ asset('storage/' . $user->profile_photo) }}"
                                     class="rounded-circle profile-pic"
                                     style="width: 70px; height: 70px; object-fit: cover; border: 3px solid white;">
                            @else
                                <div class="rounded-circle bg-white d-flex align-items-center justify-content-center profile-pic"
                                     style="width: 70px; height: 70px;">
                                    <i class="fas fa-user fa-2x text-secondary"></i>
                                </div>
                            @endif
                        </div>
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

    <!-- Support Links -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="support-links">
                <a href="{{ route('known-errors') }}" class="support-link">
                    <i class="fas fa-book-open"></i>
                    Erreurs Connues
                </a>
                <a href="{{ \App\Models\SystemSetting::getWhatsAppLink() }}" target="_blank" class="support-link">
                    <i class="fab fa-whatsapp"></i>
                    Signaler un Problème
                </a>
            </div>
        </div>
    </div>

    {{-- Alerte compte Pending : doit soumettre vidéo --}}
    @if($user->account_level === 'pending' && $user->video_status === 'none')
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-video fa-2x me-3 text-warning"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>Compte en attente d'activation
                    </h5>
                    <p class="mb-2">
                        Votre compte est en mode <strong>Pending</strong>. Pour passer au niveau <strong>Basic</strong>, vous devez soumettre une photo et une vidéo de vérification.
                    </p>
                    <a href="{{ route('video.recapture') }}" class="btn btn-warning">
                        <i class="fas fa-video me-2"></i>Soumettre ma vidéo maintenant
                    </a>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($user->video_status === 'rejected')
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-video-slash fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>Vidéo de vérification rejetée
                    </h5>
                    <p class="mb-2">
                        <strong>Raison du rejet :</strong> {{ $user->video_rejection_reason ?? 'Non spécifiée' }}
                    </p>
                    <p class="mb-3">
                        Votre vidéo de vérification n'a pas été approuvée. Veuillez en enregistrer une nouvelle en suivant les instructions.
                    </p>
                    <a href="{{ route('video.recapture') }}" class="btn btn-danger">
                        <i class="fas fa-redo me-2"></i>Enregistrer une nouvelle vidéo
                    </a>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @elseif($user->video_status === 'pending')
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-clock me-2"></i>
            <strong>Vidéo de vérification en cours :</strong> Votre vidéo est en cours d'examen par notre équipe. Vous recevrez une notification dès qu'elle sera traitée.
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
        <!-- Badge Numérique -->
        <div class="col-lg-6 mb-4">
            @if($user->account_level === 'basic' || $user->account_level === 'verified')
                @php
                    // Générer ou récupérer le badge actif
                    $activeBadge = \App\Models\DigitalBadge::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->where('expires_at', '>', now())
                        ->first();

                    if (!$activeBadge) {
                        $activeBadge = \App\Models\DigitalBadge::generateForUser($user, request()->ip(), request()->userAgent());
                    }

                    // Générer le QR code
                    $validationUrl = $activeBadge->getValidationUrl();
                    $writer = new \Endroid\QrCode\Writer\SvgWriter();
                    $qrCode = new \Endroid\QrCode\QrCode($validationUrl);
                    $result = $writer->write($qrCode);
                    $qrCodeSvg = $result->getString();
                @endphp
                @include('components.digital-badge', ['badge' => $activeBadge, 'qrCode' => $qrCodeSvg, 'user' => $user])
            @endif
        </div>

        <!-- Derniers documents -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>Mes Documents
                    </h5>
                    @if($user->email_verified_at && $user->video_status === 'approved')
                        <a href="{{ route('documents.create') }}" class="btn btn-sm btn-primary-custom">
                            <i class="fas fa-plus"></i> Ajouter
                        </a>
                    @elseif($user->video_status !== 'approved')
                        <button class="btn btn-sm btn-secondary" disabled title="Votre vidéo doit être approuvée pour ajouter des documents">
                            <i class="fas fa-lock"></i> Ajouter
                        </button>
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
                            @if($user->email_verified_at && $user->video_status === 'approved')
                                <a href="{{ route('documents.create') }}" class="btn btn-primary-custom">
                                    Ajouter votre premier document
                                </a>
                            @elseif($user->video_status !== 'approved')
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Votre vidéo doit être approuvée avant de pouvoir ajouter des documents.
                                </div>
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
                            @if($user->email_verified_at && $user->video_status === 'approved')
                                <a href="{{ route('documents.create') }}" class="text-decoration-none">
                                    <div class="p-3 hover-shadow rounded">
                                        <i class="fas fa-upload fa-2x text-primary mb-2"></i>
                                        <h6 class="mb-1">Ajouter un Document</h6>
                                        <small class="text-muted">Téléversez votre CNI ou passeport</small>
                                    </div>
                                </a>
                            @else
                                <div class="p-3 rounded bg-light text-muted">
                                    <i class="fas fa-lock fa-2x mb-2 opacity-50"></i>
                                    <h6 class="mb-1">Ajouter un Document</h6>
                                    <small>Vidéo requise</small>
                                </div>
                            @endif
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
    /* Design mobile-first optimisé */
    .mobile-app-container {
        max-width: 100%;
        padding: 0;
    }

    /* En-tête responsive */
    @media (max-width: 768px) {
        .mobile-app-container .card {
            border-radius: 16px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 12px;
        }

        /* Header avec photo qui ne déborde pas */
        .gradient-bg .card-body {
            padding: 1rem !important;
        }

        .gradient-bg h2 {
            font-size: 1.1rem !important;
            font-weight: 600;
            margin-bottom: 1rem !important;
        }

        .gradient-bg p {
            font-size: 0.75rem;
            word-break: break-word;
        }

        /* Badges compacts */
        .badge-compact {
            font-size: 0.65rem !important;
            padding: 0.25rem 0.5rem !important;
            font-weight: 500;
        }

        .gradient-bg .profile-pic {
            width: 60px !important;
            height: 60px !important;
        }

        /* GRILLE 2x2 pour statistiques sur mobile */
        .mobile-app-container .row.mb-4:not(:first-child) .col-md-3 {
            flex: 0 0 50%;
            max-width: 50%;
            padding-left: 6px;
            padding-right: 6px;
        }

        .mobile-app-container .row.mb-4 .card {
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .mobile-app-container .row.mb-4 .card-body {
            padding: 1.2rem 0.8rem;
        }

        .mobile-app-container .row.mb-4 h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem !important;
        }

        .mobile-app-container .row.mb-4 p {
            font-size: 0.75rem;
            margin-bottom: 0;
        }

        .mobile-app-container .row.mb-4 i {
            font-size: 2rem !important;
        }

        /* Documents et Services en pleine largeur sur mobile */
        .mobile-app-container > .row > .col-lg-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        /* Liste documents style app mobile */
        .list-group-item {
            border-radius: 12px !important;
            margin-bottom: 8px;
            padding: 12px !important;
            border: 1px solid #f0f0f0 !important;
        }

        .list-group-item h6 {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .list-group-item small {
            font-size: 0.75rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.35rem 0.6rem;
        }

        /* En-têtes de cartes */
        .card-header {
            background: white !important;
            border-bottom: 1px solid #f0f0f0 !important;
            padding: 1rem !important;
            border-radius: 16px 16px 0 0 !important;
        }

        .card-header h5 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        /* GRILLE pour actions rapides sur mobile */
        .mobile-app-container .card-body .row.text-center > [class*='col-md'] {
            flex: 0 0 50%;
            max-width: 50%;
            margin-bottom: 12px;
        }

        /* Dernière action (3ème) prend toute la largeur */
        .mobile-app-container .card-body .row.text-center > [class*='col-md']:last-child {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .hover-shadow {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 1.2rem !important;
            transition: all 0.2s;
            border: 1px solid #e0e0e0;
        }

        .hover-shadow:active {
            transform: scale(0.98);
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        }

        .hover-shadow i {
            font-size: 2rem !important;
            margin-bottom: 0.5rem;
        }

        .hover-shadow h6 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .hover-shadow small {
            font-size: 0.7rem;
            color: #6c757d;
        }

        /* Alerts responsive */
        .alert {
            border-radius: 12px;
            padding: 1rem;
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .alert-heading {
            font-size: 0.95rem !important;
        }

        /* Boutons responsive */
        .btn {
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-sm {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        /* Ajustement grille row */
        .mobile-app-container .row {
            margin-left: -6px;
            margin-right: -6px;
        }
    }

    /* Desktop - apparence normale */
    @media (min-width: 769px) {
        .mobile-app-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .hover-shadow {
            transition: all 0.3s;
        }

        .hover-shadow:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .card {
            border-radius: 12px;
        }
    }

    /* Améliorations communes */
    .list-group-flush .list-group-item:first-child {
        border-top: 0;
    }

    .list-group-flush .list-group-item:last-child {
        border-bottom: 0;
    }

    /* Badges en ligne */
    .badges-inline {
        display: flex;
        gap: 0.35rem;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .badges-inline::-webkit-scrollbar {
        display: none;
    }

    .badges-inline .badge {
        flex-shrink: 0;
        white-space: nowrap;
    }

    /* Espacement vertical uniforme */
    @media (max-width: 768px) {
        .mb-4 {
            margin-bottom: 1rem !important;
        }

        .mb-3 {
            margin-bottom: 0.75rem !important;
        }
    }

    /* Support links */
    .support-links {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .support-link {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        background: white;
        border-radius: 50px;
        text-decoration: none;
        color: #667eea;
        font-weight: 600;
        transition: all 0.3s;
        border: 2px solid #667eea;
    }

    .support-link:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    .support-link i {
        margin-right: 0.5rem;
    }
</style>
@endpush
