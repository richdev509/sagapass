@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-shield-check me-2"></i>
                        Vérifiez votre email
                    </h4>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-white" role="progressbar" style="width: 20%"></div>
                    </div>
                    <small class="mt-2 d-block opacity-75">Étape 1/4 : Code de vérification</small>
                </div>

                <div class="card-body p-4">
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p class="mb-2">
                            <strong>Code envoyé à :</strong><br>
                            <span class="fs-5">{{ $email }}</span>
                        </p>
                        <small class="text-muted">
                            Vérifiez votre boîte de réception et vos spams
                        </small>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.basic.verify-code.submit') }}" id="verifyForm">
                        @csrf

                        <div class="mb-4">
                            <label for="code" class="form-label fw-bold text-center d-block">
                                Entrez le code à 6 chiffres
                            </label>
                            <div class="code-input-container d-flex justify-content-center gap-2 mb-3">
                                <input type="text"
                                       class="code-digit form-control form-control-lg text-center @error('code') is-invalid @enderror"
                                       maxlength="1"
                                       pattern="[0-9]"
                                       inputmode="numeric"
                                       required>
                                <input type="text" class="code-digit form-control form-control-lg text-center" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                <input type="text" class="code-digit form-control form-control-lg text-center" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                <input type="text" class="code-digit form-control form-control-lg text-center" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                <input type="text" class="code-digit form-control form-control-lg text-center" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                <input type="text" class="code-digit form-control form-control-lg text-center" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                            </div>
                            <input type="hidden" name="code" id="fullCode">
                            @error('code')
                                <div class="text-danger text-center">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info small">
                            <i class="fas fa-clock me-1"></i>
                            <span id="countdown-message">Le code expire dans <strong><span id="countdown"></span></strong></span>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                <i class="fas fa-check me-2"></i>
                                Confirmer le code
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="text-muted small mb-2">
                            Vous n'avez pas reçu le code ?
                        </p>
                        <form method="POST" action="{{ route('register.basic.send-code') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">
                            <button type="submit" class="btn btn-link btn-sm" id="resendBtn">
                                <i class="fas fa-redo me-1"></i>
                                Renvoyer le code
                            </button>
                        </form>
                        <span class="mx-2">|</span>
                        <a href="{{ route('register.basic.email-request') }}" class="btn btn-link btn-sm">
                            <i class="fas fa-edit me-1"></i>
                            Changer d'email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.code-digit {
    width: 50px;
    height: 60px;
    font-size: 24px;
    font-weight: bold;
    border: 2px solid #dee2e6;
}
.code-digit:focus {
    border-color: #198754;
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.code-digit');
    const fullCodeInput = document.getElementById('fullCode');
    const form = document.getElementById('verifyForm');
    const submitBtn = document.getElementById('submitBtn');

    // Permettre la saisie dans les champs
    inputs.forEach((input, index) => {
        // Forcer le type numérique
        input.setAttribute('type', 'text');
        input.setAttribute('inputmode', 'numeric');

        input.addEventListener('input', (e) => {
            // Ne garder que les chiffres
            e.target.value = e.target.value.replace(/[^0-9]/g, '');

            if (e.target.value.length === 1) {
                // Passer au champ suivant
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
            updateFullCode();
        });

        input.addEventListener('keydown', (e) => {
            // Retour arrière : revenir au champ précédent
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

        // Permettre le collage du code complet
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/\D/g, '');

            if (pastedData.length === 6) {
                pastedData.split('').forEach((char, i) => {
                    if (inputs[i]) {
                        inputs[i].value = char;
                    }
                });
                inputs[5].focus();
                updateFullCode();
            }
        });

        // Focus sur le premier champ au chargement
        if (index === 0) {
            input.focus();
        }
    });

    function updateFullCode() {
        const code = Array.from(inputs).map(input => input.value).join('');
        fullCodeInput.value = code;
    }

    // Countdown timer
    const expiresAt = new Date('{{ $expiresAt->format("Y-m-d H:i:s") }}');
    const countdownEl = document.getElementById('countdown');
    const countdownMessageEl = document.getElementById('countdown-message');
    const alertInfo = countdownMessageEl.closest('.alert');

    function updateCountdown() {
        const now = new Date();
        const diff = expiresAt - now;

        if (diff <= 0) {
            // Code expiré
            alertInfo.classList.remove('alert-info');
            alertInfo.classList.add('alert-danger');
            countdownMessageEl.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Code expiré !</strong> Veuillez renvoyer un nouveau code.';

            submitBtn.disabled = true;
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-secondary');
            submitBtn.innerHTML = '<i class="fas fa-times me-2"></i>Code expiré';

            // Désactiver les inputs
            inputs.forEach(input => {
                input.disabled = true;
                input.style.backgroundColor = '#e9ecef';
            });
            return;
        }

        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        // Changer couleur si moins d'une minute
        if (minutes === 0 && seconds <= 60) {
            alertInfo.classList.remove('alert-info');
            alertInfo.classList.add('alert-warning');
            countdownEl.classList.add('text-danger');
        }

        setTimeout(updateCountdown, 1000);
    }

    updateCountdown();
});
</script>
@endsection
