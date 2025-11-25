<footer class="footer" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 3rem 0; color: #fff;">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h3 class="footer-title">
                    <i class="fas fa-shield-alt me-2"></i>{{ config('app.name', 'SAGAPASS') }}
                </h3>
                <p>Votre passeport numérique sécurisé pour accéder à tous les services en ligne.</p>
                <div class="mt-3">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-instagram fa-lg"></i></a>
                </div>
            </div>

            <div class="col-md-2 mb-4">
                <h4 class="footer-title">Produit</h4>
                <ul class="footer-links">
                    <li><a href="{{ url('/#features') }}">Fonctionnalités</a></li>
                    <li><a href="{{ url('/#how-it-works') }}">Comment ça marche</a></li>
                    <li><a href="#">Tarifs</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>

            <div class="col-md-2 mb-4">
                <h4 class="footer-title">Entreprise</h4>
                <ul class="footer-links">
                    <li><a href="#">À propos</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Carrières</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>

            <div class="col-md-2 mb-4">
                <h4 class="footer-title">Ressources</h4>
                <ul class="footer-links">
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">API</a></li>
                    <li><a href="#">Support</a></li>
                    <li><a href="#">Statut</a></li>
                </ul>
            </div>

            <div class="col-md-2 mb-4">
                <h4 class="footer-title">Légal</h4>
                <ul class="footer-links">
                    <li><a href="#">Confidentialité</a></li>
                    <li><a href="#">CGU</a></li>
                    <li><a href="#">Mentions légales</a></li>
                    <li><a href="#">Cookies</a></li>
                </ul>
            </div>
        </div>

        <hr style="border-color: rgba(255,255,255,0.2); margin-top: 2rem; margin-bottom: 2rem;">

        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <p class="mb-1">
                    <strong><i class="fas fa-building me-2"></i>Sagacetech</strong>
                </p>
                <p class="mb-0 small" style="opacity: 0.9;">
                    &copy; {{ date('Y') }} {{ config('app.name', 'SAGAPASS') }}. Tous droits réservés.
                </p>
            </div>

            <div class="col-md-6 text-center text-md-end">
                <p class="mb-1 small" style="opacity: 0.9;">
                    <i class="fas fa-code me-2"></i>Développé par <strong>Richardson</strong>
                </p>
                <p class="mb-0 small" style="opacity: 0.85;">
                    <i class="fas fa-user-tie me-1"></i>CEO & Ingénieur Informatique
                </p>
                <p class="mb-0 small mt-1" style="opacity: 0.85;">
                    <i class="fas fa-envelope me-1"></i>
                    <a href="mailto:sagapass@sagapass.com" style="color: #fff; text-decoration: none;">
                        sagapass@sagapass.com
                    </a>
                </p>
            </div>
        </div>
    </div>
</footer>
