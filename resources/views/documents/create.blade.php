@extends('layouts.app')

@section('title', 'Ajouter un Document')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-upload me-2"></i>Ajouter un Nouveau Document
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Important :</strong> Assurez-vous que les photos sont claires et lisibles.
                        Les documents seront vérifiés par notre équipe avant validation.
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(isset($activeDocuments) && $activeDocuments->isNotEmpty())
                        <div class="alert alert-warning mb-4">
                            <h6><i class="fas fa-shield-alt me-2"></i>Documents Actifs</h6>
                            <p class="mb-2">Vous avez déjà les documents suivants en cours :</p>
                            <ul class="mb-0">
                                @foreach($activeDocuments as $doc)
                                    <li>
                                        <strong>{{ $doc->document_type === 'cni' ? 'NIU (Carte Nationale)' : 'Passeport' }}</strong>
                                        - N° {{ $doc->document_number }}
                                        - Statut :
                                        @if($doc->verification_status === 'verified')
                                            <span class="badge bg-success">Vérifié</span>
                                        @else
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Vous ne pouvez pas soumettre un document du même type tant qu'il est actif (en attente ou vérifié).
                            </small>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs de validation :</h6>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Type de document -->
                        <div class="mb-4">
                            <label class="form-label">Type de Document <span class="text-danger">*</span></label>
                            <div class="row">
                                @php
                                    $hasCNI = isset($activeDocuments) && $activeDocuments->where('document_type', 'cni')->isNotEmpty();
                                    $hasPassport = isset($activeDocuments) && $activeDocuments->where('document_type', 'passport')->isNotEmpty();
                                @endphp

                                <div class="col-md-6">
                                    <div class="form-check card p-3 mb-2 {{ $hasCNI ? 'bg-light opacity-75' : '' }}">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="document_type"
                                               id="type_cni"
                                               value="cni"
                                               {{ old('document_type') === 'cni' ? 'checked' : '' }}
                                               {{ $hasCNI ? 'disabled' : 'required' }}
                                               onchange="updateDocumentType()">
                                        <label class="form-check-label ms-2" for="type_cni">
                                            <i class="fas fa-id-card fa-2x text-primary mb-2 d-block"></i>
                                            <strong>Carte Nationale d'Identité</strong><br>
                                            <small class="text-muted">Numéro NIU - 10 chiffres</small>
                                            @if($hasCNI)
                                                <br><small class="text-danger"><i class="fas fa-lock"></i> Vous avez déjà une Carte Nationale active</small>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check card p-3 mb-2 {{ $hasPassport ? 'bg-light opacity-75' : '' }}">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="document_type"
                                               id="type_passport"
                                               value="passport"
                                               {{ old('document_type') === 'passport' ? 'checked' : '' }}
                                               {{ $hasPassport ? 'disabled' : 'required' }}
                                               onchange="updateDocumentType()">
                                        <label class="form-check-label ms-2" for="type_passport">
                                            <i class="fas fa-passport fa-2x text-success mb-2 d-block"></i>
                                            <strong>Passeport</strong><br>
                                            <small class="text-muted">Passeport international</small>
                                            @if($hasPassport)
                                                <br><small class="text-danger"><i class="fas fa-lock"></i> Vous avez déjà un Passeport actif</small>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @error('document_type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Numéro de carte (uniquement pour CNI) -->
                        <div class="mb-3" id="card_number_section" style="display: none;">
                            <label for="card_number" class="form-label">
                                Numéro de Carte <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control text-uppercase @error('card_number') is-invalid @enderror"
                                   id="card_number"
                                   name="card_number"
                                   value="{{ old('card_number') }}"
                                   placeholder="Ex: ABC123DEF"
                                   maxlength="9"
                                   pattern="[A-Z0-9]{9}"
                                   style="text-transform: uppercase;">
                            @error('card_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 9 caractères alphanumériques (lettres en majuscules et chiffres)
                            </small>
                        </div>

                        <!-- Numéro de document -->
                        <div class="mb-3">
                            <label for="document_number" class="form-label">
                                <span id="document_label">Numéro du Document</span> <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('document_number') is-invalid @enderror"
                                   id="document_number"
                                   name="document_number"
                                   value="{{ old('document_number') }}"
                                   placeholder="Ex: 1234567890"
                                   maxlength="20"
                                   required>
                            @error('document_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted" id="document_hint">
                                <i class="fas fa-info-circle"></i> <span id="document_format">10 chiffres pour NIU</span>
                            </small>
                        </div>                        <!-- Dates -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="issue_date" class="form-label">
                                    Date de Délivrance <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       class="form-control @error('issue_date') is-invalid @enderror"
                                       id="issue_date"
                                       name="issue_date"
                                       value="{{ old('issue_date') }}"
                                       max="{{ date('Y-m-d') }}"
                                       onchange="validateDates()"
                                       required>
                                @error('issue_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Date de délivrance du document
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label for="expiry_date" class="form-label">
                                    Date d'Expiration <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       class="form-control @error('expiry_date') is-invalid @enderror"
                                       id="expiry_date"
                                       name="expiry_date"
                                       value="{{ old('expiry_date') }}"
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                       onchange="validateDates()"
                                       required>
                                @error('expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="date_error" class="text-danger small mt-1" style="display: none;">
                                    <i class="fas fa-exclamation-triangle"></i> La date d'expiration doit être postérieure à la date de délivrance
                                </div>
                            </div>
                        </div>

                        <!-- Photo recto -->
                        <div class="mb-3">
                            <label for="front_photo" class="form-label">
                                Photo Recto (Face avant) <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('front_photo') is-invalid @enderror"
                                   id="front_photo"
                                   name="front_photo"
                                   accept="image/jpeg,image/jpg,image/png"
                                   required
                                   onchange="previewImage(this, 'frontPreview')">
                            @error('front_photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> JPG, PNG (max 5MB)
                            </small>
                            <div id="frontPreview" class="mt-2"></div>
                        </div>

                        <!-- Photo verso -->
                        <div class="mb-4" id="back_photo_section">
                            <label for="back_photo" class="form-label">
                                Photo Verso (Face arrière) <span class="text-danger" id="back_photo_required">*</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('back_photo') is-invalid @enderror"
                                   id="back_photo"
                                   name="back_photo"
                                   accept="image/jpeg,image/jpg,image/png"
                                   required
                                   onchange="previewImage(this, 'backPreview')">
                            @error('back_photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted" id="back_photo_hint">
                                <i class="fas fa-info-circle"></i> <span id="back_photo_text">Obligatoire pour les cartes nationales</span>
                            </small>
                            <div id="backPreview" class="mt-2"></div>
                        </div>

                        <!-- Conseils -->
                        <div class="alert alert-light border mb-4">
                            <h6 class="mb-2"><i class="fas fa-lightbulb me-2 text-warning"></i>Conseils pour une bonne photo :</h6>
                            <ul class="mb-0 small">
                                <li>Placez le document sur une surface plane et bien éclairée</li>
                                <li>Évitez les reflets et les ombres</li>
                                <li>Assurez-vous que tous les textes sont lisibles</li>
                                <li>Cadrez le document en entier</li>
                            </ul>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-upload me-2"></i>Soumettre le Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'img-thumbnail';
            img.style.maxWidth = '300px';
            preview.appendChild(img);
        }

        reader.readAsDataURL(input.files[0]);
    }
}

function updateDocumentType() {
    const typeNIU = document.getElementById('type_cni');
    const typePassport = document.getElementById('type_passport');
    const documentNumber = document.getElementById('document_number');
    const documentLabel = document.getElementById('document_label');
    const documentFormat = document.getElementById('document_format');
    const backPhoto = document.getElementById('back_photo');
    const backPhotoRequired = document.getElementById('back_photo_required');
    const backPhotoText = document.getElementById('back_photo_text');
    const cardNumberSection = document.getElementById('card_number_section');
    const cardNumberInput = document.getElementById('card_number');

    if (typeNIU.checked) {
        documentLabel.textContent = 'Numéro NIU';
        documentNumber.placeholder = 'Ex: 1234567890';
        documentNumber.maxLength = 10;
        documentFormat.textContent = '10 chiffres pour NIU';
        documentNumber.pattern = '[0-9]{10}';
        documentNumber.title = 'Le NIU doit contenir exactement 10 chiffres';

        // Afficher et rendre obligatoire le numéro de carte pour CNI
        cardNumberSection.style.display = 'block';
        cardNumberInput.required = true;

        // Rendre le verso obligatoire pour carte nationale
        backPhoto.required = true;
        backPhotoRequired.style.display = 'inline';
        backPhotoText.textContent = 'Obligatoire pour les cartes nationales';
    } else if (typePassport.checked) {
        documentLabel.textContent = 'Numéro de Passeport';
        documentNumber.placeholder = 'Ex: AB1234567';
        documentNumber.maxLength = 20;
        documentFormat.textContent = 'Format alphanumerique';
        documentNumber.pattern = '[A-Z0-9]{6,20}';
        documentNumber.title = 'Le passeport doit contenir entre 6 et 20 caractères alphanumériques';

        // Masquer et rendre optionnel le numéro de carte pour passeport
        cardNumberSection.style.display = 'none';
        cardNumberInput.required = false;
        cardNumberInput.value = '';

        // Rendre le verso optionnel pour passeport
        backPhoto.required = false;
        backPhotoRequired.style.display = 'none';
        backPhotoText.textContent = 'Optionnel pour les passeports';
    }
}

function validateDates() {
    const issueDate = document.getElementById('issue_date');
    const expiryDate = document.getElementById('expiry_date');
    const dateError = document.getElementById('date_error');
    const submitButton = document.querySelector('button[type="submit"]');

    if (issueDate.value && expiryDate.value) {
        const issue = new Date(issueDate.value);
        const expiry = new Date(expiryDate.value);

        if (issue >= expiry) {
            dateError.style.display = 'block';
            expiryDate.classList.add('is-invalid');
            if (submitButton) {
                submitButton.disabled = true;
            }
            return false;
        } else {
            dateError.style.display = 'none';
            expiryDate.classList.remove('is-invalid');
            if (submitButton) {
                submitButton.disabled = false;
            }
            return true;
        }
    }
    return true;
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updateDocumentType();

    // Valider en temps réel le format du numéro
    const documentNumber = document.getElementById('document_number');
    const typeNIU = document.getElementById('type_cni');
    const cardNumber = document.getElementById('card_number');

    documentNumber.addEventListener('input', function(e) {
        if (typeNIU.checked) {
            // Pour NIU, accepter seulement les chiffres
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
        }
    });

    // Validation du numéro de carte : 9 caractères alphanumériques, lettres en majuscules
    cardNumber.addEventListener('input', function(e) {
        // Convertir en majuscules et ne garder que lettres et chiffres
        let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        // Limiter à 9 caractères
        this.value = value.substring(0, 9);
    });

    // Validation du formulaire avant soumission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateDates()) {
                e.preventDefault();
                alert('Veuillez corriger les dates avant de soumettre le formulaire.');
                return false;
            }

            // Valider le numéro de carte si carte nationale
            if (typeNIU.checked && cardNumber.value.length !== 9) {
                e.preventDefault();
                alert('Le numéro de carte doit contenir exactement 9 caractères alphanumériques.');
                cardNumber.focus();
                return false;
            }
        });
    }
});
</script>
@endpush
