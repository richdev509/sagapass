@extends('admin.layouts.admin')

@section('title', 'Authentification à Deux Facteurs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-gradient-primary">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Authentification à Deux Facteurs (2FA)
                    </h3>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="mb-4">
                        <h5>Qu'est-ce que l'authentification à deux facteurs ?</h5>
                        <p class="text-muted">
                            L'authentification à deux facteurs (2FA) ajoute une couche de sécurité supplémentaire à votre compte.
                            En plus de votre mot de passe, vous devrez entrer un code à 6 chiffres généré par une application
                            d'authentification comme <strong>Google Authenticator</strong> ou <strong>Microsoft Authenticator</strong>.
                        </p>
                    </div>

                    @if($twoFactorEnabled)
                        <!-- 2FA Activé -->
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-2x float-start me-3"></i>
                            <h5 class="alert-heading">2FA Activé</h5>
                            <p class="mb-0">
                                Votre compte est protégé par l'authentification à deux facteurs.
                                Vous devrez entrer un code à chaque connexion.
                            </p>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-key me-2 text-primary"></i>
                                            Régénérer les codes de récupération
                                        </h6>
                                        <p class="card-text small text-muted">
                                            Générez de nouveaux codes de récupération si vous avez perdu les anciens.
                                        </p>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#regenerateCodesModal">
                                            Régénérer les codes
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-times-circle me-2 text-danger"></i>
                                            Désactiver le 2FA
                                        </h6>
                                        <p class="card-text small text-muted">
                                            Désactivez l'authentification à deux facteurs (non recommandé).
                                        </p>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#disableTwoFactorModal">
                                            Désactiver le 2FA
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Désactiver 2FA -->
                        <div class="modal fade" id="disableTwoFactorModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.two-factor.disable') }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Désactiver le 2FA</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Attention !</strong> Désactiver le 2FA rendra votre compte moins sécurisé.
                                            </div>
                                            <div class="mb-3">
                                                <label for="password_disable" class="form-label">
                                                    Confirmez votre mot de passe
                                                </label>
                                                <input type="password"
                                                       class="form-control @error('password') is-invalid @enderror"
                                                       id="password_disable"
                                                       name="password"
                                                       required>
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-danger">Désactiver</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Régénérer Codes -->
                        <div class="modal fade" id="regenerateCodesModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.two-factor.recovery-codes.regenerate') }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Régénérer les codes de récupération</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Les anciens codes de récupération seront invalidés et ne fonctionneront plus.
                                            </div>
                                            <div class="mb-3">
                                                <label for="password_regenerate" class="form-label">
                                                    Confirmez votre mot de passe
                                                </label>
                                                <input type="password"
                                                       class="form-control @error('password') is-invalid @enderror"
                                                       id="password_regenerate"
                                                       name="password"
                                                       required>
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-primary">Régénérer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    @else
                        <!-- 2FA Désactivé -->
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle fa-2x float-start me-3"></i>
                            <h5 class="alert-heading">2FA Non Activé</h5>
                            <p class="mb-0">
                                Votre compte n'est pas protégé par l'authentification à deux facteurs.
                                Nous vous recommandons fortement de l'activer pour sécuriser votre compte.
                            </p>
                        </div>

                        <div class="text-center mt-4">
                            <a href="{{ route('admin.two-factor.enable') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-shield-alt me-2"></i>
                                Activer le 2FA
                            </a>
                        </div>

                        <div class="mt-4">
                            <h6>Prérequis :</h6>
                            <ul>
                                <li>Installez une application d'authentification sur votre smartphone</li>
                                <li>Exemples : Google Authenticator, Microsoft Authenticator, Authy</li>
                                <li>Vous aurez besoin de scanner un QR code</li>
                            </ul>
                        </div>
                    @endif
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

    .card-header {
        border-radius: 10px 10px 0 0 !important;
    }
</style>
@endsection
