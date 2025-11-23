@extends('admin.layouts.admin')

@section('title', 'Vérification vidéo - ' . $user->full_name)

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">Vérification de la vidéo</h1>
            <p class="text-muted mb-0">{{ $user->full_name }} - {{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.video-verification.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row">
        <!-- Informations utilisateur -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Informations utilisateur</h5>
                </div>
                <div class="card-body">
                    <!-- Photo de profil -->
                    <div class="text-center mb-4">
                        @if($user->profile_picture)
                            <img src="{{ $user->profile_picture_url }}" alt="Photo de profil" class="rounded-circle border border-3 border-primary" style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center border border-3 border-primary" style="width: 150px; height: 150px;">
                                <i class="fas fa-user fa-4x text-white"></i>
                            </div>
                        @endif
                    </div>

                    <table class="table table-sm">
                        <tr>
                            <th width="120">Nom complet:</th>
                            <td>{{ $user->full_name }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <th>Téléphone:</th>
                            <td>{{ $user->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Date naissance:</th>
                            <td>{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d/m/Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Inscription:</th>
                            <td>
                                {{ $user->created_at->format('d/m/Y H:i') }}
                                <br>
                                <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                            </td>
                        </tr>
                        <tr>
                            <th>Niveau compte:</th>
                            <td>
                                <span class="badge bg-{{ $user->account_level === 'verified' ? 'success' : 'info' }}">
                                    {{ ucfirst($user->account_level) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Statut vidéo:</th>
                            <td>
                                @if($user->video_status === 'pending')
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @elseif($user->video_status === 'approved')
                                    <span class="badge bg-success">Approuvée</span>
                                @elseif($user->video_status === 'rejected')
                                    <span class="badge bg-danger">Rejetée</span>
                                @else
                                    <span class="badge bg-secondary">Aucune</span>
                                @endif
                            </td>
                        </tr>
                        @if($user->video_consent_at)
                        <tr>
                            <th>Consentement:</th>
                            <td>
                                <i class="fas fa-check-circle text-success"></i>
                                {{ \Carbon\Carbon::parse($user->video_consent_at)->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Historique des vérifications -->
            @if($user->videoVerifications->count() > 0)
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-history"></i> Historique</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($user->videoVerifications as $verification)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-{{ $verification->status === 'approved' ? 'success' : ($verification->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($verification->status) }}
                                    </span>
                                    <br>
                                    <small class="text-muted">{{ $verification->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                            </div>
                            @if($verification->reviewed_by)
                            <small class="text-muted">
                                Par: {{ $verification->reviewer->name ?? 'Admin' }}
                            </small>
                            @endif
                            @if($verification->rejection_reason)
                            <div class="mt-2">
                                <small class="text-danger">{{ $verification->rejection_reason }}</small>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Vidéo de vérification -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-video"></i> Vidéo de vérification</h5>
                </div>
                <div class="card-body">
                    @if($user->verification_video)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Instructions de vérification :</strong>
                            <ul class="mb-0 mt-2">
                                <li>L'utilisateur doit dire son nom clairement</li>
                                <li>Tourner la tête vers la gauche</li>
                                <li>Tourner la tête vers la droite</li>
                                <li>Durée maximale : 15 secondes</li>
                            </ul>
                        </div>

                        <!-- Lecteur vidéo -->
                        <div class="text-center mb-4">
                            <video
                                id="verification-video"
                                controls
                                style="max-width: 100%; width: 640px; height: auto; border-radius: 10px; border: 3px solid #ffc107;"
                                preload="metadata">
                                <source src="{{ route('admin.video-verification.video', $user) }}" type="video/webm">
                                Votre navigateur ne supporte pas la lecture de vidéos.
                            </video>
                        </div>

                        <!-- Contrôles vidéo -->
                        <div class="d-flex justify-content-center gap-2 mb-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('verification-video').currentTime = 0">
                                <i class="fas fa-redo"></i> Recommencer
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('verification-video').playbackRate = 0.5">
                                <i class="fas fa-tachometer-alt"></i> x0.5
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('verification-video').playbackRate = 1">
                                <i class="fas fa-play"></i> x1
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('verification-video').playbackRate = 1.5">
                                <i class="fas fa-forward"></i> x1.5
                            </button>
                        </div>

                        <!-- Actions de vérification -->
                        @if($user->video_status === 'pending')
                        <div class="row g-3">
                            <!-- Approuver -->
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-check-circle"></i> Approuver</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('admin.video-verification.approve', $user) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir approuver cette vidéo ?')">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">Notes internes (optionnel)</label>
                                                <textarea name="notes" class="form-control" rows="3" placeholder="Notes pour le dossier..."></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fas fa-check"></i> Approuver la vidéo
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Rejeter -->
                            <div class="col-md-6">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="fas fa-times-circle"></i> Rejeter</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('admin.video-verification.reject', $user) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette vidéo ?')">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">Raison du rejet *</label>
                                                <textarea name="rejection_reason" class="form-control @error('rejection_reason') is-invalid @enderror" rows="3" placeholder="Expliquez pourquoi vous rejetez cette vidéo..." required></textarea>
                                                @error('rejection_reason')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Cette raison sera envoyée à l'utilisateur</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Notes internes (optionnel)</label>
                                                <textarea name="notes" class="form-control" rows="2" placeholder="Notes pour le dossier..."></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fas fa-times"></i> Rejeter la vidéo
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Cette vidéo a déjà été traitée.
                            @if($user->video_status === 'approved')
                                <strong class="text-success">Statut : Approuvée</strong>
                            @elseif($user->video_status === 'rejected')
                                <strong class="text-danger">Statut : Rejetée</strong>
                                @if($user->video_rejection_reason)
                                <p class="mb-0 mt-2"><strong>Raison :</strong> {{ $user->video_rejection_reason }}</p>
                                @endif
                            @endif
                        </div>
                        @endif

                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Aucune vidéo de vérification n'a été soumise par cet utilisateur.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
