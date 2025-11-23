<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation Badge - SAGAPASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .validation-card {
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .user-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="validation-card">
        <div class="card border-0 shadow-lg">
            <div class="card-body text-center p-4">
                @if($valid)
                    <!-- Badge Valide -->
                    <div class="status-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="fw-bold mb-3">Badge Vérifié ✓</h2>
                    <p class="text-muted mb-4">{{ $message }}</p>

                    @if(isset($user))
                        <!-- Photo de profil -->
                        @if($user->profile_picture)
                            <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                 alt="{{ $user->first_name }}"
                                 class="user-photo mb-3">
                        @else
                            <div class="user-photo mb-3 d-flex align-items-center justify-content-center bg-secondary text-white">
                                <i class="fas fa-user fa-3x"></i>
                            </div>
                        @endif

                        <!-- Informations utilisateur -->
                        <div class="text-start mt-4">
                            <h5 class="fw-bold mb-3">Informations du Titulaire</h5>

                            <div class="info-row">
                                <span class="text-muted">Nom complet</span>
                                <span class="fw-bold">{{ $user->first_name }} {{ $user->last_name }}</span>
                            </div>

                            <div class="info-row">
                                <span class="text-muted">Email</span>
                                <span class="fw-bold">{{ $user->email }}</span>
                            </div>

                            <div class="info-row">
                                <span class="text-muted">Niveau de compte</span>
                                <span>
                                    @if($user->account_level === 'verified')
                                        <span class="badge bg-success">
                                            <i class="fas fa-shield-alt"></i> Vérifié
                                        </span>
                                    @elseif($user->account_level === 'basic')
                                        <span class="badge bg-info">
                                            <i class="fas fa-user"></i> Basic
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-hourglass-half"></i> En attente
                                        </span>
                                    @endif
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="text-muted">Vérification</span>
                                <span class="fw-bold">{{ ucfirst($user->verification_level) }}</span>
                            </div>

                            @if(isset($badge))
                                <div class="info-row">
                                    <span class="text-muted">Badge expire</span>
                                    <span class="fw-bold">{{ $badge->expires_at->format('d/m/Y H:i') }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="text-muted">Scanné</span>
                                    <span class="fw-bold">{{ $badge->scan_count }} fois</span>
                                </div>
                            @endif
                        </div>

                        <!-- Statut de vérification -->
                        <div class="alert alert-success mt-4 mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Identité vérifiée</strong> - Ce badge est valide et authentique
                        </div>
                    @endif

                @else
                    <!-- Badge Invalide -->
                    <div class="status-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h2 class="fw-bold mb-3">Badge Invalide ✗</h2>
                    <p class="text-muted mb-4">{{ $message }}</p>

                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Ce badge n'est pas valide ou a expiré
                    </div>
                @endif

                <!-- Footer -->
                <div class="mt-4 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Vérification sécurisée par SAGAPASS
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
