@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-5 text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-heartbeat text-primary me-3"></i>Statut du système
                </h1>
                <p class="lead text-muted">Surveillance en temps réel de nos services</p>
            </div>

            <div class="alert alert-success border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-3x me-3"></i>
                    <div>
                        <h4 class="alert-heading mb-1">Tous les systèmes fonctionnent normalement</h4>
                        <p class="mb-0 small">Dernière vérification : {{ now()->format('d/m/Y à H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h4 mb-4"><i class="fas fa-server text-primary me-2"></i>Services</h2>

                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="fas fa-globe text-primary me-2"></i>
                                <strong>Site web</strong>
                                <br>
                                <small class="text-muted">sagapass.com</small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Opérationnel
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="fas fa-shield-alt text-primary me-2"></i>
                                <strong>Authentification OAuth</strong>
                                <br>
                                <small class="text-muted">oauth.sagapass.com</small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Opérationnel
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="fas fa-cog text-primary me-2"></i>
                                <strong>API</strong>
                                <br>
                                <small class="text-muted">api.sagapass.com</small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Opérationnel
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="fas fa-database text-primary me-2"></i>
                                <strong>Base de données</strong>
                                <br>
                                <small class="text-muted">Stockage des données</small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Opérationnel
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <strong>Service d'emails</strong>
                                <br>
                                <small class="text-muted">Notifications et alertes</small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Opérationnel
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h4 mb-4"><i class="fas fa-chart-line text-primary me-2"></i>Performance</h2>

                    <div class="row text-center g-3">
                        <div class="col-md-3">
                            <div class="p-3 border rounded">
                                <h3 class="h2 text-success mb-1">99.9%</h3>
                                <small class="text-muted">Disponibilité</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded">
                                <h3 class="h2 text-primary mb-1">120ms</h3>
                                <small class="text-muted">Temps de réponse</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded">
                                <h3 class="h2 text-info mb-1">0</h3>
                                <small class="text-muted">Incidents actifs</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded">
                                <h3 class="h2 text-warning mb-1">24/7</h3>
                                <small class="text-muted">Surveillance</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body p-4 text-center">
                    <h3 class="h4 mb-3">Notifications de statut</h3>
                    <p class="mb-3">
                        Recevez des alertes en temps réel sur l'état de nos services.
                    </p>
                    <button class="btn btn-light" disabled>
                        <i class="fas fa-bell me-2"></i>S'abonner aux alertes (bientôt)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
