@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-5">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-cookie-bite text-primary me-3"></i>Politique de Cookies
                </h1>
                <p class="text-muted">Dernière mise à jour : {{ date('d F Y') }}</p>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Qu'est-ce qu'un cookie ?</h2>
                    <p>
                        Un cookie est un petit fichier texte stocké sur votre appareil lorsque vous visitez
                        un site web. Les cookies permettent au site de mémoriser vos actions et préférences
                        (identifiant de session, langue, préférences d'affichage, etc.) pendant une certaine
                        période.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Types de cookies utilisés</h2>

                    <h5 class="mt-4">1. Cookies essentiels (obligatoires)</h5>
                    <p>
                        Ces cookies sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés.
                    </p>
                    <ul>
                        <li><strong>Session :</strong> Maintient votre session de connexion</li>
                        <li><strong>CSRF Token :</strong> Protection contre les attaques de sécurité</li>
                        <li><strong>Langue :</strong> Mémorise votre préférence linguistique</li>
                    </ul>

                    <h5 class="mt-4">2. Cookies de fonctionnalité (optionnels)</h5>
                    <p>
                        Ces cookies améliorent l'expérience utilisateur en mémorisant vos préférences.
                    </p>
                    <ul>
                        <li><strong>Préférences d'affichage :</strong> Thème clair/sombre, taille de police</li>
                        <li><strong>Remember me :</strong> Connexion automatique</li>
                    </ul>

                    <h5 class="mt-4">3. Cookies d'analyse (optionnels)</h5>
                    <p>
                        Ces cookies nous aident à comprendre comment vous utilisez notre site pour l'améliorer.
                    </p>
                    <ul>
                        <li><strong>Statistiques de visite :</strong> Pages consultées, temps passé</li>
                        <li><strong>Comportement utilisateur :</strong> Parcours de navigation</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Durée de conservation</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Type de cookie</th>
                                    <th>Durée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Session (connexion)</td>
                                    <td>Jusqu'à la fermeture du navigateur</td>
                                </tr>
                                <tr>
                                    <td>Remember me</td>
                                    <td>30 jours</td>
                                </tr>
                                <tr>
                                    <td>Préférences</td>
                                    <td>1 an</td>
                                </tr>
                                <tr>
                                    <td>Analyse</td>
                                    <td>2 ans</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Gestion des cookies</h2>
                    <p>
                        Vous pouvez contrôler et/ou supprimer les cookies selon vos préférences.
                    </p>

                    <h5 class="mt-4">Via votre navigateur</h5>
                    <ul>
                        <li><strong>Chrome :</strong> Paramètres → Confidentialité et sécurité → Cookies</li>
                        <li><strong>Firefox :</strong> Options → Vie privée et sécurité → Cookies</li>
                        <li><strong>Safari :</strong> Préférences → Confidentialité → Cookies</li>
                        <li><strong>Edge :</strong> Paramètres → Confidentialité → Cookies</li>
                    </ul>

                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> La désactivation des cookies essentiels empêchera
                        le bon fonctionnement du site (vous ne pourrez pas vous connecter).
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Cookies tiers</h2>
                    <p>
                        Nous n'utilisons actuellement aucun cookie de tiers (publicité, réseaux sociaux, etc.).
                        Si cela devait changer, cette politique sera mise à jour et vous en serez informé.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Vos droits</h2>
                    <p>Conformément au RGPD, vous avez le droit de :</p>
                    <ul>
                        <li>Refuser les cookies non essentiels</li>
                        <li>Supprimer les cookies existants</li>
                        <li>Accéder aux données collectées via cookies</li>
                        <li>Retirer votre consentement à tout moment</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Modifications de la politique</h2>
                    <p>
                        Cette politique de cookies peut être mise à jour. Toute modification significative
                        vous sera notifiée via notre site ou par email.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-4 text-center">
                    <h3 class="h4 mb-3">Questions sur les cookies ?</h3>
                    <p class="mb-3">
                        Contactez-nous pour toute question concernant notre utilisation des cookies.
                    </p>
                    <a href="{{ route('contact') }}" class="btn btn-light">
                        <i class="fas fa-envelope me-2"></i>Nous contacter
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
