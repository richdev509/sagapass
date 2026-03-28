@extends('admin.layouts.admin')

@section('title', 'Révision Inscription Mobile')
@section('page-title', 'Révision d\'Inscription Mobile')
@section('page-subtitle', 'Vérifier les documents soumis via l\'application SAGA ID')

@section('styles')
<style>
    .doc-photo {
        width: 100%;
        max-height: 300px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        cursor: zoom-in;
        transition: transform 0.2s;
    }
    .doc-photo:hover { transform: scale(1.02); }
    .selfie-photo {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #667eea;
        box-shadow: 0 4px 15px rgba(102,126,234,0.3);
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #718096; font-size: 13px; font-weight: 500; }
    .info-value { color: #2d3748; font-weight: 600; }
    .lightbox-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .lightbox-overlay.active { display: flex; }
    .lightbox-overlay img {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 8px;
    }
    .lightbox-close {
        position: absolute;
        top: 20px; right: 30px;
        color: white; font-size: 36px;
        cursor: pointer;
        background: none; border: none;
    }
    .status-banner {
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }
    .status-banner.pending { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
    .status-banner.approved { background: #d1e7dd; color: #155724; border: 1px solid #198754; }
    .status-banner.rejected { background: #f8d7da; color: #842029; border: 1px solid #dc3545; }
</style>
@endsection

@section('content')

{{-- Alertes --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-4">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Retour --}}
<div class="mb-3">
    <a href="{{ route('admin.mobile-verification.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
    </a>
</div>

{{-- Bandeau statut --}}
<div class="status-banner {{ $mobileVerification->status }}">
    @if($mobileVerification->status === 'pending')
        <i class="fas fa-clock fa-lg"></i>
        <div>
            <div>En attente de vérification</div>
            <small>Soumis le {{ $mobileVerification->created_at->format('d/m/Y à H:i') }}</small>
        </div>
    @elseif($mobileVerification->status === 'approved')
        <i class="fas fa-check-circle fa-lg"></i>
        <div>
            <div>Inscription approuvée</div>
            <small>Le {{ $mobileVerification->verified_at?->format('d/m/Y à H:i') }}</small>
        </div>
    @else
        <i class="fas fa-times-circle fa-lg"></i>
        <div>
            <div>Inscription rejetée</div>
            @if($mobileVerification->rejection_reason)
            <small>Motif : {{ $mobileVerification->rejection_reason }}</small>
            @endif
        </div>
    @endif
</div>

<div class="row g-4">

    {{-- Colonne gauche : infos citoyen --}}
    <div class="col-lg-4">

        {{-- Selfie + identité --}}
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user me-2"></i>Identité du Citoyen</div>
            <div class="card-body text-center">
                <img src="{{ route('admin.mobile-verification.image', [$mobileVerification, 'selfie']) }}"
                     alt="Selfie"
                     class="selfie-photo mb-3"
                     onclick="openLightbox(this.src)"
                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($mobileVerification->user->first_name . '+' . $mobileVerification->user->last_name) }}&background=667eea&color=fff&size=180'">
                <h5 class="mb-0">{{ $mobileVerification->user->first_name }} {{ $mobileVerification->user->last_name }}</h5>
                <small class="text-muted">{{ $mobileVerification->user->email }}</small>
            </div>
            <div class="card-body pt-0">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-id-card me-1"></i>NIU</span>
                    <span class="info-value">
                        @if($mobileVerification->user->niu)
                            <code>{{ $mobileVerification->user->niu }}</code>
                        @else
                            <span class="text-warning">Non renseigné</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-phone me-1"></i>Téléphone</span>
                    <span class="info-value">{{ $mobileVerification->user->phone ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-birthday-cake me-1"></i>Date de naissance</span>
                    <span class="info-value">{{ $mobileVerification->user->date_of_birth?->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar me-1"></i>Inscription</span>
                    <span class="info-value">{{ $mobileVerification->user->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-circle me-1"></i>Compte</span>
                    <span class="info-value">
                        @if($mobileVerification->user->account_status === 'active')
                            <span class="badge bg-success">Actif</span>
                        @else
                            <span class="badge bg-secondary">{{ $mobileVerification->user->account_status }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Actions (si en attente) --}}
        @if($mobileVerification->status === 'pending')
        <div class="card">
            <div class="card-header"><i class="fas fa-gavel me-2"></i>Décision</div>
            <div class="card-body">
                {{-- Approuver --}}
                <form action="{{ route('admin.mobile-verification.approve', $mobileVerification) }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit"
                        id="btnApprove"
                        class="btn btn-success w-100"
                        disabled
                        onclick="return confirm('Confirmer l\'approbation de cette inscription ?')">
                        <i class="fas fa-check-circle me-2"></i>Approuver l'Inscription
                        <small class="d-block mt-1" id="checkHint">(Cochez tous les points de vérification)</small>
                    </button>
                </form>
                <hr>
                {{-- Rejeter --}}
                <button class="btn btn-danger w-100" data-bs-toggle="collapse" data-bs-target="#rejectForm">
                    <i class="fas fa-times-circle me-2"></i>Rejeter l'Inscription
                </button>
                <div class="collapse mt-3" id="rejectForm">
                    <form action="{{ route('admin.mobile-verification.reject', $mobileVerification) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Motif rapide</label>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-danger reject-preset" data-reason="Le NIU ne correspond pas au document fourni">NIU invalide</button>
                                <button type="button" class="btn btn-sm btn-outline-danger reject-preset" data-reason="La photo du recto de la carte nationale est floue ou illisible">Recto flou</button>
                                <button type="button" class="btn btn-sm btn-outline-danger reject-preset" data-reason="La photo du verso de la carte nationale est floue ou illisible">Verso flou</button>
                                <button type="button" class="btn btn-sm btn-outline-danger reject-preset" data-reason="Le selfie est flou ou le visage n'est pas visible">Selfie non conforme</button>
                                <button type="button" class="btn btn-sm btn-outline-danger reject-preset" data-reason="Le nom/prénom ne correspond pas à la carte nationale">Nom incorrect</button>
                                <button type="button" class="btn btn-sm btn-outline-danger reject-preset" data-reason="Document d'identité expiré ou endommagé">Document expiré</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Motif du rejet <span class="text-danger">*</span></label>
                            <textarea name="reason" id="rejectReason" class="form-control" rows="4" required
                                placeholder="Sélectionnez un motif rapide ou tapez le motif..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-times me-1"></i>Confirmer le Rejet
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Colonne droite : documents --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-id-card me-2"></i>Documents Soumis</span>
                <small class="text-muted">Cliquer sur une image pour agrandir</small>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    {{-- Recto CNI --}}
                    <div class="col-md-6">
                        <p class="fw-semibold text-muted mb-2"><i class="fas fa-arrow-right me-1"></i>Recto de la Carte Nationale</p>
                        <img src="{{ route('admin.mobile-verification.image', [$mobileVerification, 'id_card_front']) }}"
                             alt="Recto CNI"
                             class="doc-photo"
                             onclick="openLightbox(this.src)"
                             onerror="this.outerHTML='<div class=\'text-center p-4 bg-light rounded text-muted\'><i class=\'fas fa-image fa-2x mb-2 d-block\'></i>Image non disponible</div>'">
                    </div>
                    {{-- Verso CNI --}}
                    <div class="col-md-6">
                        <p class="fw-semibold text-muted mb-2"><i class="fas fa-arrow-left me-1"></i>Verso de la Carte Nationale</p>
                        <img src="{{ route('admin.mobile-verification.image', [$mobileVerification, 'id_card_back']) }}"
                             alt="Verso CNI"
                             class="doc-photo"
                             onclick="openLightbox(this.src)"
                             onerror="this.outerHTML='<div class=\'text-center p-4 bg-light rounded text-muted\'><i class=\'fas fa-image fa-2x mb-2 d-block\'></i>Image non disponible</div>'">
                    </div>
                </div>

                <hr class="my-4">

                {{-- Selfie (grande version) --}}
                <div>
                    <p class="fw-semibold text-muted mb-2"><i class="fas fa-camera me-1"></i>Selfie Biométrique</p>
                    <img src="{{ route('admin.mobile-verification.image', [$mobileVerification, 'selfie']) }}"
                         alt="Selfie"
                         style="max-height:350px; object-fit:cover; border-radius:10px; border:2px solid #e2e8f0; cursor:zoom-in; width:100%;"
                         onclick="openLightbox(this.src)"
                         onerror="this.outerHTML='<div class=\'text-center p-4 bg-light rounded text-muted\'><i class=\'fas fa-user fa-2x mb-2 d-block\'></i>Selfie non disponible</div>'">
                </div>
            </div>
        </div>

        {{-- Checklist de vérification --}}
        <div class="card mt-4">
            <div class="card-header"><i class="fas fa-tasks me-2"></i>Points de Vérification</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input verification-check" type="checkbox" id="check1">
                            <label class="form-check-label" for="check1">NIU lisible et correspond au document</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input verification-check" type="checkbox" id="check2">
                            <label class="form-check-label" for="check2">Photo du recto nette et complète</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input verification-check" type="checkbox" id="check3">
                            <label class="form-check-label" for="check3">Photo du verso nette et complète</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input verification-check" type="checkbox" id="check4">
                            <label class="form-check-label" for="check4">Selfie : visage visible et non flou</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input verification-check" type="checkbox" id="check5">
                            <label class="form-check-label" for="check5">Nom/prénom correspondent à la CNI</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input verification-check" type="checkbox" id="check6">
                            <label class="form-check-label" for="check6">Date de naissance concordante</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Lightbox --}}
<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <img id="lightboxImg" src="" alt="Vue agrandie">
</div>

@endsection

@section('scripts')
<script>
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});

// Activer le bouton Approuver uniquement quand tous les points sont cochés
const checks = document.querySelectorAll('.verification-check');
const btnApprove = document.getElementById('btnApprove');
const checkHint = document.getElementById('checkHint');
if (checks.length && btnApprove) {
    checks.forEach(function(cb) {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(checks).every(function(c) { return c.checked; });
            btnApprove.disabled = !allChecked;
            if (checkHint) checkHint.style.display = allChecked ? 'none' : 'block';
        });
    });
}

// Motifs de rejet prédéfinis
document.querySelectorAll('.reject-preset').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var textarea = document.getElementById('rejectReason');
        if (textarea) {
            textarea.value = this.getAttribute('data-reason');
            textarea.focus();
        }
    });
});
</script>
@endsection
