@extends('layouts.app')

@section('title', 'Modifier ' . $application->name)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Header --}}
            <div class="mb-4">
                <a href="{{ route('developers.applications.show', $application) }}" class="text-decoration-none text-muted mb-3 d-inline-block">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux détails
                </a>
                <h2 class="fw-bold">
                    <i class="fas fa-edit me-2"></i>
                    Modifier l'application
                </h2>
                <p class="text-muted">{{ $application->name }}</p>
            </div>

            {{-- Formulaire --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('developers.applications.update', $application) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Nom de l'application --}}
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">
                                Nom de l'application <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $application->name) }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                      required>{{ old('description', $application->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                   value="{{ old('website', $application->website) }}"
                                   required>
                            @error('website')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Logo actuel --}}
                        @if($application->logo_path)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Logo actuel</label>
                                <div>
                                    <img src="{{ asset('storage/' . $application->logo_path) }}"
                                         alt="{{ $application->name }}"
                                         class="rounded"
                                         style="max-width: 150px;">
                                </div>
                            </div>
                        @endif

                        {{-- Nouveau logo --}}
                        <div class="mb-4">
                            <label for="logo" class="form-label fw-semibold">
                                {{ $application->logo_path ? 'Changer le logo' : 'Ajouter un logo' }}
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
                                Format: PNG, JPG, JPEG. Taille max: 2MB
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
                                      required>{{ old('redirect_uris', implode("\n", $application->redirect_uris ?? [])) }}</textarea>
                            @error('redirect_uris')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Une URI par ligne. HTTPS obligatoire en production.
                            </small>
                        </div>

                        {{-- Note --}}
                        <div class="alert alert-info mb-4">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Informations importantes
                            </h6>
                            <ul class="mb-0 small">
                                <li>Le <strong>Client ID</strong> et le <strong>Client Secret</strong> ne peuvent pas être modifiés ici</li>
                                <li>Les modifications seront effectives immédiatement</li>
                                <li>Si vous modifiez les URIs de redirection, assurez-vous de mettre à jour votre code</li>
                                <li>Pour modifier les scopes autorisés, contactez l'équipe SAGAPASS</li>
                            </ul>
                        </div>

                        {{-- Boutons --}}
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('developers.applications.show', $application) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Enregistrer les modifications
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
