@extends('layouts.app')

@section('title', 'Documentation Développeur - SAGAPASS')

@push('styles')
<style>
    .doc-sidebar {
        position: sticky;
        top: 100px;
        height: calc(100vh - 120px);
        overflow-y: auto;
        padding-right: 1.5rem;
        border-right: 1px solid var(--border-color);
    }
    .doc-sidebar .nav-link {
        color: var(--text-muted);
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-left: 3px solid transparent;
    }
    .doc-sidebar .nav-link:hover {
        color: var(--text-dark);
        background-color: var(--bg-light);
    }
    .doc-sidebar .nav-link.active {
        color: var(--primary);
        font-weight: 600;
        border-left-color: var(--primary);
    }
    .doc-content h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-top: 3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    .doc-content h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
    }
    .doc-content p, .doc-content li {
        color: var(--text-body);
        font-size: 1rem;
        line-height: 1.8;
    }
    .doc-content code {
        background-color: var(--primary-light);
        color: var(--primary-dark);
        padding: 0.2em 0.4em;
        border-radius: 4px;
        font-size: 0.9em;
    }
    .doc-content pre {
        background: var(--secondary);
        color: #e2e8f0;
        padding: 1.5rem;
        border-radius: 8px;
        font-family: 'SF Mono', 'Menlo', 'Monaco', 'Consolas', monospace;
        font-size: 0.9rem;
        white-space: pre-wrap;
    }
    .doc-content table {
        width: 100%;
        margin: 1.5rem 0;
        border-collapse: collapse;
    }
    .doc-content th, .doc-content td {
        border: 1px solid var(--border-color);
        padding: 0.6rem 0.8rem;
        font-size: 0.92rem;
        text-align: left;
    }
    .doc-content .alert {
        border-radius: 8px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid mt-5">
    <div class="row">
        <!-- Sidebar -->
        <nav id="doc-sidebar" class="col-lg-3 col-xl-2 d-none d-lg-block doc-sidebar">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="#introduction">Introduction</a></li>
                <li class="nav-item"><a class="nav-link" href="#credentials">Identifiants</a></li>
                <li class="nav-item"><a class="nav-link" href="#create-session">Créer une session</a></li>
                <li class="nav-item"><a class="nav-link" href="#status">Vérifier le statut</a></li>
                <li class="nav-item"><a class="nav-link" href="#webhook">Webhook de résultat</a></li>
                <li class="nav-item"><a class="nav-link" href="#fields">Champs par type de pièce</a></li>
                <li class="nav-item"><a class="nav-link" href="#expiry">Pièce expirée</a></li>
                <li class="nav-item"><a class="nav-link" href="#kyc-id">Revérifier un KYC ID</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="col-lg-9 col-xl-10 ms-sm-auto px-md-4 doc-content">
            <div class="pb-5">
                <div class="text-center text-lg-start">
                    <h1 class="display-4 fw-bold">Documentation Développeur</h1>
                    <p class="lead text-muted">Intégrez la vérification d'identité SAGAPASS — le seul service exposé aux partenaires pour le moment.</p>
                </div>

                <section id="introduction">
                    <h2><i class="fas fa-rocket me-2 text-primary"></i>Introduction</h2>
                    <p>SAGAPASS capture lui-même (sur sa propre page web, caméra en direct) la pièce d'identité et le selfie de votre utilisateur, effectue l'OCR, la vivacité et la correspondance visage↔pièce, puis vous notifie le résultat par webhook. Vous n'avez jamais besoin de manipuler de photos vous-même.</p>
                    <p>C'est aujourd'hui le seul service exposé via l'API partenaire. D'autres services (identification entreprise, etc.) sont en préparation — <a href="{{ route('contact') }}">contactez-nous</a> si votre besoin dépasse la vérification d'identité.</p>
                </section>

                <section id="credentials">
                    <h2><i class="fas fa-key me-2 text-primary"></i>Identifiants</h2>
                    <p>Après approbation de votre <a href="{{ route('partner.apply') }}">demande de partenariat</a>, vous recevez depuis votre <a href="{{ route('partner.dashboard') }}">tableau de bord</a> :</p>
                    <ul>
                        <li><code>client_id</code> / <code>client_secret</code> — authentification HTTP Basic sur les appels sortants.</li>
                        <li><code>webhook_secret</code> — vérifie la signature des webhooks entrants. Distinct du <code>client_secret</code>.</li>
                    </ul>
                </section>

                <section id="create-session">
                    <h2><i class="fas fa-network-wired me-2 text-primary"></i>Créer une session de vérification</h2>
                    <pre><code>POST {{ config('app.url') }}/api/partner/v1/verification-sessions
Auth: Basic (client_id, client_secret)
Body (JSON):
  document_type: "national_id" | "passport" | "drivers_license"
  partner_reference: string
  webhook_url: string
  partner_submitted_data: object

Réponse (201):
  session_token: string
  capture_url: string
  expires_at: string (ISO 8601)</code></pre>
                    <p>Redirigez l'utilisateur vers <code>capture_url</code> — SagaPass gère toute la capture caméra (pièce + selfie + vivacité active) sur sa propre page.</p>
                </section>

                <section id="status">
                    <h2><i class="fas fa-satellite-dish me-2 text-primary"></i>Vérifier le statut (filet de sécurité)</h2>
                    <pre><code>GET {{ config('app.url') }}/api/partner/v1/verification-sessions/{session_token}/status
Auth: Basic (client_id, client_secret)</code></pre>
                    <p>À utiliser uniquement si le webhook tarde — le webhook reste le chemin principal, pas du polling actif.</p>
                </section>

                <section id="webhook">
                    <h2><i class="fas fa-bell me-2 text-primary"></i>Webhook de résultat</h2>
                    <p>SagaPass envoie le résultat en <code>POST</code> sur votre <code>webhook_url</code>.</p>
                    <ul>
                        <li>Header <code>X-Saga-Signature</code>, valeur <code>sha256=&lt;hmac&gt;</code>.</li>
                        <li>Calcul : <code>hash_hmac('sha256', &lt;corps brut&gt;, webhook_secret)</code> — comparez en temps constant (<code>hash_equals</code>).</li>
                        <li>Événements : <code>verification.completed</code>, <code>verification.failed</code>, <code>verification.expired</code>, <code>kyc.expired</code>.</li>
                        <li>Le payload contient <code>session_token</code> et/ou <code>kyc_id</code> — utilisez-les comme clé d'idempotence.</li>
                    </ul>
                    <p>Répondez vite (<code>200 {"received": true}</code>) et traitez le résultat en file d'attente, jamais en synchrone dans le contrôleur du webhook.</p>
                </section>

                <section id="fields">
                    <h2><i class="fas fa-table me-2 text-primary"></i>Champs disponibles selon le type de pièce</h2>
                    <p>Tous les types renvoient au minimum <code>document_number</code>, <code>full_name</code>, <code>date_of_birth</code>, <code>date_of_expiry</code>.</p>
                    <table>
                        <thead><tr><th>Type</th><th>Champs supplémentaires</th></tr></thead>
                        <tbody>
                            <tr><td><code>national_id</code></td><td><code>sex</code>, <code>place_of_birth</code>, <code>date_of_issue</code></td></tr>
                            <tr><td><code>passport</code></td><td><code>sex</code>, <code>nationality</code>, <code>personal_number</code>, <code>mrz_line1</code>, <code>mrz_line2</code></td></tr>
                            <tr><td><code>drivers_license</code></td><td><code>sex</code>, <code>nif</code>, <code>address</code>, <code>blood_type</code>, <code>license_category</code>, <code>place_of_issue</code>, <code>date_of_issue</code></td></tr>
                        </tbody>
                    </table>
                    <p>Ne bloquez pas votre décision sur <code>face_match_score</code> seul — c'est un signal indicatif, pas un verdict.</p>
                </section>

                <section id="expiry">
                    <h2><i class="fas fa-calendar-times me-2 text-primary"></i>Pièce expirée</h2>
                    <p>Une pièce dont <code>date_of_expiry</code> est dans le passé est automatiquement rejetée (<code>verification.failed</code>) — vous n'avez rien à vérifier vous-même.</p>
                </section>

                <section id="kyc-id">
                    <h2><i class="fas fa-id-badge me-2 text-primary"></i>Revérifier un KYC ID durable</h2>
                    <pre><code>GET {{ config('app.url') }}/api/partner/v1/kyc-identities/{kyc_id}/status
Auth: Basic (client_id, client_secret)</code></pre>
                    <p>Permet de vérifier qu'une identité déjà validée est toujours dans sa période de validité, sans repasser par toute la capture.</p>
                </section>

                <div class="alert alert-light border mt-5">
                    <strong>Aller plus loin :</strong> le <a href="{{ route('partner.docs.index') }}">centre de documentation partenaire</a> couvre en détail chaque guide, y compris les cas particuliers déjà rencontrés en production.
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Active link scrolling
    const sidebar = document.getElementById('doc-sidebar');
    const sections = document.querySelectorAll('.doc-content section');

    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 120) {
                current = section.getAttribute('id');
            }
        });

        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(current)) {
                link.classList.add('active');
            }
        });
    });
</script>
@endpush
