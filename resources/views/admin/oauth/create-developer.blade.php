@extends('admin.layouts.admin')

@section('title', 'Créer un compte développeur')
@section('page-title', 'Créer un compte développeur')
@section('page-subtitle', 'Rechercher un citoyen et créer son compte développeur OAuth')

@section('styles')
<style>
    .step-card .card-header {
        border-radius: 12px 12px 0 0 !important;
        padding: 15px 20px;
    }
    .step-card .card-header.bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; }
    .step-card .card-header.bg-info { background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%) !important; }
    .step-card .card-header.bg-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important; }
    .step-card .card-header.bg-warning { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%) !important; }
    .step-card .card-header.bg-dark { background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%) !important; }
    .step-card .card-header h5 { color: #fff; }
    .step-card .card-header.bg-warning h5 { color: #2d3748; }
    .step-card:hover { transform: none; }
    .citizen-info-row { display: flex; flex-wrap: wrap; gap: 8px 0; }
    .citizen-info-row .col-md-4 { margin-bottom: 4px; }
    .scope-item { padding: 8px 12px; border-radius: 8px; background: #f8f9fa; margin-bottom: 6px; }
    .scope-item label { margin-bottom: 0; word-break: break-word; }
    .btn-submit-lg { padding: 14px 24px; font-size: 16px; border-radius: 10px; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <a href="{{ route('admin.oauth.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="fas fa-arrow-left me-1"></i> Retour aux applications
            </a>
            <h2 class="mb-1">
                <i class="fas fa-user-plus text-primary me-2"></i>Créer un compte développeur
            </h2>
            <p class="text-muted mb-0">Recherchez un citoyen vérifié par NIU, puis créez son compte développeur et son application OAuth.</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>Veuillez corriger les erreurs suivantes :</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Étape 1 : Recherche par NIU -->
    <div class="row mb-4">
        <div class="col-lg-8 col-md-10">
            <div class="card step-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-search me-2"></i>Étape 1 : Rechercher le citoyen par NIU</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <div class="col-sm-7 col-md-6">
                            <label for="niu_search" class="form-label fw-bold">Numéro d'Identification Unique (NIU)</label>
                            <input type="text" class="form-control form-control-lg" id="niu_search"
                                   placeholder="Ex: 1234567890" maxlength="10">
                            <small class="text-muted">Saisissez les 10 chiffres du NIU</small>
                        </div>
                        <div class="col-sm-5 col-md-4">
                            <button type="button" class="btn btn-primary btn-lg w-100" id="btn_search" onclick="searchCitizen()">
                                <i class="fas fa-search me-1"></i> Rechercher
                            </button>
                        </div>
                    </div>

                    <!-- Résultat de la recherche -->
                    <div id="search_result" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Étape 2+ : Formulaire de création (masqué tant que citoyen non trouvé) -->
    <div id="developer_form_section" style="display: none;">
        <form action="{{ route('admin.oauth.store-developer') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" id="user_id">

            <div class="row g-4">
                <!-- Profil Développeur -->
                <div class="col-lg-6">
                    <div class="card step-card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Étape 2 : Profil développeur</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="company_name" class="form-label fw-bold">Nom de la société / organisation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="company_name" name="company_name"
                                       value="{{ old('company_name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="developer_website" class="form-label fw-bold">Site web</label>
                                <input type="url" class="form-control" id="developer_website" name="developer_website"
                                       value="{{ old('developer_website') }}" placeholder="https://example.com">
                            </div>
                            <div class="mb-3">
                                <label for="developer_bio" class="form-label fw-bold">Description / Bio</label>
                                <textarea class="form-control" id="developer_bio" name="developer_bio"
                                          rows="3" maxlength="1000">{{ old('developer_bio') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application OAuth -->
                <div class="col-lg-6">
                    <div class="card step-card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-plug me-2"></i>Étape 3 : Application OAuth</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="app_name" class="form-label fw-bold">Nom de l'application <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="app_name" name="app_name"
                                       value="{{ old('app_name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="app_description" class="form-label fw-bold">Description</label>
                                <textarea class="form-control" id="app_description" name="app_description"
                                          rows="2" maxlength="1000">{{ old('app_description') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="app_website" class="form-label fw-bold">Site web de l'application</label>
                                <input type="url" class="form-control" id="app_website" name="app_website"
                                       value="{{ old('app_website') }}" placeholder="https://myapp.com">
                            </div>
                            <div class="mb-3">
                                <label for="redirect_uris" class="form-label fw-bold">URIs de redirection <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="redirect_uris" name="redirect_uris"
                                          rows="3" required placeholder="https://myapp.com/callback&#10;myapp://auth/callback">{{ old('redirect_uris') }}</textarea>
                                <small class="text-muted">Une URI par ligne. Ex: kaypa://auth/callback</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scopes et Options -->
            <div class="row g-4 mt-1">
                <div class="col-lg-6">
                    <div class="card step-card h-100">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Scopes autorisés</h5>
                        </div>
                        <div class="card-body">
                            @foreach($availableScopes as $scope => $label)
                                <div class="scope-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="allowed_scopes[]"
                                               value="{{ $scope }}" id="scope_{{ $scope }}"
                                               {{ $scope === 'profile' ? 'checked disabled' : '' }}
                                               {{ in_array($scope, old('allowed_scopes', ['profile'])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="scope_{{ $scope }}">
                                            <strong>{{ $scope }}</strong><br>
                                            <small class="text-muted">{{ $label }}</small>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                            <input type="hidden" name="allowed_scopes[]" value="profile">
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card step-card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Options</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_approve" name="auto_approve" value="1" checked>
                                <label class="form-check-label" for="auto_approve">
                                    <strong>Approuver automatiquement</strong><br>
                                    <small class="text-muted">L'application sera directement approuvée sans passer par la file d'attente.</small>
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_trusted" name="is_trusted" value="1">
                                <label class="form-check-label" for="is_trusted">
                                    <strong>Application de confiance</strong><br>
                                    <small class="text-muted">Les utilisateurs ne verront pas l'écran de consentement (services gouvernementaux).</small>
                                </label>
                            </div>

                            <hr>

                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Informations auto-générées :</strong>
                                <ul class="mb-0 mt-1">
                                    <li><strong>Client ID</strong> — UUID unique généré automatiquement</li>
                                    <li><strong>Client Secret</strong> — Clé secrète chiffrée (visible après création)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton de soumission -->
                    <div class="d-grid mt-4 mb-4">
                        <button type="submit" class="btn btn-primary btn-submit-lg" id="btn_submit">
                            <i class="fas fa-check-circle me-2"></i>
                            Créer le compte développeur et l'application
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function searchCitizen() {
    const niu = document.getElementById('niu_search').value.trim();
    const resultDiv = document.getElementById('search_result');
    const formSection = document.getElementById('developer_form_section');
    const btn = document.getElementById('btn_search');

    if (niu.length !== 10) {
        resultDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Le NIU doit contenir exactement 10 caractères.</div>';
        resultDiv.style.display = 'block';
        formSection.style.display = 'none';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Recherche...';
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    fetch('{{ route("admin.oauth.search-citizen") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ niu: niu }),
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search me-1"></i> Rechercher';

        if (data.found) {
            const c = data.citizen;
            let html = `
                <div class="alert alert-success">
                    <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Citoyen trouvé !</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Nom :</strong> ${c.first_name} ${c.last_name}
                        </div>
                        <div class="col-md-4">
                            <strong>Email :</strong> ${c.email}
                        </div>
                        <div class="col-md-4">
                            <strong>Téléphone :</strong> ${c.phone || 'N/A'}
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <strong>NIU :</strong> <code>${c.niu}</code>
                        </div>
                        <div class="col-md-4">
                            <strong>Statut :</strong> <span class="badge bg-success">${c.verification_status}</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Niveau :</strong> <span class="badge bg-info">${c.account_level || 'N/A'}</span>
                        </div>
                    </div>`;

            if (c.has_developer_account) {
                html += `
                    <hr>
                    <div class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Ce citoyen a déjà un compte développeur.</strong>
                        Une nouvelle application sera créée sous son compte existant.
                    </div>`;
            }

            html += `</div>`;
            resultDiv.innerHTML = html;

            // Remplir le formulaire
            document.getElementById('user_id').value = c.id;
            formSection.style.display = 'block';

            // Pré-remplir le nom de l'entreprise si vide
            if (!document.getElementById('company_name').value) {
                document.getElementById('company_name').value = c.first_name + ' ' + c.last_name;
            }
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ${data.message}</div>`;
            formSection.style.display = 'none';
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search me-1"></i> Rechercher';
        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Erreur de connexion. Veuillez réessayer.</div>';
        formSection.style.display = 'none';
    });
}

// Rechercher avec Entrée
document.getElementById('niu_search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchCitizen();
    }
});
</script>
@endsection
