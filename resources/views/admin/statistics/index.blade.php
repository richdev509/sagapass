@extends('admin.layouts.admin')

@section('title', 'Statistiques Avancées')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-chart-line me-2"></i>
                Statistiques Avancées
            </h1>
            <p class="text-muted mb-0">Tableau de bord analytique - Super Admin</p>
        </div>
        <div>
            <span class="badge bg-danger">
                <i class="fas fa-crown me-1"></i>
                Accès Super Admin
            </span>
        </div>
    </div>

    <!-- Cartes de statistiques globales -->
    <div class="row g-3 mb-4">
        <!-- Total Documents -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card bg-gradient-primary">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ number_format($totalDocuments) }}</h3>
                    <p>Total Documents</p>
                </div>
            </div>
        </div>

        <!-- Total Utilisateurs -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card bg-gradient-success">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ number_format($totalUsers) }}</h3>
                    <p>Citoyens Inscrits</p>
                </div>
            </div>
        </div>

        <!-- Taux d'Approbation -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card bg-gradient-info">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ $approvalRate }}%</h3>
                    <p>Taux d'Approbation</p>
                </div>
            </div>
        </div>

        <!-- Temps Moyen -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card bg-gradient-warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ round($averageProcessingTime, 1) }}h</h3>
                    <p>Temps Moyen de Traitement</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cartes de statut détaillées -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon-sm bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ number_format($pendingDocuments) }}</h4>
                            <span class="text-muted">En Attente</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon-sm bg-success bg-opacity-10 text-success">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ number_format($verifiedDocuments) }}</h4>
                            <span class="text-muted">Vérifiés</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon-sm bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-times"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-0">{{ number_format($rejectedDocuments) }}</h4>
                            <span class="text-muted">Rejetés</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques principaux -->
    <div class="row g-3 mb-4">
        <!-- Documents par Jour (30 derniers jours) -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Évolution des Documents (30 derniers jours)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="documentsPerDayChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribution par Statut -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2 text-info"></i>
                        Distribution par Statut
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statusDistributionChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents par Type & Performance Admin -->
    <div class="row g-3 mb-4">
        <!-- Types de Documents -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2 text-success"></i>
                        Types de Documents
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="documentTypesChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Performance par Admin -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-user-tie me-2 text-warning"></i>
                        Performance des Administrateurs (Top 10)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="adminPerformanceChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents par Mois & Statistiques Horaires -->
    <div class="row g-3 mb-4">
        <!-- Tendance Mensuelle -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2 text-purple"></i>
                        Tendance Mensuelle (12 derniers mois)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Activité par Heure -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2 text-danger"></i>
                        Heures de Pointe
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="hourlyActivityChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Raisons de Rejet -->
    @if($topRejectionReasons->isNotEmpty())
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                        Top 5 Raisons de Rejet
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rang</th>
                                    <th>Raison</th>
                                    <th>Nombre</th>
                                    <th>Pourcentage</th>
                                    <th>Graphique</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topRejectionReasons as $index => $reason)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>{{ $reason->rejection_reason }}</td>
                                    <td>
                                        <strong>{{ number_format($reason->count) }}</strong>
                                    </td>
                                    <td>
                                        {{ round(($reason->count / $rejectedDocuments) * 100, 1) }}%
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 200px;">
                                            <div class="progress-bar bg-danger"
                                                 role="progressbar"
                                                 style="width: {{ ($reason->count / $rejectedDocuments) * 100 }}%"
                                                 aria-valuenow="{{ ($reason->count / $rejectedDocuments) * 100 }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
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
    @endif

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Configuration des couleurs
const colors = {
    primary: '#6f42c1',
    success: '#28a745',
    danger: '#dc3545',
    warning: '#ffc107',
    info: '#17a2b8',
    purple: '#6f42c1',
    orange: '#fd7e14',
    blue: '#007bff'
};

// 1. Documents par Jour (30 derniers jours)
const dailyCtx = document.getElementById('documentsPerDayChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($last30Days, 'date')) !!},
        datasets: [
            {
                label: 'Soumis',
                data: {!! json_encode(array_column($last30Days, 'submitted')) !!},
                borderColor: colors.blue,
                backgroundColor: colors.blue + '20',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Vérifiés',
                data: {!! json_encode(array_column($last30Days, 'verified')) !!},
                borderColor: colors.success,
                backgroundColor: colors.success + '20',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Rejetés',
                data: {!! json_encode(array_column($last30Days, 'rejected')) !!},
                borderColor: colors.danger,
                backgroundColor: colors.danger + '20',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
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

// 2. Distribution par Statut (Donut)
const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Vérifiés', 'Rejetés', 'En Attente'],
        datasets: [{
            data: [
                {{ $statusDistribution['verified'] }},
                {{ $statusDistribution['rejected'] }},
                {{ $statusDistribution['pending'] }}
            ],
            backgroundColor: [
                colors.success,
                colors.danger,
                colors.warning
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

// 3. Types de Documents (Donut)
const typesCtx = document.getElementById('documentTypesChart').getContext('2d');
new Chart(typesCtx, {
    type: 'doughnut',
    data: {
        labels: ['CNI', 'Passeport'],
        datasets: [{
            data: [{{ $cniCount }}, {{ $passportCount }}],
            backgroundColor: [colors.primary, colors.info],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

// 4. Performance des Admins (Bar)
const adminCtx = document.getElementById('adminPerformanceChart').getContext('2d');
new Chart(adminCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($adminPerformance->pluck('admin_name')) !!},
        datasets: [
            {
                label: 'Approuvés',
                data: {!! json_encode($adminPerformance->pluck('approved')) !!},
                backgroundColor: colors.success,
            },
            {
                label: 'Rejetés',
                data: {!! json_encode($adminPerformance->pluck('rejected')) !!},
                backgroundColor: colors.danger,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            x: {
                stacked: true,
            },
            y: {
                stacked: true,
                beginAtZero: true
            }
        }
    }
});

// 5. Tendance Mensuelle (Line)
const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($last12Months, 'month')) !!},
        datasets: [
            {
                label: 'Soumis',
                data: {!! json_encode(array_column($last12Months, 'submitted')) !!},
                borderColor: colors.primary,
                backgroundColor: colors.primary + '20',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Vérifiés',
                data: {!! json_encode(array_column($last12Months, 'verified')) !!},
                borderColor: colors.success,
                backgroundColor: colors.success + '20',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// 6. Activité par Heure (Radar)
const hourlyCtx = document.getElementById('hourlyActivityChart').getContext('2d');
const hourlyData = Array(24).fill(0);
@foreach($hourlyStats as $stat)
    hourlyData[{{ $stat->hour }}] = {{ $stat->count }};
@endforeach

new Chart(hourlyCtx, {
    type: 'radar',
    data: {
        labels: ['0h', '1h', '2h', '3h', '4h', '5h', '6h', '7h', '8h', '9h', '10h', '11h',
                 '12h', '13h', '14h', '15h', '16h', '17h', '18h', '19h', '20h', '21h', '22h', '23h'],
        datasets: [{
            label: 'Vérifications',
            data: hourlyData,
            backgroundColor: colors.purple + '30',
            borderColor: colors.purple,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            r: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<style>
/* Cartes de statistiques avec gradient */
.stat-card {
    padding: 20px;
    border-radius: 10px;
    color: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.9;
}

.stat-details h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
}

.stat-details p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon-sm {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.text-purple {
    color: #6f42c1;
}
</style>
@endsection
