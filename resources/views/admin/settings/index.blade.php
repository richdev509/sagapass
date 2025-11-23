@extends('admin.layouts.admin')

@section('title', 'Paramètres Système')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-gradient-primary">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Paramètres Système
                    </h3>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf

                        <!-- Mode Maintenance -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-tools me-2 text-warning"></i>
                                Mode Maintenance
                            </h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input type="hidden" name="maintenance_mode" value="0">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="maintenance_mode"
                                               name="maintenance_mode"
                                               value="1"
                                               {{ old('maintenance_mode', $settings['maintenance_mode']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="maintenance_mode">
                                            <strong>Activer le mode maintenance</strong>
                                            <br>
                                            <small class="text-muted">
                                                Lorsque activé, les citoyens verront une page de maintenance. Les administrateurs peuvent toujours accéder au système.
                                            </small>
                                        </label>
                                    </div>

                                    <div class="mb-3">
                                        <label for="maintenance_message" class="form-label">
                                            Message de maintenance
                                        </label>
                                        <textarea class="form-control @error('maintenance_message') is-invalid @enderror"
                                                  id="maintenance_message"
                                                  name="maintenance_message"
                                                  rows="3"
                                                  maxlength="500">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                                        @error('maintenance_message')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Maximum 500 caractères</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Mode Beta -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-flask me-2 text-info"></i>
                                Mode Beta / Early Access
                            </h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="beta_mode" value="0">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="beta_mode"
                                               name="beta_mode"
                                               value="1"
                                               {{ old('beta_mode', $settings['beta_mode']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="beta_mode">
                                            <strong>Afficher la bannière Beta</strong>
                                            <br>
                                            <small class="text-muted">
                                                Affiche une bannière en haut de la page d'accueil et du tableau de bord pour informer les utilisateurs que le système est en phase Beta.
                                            </small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Sécurité 2FA -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-shield-alt me-2 text-danger"></i>
                                Authentification à Deux Facteurs (2FA)
                            </h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="force_2fa_for_admins" value="0">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="force_2fa_for_admins"
                                               name="force_2fa_for_admins"
                                               value="1"
                                               {{ old('force_2fa_for_admins', $settings['force_2fa_for_admins']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="force_2fa_for_admins">
                                            <strong>Forcer le 2FA pour tous les administrateurs</strong>
                                            <br>
                                            <small class="text-muted">
                                                Si activé, tous les administrateurs devront configurer l'authentification à deux facteurs (Google Authenticator) avant d'accéder au dashboard.
                                                Les admins sans 2FA configuré seront automatiquement redirigés vers la page de configuration.
                                            </small>
                                        </label>
                                    </div>

                                    <div class="alert alert-warning mt-3 mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Important:</strong> Assurez-vous d'avoir configuré votre propre 2FA avant d'activer cette option pour éviter tout blocage.
                                        <a href="{{ route('admin.two-factor.index') }}" class="alert-link">Gérer mon 2FA</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Support WhatsApp -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fab fa-whatsapp me-2 text-success"></i>
                                Support WhatsApp
                            </h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="whatsapp_support_link" class="form-label">
                                            Lien WhatsApp de support
                                        </label>
                                        <input type="url"
                                               class="form-control @error('whatsapp_support_link') is-invalid @enderror"
                                               id="whatsapp_support_link"
                                               name="whatsapp_support_link"
                                               value="{{ old('whatsapp_support_link', $settings['whatsapp_support_link']) }}"
                                               placeholder="https://wa.me/221700000000">
                                        @error('whatsapp_support_link')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">
                                            Utilisé pour le bouton "Signaler un problème". Format: https://wa.me/[indicatif][numéro]
                                        </small>
                                    </div>

                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Exemple:</strong> Pour le numéro +221 70 000 00 00, utilisez: https://wa.me/221700000000
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour au tableau de bord
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Enregistrer les paramètres
                            </button>
                        </div>
                    </form>
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

    .form-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
        cursor: pointer;
    }

    .form-switch .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
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
