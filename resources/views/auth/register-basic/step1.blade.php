@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Créer votre SagaPass Basic</h4>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 33%"></div>
                    </div>
                    <small class="text-muted">Étape 1/3 : Informations de base</small>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>SagaPass Basic</strong>
                        <p class="mb-0 mt-2">Créez rapidement votre compte avec votre photo et une vidéo de vérification. Vous pourrez le passer en compte vérifié plus tard en ajoutant vos documents d'identité.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.basic.step1.submit') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Prénom *</label>
                                <input type="text"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       id="first_name"
                                       name="first_name"
                                       value="{{ old('first_name') }}"
                                       required
                                       autofocus>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nom *</label>
                                <input type="text"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       id="last_name"
                                       name="last_name"
                                       value="{{ old('last_name') }}"
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse Email *</label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Mot de passe *</label>
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="password"
                                       name="password"
                                       required
                                       minlength="8">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Minimum 8 caractères</small>
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmer le mot de passe *</label>
                                <input type="password"
                                       class="form-control"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date de naissance *</label>
                                <input type="date"
                                       class="form-control @error('date_of_birth') is-invalid @enderror"
                                       id="date_of_birth"
                                       name="date_of_birth"
                                       value="{{ old('date_of_birth') }}"
                                       max="{{ date('Y-m-d') }}"
                                       required>
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone <span class="text-muted">(optionnel)</span></label>
                                <div class="input-group">
                                    <select class="form-select @error('phone') is-invalid @enderror"
                                            id="phone_country"
                                            style="max-width: 140px;">
                                        <option value="+509" data-length="8" data-format="XXXX-XXXX" {{ old('phone_country', '+509') == '+509' ? 'selected' : '' }}>
                                            🇭🇹 +509 (Haïti)
                                        </option>
                                        <option value="+1" data-length="10" data-format="(XXX) XXX-XXXX" {{ old('phone_country') == '+1' ? 'selected' : '' }}>
                                            🇺🇸 +1 (USA)
                                        </option>
                                        <option value="+1" data-length="10" data-format="(XXX) XXX-XXXX" {{ old('phone_country') == '+1809' ? 'selected' : '' }}>
                                            🇩🇴 +1 (R.D.)
                                        </option>
                                    </select>
                                    <input type="tel"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           id="phone"
                                           name="phone"
                                           value="{{ old('phone') }}"
                                           placeholder="3456-7890">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted" id="phone_hint">
                                    <i class="fas fa-info-circle"></i> Format: <span id="phone_format">XXXX-XXXX</span> (8 chiffres)
                                </small>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right"></i> Continuer vers la photo
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <p class="text-muted mb-0">
                                Déjà un compte ?
                                <a href="{{ route('login') }}">Se connecter</a>
                            </p>
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
document.addEventListener('DOMContentLoaded', function() {
    const phoneCountry = document.getElementById('phone_country');
    const phoneInput = document.getElementById('phone');
    const phoneFormat = document.getElementById('phone_format');
    const phoneHint = document.getElementById('phone_hint');

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

    // Initialisation
    parseExistingPhone();
    updatePhoneFormat();

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

    // Avant soumission, combiner indicatif + numéro
    const form = phoneInput.closest('form');
    form.addEventListener('submit', function(e) {
        // Créer un champ caché avec le numéro complet
        const fullPhone = getFullPhoneNumber();
        if (fullPhone) {
            phoneInput.value = fullPhone;
        }
    });
});
</script>
@endpush
