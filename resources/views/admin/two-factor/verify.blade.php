<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('sagapass-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('sagapass-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('sagapass-logo.png') }}">

    <title>Vérification 2FA - SAGAPASS</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-shield-alt fa-3x text-primary"></i>
                        </div>
                        <h3 class="mb-2">Vérification à Deux Facteurs</h3>
                        <p class="text-muted">
                            Entrez le code généré par votre application d'authentification
                        </p>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.two-factor.verify') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="code" class="form-label fw-bold">
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
                                   autofocus
                                   autocomplete="one-time-code">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check me-2"></i>
                                Vérifier
                            </button>
                        </div>

                        <div class="text-center">
                            <button type="button"
                                    class="btn btn-link text-muted"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#recoveryCodeForm">
                                <i class="fas fa-key me-2"></i>
                                Utiliser un code de récupération
                            </button>
                        </div>
                    </form>

                    <!-- Formulaire codes de récupération (caché par défaut) -->
                    <div class="collapse mt-3" id="recoveryCodeForm">
                        <hr>
                        <form method="POST" action="{{ route('admin.two-factor.verify') }}">
                            @csrf
                            <input type="hidden" name="recovery" value="1">

                            <div class="mb-3">
                                <label for="recovery_code" class="form-label fw-bold">
                                    Code de récupération
                                </label>
                                <input type="text"
                                       class="form-control text-center font-monospace @error('recovery_code') is-invalid @enderror"
                                       id="recovery_code"
                                       name="recovery_code"
                                       placeholder="XXXXXXXXXX"
                                       maxlength="10"
                                       required>
                                @error('recovery_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Entrez l'un de vos codes de récupération (10 caractères)
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-unlock me-2"></i>
                                    Utiliser le code de récupération
                                </button>
                            </div>
                        </form>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-link text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Se déconnecter
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Le code change toutes les 30 secondes
                </small>
            </div>
        </div>
    </div>
</div>

<style>
    .font-monospace {
        font-family: 'Courier New', monospace;
        font-size: 24px;
        letter-spacing: 5px;
    }

    #code, #recovery_code {
        font-size: 20px;
        letter-spacing: 3px;
    }

    .card {
        border: none;
        border-radius: 15px;
    }
</style>

<script>
    // Auto-format code input (only numbers)
    document.getElementById('code').addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });

    // Auto-format recovery code input (uppercase)
    document.getElementById('recovery_code').addEventListener('input', function(e) {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });
</script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
