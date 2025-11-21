@extends('admin.layouts.admin')

@section('title', 'IPs Bloquées')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="bi bi-x-octagon-fill text-danger"></i> IPs Bloquées
        </h1>
        <div>
            @can('manage-security', 'admin')
            <button class="btn btn-sm btn-warning" onclick="cleanExpired()">
                <i class="bi bi-trash"></i> Nettoyer expirés
            </button>
            @endcan
            @can('block-ips', 'admin')
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#blockIpModal">
                <i class="bi bi-plus-circle"></i> Bloquer une IP
            </button>
            @endcan
            <a href="{{ route('admin.security.dashboard') }}" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Total Bloquées</p>
                            <h3 class="mb-0">{{ $blockedIps->total() }}</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded p-3">
                            <i class="bi bi-ban text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Blocages Permanents</p>
                            <h3 class="mb-0">{{ $blockedIps->where('is_permanent', true)->count() }}</h3>
                        </div>
                        <div class="bg-dark bg-opacity-10 rounded p-3">
                            <i class="bi bi-infinity text-dark fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Blocages Temporaires</p>
                            <h3 class="mb-0">{{ $blockedIps->where('is_permanent', false)->count() }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded p-3">
                            <i class="bi bi-clock-history text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Total Tentatives</p>
                            <h3 class="mb-0">{{ $blockedIps->sum('attempts') }}</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded p-3">
                            <i class="bi bi-exclamation-triangle text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>IP Address</th>
                            <th>Raison</th>
                            <th>Tentatives</th>
                            <th>Bloqué le</th>
                            <th>Expire le</th>
                            <th>Type</th>
                            <th>Bloqué par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blockedIps as $blocked)
                        <tr>
                            <td><code class="fs-6">{{ $blocked->ip_address }}</code></td>
                            <td>{{ $blocked->reason }}</td>
                            <td>
                                <span class="badge bg-danger rounded-pill">
                                    {{ $blocked->attempts }}
                                </span>
                            </td>
                            <td class="small">{{ $blocked->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($blocked->is_permanent)
                                    <span class="badge bg-dark">
                                        <i class="bi bi-infinity"></i> Permanent
                                    </span>
                                @else
                                    <span class="small">{{ $blocked->blocked_until->format('d/m/Y H:i') }}</span>
                                    <br>
                                    <small class="text-muted">
                                        ({{ $blocked->blocked_until->diffForHumans() }})
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($blocked->is_permanent)
                                    <span class="badge bg-danger">Permanent</span>
                                @else
                                    <span class="badge bg-warning">Temporaire</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($blocked->blockedBy)
                                    {{ $blocked->blockedBy->name }}
                                @else
                                    <span class="text-muted">Système</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @can('unblock-ips', 'admin')
                                    <button class="btn btn-success" onclick="unblockIp('{{ $blocked->ip_address }}')" title="Débloquer">
                                        <i class="bi bi-unlock"></i>
                                    </button>
                                    @endcan
                                    <button class="btn btn-info" onclick="viewHistory('{{ $blocked->ip_address }}')" title="Historique">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-check-circle fs-1 d-block mb-3 text-success"></i>
                                <h5>Aucune IP bloquée</h5>
                                <p>Le système est propre !</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($blockedIps->hasPages())
            <div class="card-footer bg-white">
                {{ $blockedIps->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal: Bloquer une IP -->
<div class="modal fade" id="blockIpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-ban"></i> Bloquer une IP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="blockIpForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Adresse IP *</label>
                        <input type="text" class="form-control" name="ip_address" placeholder="Ex: 192.168.1.100" required pattern="^(\d{1,3}\.){3}\d{1,3}$">
                        <small class="text-muted">Format IPv4 uniquement</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Raison *</label>
                        <textarea class="form-control" name="reason" rows="2" required placeholder="Ex: Tentatives de connexion suspectes"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Durée (heures)</label>
                        <input type="number" class="form-control" name="duration" value="24" min="1" id="duration">
                        <small class="text-muted">Laissez vide pour permanent</small>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_permanent" id="isPermanent" onchange="toggleDuration()">
                        <label class="form-check-label" for="isPermanent">
                            Blocage permanent
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-ban"></i> Bloquer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('blockIpForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            ip_address: formData.get('ip_address'),
            reason: formData.get('reason'),
            duration: parseInt(formData.get('duration')) || 24,
            is_permanent: formData.get('is_permanent') === 'on'
        };

        fetch('{{ route('admin.security.block-ip') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            }
        })
        .catch(error => {
            alert('Erreur lors du blocage');
            console.error(error);
        });
    });

    function toggleDuration() {
        const durationInput = document.getElementById('duration');
        const isPermanent = document.getElementById('isPermanent').checked;
        durationInput.disabled = isPermanent;
        if (isPermanent) {
            durationInput.value = '';
        } else {
            durationInput.value = '24';
        }
    }

    function unblockIp(ip) {
        if (!confirm(`Débloquer l'IP ${ip} ?`)) return;

        fetch('{{ route('admin.security.unblock-ip') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ ip_address: ip })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            alert('Erreur lors du déblocage');
            console.error(error);
        });
    }

    function viewHistory(ip) {
        window.location.href = `/admin/security/logs?ip=${encodeURIComponent(ip)}`;
    }

    function cleanExpired() {
        if (!confirm('Nettoyer tous les blocages expirés ?')) return;

        fetch('{{ route('admin.security.clean-expired') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            location.reload();
        })
        .catch(error => {
            alert('Erreur lors du nettoyage');
            console.error(error);
        });
    }
</script>
@endpush
@endsection
