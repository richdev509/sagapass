@extends('layouts.app')

@section('title', 'Tarifs - SAGAPASS')

@push('styles')
<style>
    .pricing-section { padding: 6rem 0; background-color: var(--bg-light); }
    .pricing-card {
        background: #fff;
        border-radius: 0.9rem;
        padding: 2.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        height: 100%;
    }
    .pricing-card .price { font-size: 2.5rem; font-weight: 800; color: var(--primary); }
    .pricing-card .price small { font-size: 1rem; font-weight: 500; color: var(--text-muted); }
    .pricing-card ul { list-style: none; padding: 0; margin: 1.5rem 0; }
    .pricing-card ul li { padding: 0.4rem 0; color: var(--text-body); }
    .pricing-card ul li i { color: var(--primary); margin-right: 0.5rem; }
</style>
@endpush

@section('content')
<section class="pricing-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h1 class="display-5 fw-bold">Tarifs</h1>
            <p class="lead text-muted">Simple et transparent — vous ne payez que ce que vous utilisez.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-5" data-aos="fade-up">
                <div class="pricing-card">
                    <h5 class="fw-bold text-uppercase text-muted mb-3">Vérification d'identité</h5>
                    <div class="price">0.15 HTG <small>/ vérification</small></div>
                    <p class="text-muted mt-2">Facturé uniquement pour chaque vérification d'identité effectuée via l'API partenaire (carte nationale, passeport, permis de conduire).</p>
                    <ul>
                        <li><i class="fas fa-check-circle"></i>OCR + vivacité + correspondance faciale</li>
                        <li><i class="fas fa-check-circle"></i>Résultat par webhook en temps réel</li>
                        <li><i class="fas fa-check-circle"></i>Aucun engagement, aucun minimum mensuel</li>
                    </ul>
                    <a href="{{ route('partner.apply') }}" class="btn btn-primary w-100">Devenir partenaire</a>
                </div>
            </div>

            <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
                <div class="pricing-card">
                    <h5 class="fw-bold text-uppercase text-muted mb-3">Autres services</h5>
                    <div class="price" style="font-size:1.5rem;">Sur devis</div>
                    <p class="text-muted mt-2">Identification entreprise (KYB) et autres besoins d'intégration sur mesure : contactez-nous pour en savoir plus et obtenir un tarif adapté.</p>
                    <ul>
                        <li><i class="fas fa-check-circle"></i>Identification entreprise (KYB)</li>
                        <li><i class="fas fa-check-circle"></i>Intégrations sur mesure</li>
                    </ul>
                    <a href="{{ route('contact') }}" class="btn btn-outline-primary w-100">Nous contacter</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
