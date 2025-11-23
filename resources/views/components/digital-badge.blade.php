<div class="card border-0 shadow-sm mb-4" id="digital-badge-card">
    <div class="card-body text-center">
        <h5 class="fw-bold mb-3">
            <i class="fas fa-qrcode me-2"></i>Mon Badge Numérique
        </h5>

        <div id="qr-code-container" class="mb-3 d-flex justify-content-center">
            {!! $qrCode !!}
        </div>

        <div class="badge-info mb-3">
            <p class="small text-muted mb-2">
                <i class="fas fa-clock me-1"></i>
                Expire dans: <span id="expiry-countdown" class="fw-bold">Calcul...</span>
            </p>

            <p class="small mb-2">
                Niveau:
                @if($user->account_level === 'verified')
                    <span class="badge bg-success"><i class="fas fa-shield-alt"></i> Vérifié</span>
                @elseif($user->account_level === 'basic')
                    <span class="badge bg-info"><i class="fas fa-user"></i> Basic</span>
                @else
                    <span class="badge bg-secondary"><i class="fas fa-hourglass-half"></i> En attente</span>
                @endif
            </p>

            <p class="small text-muted mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Ce badge se renouvelle automatiquement
            </p>
        </div>

        <div class="d-grid gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshBadge()">
                <i class="fas fa-sync-alt me-1"></i> Renouveler maintenant
            </button>
        </div>
    </div>
</div>

<script>
    let badgeExpiresAt = {{ $badge->expires_at->timestamp * 1000 }};
    let refreshInProgress = false;

    function updateCountdown() {
        const now = Date.now();
        const diff = badgeExpiresAt - now;
        const countdownElement = document.getElementById('expiry-countdown');

        if (diff <= 0) {
            countdownElement.textContent = 'Expiré';
            countdownElement.classList.add('text-danger');
            refreshBadge();
        } else {
            const hours = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            countdownElement.textContent = `${hours}h ${minutes}min`;
            countdownElement.classList.remove('text-danger');

            // Auto-refresh 30 minutes avant expiration
            if (diff < 1800000 && !refreshInProgress) {
                refreshBadge();
            }
        }
    }

    function refreshBadge() {
        if (refreshInProgress) return;

        refreshInProgress = true;
        const btn = document.querySelector('#digital-badge-card button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Renouvellement...';
        btn.disabled = true;

        fetch('{{ route("badge.refresh") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('qr-code-container').innerHTML = data.qr_html;
                badgeExpiresAt = data.expires_at * 1000;
                updateCountdown();
            }
        })
        .catch(error => {
            console.error('Erreur lors du renouvellement:', error);
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            refreshInProgress = false;
        });
    }

    // Mise à jour du compte à rebours toutes les minutes
    setInterval(updateCountdown, 60000);
    updateCountdown(); // Appel initial
</script>

<style>
    #qr-code-container svg {
        max-width: 250px;
        height: auto;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px;
        background: white;
    }

    @media (max-width: 768px) {
        #qr-code-container svg {
            max-width: 200px;
        }
    }
</style>
