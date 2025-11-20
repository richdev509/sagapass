@extends('layouts.app')

@section('title', 'Statistiques - ' . $application->name)

@section('content')
<div class="container py-5">
    {{-- Header --}}
    <div class="mb-4">
        <a href="{{ route('developers.applications.show', $application) }}" class="text-decoration-none text-muted mb-3 d-inline-block">
            <i class="fas fa-arrow-left me-2"></i>Retour aux détails
        </a>
        <div class="d-flex align-items-center">
            @if($application->logo_path)
                <img src="{{ asset('storage/' . $application->logo_path) }}"
                     alt="{{ $application->name }}"
                     class="rounded me-3"
                     style="width: 60px; height: 60px; object-fit: cover;">
            @endif
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-chart-line me-2"></i>
                    Statistiques
                </h2>
                <p class="text-muted mb-0">{{ $application->name }} - 30 derniers jours</p>
            </div>
        </div>
    </div>

    {{-- Graphique --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-chart-area me-2"></i>
                Évolution des autorisations
            </h5>
        </div>
        <div class="card-body">
            <canvas id="authorizationsChart" style="height: 300px;"></canvas>
        </div>
    </div>

    {{-- Statistiques détaillées --}}
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-users me-2"></i>
                        Utilisateurs
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="fw-bold text-success mb-1">
                                    {{ $application->userAuthorizations()->whereNull('revoked_at')->count() }}
                                </h3>
                                <small class="text-muted">Actifs</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="fw-bold text-danger mb-1">
                                    {{ $application->userAuthorizations()->whereNotNull('revoked_at')->count() }}
                                </h3>
                                <small class="text-muted">Révoqués</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4 class="fw-bold text-primary mb-1">
                            {{ $application->userAuthorizations()->count() }}
                        </h4>
                        <small class="text-muted">Total d'autorisations</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-key me-2"></i>
                        Tokens & Codes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="fw-bold text-info mb-1">
                                    {{ $application->authorizationCodes()->count() }}
                                </h3>
                                <small class="text-muted">Codes générés</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="fw-bold text-success mb-1">
                                    {{ $application->authorizationCodes()->where('used', true)->count() }}
                                </h3>
                                <small class="text-muted">Codes utilisés</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4 class="fw-bold text-warning mb-1">
                            {{ $application->authorizationCodes()->where('used', false)->where('expires_at', '>', now())->count() }}
                        </h4>
                        <small class="text-muted">Codes en attente</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau des données quotidiennes --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-table me-2"></i>
                Détails par jour
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-center">Autorisations</th>
                            <th class="text-center">Révocations</th>
                            <th class="text-center">Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_reverse($dailyStats) as $stat)
                            <tr>
                                <td>{{ $stat['date'] }}</td>
                                <td class="text-center">
                                    @if($stat['authorizations'] > 0)
                                        <span class="badge bg-success">+{{ $stat['authorizations'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($stat['revocations'] > 0)
                                        <span class="badge bg-danger">-{{ $stat['revocations'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <strong>{{ $stat['authorizations'] - $stat['revocations'] }}</strong>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('authorizationsChart').getContext('2d');
const dailyStats = @json($dailyStats);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: dailyStats.map(stat => stat.date),
        datasets: [
            {
                label: 'Autorisations',
                data: dailyStats.map(stat => stat.authorizations),
                borderColor: 'rgb(40, 167, 69)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            },
            {
                label: 'Révocations',
                data: dailyStats.map(stat => stat.revocations),
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
@endsection
