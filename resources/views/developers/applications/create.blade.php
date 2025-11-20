@extends('layouts.app')

@section('title', 'Créer une application')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Header --}}
            <div class="mb-4">
                <a href="{{ route('developers.applications.index') }}" class="text-decoration-none text-muted mb-3 d-inline-block">
                    <i class="fas fa-arrow-left me-2"></i>Retour à mes applications
                </a>
                <h2 class="fw-bold">
                    <i class="fas fa-plus-circle me-2"></i>
                    Créer une nouvelle application
                </h2>
                <p class="text-muted">
                    Remplissez les informations ci-dessous pour créer votre application OAuth
                </p>
            </div>

            {{-- Formulaire --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('developers.applications.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Nom de l'application --}}
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">
                                Nom de l'application <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Mon Application"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Le nom qui sera affiché aux utilisateurs lors de la connexion
                            </small>
                        </div>

                        {{-- Description --}}
                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="Décrivez votre application et son utilisation..."
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Maximum 1000 caractères. Cette description sera visible par les utilisateurs.
                            </small>
                        </div>

                        {{-- Site web --}}
                        <div class="mb-4">
                            <label for="website" class="form-label fw-semibold">
                                Site web <span class="text-danger">*</span>
                            </label>
                            <input type="url"
                                   class="form-control @error('website') is-invalid @enderror"
                                   id="website"
                                   name="website"
                                   value="{{ old('website') }}"
                                   placeholder="https://monsite.com"
                                   required>
                            @error('website')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                L'URL de votre site web ou application
                            </small>
                        </div>

                        {{-- Logo --}}
                        <div class="mb-4">
                            <label for="logo" class="form-label fw-semibold">
                                Logo de l'application
                            </label>
                            <input type="file"
                                   class="form-control @error('logo') is-invalid @enderror"
                                   id="logo"
                                   name="logo"
                                   accept="image/png,image/jpeg,image/jpg">
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Format: PNG, JPG, JPEG. Taille max: 2MB. Recommandé: 512x512px
                            </small>
                            <div id="logoPreview" class="mt-3" style="display: none;">
                                <img id="logoPreviewImage" src="" alt="Aperçu" class="rounded" style="max-width: 150px;">
                            </div>
                        </div>

                        {{-- URIs de redirection --}}
                        <div class="mb-4">
                            <label for="redirect_uris" class="form-label fw-semibold">
                                URIs de redirection <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control font-monospace @error('redirect_uris') is-invalid @enderror"
                                      id="redirect_uris"
                                      name="redirect_uris"
                                      rows="5"
                                      placeholder="https://monsite.com/auth/callback&#10;https://localhost:3000/callback"
                                      required>{{ old('redirect_uris') }}</textarea>
                            @error('redirect_uris')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Important :</strong> Une URI par ligne. Ces URLs doivent correspondre exactement aux redirections configurées dans votre code.
                                En production, seules les URLs HTTPS sont autorisées.
                            </small>
                        </div>

                        {{-- Informations importantes --}}
                        <div class="alert alert-info mb-4">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Ce qui se passera après la création
                            </h6>
                            <ul class="mb-0 small">
                                <li>Votre application sera créée avec le statut <strong>"En attente"</strong></li>
                                <li>Un <strong>Client ID</strong> et un <strong>Client Secret</strong> seront générés automatiquement</li>
                                <li>Un administrateur devra approuver votre application avant qu'elle ne soit utilisable</li>
                                <li>Par défaut, seul le scope <strong>"profile"</strong> sera autorisé</li>
                                <li>Vous pourrez demander des scopes additionnels après approbation</li>
                            </ul>
                        </div>

                        {{-- Scopes disponibles (info) --}}
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-key me-2"></i>
                                    Scopes disponibles
                                </h6>
                                <div class="row g-3">
                                    @foreach($availableScopes as $scope => $description)
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                                <div>
                                                    <code class="small">{{ $scope }}</code>
                                                    <p class="mb-0 small text-muted">{{ $description }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mt-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Par défaut, seul "profile" est activé. Vous pourrez demander l'ajout d'autres scopes après l'approbation de votre application.
                                </small>
                            </div>
                        </div>

                        {{-- Conditions --}}
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                J'accepte les <a href="#" target="_blank">Conditions d'utilisation</a> et la
                                <a href="#" target="_blank">Politique de confidentialité</a> de SAGAPASS
                            </label>
                        </div>

                        {{-- Boutons --}}
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('developers.applications.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-2"></i>
                                Créer l'application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Aperçu du logo
document.getElementById('logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreviewImage').src = e.target.result;
            document.getElementById('logoPreview').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>
@endsection
