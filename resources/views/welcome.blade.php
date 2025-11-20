<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SAGAPASS - Identité Numérique Sécurisée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; overflow-x: hidden; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .navbar { background: white !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #667eea !important; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 0.6rem 1.8rem; border-radius: 50px; color: white; font-weight: 600; transition: all 0.3s; }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); }
        .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; color: white; position: relative; }
        .hero-title { font-size: 3.5rem; font-weight: 800; margin-bottom: 1.5rem; }
        .hero-subtitle { font-size: 1.3rem; margin-bottom: 2rem; opacity: 0.95; }
        .btn-hero { padding: 1rem 2.5rem; border-radius: 50px; font-weight: 600; font-size: 1.1rem; transition: all 0.3s; }
        .btn-hero-white { background: white; color: #667eea; border: none; }
        .btn-hero-white:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .btn-hero-outline { background: transparent; color: white; border: 2px solid white; }
        .btn-hero-outline:hover { background: white; color: #667eea; }
        .feature-card { background: white; border-radius: 15px; padding: 2rem; text-align: center; transition: all 0.3s; border: 1px solid #e0e0e0; height: 100%; }
        .feature-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .feature-icon { width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; color: white; }
        .section-title { font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
        .step-number { width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.8rem; font-weight: 700; color: white; box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3); }
        .footer { background: #1a1a2e; color: #cbd5e0; padding: 3rem 0; }
        .footer-title { color: white; font-weight: 600; margin-bottom: 1rem; }
        .footer-links { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 0.5rem; }
        .footer-links a { color: #cbd5e0; text-decoration: none; transition: all 0.3s; }
        .footer-links a:hover { color: #667eea; padding-left: 5px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-shield-alt me-2"></i>SAGAPASS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Fonctionnalités</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">Comment ça marche</a></li>
                    @if (Route::has('login'))
                        @auth
                            @if(auth()->user()->is_developer)
                                <li class="nav-item"><a href="{{ route('developers.dashboard') }}" class="btn btn-primary-custom ms-3"><i class="fas fa-code me-2"></i>Developer Dashboard</a></li>
                            @else
                                <li class="nav-item"><a href="{{ url('/dashboard') }}" class="btn btn-primary-custom ms-3">Dashboard</a></li>
                                <li class="nav-item"><a href="{{ route('developers.register') }}" class="nav-link"><i class="fas fa-code me-2"></i>Devenir développeur</a></li>
                            @endif
                        @else
                            <li class="nav-item"><a href="{{ route('login') }}" class="nav-link">Connexion</a></li>
                            @if (Route::has('register'))
                                <li class="nav-item"><a href="{{ route('register') }}" class="btn btn-primary-custom ms-3">S'inscrire</a></li>
                            @endif
                        @endauth
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h1 class="hero-title">Votre Identité Numérique Sécurisée</h1>
                    <p class="hero-subtitle">SAGAPASS simplifie et sécurise votre identité en ligne. Une seule authentification pour tous vos services.</p>
                    <div>
                        @if (Route::has('register'))
                            <div class="mb-3">
                                <a href="{{ route('register') }}" class="btn btn-hero btn-hero-white me-2 mb-2"><i class="fas fa-user me-2"></i>Compte Citoyen</a>
                                <a href="{{ route('developers.register') }}" class="btn btn-hero btn-hero-outline mb-2"><i class="fas fa-code me-2"></i>Compte Développeur</a>
                            </div>
                        @endif
                        <a href="#features" class="btn btn-link text-white"><i class="fas fa-arrow-down me-2"></i>Découvrir plus</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-user-shield" style="font-size: 15rem; opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 gradient-bg text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-3"><div class="p-3"><h2 class="display-4 fw-bold"><i class="fas fa-users"></i> 10K+</h2><p>Utilisateurs actifs</p></div></div>
                <div class="col-md-3 mb-3"><div class="p-3"><h2 class="display-4 fw-bold"><i class="fas fa-check-circle"></i> 99.9%</h2><p>Taux de sécurité</p></div></div>
                <div class="col-md-3 mb-3"><div class="p-3"><h2 class="display-4 fw-bold"><i class="fas fa-clock"></i> 24/7</h2><p>Support disponible</p></div></div>
                <div class="col-md-3 mb-3"><div class="p-3"><h2 class="display-4 fw-bold"><i class="fas fa-globe"></i> 50+</h2><p>Services partenaires</p></div></div>
            </div>
        </div>
    </section>

    <section id="features" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="section-title">Fonctionnalités Puissantes</h2>
                <p class="text-muted fs-5">Tout ce dont vous avez besoin pour gérer votre identité numérique</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-lock"></i></div>
                        <h3 class="h5 fw-bold mb-3">Sécurité Maximale</h3>
                        <p class="text-muted">Vos données sont cryptées avec les dernières technologies de sécurité.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-user-check"></i></div>
                        <h3 class="h5 fw-bold mb-3">Vérification Officielle</h3>
                        <p class="text-muted">Chaque document est vérifié manuellement par nos experts.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                        <h3 class="h5 fw-bold mb-3">Connexion Rapide</h3>
                        <p class="text-muted">Connectez-vous instantanément avec OAuth2.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h3 class="h5 fw-bold mb-3">Multi-Plateforme</h3>
                        <p class="text-muted">Accès depuis n'importe quel appareil.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-history"></i></div>
                        <h3 class="h5 fw-bold mb-3">Historique Complet</h3>
                        <p class="text-muted">Traçabilité totale de vos actions.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-headset"></i></div>
                        <h3 class="h5 fw-bold mb-3">Support 24/7</h3>
                        <p class="text-muted">Assistance disponible à tout moment.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="section-title">Comment ça marche ?</h2>
                <p class="text-muted fs-5">4 étapes simples pour commencer</p>
            </div>
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="step-number">1</div>
                    <h3 class="h5 fw-bold mb-2">Créez votre compte</h3>
                    <p class="text-muted">Inscription gratuite en quelques secondes</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="step-number">2</div>
                    <h3 class="h5 fw-bold mb-2">Téléchargez vos documents</h3>
                    <p class="text-muted">Soumettez votre CNI ou passeport</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="step-number">3</div>
                    <h3 class="h5 fw-bold mb-2">Vérification rapide</h3>
                    <p class="text-muted">Validation en moins de 24h</p>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="step-number">4</div>
                    <h3 class="h5 fw-bold mb-2">Profitez !</h3>
                    <p class="text-muted">Utilisez votre ID partout</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 gradient-bg text-white text-center">
        <div class="container py-5">
            <h2 class="display-4 fw-bold mb-4">Prêt à simplifier votre vie numérique ?</h2>
            <p class="fs-5 mb-4">Rejoignez des milliers d'utilisateurs qui font confiance à SAGAPASS</p>
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-hero btn-hero-white"><i class="fas fa-user-plus me-2"></i>Créer mon compte gratuitement</a>
            @endif
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h3 class="footer-title"><i class="fas fa-shield-alt me-2"></i>SAGAPASS</h3>
                    <p>Votre passeport numérique sécurisé</p>
                </div>
                <div class="col-md-2 mb-4">
                    <h4 class="footer-title">Produit</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Fonctionnalités</a></li>
                        <li><a href="#how-it-works">Comment ça marche</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h4 class="footer-title">Entreprise</h4>
                    <ul class="footer-links">
                        <li><a href="#">À propos</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h4 class="footer-title">Légal</h4>
                    <ul class="footer-links">
                        <li><a href="#">Confidentialité</a></li>
                        <li><a href="#">CGU</a></li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: #333;">
            <div class="text-center pt-3"><p>&copy; 2025 SAGAPASS. Tous droits réservés.</p></div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
