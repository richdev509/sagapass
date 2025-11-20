@extends('layouts.app')

@section('title', 'Historique des connexions')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- Header --}}
            <div class="mb-4">
                <a href="{{ route('profile.connected-services') }}" class="text-decoration-none text-muted mb-3 d-inline-block">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux services connectés
                </a>
                <h2 class="fw-bold mb-2">
                    <i class="fas fa-history me-2"></i>
                    Historique des connexions
                </h2>
                <p class="text-muted">
                    Toutes vos autorisations OAuth, actives et révoquées
                </p>
            </div>

            {{-- Tableau --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($connections->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                            <h5 class="text-muted">Aucun historique</h5>
                            <p class="text-muted mb-0">
                                Vous n'avez jamais autorisé d'application
                            </p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Application</th>
                                        <th>Action</th>
                                        <th>Device</th>
                                        <th>Localisation</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($connections as $log)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($log->application->logo_path)
                                                        <img src="{{ asset('storage/' . $log->application->logo_path) }}"
                                                             alt="{{ $log->application->name }}"
                                                             class="rounded me-2"
                                                             style="width: 40px; height: 40px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center"
                                                             style="width: 40px; height: 40px;">
                                                            <i class="fas fa-cube text-muted"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-semibold">{{ $log->application->name }}</div>
                                                        @if($log->application->is_trusted)
                                                            <small class="text-success">
                                                                <i class="fas fa-shield-check me-1"></i>Vérifiée
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($log->action === 'authorized')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Première autorisation
                                                    </span>
                                                @elseif($log->action === 'reconnected')
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-sync me-1"></i>Reconnexion
                                                    </span>
                                                @elseif($log->action === 'token_issued')
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-key me-1"></i>Token émis
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $log->action }}</span>
                                                @endif
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    @if($log->scopes)
                                                        @foreach($log->scopes as $scope)
                                                            <span class="badge bg-light text-dark" style="font-size: 0.7rem;">{{ $scope }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-{{ $log->device_type === 'mobile' ? 'mobile-alt' : ($log->device_type === 'tablet' ? 'tablet-alt' : 'desktop') }} me-1"></i>
                                                    {{ ucfirst($log->device_type) }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ $log->browser }} • {{ $log->platform }}
                                                </small>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $log->ip_address }}
                                                </div>
                                            </td>
                                            <td>
                                                <div>{{ $log->connected_at->format('d/m/Y H:i') }}</div>
                                                <small class="text-muted">{{ $log->connected_at->diffForHumans() }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-4">
                            {{ $connections->links() }}
                        </div>

                        {{-- Statistiques --}}
                        <div class="row g-3 mt-3">
                            <div class="col-md-3">
                                <div class="bg-light rounded p-3 text-center">
                                    <h4 class="fw-bold text-success mb-1">
                                        {{ $connections->where('action', 'authorized')->count() }}
                                    </h4>
                                    <small class="text-muted">Autorisations</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-light rounded p-3 text-center">
                                    <h4 class="fw-bold text-primary mb-1">
                                        {{ $connections->where('action', 'reconnected')->count() }}
                                    </h4>
                                    <small class="text-muted">Reconnexions</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-light rounded p-3 text-center">
                                    <h4 class="fw-bold text-info mb-1">
                                        {{ $connections->unique('application_id')->count() }}
                                    </h4>
                                    <small class="text-muted">Applications</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-light rounded p-3 text-center">
                                    <h4 class="fw-bold text-warning mb-1">
                                        {{ $connections->unique('ip_address')->count() }}
                                    </h4>
                                    <small class="text-muted">IP uniques</small>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Info de sécurité --}}
            <div class="alert alert-info mt-4">
                <h6 class="fw-bold mb-2">
                    <i class="fas fa-shield-alt me-2"></i>
                    Conseils de sécurité
                </h6>
                <ul class="mb-0 small">
                    <li>Révisez régulièrement les applications qui ont accès à votre compte</li>
                    <li>Révoquez l'accès des applications que vous n'utilisez plus</li>
                    <li>Ne partagez jamais vos identifiants SAGAPASS avec des tiers</li>
                    <li>Signalez toute activité suspecte à notre équipe de support</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
