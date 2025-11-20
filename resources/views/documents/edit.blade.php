@extends('layouts.app')

@section('title', 'Modifier le Document')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="mb-3">
                <a href="{{ route('documents.show', $document->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour au document
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Modifier le Document
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Important :</strong> Vous pouvez modifier ce document tant qu'il est en attente de vérification.
                        Une fois vérifié, les modifications ne seront plus possibles.
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

                    <form method="POST" action="{{ route('documents.update', $document->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <!-- Type de document (non modifiable) -->
                        <div class="mb-4">
                            <label class="form-label">Type de Document <span class="text-danger">*</span></label>
                            <div class="card p-3 bg-light">
                                <div class="d-flex align-items-center">
                                    @if($document->document_type === 'cni')
                                        <i class="fas fa-id-card fa-2x text-primary me-3"></i>
                                        <div>
                                            <strong>NIU (Numéro d'Identification Unique)</strong><br>
                                            <small class="text-muted">Carte Nationale d'Identité</small>
                                        </div>
                                    @else
                                        <i class="fas fa-passport fa-2x text-success me-3"></i>
                                        <div>
                                            <strong>Passeport</strong><br>
                                            <small class="text-muted">Passeport international</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" name="document_type" value="{{ $document->document_type }}">
                            <small class="form-text text-muted">
                                <i class="fas fa-lock"></i> Le type de document ne peut pas être modifié
                            </small>
                        </div>

                        <!-- Numéro de document -->
                        <div class="mb-3">
                            <label for="document_number" class="form-label">
                                {{ $document->document_type === 'cni' ? 'Numéro NIU' : 'Numéro de Passeport' }} <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('document_number') is-invalid @enderror"
                                   id="document_number"
                                   name="document_number"
                                   value="{{ old('document_number', $document->document_number) }}"
                                   placeholder="{{ $document->document_type === 'cni' ? 'Ex: 1234567890' : 'Ex: AB1234567' }}"
                                   maxlength="{{ $document->document_type === 'cni' ? '10' : '20' }}"
                                   pattern="{{ $document->document_type === 'cni' ? '[0-9]{10}' : '[A-Z0-9]{6,20}' }}"
                                   title="{{ $document->document_type === 'cni' ? 'Le NIU doit contenir exactement 10 chiffres' : 'Le passeport doit contenir entre 6 et 20 caractères alphanumériques' }}"
                                   required>
                            @error('document_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i>
                                {{ $document->document_type === 'cni' ? '10 chiffres pour NIU' : 'Format alphanumerique' }}
                            </small>
                        </div>

                        <!-- Dates -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="issue_date" class="form-label">
                                    Date de Délivrance <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       class="form-control @error('issue_date') is-invalid @enderror"
                                       id="issue_date"
                                       name="issue_date"
                                       value="{{ old('issue_date', $document->issue_date->format('Y-m-d')) }}"
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
                                       value="{{ old('expiry_date', $document->expiry_date->format('Y-m-d')) }}"
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

                        <!-- Photos du document -->
                        <h6 class="text-muted mb-3">Photos du Document</h6>
                        <div class="row">
                            <!-- Photo recto -->
                            <div class="col-md-6 mb-3">
                                <label for="front_photo" class="form-label">
                                    Photo Recto (Face avant)
                                </label>
                                <div class="mb-2">
                                    <img src="{{ route('documents.image', ['id' => $document->id, 'type' => 'front']) }}"
                                         alt="Photo actuelle recto"
                                         class="img-thumbnail"
                                         style="max-height: 200px;">
                                    <p class="text-muted small mt-1">Photo actuelle</p>
                                </div>
                                <input type="file"
                                       class="form-control @error('front_photo') is-invalid @enderror"
                                       id="front_photo"
                                       name="front_photo"
                                       accept="image/jpeg,image/jpg,image/png"
                                       onchange="previewImage(this, 'preview_front')">
                                @error('front_photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted d-block mb-2">
                                    <i class="fas fa-info-circle"></i> Laissez vide pour conserver l'image actuelle
                                </small>
                                <div id="preview_front"></div>
                            </div>

                            <!-- Photo verso -->
                            <div class="col-md-6 mb-3">
                                <label for="back_photo" class="form-label">
                                    Photo Verso (Face arrière) <small class="text-muted">(Optionnel)</small>
                                </label>
                                @if($document->back_photo_path)
                                    <div class="mb-2">
                                        <img src="{{ route('documents.image', ['id' => $document->id, 'type' => 'back']) }}"
                                             alt="Photo actuelle verso"
                                             class="img-thumbnail"
                                             style="max-height: 200px;">
                                        <p class="text-muted small mt-1">Photo actuelle</p>
                                    </div>
                                @else
                                    <p class="text-muted small">Aucune photo verso actuellement</p>
                                @endif
                                <input type="file"
                                       class="form-control @error('back_photo') is-invalid @enderror"
                                       id="back_photo"
                                       name="back_photo"
                                       accept="image/jpeg,image/jpg,image/png"
                                       onchange="previewImage(this, 'preview_back')">
                                @error('back_photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted d-block mb-2">
                                    <i class="fas fa-info-circle"></i> Laissez vide pour conserver l'image actuelle
                                </small>
                                <div id="preview_back"></div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('documents.show', $document->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i>Enregistrer les Modifications
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
            const div = document.createElement('div');
            div.className = 'mt-2';
            div.innerHTML = '<p class="text-success small"><i class="fas fa-check"></i> Nouvelle image sélectionnée :</p>';
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'img-thumbnail';
            img.style.maxHeight = '200px';
            div.appendChild(img);
            preview.appendChild(div);
        }

        reader.readAsDataURL(input.files[0]);
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

// Validation en temps réel pour le numéro de document
document.addEventListener('DOMContentLoaded', function() {
    const documentNumber = document.getElementById('document_number');
    const documentType = '{{ $document->document_type }}';

    if (documentNumber && documentType === 'cni') {
        documentNumber.addEventListener('input', function(e) {
            // Pour NIU, accepter seulement les chiffres
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
        });
    }

    // Validation du formulaire avant soumission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateDates()) {
                e.preventDefault();
                alert('Veuillez corriger les dates avant de soumettre le formulaire.');
                return false;
            }
        });
    }
});
</script>
@endpush
