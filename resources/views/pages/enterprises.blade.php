@extends('layouts.app')

@section('title', 'Pour les entreprises - SAGAPASS')

@push('styles')
<style>
    .ent-hero { padding: 6rem 0 3rem; background-color: #FFFFFF; }
    .ent-section { padding: 5rem 0; }
    .ent-section.alt { background-color: var(--bg-light); }
    .ent-feature { padding: 1.5rem; }
    .ent-feature i { font-size: 1.75rem; color: var(--primary); margin-bottom: 1rem; }
</style>
@endpush

@section('content')
<section class="ent-hero text-center">
    <div class="container" data-aos="fade-up">
        <h1 class="display-4 fw-bold">Vérification d'identité pour votre entreprise</h1>
        <p class="lead text-muted mx-auto" style="max-width: 42rem;">SAGAPASS vérifie vos utilisateurs à votre place : pièce d'identité, vivacité et correspondance faciale, sans que vous ayez à manipuler une seule photo.</p>
        <div class="mt-4 d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('partner.apply') }}" class="btn btn-primary btn-lg">Devenir partenaire</a>
            <a href="{{ route('pricing') }}" class="btn btn-outline-primary btn-lg">Voir les tarifs</a>
        </div>
    </div>
</section>

<section class="ent-section alt">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Ce que nous exposons aujourd'hui</h2>
            <p class="text-muted">Un seul service, pensé pour être intégré rapidement.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="ent-feature text-center">
                    <i class="fas fa-id-card"></i>
                    <h5 class="fw-bold">Vérification d'identité partenaire</h5>
                    <p class="text-muted">Carte nationale, passeport ou permis de conduire — capture, OCR, vivacité et correspondance faciale gérés entièrement par SagaPass.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="ent-feature text-center">
                    <i class="fas fa-bell"></i>
                    <h5 class="fw-bold">Résultat en temps réel</h5>
                    <p class="text-muted">Un webhook signé vous notifie dès que la vérification est terminée — aucune intégration de polling nécessaire.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="ent-feature text-center">
                    <i class="fas fa-shield-alt"></i>
                    <h5 class="fw-bold">Anti-fraude intégré</h5>
                    <p class="text-muted">Détection de doublons de visage et de pièces expirées appliquée automatiquement, sans configuration de votre côté.</p>
                </div>
            </div>
        </div>
        <p class="text-center text-muted mt-5">D'autres services (identification entreprise, etc.) sont en préparation — <a href="{{ route('contact') }}">contactez-nous</a> pour en discuter.</p>
    </div>
</section>

<section class="ent-section text-center">
    <div class="container" data-aos="fade-up">
        <h2 class="fw-bold">Prêt à intégrer SAGAPASS ?</h2>
        <p class="lead text-muted mb-4">Soumettez votre demande de partenariat, un administrateur l'examine avant l'activation de vos identifiants API.</p>
        <a href="{{ route('partner.apply') }}" class="btn btn-primary btn-lg me-2">Devenir partenaire</a>
        <a href="{{ route('partner.docs.index') }}" class="btn btn-outline-primary btn-lg">Consulter la documentation</a>
    </div>
</section>
@endsection
