@extends('admin.layouts.admin')

@section('title', 'Activer le 2FA')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-gradient-primary">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-qrcode me-2"></i>
                        Activer l'Authentification à Deux Facteurs
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- QR Code -->
                        <div class="col-md-6 text-center">
                            <h5 class="mb-3">Étape 1 : Scannez le QR Code</h5>
                            <div class="qr-code-container mb-3">
                                <img src="data:image/png;base64,{{ $qrCodeImage }}"
                                     alt="QR Code 2FA"
                                     class="img-fluid"
                                     style="max-width: 300px;">
                            </div>
                            <p class="text-muted small">
                                Utilisez Google Authenticator, Microsoft Authenticator ou Authy
                            </p>
                        </div>

                        <!-- Instructions -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Instructions</h5>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Important :</strong> Conservez ce secret en lieu sûr !
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Secret (si vous ne pouvez pas scanner) :</label>
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control font-monospace"
                                           value="{{ $secret }}"
                                           id="secretKey"
                                           readonly>
                                    <button class="btn btn-outline-secondary"
                                            type="button"
                                            onclick="copySecret()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <small class="text-muted">
                                    Si vous avez déjà scanné un QR code, supprimez l'ancienne entrée et re-scannez ce nouveau code.
                                </small>
                            </div>

                            <div class="steps">
                                <h6 class="fw-bold">Comment faire :</h6>
                                <ol>
                                    <li class="mb-2">Ouvrez votre application d'authentification</li>
                                    <li class="mb-2">Scannez le QR code ci-contre</li>
                                    <li class="mb-2">Entrez le code à 6 chiffres généré ci-dessous</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Formulaire de confirmation -->
                    <div class="row">
                        <div class="col-md-6 mx-auto">
                            <h5 class="text-center mb-3">Étape 2 : Confirmez avec le code</h5>

                            <form method="POST" action="{{ route('admin.two-factor.confirm') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="code" class="form-label">
                                        Code à 6 chiffres
                                    </label>
                                    <input type="text"
                                           class="form-control form-control-lg text-center font-monospace @error('code') is-invalid @enderror"
                                           id="code"
                                           name="code"
                                           placeholder="000000"
                                           maxlength="6"
                                           pattern="[0-9]{6}"
                                           required
                                           autofocus>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        Entrez le code affiché dans votre application d'authentification
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-check me-2"></i>
                                        Confirmer et Activer
                                    </button>
                                    <a href="{{ route('admin.two-factor.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Annuler
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .card {
        border: none;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        border-radius: 10px;
    }

    .qr-code-container {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        display: inline-block;
    }

    .font-monospace {
        font-family: 'Courier New', monospace;
        font-size: 14px;
        letter-spacing: 2px;
    }

    #code {
        font-size: 24px;
        letter-spacing: 5px;
    }
</style>

<script>
    function copySecret() {
        const secretInput = document.getElementById('secretKey');
        secretInput.select();
        document.execCommand('copy');

        // Visual feedback
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => {
            btn.innerHTML = originalHTML;
        }, 1000);
    }

    // Auto-format code input
    document.getElementById('code').addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });
</script>
@endsection
