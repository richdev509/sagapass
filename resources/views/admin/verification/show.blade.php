@extends('admin.layouts.admin')

@section('title', 'Vérifier Document #' . $document->id)
@section('page-title', 'Vérification Document #' . $document->id)
@section('page-subtitle', 'Examiner les détails et valider le document')

@section('styles')
<style>
    .document-image {
        width: 100%;
        height: auto;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.3s;
    }

    .document-image:hover {
        transform: scale(1.02);
    }

    .info-row {
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #4a5568;
    }

    .info-value {
        color: #2d3748;
        font-weight: 500;
    }

    .action-buttons {
        position: sticky;
        top: 80px;
        z-index: 10;
    }

    /* Modal Image */
    .modal-image {
        max-width: 100%;
        height: auto;
    }

    /* Timeline Styles */
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        position: relative;
        padding-left: 50px;
        padding-bottom: 30px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 35px;
        bottom: -10px;
        width: 2px;
        background: #e2e8f0;
    }

    .timeline-item:last-child::before {
        display: none;
    }

    .timeline-marker {
        position: absolute;
        left: 10px;
        top: 8px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #dee2e6;
    }

    .timeline-content h6 {
        margin-bottom: 8px;
    }
</style>
@endsection

@section('content')
<div class="row g-4">
    <!-- Document Details -->
    <div class="col-lg-8">
        <!-- User Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user me-2"></i>Informations Utilisateur
            </div>
            <div class="card-body p-0">
                <div class="info-row">
                    <span class="info-label">Nom Complet</span>
                    <span class="info-value">{{ $document->user->first_name }} {{ $document->user->last_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value">{{ $document->user->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Téléphone</span>
                    <span class="info-value">{{ $document->user->phone ?? 'Non renseigné' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de Naissance</span>
                    <span class="info-value">
                        @if($document->user->date_of_birth)
                        {{ \Carbon\Carbon::parse($document->user->date_of_birth)->format('d/m/Y') }}
                        ({{ \Carbon\Carbon::parse($document->user->date_of_birth)->age }} ans)
                        @else
                        Non renseigné
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Compte créé le</span>
                    <span class="info-value">{{ $document->user->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email vérifié</span>
                    <span>
                        @if($document->user->email_verified_at)
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Oui</span>
                        @else
                        <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Non</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Document Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-file-alt me-2"></i>Informations Document
            </div>
            <div class="card-body p-0">
                <div class="info-row">
                    <span class="info-label">Type de Document</span>
                    <span>
                        @if($document->document_type === 'cni')
                        <span class="badge bg-primary"><i class="fas fa-id-card me-1"></i>Carte Nationale d'Identité</span>
                        @else
                        <span class="badge bg-info"><i class="fas fa-passport me-1"></i>Passeport</span>
                        @endif
                    </span>
                </div>
                @if($document->document_type === 'cni' && $document->card_number)
                <div class="info-row">
                    <span class="info-label">Numéro de Carte (CNI)</span>
                    <span class="info-value"><code class="text-primary fs-5">{{ $document->card_number }}</code></span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Numéro de Document</span>
                    <span class="info-value"><code class="text-dark fs-5">{{ $document->document_number }}</code></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de Délivrance</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date d'Expiration</span>
                    <span class="info-value">
                        {{ \Carbon\Carbon::parse($document->expiry_date)->format('d/m/Y') }}
                        @if(\Carbon\Carbon::parse($document->expiry_date)->isPast())
                        <span class="badge bg-danger ms-2">Expiré</span>
                        @elseif(\Carbon\Carbon::parse($document->expiry_date)->diffInDays(now()) < 30)
                        <span class="badge bg-warning ms-2">Expire dans {{ \Carbon\Carbon::parse($document->expiry_date)->diffInDays(now()) }} jours</span>
                        @else
                        <span class="badge bg-success ms-2">Valide</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lieu de Délivrance</span>
                    <span class="info-value">{{ $document->place_of_issue ?? 'Non renseigné' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de Soumission</span>
                    <span class="info-value">{{ $document->created_at->format('d/m/Y H:i') }} ({{ $document->created_at->diffForHumans() }})</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span>
                        @if($document->verification_status === 'pending')
                        <span class="badge bg-warning"><i class="fas fa-clock"></i> En Attente</span>
                        @elseif($document->verification_status === 'verified')
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Vérifié</span>
                        @else
                        <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejeté</span>
                        @endif
                    </span>
                </div>

                @if($document->verification_status !== 'pending' && $document->verifiedBy)
                <div class="info-row">
                    <span class="info-label">Vérifié par</span>
                    <span class="info-value">
                        <i class="fas fa-user-shield me-1"></i>
                        {{ $document->verifiedBy->name }}
                        @if($document->verifiedBy->hasRole('Super Admin', 'admin'))
                            <span class="badge bg-danger ms-1">Super Admin</span>
                        @elseif($document->verifiedBy->hasRole('Manager', 'admin'))
                            <span class="badge bg-warning ms-1">Manager</span>
                        @else
                            <span class="badge bg-info ms-1">Agent</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de vérification</span>
                    <span class="info-value">
                        {{ $document->verified_at->format('d/m/Y à H:i:s') }}
                        <small class="text-muted">({{ $document->verified_at->diffForHumans() }})</small>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Temps de traitement</span>
                    <span class="info-value">
                        @php
                            $processingTime = $document->created_at->diff($document->verified_at);
                        @endphp
                        @if($processingTime->d > 0)
                            {{ $processingTime->d }} jour(s)
                        @endif
                        @if($processingTime->h > 0)
                            {{ $processingTime->h }} heure(s)
                        @endif
                        @if($processingTime->i > 0)
                            {{ $processingTime->i }} minute(s)
                        @endif
                    </span>
                </div>
                @endif

                @if($document->verification_status === 'rejected' && $document->rejection_reason)
                <div class="info-row">
                    <span class="info-label">Raison du rejet</span>
                    <span class="info-value">
                        <div class="alert alert-danger mb-0 mt-2">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            {{ $document->rejection_reason }}
                        </div>
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Document Images -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-images me-2"></i>Photos du Document
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="mb-2">Recto</h6>
                        <img src="{{ route('admin.verification.image', [$document, 'front']) }}"
                             alt="Recto"
                             class="document-image"
                             data-bs-toggle="modal"
                             data-bs-target="#imageModal"
                             data-image-url="{{ route('admin.verification.image', [$document, 'front']) }}"
                             data-image-title="Recto du Document">
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-2">Verso</h6>
                        <img src="{{ route('admin.verification.image', [$document, 'back']) }}"
                             alt="Verso"
                             class="document-image"
                             data-bs-toggle="modal"
                             data-bs-target="#imageModal"
                             data-image-url="{{ route('admin.verification.image', [$document, 'back']) }}"
                             data-image-title="Verso du Document">
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique du Document -->
        @if($document->histories->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-history me-2"></i>Historique des Actions
            </div>
            <div class="card-body p-0">
                <div class="timeline">
                    @foreach($document->histories as $history)
                    <div class="timeline-item">
                        <div class="timeline-marker
                            @if($history->action === 'verified') bg-success
                            @elseif($history->action === 'rejected') bg-danger
                            @elseif($history->action === 'submitted') bg-primary
                            @else bg-info
                            @endif">
                            <i class="fas
                                @if($history->action === 'verified') fa-check-circle
                                @elseif($history->action === 'rejected') fa-times-circle
                                @elseif($history->action === 'submitted') fa-upload
                                @else fa-edit
                                @endif"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        @if($history->action === 'verified')
                                            <span class="badge bg-success">Document Vérifié</span>
                                        @elseif($history->action === 'rejected')
                                            <span class="badge bg-danger">Document Rejeté</span>
                                        @elseif($history->action === 'submitted')
                                            <span class="badge bg-primary">Document Soumis</span>
                                        @elseif($history->action === 'updated')
                                            <span class="badge bg-info">Document Mis à Jour</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($history->action) }}</span>
                                        @endif
                                    </h6>
                                    @if($history->admin)
                                    <p class="mb-0 small text-muted">
                                        <i class="fas fa-user-shield me-1"></i>
                                        Par: <strong>{{ $history->admin->name }}</strong>
                                        @if($history->admin->hasRole('Super Admin', 'admin'))
                                            <span class="badge bg-danger ms-1">Super Admin</span>
                                        @elseif($history->admin->hasRole('Manager', 'admin'))
                                            <span class="badge bg-warning ms-1">Manager</span>
                                        @else
                                            <span class="badge bg-info ms-1">Agent</span>
                                        @endif
                                    </p>
                                    @endif
                                </div>
                                <small class="text-muted">
                                    {{ $history->created_at->format('d/m/Y H:i') }}<br>
                                    <em>({{ $history->created_at->diffForHumans() }})</em>
                                </small>
                            </div>
                            @if($history->details)
                            <p class="mb-0 small">
                                <i class="fas fa-info-circle me-1"></i>
                                {{ $history->details }}
                            </p>
                            @endif
                            @if($history->old_status && $history->new_status)
                            <p class="mb-0 small text-muted mt-1">
                                Statut: <code>{{ $history->old_status }}</code> → <code>{{ $history->new_status }}</code>
                            </p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Action Panel -->
    <div class="col-lg-4">
        <div class="action-buttons">
            @if($document->verification_status === 'pending')
            <!-- Approve Form -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Approuver</h5>
                    <p class="text-muted small mb-3">Valider ce document comme authentique et conforme.</p>
                    <form method="POST" action="{{ route('admin.verification.approve', $document) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir approuver ce document ?')">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-2"></i>Approuver le Document
                        </button>
                    </form>
                </div>
            </div>

            <!-- Reject Form -->
            <div class="card border-danger">
                <div class="card-body">
                    <h5 class="text-danger mb-3"><i class="fas fa-times-circle me-2"></i>Rejeter</h5>
                    <p class="text-muted small mb-3">Refuser ce document avec une raison obligatoire.</p>
                    <form method="POST" action="{{ route('admin.verification.reject', $document) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Raison du rejet <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason"
                                      id="rejection_reason"
                                      class="form-control @error('rejection_reason') is-invalid @enderror"
                                      rows="4"
                                      required
                                      placeholder="Ex: Document illisible, informations incohérentes, document expiré...">{{ old('rejection_reason') }}</textarea>
                            @error('rejection_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Minimum 10 caractères</small>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-times me-2"></i>Rejeter le Document
                        </button>
                    </form>
                </div>
            </div>
            @else
            <!-- Document Already Processed -->
            <div class="card">
                <div class="card-body text-center">
                    @if($document->verification_status === 'verified')
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h5 class="text-success">Document Approuvé</h5>
                    <p class="text-muted">Ce document a été vérifié le {{ $document->verified_at?->format('d/m/Y H:i') }}</p>
                    @else
                    <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
                    <h5 class="text-danger">Document Rejeté</h5>
                    <p class="text-muted mb-2">Ce document a été rejeté le {{ $document->verified_at?->format('d/m/Y H:i') }}</p>
                    @if($document->rejection_reason)
                    <div class="alert alert-danger mt-3 text-start">
                        <strong>Raison:</strong><br>
                        {{ $document->rejection_reason }}
                    </div>
                    @endif
                    @endif
                </div>
            </div>
            @endif

            <!-- Back Button -->
            <a href="{{ route('admin.verification.index') }}" class="btn btn-outline-secondary w-100 mt-3">
                <i class="fas fa-arrow-left me-2"></i>Retour à la Liste
            </a>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalTitle">Image du Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="Document" class="modal-image" id="modalImage">
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Handle image modal
    document.querySelectorAll('.document-image').forEach(img => {
        img.addEventListener('click', function() {
            const imageUrl = this.getAttribute('data-image-url');
            const imageTitle = this.getAttribute('data-image-title');
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('imageModalTitle').textContent = imageTitle;
        });
    });
</script>
@endsection
