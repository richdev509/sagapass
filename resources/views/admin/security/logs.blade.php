@extends('admin.layouts.admin')

@section('title', 'Logs de Sécurité')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="bi bi-list-ul"></i> Logs de Sécurité
        </h1>
        <div>
            @can('delete-security-logs', 'admin')
            <button class="btn btn-sm btn-danger" onclick="deleteLogs()">
                <i class="bi bi-trash"></i> Nettoyer anciens logs
            </button>
            @endcan
            <a href="{{ route('admin.security.dashboard') }}" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Type d'attaque</label>
                    <select name="type" class="form-select">
                        <option value="">Tous les types</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Sévérité</label>
                    <select name="severity" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($severities as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Adresse IP</label>
                    <input type="text" name="ip" class="form-control" placeholder="Ex: 192.168.1.1">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_from" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_to" class="form-control">
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des logs -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div id="loading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0" id="logsTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date/Heure</th>
                            <th>IP Address</th>
                            <th>Type</th>
                            <th>Sévérité</th>
                            <th>URL</th>
                            <th>Description</th>
                            <th>Bloqué</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <!-- Rempli via JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top" id="paginationContainer">
                <div id="paginationInfo"></div>
                <nav>
                    <ul class="pagination mb-0" id="paginationLinks"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentPage = 1;
    let perPage = 50;

    document.addEventListener('DOMContentLoaded', function() {
        loadLogs();

        // Soumettre le formulaire de filtres
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            loadLogs();
        });
    });

    function loadLogs(page = 1) {
        currentPage = page;

        // Afficher le loading
        document.getElementById('loading').classList.remove('d-none');
        document.getElementById('logsTableBody').innerHTML = '';

        // Récupérer les filtres
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData);
        params.append('page', currentPage);
        params.append('per_page', perPage);

        fetch('{{ route('admin.security.api.logs') }}?' + params.toString(), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            renderLogs(data.data);
            renderPagination(data);
            document.getElementById('loading').classList.add('d-none');
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('loading').classList.add('d-none');
            alert('Erreur lors du chargement des logs');
        });
    }

    function renderLogs(logs) {
        const tbody = document.getElementById('logsTableBody');

        if (logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Aucun log trouvé avec ces filtres
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = logs.map(log => `
            <tr>
                <td>${log.id}</td>
                <td class="small">${formatDate(log.created_at)}</td>
                <td><code class="small">${log.ip_address}</code></td>
                <td><span class="badge bg-warning">${log.type}</span></td>
                <td>${getSeverityBadge(log.severity)}</td>
                <td class="small text-truncate" style="max-width: 200px;" title="${escapeHtml(log.url)}">
                    ${escapeHtml(log.url)}
                </td>
                <td class="small text-truncate" style="max-width: 250px;" title="${escapeHtml(log.description)}">
                    ${escapeHtml(log.description)}
                </td>
                <td>
                    ${log.is_blocked
                        ? '<span class="badge bg-danger">Oui</span>'
                        : '<span class="badge bg-success">Non</span>'}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewLog(${log.id})" title="Détails">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="blockIp('${log.ip_address}')" title="Bloquer IP">
                            <i class="bi bi-ban"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        const info = document.getElementById('paginationInfo');
        info.textContent = `Affichage ${data.from || 0} à ${data.to || 0} sur ${data.total} logs`;

        const links = document.getElementById('paginationLinks');
        links.innerHTML = '';

        if (data.last_page <= 1) return;

        // Bouton précédent
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${data.current_page === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" onclick="loadLogs(${data.current_page - 1}); return false;">Précédent</a>`;
        links.appendChild(prevLi);

        // Numéros de pages
        for (let i = 1; i <= data.last_page; i++) {
            if (i === 1 || i === data.last_page || (i >= data.current_page - 2 && i <= data.current_page + 2)) {
                const li = document.createElement('li');
                li.className = `page-item ${i === data.current_page ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" onclick="loadLogs(${i}); return false;">${i}</a>`;
                links.appendChild(li);
            } else if (i === data.current_page - 3 || i === data.current_page + 3) {
                const li = document.createElement('li');
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link">...</span>';
                links.appendChild(li);
            }
        }

        // Bouton suivant
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${data.current_page === data.last_page ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" onclick="loadLogs(${data.current_page + 1}); return false;">Suivant</a>`;
        links.appendChild(nextLi);
    }

    function getSeverityBadge(severity) {
        const badges = {
            'critical': '<span class="badge bg-danger">Critique</span>',
            'high': '<span class="badge bg-warning">Élevé</span>',
            'medium': '<span class="badge bg-info">Moyen</span>',
            'low': '<span class="badge bg-secondary">Faible</span>'
        };
        return badges[severity] || `<span class="badge bg-secondary">${severity}</span>`;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function viewLog(id) {
        window.location.href = `/admin/security/logs/${id}`;
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
            loadLogs(currentPage);
        })
        .catch(error => {
            alert('Erreur lors du blocage de l\'IP');
            console.error(error);
        });
    }

    function deleteLogs() {
        const days = prompt('Supprimer les logs plus anciens que (jours):', '30');
        if (!days) return;

        if (!confirm(`Confirmer la suppression des logs de plus de ${days} jours ?`)) return;

        fetch('{{ route('admin.security.logs.delete') }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                days: parseInt(days)
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            loadLogs(1);
        })
        .catch(error => {
            alert('Erreur lors de la suppression');
            console.error(error);
        });
    }
</script>
@endpush
@endsection
