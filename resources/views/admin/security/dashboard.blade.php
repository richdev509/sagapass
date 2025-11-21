@extends('admin.layouts.admin')

@section('title', 'Dashboard de Sécurité')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="bi bi-shield-lock-fill text-primary"></i> Dashboard de Sécurité
        </h1>
        <div>
            <button class="btn btn-sm btn-outline-secondary" onclick="refreshStats()">
                <i class="bi bi-arrow-clockwise"></i> Actualiser
            </button>
            <a href="{{ route('admin.security.logs') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-list-ul"></i> Tous les logs
            </a>
        </div>
    </div>

    <!-- Statistiques 24h -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Attaques (24h)</p>
                            <h2 class="mb-0" id="total-attacks">{{ $stats['total_attacks'] }}</h2>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded p-3">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">IPs Bloquées</p>
                            <h2 class="mb-0" id="blocked-count">{{ $stats['blocked_count'] }}</h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded p-3">
                            <i class="bi bi-x-octagon-fill text-warning fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Attaques Critiques</p>
                            <h2 class="mb-0" id="critical-count">{{ $stats['critical_count'] }}</h2>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded p-3">
                            <i class="bi bi-fire text-danger fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">IPs Uniques</p>
                            <h2 class="mb-0" id="unique-ips">{{ $stats['unique_ips'] }}</h2>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded p-3">
                            <i class="bi bi-hdd-network-fill text-info fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Attaques par heure (24h)</h5>
                </div>
                <div class="card-body">
                    <canvas id="hourlyChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Types d'attaques</h5>
                </div>
                <div class="card-body">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top IPs attaquantes -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Top 10 IPs Attaquantes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>IP Address</th>
                                    <th>Tentatives</th>
                                    <th>Dernier Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="top-ips-table">
                                @foreach($topIps as $ip)
                                <tr>
                                    <td><code>{{ $ip->ip_address }}</code></td>
                                    <td><span class="badge bg-danger">{{ $ip->attack_count }}</span></td>
                                    <td><span class="badge bg-secondary">{{ $ip->type }}</span></td>
                                    <td>
                                        @can('block-ips', 'admin')
                                        <button class="btn btn-sm btn-danger" onclick="blockIp('{{ $ip->ip_address }}')">
                                            <i class="bi bi-ban"></i>
                                        </button>
                                        @else
                                        <span class="text-muted small">-</span>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attaques récentes -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Attaques Récentes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Heure</th>
                                    <th>IP</th>
                                    <th>Type</th>
                                    <th>Sévérité</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAttacks as $attack)
                                <tr>
                                    <td class="small">{{ $attack->created_at->format('H:i:s') }}</td>
                                    <td><code class="small">{{ $attack->ip_address }}</code></td>
                                    <td><span class="badge bg-warning small">{{ $attack->type }}</span></td>
                                    <td>
                                        @if($attack->severity === 'critical')
                                            <span class="badge bg-danger small">Critique</span>
                                        @elseif($attack->severity === 'high')
                                            <span class="badge bg-warning small">Élevé</span>
                                        @else
                                            <span class="badge bg-secondary small">{{ $attack->severity }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- IPs bloquées -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">IPs Bloquées Actives</h5>
            <a href="{{ route('admin.security.blocked-ips') }}" class="btn btn-sm btn-outline-primary">
                Voir tout
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>IP Address</th>
                            <th>Raison</th>
                            <th>Tentatives</th>
                            <th>Bloqué jusqu'au</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blockedIps as $blocked)
                        <tr>
                            <td><code>{{ $blocked->ip_address }}</code></td>
                            <td>{{ $blocked->reason }}</td>
                            <td><span class="badge bg-danger">{{ $blocked->attempts }}</span></td>
                            <td>
                                @if($blocked->is_permanent)
                                    <span class="badge bg-dark">Permanent</span>
                                @else
                                    {{ $blocked->blocked_until->format('d/m/Y H:i') }}
                                @endif
                            </td>
                            <td>
                                @if($blocked->is_permanent)
                                    <span class="badge bg-danger">Permanent</span>
                                @else
                                    <span class="badge bg-warning">Temporaire</span>
                                @endif
                            </td>
                            <td>
                                @can('unblock-ips', 'admin')
                                <button class="btn btn-sm btn-success" onclick="unblockIp('{{ $blocked->ip_address }}')">
                                    <i class="bi bi-unlock"></i> Débloquer
                                </button>
                                @else
                                <span class="text-muted small">-</span>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle fs-2 d-block mb-2"></i>
                                Aucune IP bloquée actuellement
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    let hourlyChart, typeChart;
    let refreshInterval;

    // Initialiser les graphiques
    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        startAutoRefresh();
    });

    function initCharts() {
        // Graphique horaire
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        hourlyChart = new Chart(hourlyCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Attaques',
                    data: [],
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Graphique par type
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        'rgb(220, 53, 69)',
                        'rgb(255, 193, 7)',
                        'rgb(13, 110, 253)',
                        'rgb(25, 135, 84)',
                        'rgb(108, 117, 125)',
                        'rgb(214, 51, 132)',
                        'rgb(111, 66, 193)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Charger les données initiales
        refreshStats();
    }

    function refreshStats() {
        fetch('{{ route('admin.security.api.stats') }}')
            .then(response => response.json())
            .then(data => {
                // Mettre à jour les cartes statistiques
                document.getElementById('total-attacks').textContent = data.stats_24h.total_attacks;
                document.getElementById('blocked-count').textContent = data.stats_24h.blocked_count;
                document.getElementById('critical-count').textContent = data.stats_24h.critical_count;
                document.getElementById('unique-ips').textContent = data.stats_24h.unique_ips;

                // Mettre à jour le graphique horaire
                hourlyChart.data.labels = data.hourly_chart.map(item => item.hour);
                hourlyChart.data.datasets[0].data = data.hourly_chart.map(item => item.count);
                hourlyChart.update();

                // Mettre à jour le graphique par type
                typeChart.data.labels = data.stats_by_type.map(item => item.type);
                typeChart.data.datasets[0].data = data.stats_by_type.map(item => item.count);
                typeChart.update();
            })
            .catch(error => console.error('Erreur lors du rafraîchissement:', error));
    }

    function startAutoRefresh() {
        // Rafraîchir toutes les 5 secondes
        refreshInterval = setInterval(refreshStats, 5000);
    }

    function blockIp(ip) {
        if (!confirm(`Bloquer l'IP ${ip} ?`)) return;

        const reason = prompt('Raison du blocage:', 'Activité suspecte détectée');
        if (!reason) return;

        const duration = prompt('Durée du blocage (heures):', '24');
        if (!duration) return;

        fetch('{{ route('admin.security.block-ip') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                ip_address: ip,
                reason: reason,
                duration: parseInt(duration),
                is_permanent: false
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            location.reload();
        })
        .catch(error => {
            alert('Erreur lors du blocage de l\'IP');
            console.error(error);
        });
    }

    function unblockIp(ip) {
        if (!confirm(`Débloquer l'IP ${ip} ?`)) return;

        fetch('{{ route('admin.security.unblock-ip') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                ip_address: ip
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            location.reload();
        })
        .catch(error => {
            alert('Erreur lors du déblocage de l\'IP');
            console.error(error);
        });
    }

    // Nettoyer l'intervalle quand on quitte la page
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
</script>
@endpush
@endsection
