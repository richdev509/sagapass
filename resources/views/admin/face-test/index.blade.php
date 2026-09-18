@extends('admin.layouts.admin')

@section('title', 'Test reconnaissance faciale')
@section('page-title', 'Test de reconnaissance faciale')
@section('page-subtitle', "Comparez une photo de référence à votre caméra — rien n'est enregistré")

@section('styles')
<style>
    .ft-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid rgba(60, 60, 67, 0.12);
        box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.04);
        padding: 20px;
        margin-bottom: 18px;
    }
    .ft-card h6 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #8E8E93; margin-bottom: 14px; }
    .ft-media { width: 100%; max-width: 420px; aspect-ratio: 3 / 4; background: #000; border-radius: 14px; object-fit: cover; display: block; margin: 0 auto 14px; }
    .ft-video { transform: scaleX(-1); } /* effet miroir pour se cadrer ; la capture n'est PAS inversée */
    .ft-btn { display: block; width: 100%; max-width: 420px; margin: 0 auto 10px; padding: 14px; border: none; border-radius: 100px; font-weight: 600; font-size: 15px; background: #007AFF; color: #fff; }
    .ft-btn:disabled { opacity: .45; }
    .ft-btn.secondary { background: #F2F2F7; color: #1c1c1e; }
    .ft-row { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid rgba(60,60,67,.12); font-size: 14px; }
    .ft-row:last-child { border-bottom: none; }
    .ft-row span:first-child { color: #8E8E93; }
    .ft-row span:last-child { font-weight: 600; text-align: right; }
    .ft-verdict { font-size: 20px; font-weight: 700; text-align: center; padding: 14px; border-radius: 14px; margin-bottom: 14px; }
    .ft-ok { background: rgba(52,199,89,.14); color: #1a8f3c; }
    .ft-ko { background: rgba(255,59,48,.12); color: #d70015; }
    .ft-na { background: #F2F2F7; color: #636366; }
    .ft-history { font-size: 13px; width: 100%; }
    .ft-history td, .ft-history th { padding: 6px 4px; border-bottom: 1px solid rgba(60,60,67,.12); }
    .ft-warn { font-size: 12px; color: #b26a00; margin-top: 10px; word-break: break-word; }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">

        <div class="ft-card">
            <h6>1. Photo de référence</h6>
            <img id="refPreview" class="ft-media" alt="" hidden>
            <input type="file" id="refInput" accept="image/*" hidden>
            <button type="button" class="ft-btn secondary" id="refBtn">Choisir la photo de référence</button>
        </div>

        <div class="ft-card">
            <h6>2. Caméra</h6>
            <video id="video" class="ft-media ft-video" autoplay playsinline muted></video>
            <button type="button" class="ft-btn secondary" id="camBtn">Démarrer la caméra</button>
            <button type="button" class="ft-btn" id="compareBtn" disabled>Comparer</button>
            <p style="font-size:12px;color:#8E8E93;text-align:center;margin:0;">
                Le calcul prend quelques secondes. Cadrez votre visage de face, bien éclairé.
            </p>
        </div>

        <div class="ft-card" id="resultCard" hidden>
            <h6>Résultat</h6>
            <div id="verdict" class="ft-verdict"></div>
            <div id="details"></div>
            <div id="warnings" class="ft-warn"></div>
        </div>

        <div class="ft-card" id="historyCard" hidden>
            <h6>Historique de cette page</h6>
            <table class="ft-history">
                <thead><tr><th>#</th><th>Similarité</th><th>Verdict</th><th>Vivacité</th></tr></thead>
                <tbody id="historyBody"></tbody>
            </table>
        </div>

        <p style="font-size:12px;color:#8E8E93;text-align:center;">
            Seuil actuel de détection de doublons : <strong>{{ number_format($threshold, 3) }}</strong> (similarité cosinus).
            Ni photo ni empreinte n'est conservée par cette page.
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const refInput = document.getElementById('refInput');
    const refBtn = document.getElementById('refBtn');
    const refPreview = document.getElementById('refPreview');
    const video = document.getElementById('video');
    const camBtn = document.getElementById('camBtn');
    const compareBtn = document.getElementById('compareBtn');
    const resultCard = document.getElementById('resultCard');
    const verdictEl = document.getElementById('verdict');
    const detailsEl = document.getElementById('details');
    const warningsEl = document.getElementById('warnings');
    const historyCard = document.getElementById('historyCard');
    const historyBody = document.getElementById('historyBody');

    let referenceFile = null;
    let stream = null;
    let attempts = 0;

    function refresh() {
        compareBtn.disabled = !(referenceFile && stream);
    }

    refBtn.addEventListener('click', () => refInput.click());
    refInput.addEventListener('change', () => {
        referenceFile = refInput.files[0] || null;
        if (referenceFile) {
            refPreview.src = URL.createObjectURL(referenceFile);
            refPreview.hidden = false;
            refBtn.textContent = 'Changer la photo de référence';
        }
        refresh();
    });

    camBtn.addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
                audio: false,
            });
            video.srcObject = stream;
            await video.play();
            camBtn.textContent = 'Caméra active';
            camBtn.disabled = true;
        } catch (e) {
            alert("Impossible d'accéder à la caméra. Autorisez-la dans le navigateur (la page doit être en HTTPS).");
        }
        refresh();
    });

    function row(label, value) {
        return '<div class="ft-row"><span>' + label + '</span><span>' + value + '</span></div>';
    }

    function show(res) {
        resultCard.hidden = false;
        warningsEl.textContent = '';

        if (res.error) {
            verdictEl.className = 'ft-verdict ft-na';
            verdictEl.textContent = res.error;
            detailsEl.innerHTML = '';
            return;
        }

        if (res.cosine_similarity === null || res.cosine_similarity === undefined) {
            verdictEl.className = 'ft-verdict ft-na';
            verdictEl.textContent = !res.probe_face_found ? 'Aucun visage détecté sur la caméra'
                : (!res.reference_face_found ? 'Aucun visage détecté sur la référence' : 'Comparaison impossible');
        } else if (res.same_face) {
            verdictEl.className = 'ft-verdict ft-ok';
            verdictEl.textContent = 'Même visage — ' + res.cosine_similarity.toFixed(3);
        } else {
            verdictEl.className = 'ft-verdict ft-ko';
            verdictEl.textContent = 'Visage différent — ' + res.cosine_similarity.toFixed(3);
        }

        const liveness = res.liveness_passed === null || res.liveness_passed === undefined ? '—'
            : (res.liveness_passed ? 'Vrai visage' : 'Suspect (photo/écran)');
        detailsEl.innerHTML =
            row('Similarité cosinus', res.cosine_similarity ?? '—') +
            row('Seuil doublons', res.threshold) +
            row('DeepFace : distance / seuil', (res.deepface_distance != null ? res.deepface_distance.toFixed(3) : '—') + ' / ' + (res.deepface_threshold ?? '—')) +
            row('DeepFace : verdict', res.deepface_verified === null || res.deepface_verified === undefined ? '—' : (res.deepface_verified ? 'même personne' : 'différent')) +
            row('Vivacité passive', liveness) +
            row('Score anti-usurpation', res.antispoof_score ?? '—');
        if (res.warnings && res.warnings.length) warningsEl.textContent = res.warnings.join(' | ');

        attempts++;
        historyCard.hidden = false;
        historyBody.insertAdjacentHTML('afterbegin',
            '<tr><td>' + attempts + '</td><td>' + (res.cosine_similarity ?? '—') + '</td><td>' +
            (res.same_face === null || res.same_face === undefined ? '—' : (res.same_face ? 'même' : 'différent')) +
            '</td><td>' + liveness + '</td></tr>');
    }

    compareBtn.addEventListener('click', async () => {
        if (!referenceFile || !stream) return;
        compareBtn.disabled = true;
        compareBtn.textContent = 'Analyse en cours…';

        try {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.92));

            const form = new FormData();
            form.append('reference', referenceFile, referenceFile.name || 'reference.jpg');
            form.append('probe', blob, 'probe.jpg');

            const response = await fetch(@json(route('admin.face-test.compare')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: form,
            });
            const data = await response.json().catch(() => ({ error: 'Réponse invalide du serveur.' }));
            show(response.ok ? data : { error: data.message || data.error || 'Erreur ' + response.status });
        } catch (e) {
            show({ error: 'Erreur réseau : ' + e.message });
        } finally {
            compareBtn.textContent = 'Comparer';
            refresh();
        }
    });
})();
</script>
@endsection
