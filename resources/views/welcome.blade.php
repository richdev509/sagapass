@extends('layouts.app')

@section('title', 'Accueil - Votre Identité Numérique Souveraine')

@push('styles')
<style>
    .hero-section {
        padding: 6rem 0;
        background-color: #FFFFFF;
    }
    .hero-section .display-4 {
        font-weight: 800;
        color: var(--secondary);
    }
    .hero-section .lead {
        font-size: 1.25rem;
        color: var(--text-body);
    }
    .hero-visual {
        position: relative;
    }
    .hero-visual img {
        max-height: 450px;
        object-fit: contain;
    }
    @keyframes floatAnimation {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    .floating {
        animation: floatAnimation 4s ease-in-out infinite;
    }
    
    @keyframes pulseAnimation {
        0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
        70% { box-shadow: 0 0 0 20px rgba(13, 110, 253, 0); }
        100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
    }

    .features-section {
        padding: 6rem 0;
        background-color: var(--bg-light);
    }
    .feature-card {
        background-color: #FFFFFF;
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 2rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .feature-icon {
        font-size: 2rem;
        color: var(--primary);
    }
    .business-section {
        padding: 6rem 0;
    }
    .business-section img {
        max-height: 400px;
        object-fit: contain;
    }
    .cta-section {
        padding: 6rem 0;
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        color: #fff;
    }

    /* ===== MOBILE STICKY SCROLL ANIMATION ===== */
    @media (max-width: 991.98px) {
        .hero-section {
            padding: 0;
        }

        /* Outer scene: tall enough to allow scrolling through the animation */
        .mobile-scroll-scene {
            height: 145vh;
            position: relative;
        }

        /* Inner sticky stage: stays glued to the top of viewport while scrolling */
        .mobile-scroll-stage {
            position: sticky;
            top: 0;
            height: 52vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 1.5rem;
            background: #fff;
        }

        /* Logo circle */
        .mobile-logo-wrapper {
            position: relative;
            width: 160px;
            height: 160px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(13,110,253,0.2);
            animation: pulseAnimation 2s infinite;
            flex-shrink: 0;
            z-index: 10;
            transition: width 0.05s linear, height 0.05s linear, box-shadow 0.05s linear;
        }
        .mobile-logo-wrapper img {
            height: 95px;
            width: auto;
            transition: opacity 0.1s linear;
        }
        .mobile-logo-wrapper .check-icon {
            position: absolute;
            font-size: 0px;
            color: var(--primary);
            opacity: 0;
            transition: opacity 0.1s linear, font-size 0.1s linear;
        }

        /* Scroll hint arrow */
        .scroll-hint {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            margin-top: -16.25rem;
            opacity: 1;
            transition: opacity 0.2s linear;
            z-index: 20;
        }
        .scroll-hint span {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--primary);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .scroll-hint .arrow-down {
            width: 30px;
            height: 30px;
            border-right: 3px solid var(--primary);
            border-bottom: 3px solid var(--primary);
            transform: rotate(45deg);
            animation: bounceArrow 1.2s ease-in-out infinite;
        }
        @keyframes bounceArrow {
            0%, 100% { transform: rotate(45deg) translateY(0); }
            50% { transform: rotate(45deg) translateY(6px); }
        }

        /* Mokup image: starts below viewport, scrolls up */
        .mobile-mokup-img {
            width: auto;
            height: 49vh;
            max-width: 100%;
            margin-top: 0.5rem;
            transform: translateY(100vh);
            opacity: 0;
            transition: transform 0.05s linear, opacity 0.05s linear;
        }

        /* Hero text below the sticky scene — peek visible at bottom */
        .hero-text-content {
            padding: 1rem 1.5rem 2rem;
            margin-top: -2rem; /* pull text up to overlap slightly under the stage */
        }
        .hero-section .display-4 {
            font-size: 1.9rem;
        }
        .hero-section .lead {
            font-size: 0.9rem;
        }
        .hero-section .cta-text {
            font-size: 0.9rem;
        }
        .d-flex.align-items-center.gap-4 {
            flex-direction: column;
            align-items: stretch !important;
        }
    }
</style>
@endpush

@section('content')

<!-- Hero Section -->
<section class="hero-section" id="hero">
    <div class="container">
        
        <!-- ===== MOBILE STICKY SCROLL ANIMATION ===== -->
        <div class="d-block d-lg-none mobile-scroll-scene" id="mobile-scroll-scene">
            <div class="mobile-scroll-stage" id="mobile-scroll-stage">
                <div class="mobile-logo-wrapper" id="mobile-logo">
                    <img src="{{ asset('assets/images/sagapass-logo.png') }}" alt="SAGAPASS Logo" id="mobile-logo-img">
                    <i class="fas fa-check-circle check-icon" id="mobile-check-icon"></i>
                </div>
                <img src="{{ asset('assets/images/MOKUP.png') }}" alt="SagaPass App" class="mobile-mokup-img" id="mobile-mokup">
                <div class="scroll-hint" id="scroll-hint">
                    <span>Scroll</span>
                    <div class="arrow-down"></div>
                </div>
            </div>
        </div>

        <!-- Hero Text (mobile: below scene, desktop: side-by-side) -->
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="hero-text-content">
                    <h1 class="display-4 mb-4">Une seule identité pour un accès universel et sécurisé.</h1>
                    <p class="lead mb-5">SAGAPASS est votre clé d'accès numérique qui simplifie votre vie en ligne. Connectez-vous à tous vos services en un clic, sans compromettre votre sécurité.</p>
                    <div class="d-flex align-items-center gap-4 mt-5">
                        <h5 class="mb-0 fw-bold text-secondary cta-text">Créez votre SAGAPASS</h5>
                        <a href="#" class="btn btn-primary btn-lg">
                            <i class="fas fa-download me-2"></i>Télécharger l'application
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center" data-aos="fade-left">
                <div class="hero-visual d-none d-lg-block">
                    <img src="{{ asset('assets/images/MOKUP.png') }}" alt="Illustration de la sécurité numérique" class="img-fluid floating">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Pourquoi choisir SAGAPASS ?</h2>
            <p class="text-muted">La solution tout-en-un pour gérer votre identité numérique.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-card text-center">
                    <div class="feature-icon mb-3"><i class="fas fa-shield-alt"></i></div>
                    <h5 class="fw-bold">Sécurité Renforcée</h5>
                    <p>Protection de pointe avec authentification multi-facteurs et surveillance continue.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-card text-center">
                    <div class="feature-icon mb-3"><i class="fas fa-rocket"></i></div>
                    <h5 class="fw-bold">Simplicité d'Utilisation</h5>
                    <p>Une seule connexion pour accéder à tous vos services en ligne, sans effort.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-card text-center">
                    <div class="feature-icon mb-3"><i class="fas fa-globe"></i></div>
                    <h5 class="fw-bold">Contrôle Total</h5>
                    <p>Vous décidez quelles informations partager et avec qui. Vos données vous appartiennent.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- For Businesses Section -->
<section class="business-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <img src="{{ asset('assets/images/mukup2.png') }}" alt="Tableau de bord pour entreprises" class="img-fluid">
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <h2 class="fw-bold">Conçu pour les Entreprises</h2>
                <p class="text-muted mb-4">Intégrez une solution d'authentification robuste et facile à utiliser pour vos clients. Réduisez la fraude, simplifiez l'inscription et offrez une expérience utilisateur sans friction.</p>
                <ul class="list-unstyled mb-4">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Intégration facile avec notre API et SDKs.</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Conforme aux régulations sur la protection des données.</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Tableau de bord pour gérer vos utilisateurs et analyser les données.</li>
                </ul>
                <a href="{{ route('contact') }}" class="btn btn-primary">Découvrir nos solutions pro</a>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section text-center">
    <div class="container" data-aos="fade-up">
        <h2 class="fw-bold">Prêt à simplifier votre vie numérique ?</h2>
        <p class="lead mb-4">Rejoignez des milliers d'utilisateurs et d'entreprises qui font confiance à SAGAPASS.</p>
        <a href="#" class="btn btn-light btn-lg">
            <i class="fas fa-download me-2"></i>Télécharger l'application
        </a>
    </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const scene    = document.getElementById('mobile-scroll-scene');
    const logo     = document.getElementById('mobile-logo');
    const logoImg  = document.getElementById('mobile-logo-img');
    const checkIcon= document.getElementById('mobile-check-icon');
    const mokup    = document.getElementById('mobile-mokup');
    const hint     = document.getElementById('scroll-hint');

    if (!scene || window.innerWidth >= 992) return;

    function lerp(a, b, t) { return a + (b - a) * t; }
    function clamp(v, min, max) { return Math.min(Math.max(v, min), max); }
    function easeOut(t) { return 1 - Math.pow(1 - t, 3); }

    function onScroll() {
        const rect      = scene.getBoundingClientRect();
        const sceneH    = scene.offsetHeight;
        const viewH     = window.innerHeight;
        // progress 0 = scene top at viewport top, 1 = scene bottom at viewport bottom
        const scrolled  = -rect.top;
        const totalScroll = sceneH - viewH;
        const progress  = clamp(scrolled / totalScroll, 0, 1);
        const p         = easeOut(progress);

        // --- Phase 1 (0→0.5): logo shrinks + check appears ---
        const p1 = clamp(progress / 0.5, 0, 1);
        const logoSize = lerp(160, 70, p1);
        logo.style.width  = logoSize + 'px';
        logo.style.height = logoSize + 'px';
        logoImg.style.opacity  = lerp(1, 0, p1);
        checkIcon.style.opacity   = lerp(0, 1, p1);
        checkIcon.style.fontSize  = lerp(0, 38, p1) + 'px';

        // Fade out scroll hint as soon as user starts scrolling
        if (hint) {
            hint.style.opacity = lerp(1, 0, clamp(progress / 0.15, 0, 1));
        }

        // Stop pulse animation once check appears
        if (p1 > 0.1) {
            logo.style.animation = 'none';
            logo.style.boxShadow = '0 2px 10px rgba(13,110,253,0.15)';
        } else {
            logo.style.animation = '';
            logo.style.boxShadow = '';
        }

        // --- Phase 2 (0.3→1): mokup rises from bottom ---
        const p2 = clamp((progress - 0.3) / 0.7, 0, 1);
        const mokupTranslate = lerp(60, 0, easeOut(p2));
        mokup.style.transform = 'translateY(' + mokupTranslate + 'vh)';
        mokup.style.opacity   = lerp(0, 1, clamp(p2 * 2, 0, 1));
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // initial state
});
</script>
@endpush
