<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SAGAPASS - Votre Identité Numérique Souveraine')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

    <style>
        :root {
            --primary: #0D6EFD;
            --primary-dark: #0a58ca;
            --primary-light: #e7f1ff;
            --secondary: #1A202C;
            --secondary-light: #2D3748;
            --text-dark: #1A202C;
            --text-body: #4A5568;
            --text-muted: #718096;
            --bg-light: #F7FAFC;
            --border-color: #E2E8F0;
            --font-family-sans-serif: 'Inter', sans-serif;
        }
        body {
            font-family: var(--font-family-sans-serif);
            background-color: #FFFFFF;
            color: var(--text-body);
        }
        .navbar {
            padding: 1rem 0;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .navbar-brand img {
            height: 40px;
        }
        .nav-link {
            color: var(--text-dark);
            font-weight: 600;
            transition: color 0.3s;
        }
        .nav-link:hover {
            color: var(--primary);
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        .btn-outline-primary {
            border-color: var(--border-color);
            color: var(--text-dark);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s, color 0.3s;
        }
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: #fff;
        }
        footer {
            background-color: var(--secondary);
            color: #A0AEC0;
        }
        footer h5 {
            color: #FFFFFF;
            font-weight: 600;
        }
        footer a {
            color: #A0AEC0;
            text-decoration: none;
            transition: color 0.3s;
        }
        footer a:hover {
            color: #FFFFFF;
        }
        .footer-bottom {
            border-top: 1px solid var(--secondary-light);
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased">

    <!-- Header -->
    <header class="sticky-top bg-white shadow-sm">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="{{ asset('assets/images/sagapass-logo.png') }}" alt="SAGAPASS Logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#">Particuliers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Entreprises</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('documentation') }}">Développeurs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Tarifs</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('contact') }}" class="btn btn-primary">Contactez-nous</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="pt-5 pb-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <img src="{{ asset('assets/images/sagapass-logo.png') }}" alt="SAGAPASS Logo" class="mb-3" style="height: 40px;">
                    <p>Votre identité numérique souveraine, simplifiée et sécurisée.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Produit</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Pour les particuliers</a></li>
                        <li><a href="#">Pour les entreprises</a></li>
                        <li><a href="#">Tarifs</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Ressources</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('documentation') }}">Documentation API</a></li>
                        <li><a href="#">Centre d'aide</a></li>
                        <li><a href="#">Statut du service</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Légal</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Conditions d'utilisation</a></li>
                        <li><a href="#">Politique de confidentialité</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-12 mb-4 text-lg-start">
                    <h5>Contact</h5>
                    <a href="mailto:contact@sagapass.com" class="d-block">contact@sagapass.com</a>
                    <div class="social-icons mt-3">
                        <a href="#" class="me-3 fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-3 fs-5"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="fs-5"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom pt-4 mt-4 text-center text-md-start">
                <p>&copy; {{ date('Y') }} SAGAPASS. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
      AOS.init({
        duration: 800,
        once: true,
      });
    </script>
    @stack('scripts')
</body>
</html>

