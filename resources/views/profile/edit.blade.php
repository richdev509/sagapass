@extends('layouts.app')

@section('title', 'Mon Profil')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="fas fa-user-edit me-2"></i>Mon Profil
            </h2>
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

    <div class="row">
        <!-- Photo de profil -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-camera me-2"></i>Photo de Profil
                    </h5>
                </div>
                <div class="card-body text-center">
                    @if($user->profile_photo)
                        <img src="{{ asset('storage/' . $user->profile_photo) }}"
                             class="rounded-circle mb-3"
                             style="width: 150px; height: 150px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 150px; height: 150px;">
                            <i class="fas fa-user fa-4x text-secondary"></i>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file"
                                   class="form-control @error('photo') is-invalid @enderror"
                                   name="photo"
                                   accept="image/jpeg,image/jpg,image/png">
                            @error('photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">JPG, PNG (max 2MB)</small>
                        </div>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-upload me-2"></i>Téléverser
                        </button>
                    </form>
                </div>
            </div>

            <!-- Statut du compte -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Statut du Compte
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Statut de vérification :</strong><br>
                        @if($user->verification_status === 'verified')
                            <span class="badge badge-status-verified mt-1">
                                <i class="fas fa-check-circle"></i> Vérifié
                            </span>
                        @elseif($user->verification_status === 'pending')
                            <span class="badge badge-status-pending mt-1">
                                <i class="fas fa-clock"></i> En attente
                            </span>
                        @else
                            <span class="badge badge-status-rejected mt-1">
                                <i class="fas fa-times-circle"></i> Non vérifié
                            </span>
                        @endif
                    </div>
                    <div>
                        <strong>Statut du compte :</strong><br>
                        @if($user->account_status === 'active')
                            <span class="badge bg-success mt-1">
                                <i class="fas fa-check"></i> Actif
                            </span>
                        @else
                            <span class="badge bg-danger mt-1">
                                <i class="fas fa-ban"></i> Suspendu
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations personnelles -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2"></i>Informations Personnelles
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       id="first_name"
                                       name="first_name"
                                       value="{{ old('first_name', $user->first_name) }}"
                                       required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       id="last_name"
                                       name="last_name"
                                       value="{{ old('last_name', $user->last_name) }}"
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $user->email) }}"
                                       required>
                                <span class="input-group-text">
                                    @if($user->email_verified_at)
                                        <i class="fas fa-check-circle text-success" title="Email vérifié"></i>
                                    @else
                                        <i class="fas fa-times-circle text-danger" title="Email non vérifié"></i>
                                    @endif
                                </span>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if($user->email_verified_at)
                                <small class="form-text text-success">
                                    <i class="fas fa-check-circle"></i> Email vérifié le {{ $user->email_verified_at->format('d/m/Y à H:i') }}
                                </small>
                            @else
                                <small class="form-text text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Email non vérifié.
                                    <a href="{{ route('verification.notice') }}" class="text-decoration-none">Cliquez ici pour renvoyer l'email de vérification</a>
                                </small>
                            @endif
                        </div>                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-select @error('phone') is-invalid @enderror"
                                            id="phone_country"
                                            style="max-width: 140px;">
                                        <option value="+509" data-length="8" data-format="XXXX-XXXX" {{ str_starts_with(old('phone', $user->phone), '+509') ? 'selected' : '' }}>
                                            🇭🇹 +509 (Haïti)
                                        </option>
                                        <option value="+1" data-length="10" data-format="(XXX) XXX-XXXX" {{ str_starts_with(old('phone', $user->phone), '+1') ? 'selected' : '' }}>
                                            🇺🇸 +1 (USA)
                                        </option>
                                        <option value="+1" data-length="10" data-format="(XXX) XXX-XXXX" {{ str_starts_with(old('phone', $user->phone), '+1809') || str_starts_with(old('phone', $user->phone), '+1829') || str_starts_with(old('phone', $user->phone), '+1849') ? 'selected' : '' }}>
                                            🇩🇴 +1 (R.D.)
                                        </option>
                                    </select>
                                    <input type="tel"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           id="phone"
                                           name="phone"
                                           value="{{ old('phone', $user->phone) }}"
                                           placeholder="Entrez le numéro"
                                           required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted" id="phone_hint">
                                    <i class="fas fa-info-circle"></i> Format: <span id="phone_format">XXXX-XXXX</span> (8 chiffres)
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date de Naissance <span class="text-danger">*</span></label>
                                <input type="date"
                                       class="form-control @error('date_of_birth') is-invalid @enderror"
                                       id="date_of_birth"
                                       name="date_of_birth"
                                       value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                       max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                       required>
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Vous devez avoir au moins 18 ans
                                </small>
                            </div>
                        </div>                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address"
                                      name="address"
                                      rows="3">{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i>Enregistrer les Modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Changer le mot de passe -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Changer le Mot de Passe
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de Passe Actuel <span class="text-danger">*</span></label>
                            <input type="password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password"
                                   name="current_password"
                                   required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau Mot de Passe <span class="text-danger">*</span></label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Minimum 8 caractères</small>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le Nouveau Mot de Passe <span class="text-danger">*</span></label>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Changer le Mot de Passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneCountry = document.getElementById('phone_country');
    const phoneInput = document.getElementById('phone');
    const phoneFormat = document.getElementById('phone_format');
    const phoneHint = document.getElementById('phone_hint');
    const dateOfBirth = document.getElementById('date_of_birth');

    // Configuration des formats par pays
    const phoneConfigs = {
        '+509': { length: 8, format: 'XXXX-XXXX', placeholder: '3456-7890', hint: '8 chiffres' },
        '+1': { length: 10, format: '(XXX) XXX-XXXX', placeholder: '(305) 123-4567', hint: '10 chiffres' }
    };

    function updatePhoneFormat() {
        const selectedOption = phoneCountry.options[phoneCountry.selectedIndex];
        const countryCode = selectedOption.value;
        const config = phoneConfigs[countryCode];

        phoneFormat.textContent = config.format;
        phoneInput.placeholder = config.placeholder;
        phoneHint.innerHTML = `<i class="fas fa-info-circle"></i> Format: ${config.format} (${config.hint})`;
    }

    function formatPhone(value, countryCode) {
        // Enlever tout sauf les chiffres
        const numbers = value.replace(/\D/g, '');
        const config = phoneConfigs[countryCode];

        if (countryCode === '+509') {
            // Haïti: XXXX-XXXX
            if (numbers.length <= 4) {
                return numbers;
            }
            return numbers.slice(0, 4) + '-' + numbers.slice(4, 8);
        } else if (countryCode === '+1') {
            // USA/R.D.: (XXX) XXX-XXXX
            if (numbers.length <= 3) {
                return numbers;
            } else if (numbers.length <= 6) {
                return '(' + numbers.slice(0, 3) + ') ' + numbers.slice(3);
            }
            return '(' + numbers.slice(0, 3) + ') ' + numbers.slice(3, 6) + '-' + numbers.slice(6, 10);
        }
        return numbers;
    }

    function getFullPhoneNumber() {
        const countryCode = phoneCountry.value;
        const phoneValue = phoneInput.value.replace(/\D/g, ''); // Enlever la mise en forme

        if (phoneValue) {
            return countryCode + phoneValue;
        }
        return '';
    }

    function parseExistingPhone() {
        const fullPhone = phoneInput.value;
        if (!fullPhone) return;

        // Détecter et séparer l'indicatif
        if (fullPhone.startsWith('+509')) {
            phoneCountry.value = '+509';
            phoneInput.value = fullPhone.substring(4); // Enlever +509
        } else if (fullPhone.startsWith('+1')) {
            phoneCountry.value = '+1';
            phoneInput.value = fullPhone.substring(2); // Enlever +1
        }

        updatePhoneFormat();
    }

    // Calculer l'âge à partir de la date de naissance
    function calculateAge(birthDate) {
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }

        return age;
    }

    function updateAgeDisplay() {
        if (dateOfBirth.value) {
            const age = calculateAge(dateOfBirth.value);
            const ageHint = dateOfBirth.parentElement.querySelector('.age-display');

            if (age < 18) {
                if (!ageHint) {
                    const hint = document.createElement('small');
                    hint.className = 'form-text text-danger age-display';
                    hint.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Âge: ${age} ans (Minimum requis: 18 ans)`;
                    dateOfBirth.parentElement.appendChild(hint);
                    dateOfBirth.classList.add('is-invalid');
                } else {
                    ageHint.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Âge: ${age} ans (Minimum requis: 18 ans)`;
                    dateOfBirth.classList.add('is-invalid');
                }
            } else {
                if (!ageHint) {
                    const hint = document.createElement('small');
                    hint.className = 'form-text text-success age-display';
                    hint.innerHTML = `<i class="fas fa-check-circle"></i> Âge: ${age} ans`;
                    dateOfBirth.parentElement.appendChild(hint);
                    dateOfBirth.classList.remove('is-invalid');
                } else {
                    ageHint.className = 'form-text text-success age-display';
                    ageHint.innerHTML = `<i class="fas fa-check-circle"></i> Âge: ${age} ans`;
                    dateOfBirth.classList.remove('is-invalid');
                }
            }
        }
    }

    // Initialisation
    parseExistingPhone();
    updatePhoneFormat();
    updateAgeDisplay();

    // Événements téléphone
    phoneCountry.addEventListener('change', function() {
        updatePhoneFormat();
        // Reformater le numéro existant avec le nouveau format
        const numbers = phoneInput.value.replace(/\D/g, '');
        phoneInput.value = formatPhone(numbers, this.value);
    });

    phoneInput.addEventListener('input', function(e) {
        const countryCode = phoneCountry.value;
        const formatted = formatPhone(this.value, countryCode);
        this.value = formatted;
    });

    // Événement date de naissance
    dateOfBirth.addEventListener('change', updateAgeDisplay);

    // Avant soumission, combiner indicatif + numéro
    const form = phoneInput.closest('form');
    form.addEventListener('submit', function(e) {
        const fullPhone = getFullPhoneNumber();
        phoneInput.value = fullPhone;

        // Vérifier l'âge avant soumission
        if (dateOfBirth.value) {
            const age = calculateAge(dateOfBirth.value);
            if (age < 18) {
                e.preventDefault();
                alert('Vous devez avoir au moins 18 ans pour utiliser ce service.');
                return false;
            }
        }
    });
});
</script>
@endpush
