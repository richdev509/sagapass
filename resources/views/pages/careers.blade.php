@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-briefcase text-primary me-3"></i>Carrières chez SAGAPASS
                </h1>
                <p class="lead text-muted">Rejoignez une équipe passionnée par l'innovation</p>
            </div>

            <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-5 text-center">
                    <h2 class="h3 mb-3">Construisons ensemble l'avenir de l'identité numérique</h2>
                    <p class="mb-0">
                        SAGAPASS est une entreprise innovante qui révolutionne la manière dont les gens
                        accèdent aux services numériques. Nous recherchons des talents motivés pour nous aider à construire l'avenir.
                    </p>
                </div>
            </div>

            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Recrutement en cours !</strong> Nous sommes actuellement en phase de croissance.
                Les postes disponibles seront publiés prochainement.
            </div>

            <h2 class="h3 mb-4"><i class="fas fa-star text-primary me-2"></i>Pourquoi nous rejoindre ?</h2>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <i class="fas fa-rocket text-primary fa-3x"></i>
                            </div>
                            <h4>Innovation constante</h4>
                            <p class="text-muted mb-0">
                                Travaillez sur des technologies de pointe et contribuez à façonner
                                l'avenir de l'identité numérique.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <i class="fas fa-users text-primary fa-3x"></i>
                            </div>
                            <h4>Équipe collaborative</h4>
                            <p class="text-muted mb-0">
                                Intégrez une équipe passionnée, bienveillante et axée sur l'entraide
                                et le partage de connaissances.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <i class="fas fa-chart-line text-primary fa-3x"></i>
                            </div>
                            <h4>Évolution professionnelle</h4>
                            <p class="text-muted mb-0">
                                Développez vos compétences avec des formations continues et
                                des opportunités d'avancement.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <i class="fas fa-balance-scale text-primary fa-3x"></i>
                            </div>
                            <h4>Équilibre vie pro/perso</h4>
                            <p class="text-muted mb-0">
                                Bénéficiez d'horaires flexibles et de la possibilité de télétravail
                                pour un meilleur équilibre.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="h3 mb-4"><i class="fas fa-user-tie text-primary me-2"></i>Profils recherchés</h2>

            <div class="list-group mb-5">
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><i class="fas fa-code text-primary me-2"></i>Développeur Full Stack</h5>
                        <span class="badge bg-secondary">Bientôt</span>
                    </div>
                    <p class="mb-1">Laravel, Vue.js, MySQL - Expérience 3+ ans</p>
                </div>

                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><i class="fas fa-shield-alt text-primary me-2"></i>Expert en Sécurité</h5>
                        <span class="badge bg-secondary">Bientôt</span>
                    </div>
                    <p class="mb-1">Cryptographie, Sécurité des applications - Expérience 5+ ans</p>
                </div>

                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><i class="fas fa-mobile-alt text-primary me-2"></i>Développeur Mobile</h5>
                        <span class="badge bg-secondary">Bientôt</span>
                    </div>
                    <p class="mb-1">React Native ou Flutter - Expérience 3+ ans</p>
                </div>

                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><i class="fas fa-paint-brush text-primary me-2"></i>Designer UI/UX</h5>
                        <span class="badge bg-secondary">Bientôt</span>
                    </div>
                    <p class="mb-1">Figma, Adobe XD - Portfolio requis</p>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">
                    <h2 class="h3 mb-3">Candidature spontanée</h2>
                    <p class="mb-4">
                        Vous ne trouvez pas le poste qui vous correspond ? Envoyez-nous votre candidature spontanée !
                    </p>
                    <a href="{{ route('contact') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer ma candidature
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
