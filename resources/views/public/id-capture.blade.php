<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vérification d'identité - SAGAPASS</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --primary: #0D6EFD;
            --primary-dark: #0a58ca;
            --secondary: #1A202C;
            --text-body: #4A5568;
            --text-muted: #718096;
            --border-color: #E2E8F0;
            --success: #16A34A;
            --danger: #DC2626;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: var(--secondary);
            font-family: 'Inter', sans-serif;
            color: #fff;
            overscroll-behavior: none;
        }

        .screen {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.25rem calc(1.25rem + env(safe-area-inset-bottom));
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 800;
            font-size: 1.05rem;
        }
        .brand i { color: var(--primary); }

        .steps {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0 0.25rem;
        }
        .step-dot {
            width: 2.25rem;
            height: 0.35rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.18);
            transition: background 0.25s ease;
        }
        .step-dot.is-done { background: var(--success); }
        .step-dot.is-active { background: var(--primary); }

        .instruction { text-align: center; margin-top: 0.75rem; }
        .instruction h1 { font-size: 1.15rem; font-weight: 700; margin: 0 0 0.25rem; }
        .instruction p { font-size: 0.875rem; color: rgba(255,255,255,0.65); margin: 0; }

        .camera-wrap {
            position: relative;
            width: min(88vw, 380px);
            aspect-ratio: 1.586 / 1; /* format carte ID-1 (ISO/IEC 7810) */
            margin: 1.25rem 0;
        }
        .camera-video, .overlay-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0.9rem;
        }
        .camera-video { background: #000; }
        .overlay-canvas { pointer-events: none; }

        .card-guide {
            position: absolute;
            inset: 0;
            border-radius: 0.9rem;
            border: 3px dashed rgba(255,255,255,0.35);
            pointer-events: none;
        }
        .card-guide.is-detected { border-color: var(--success); border-style: solid; }

        .review-photo {
            width: min(88vw, 380px);
            aspect-ratio: 1.586 / 1;
            border-radius: 0.9rem;
            overflow: hidden;
            margin: 1.25rem 0;
            background: #000;
        }
        .review-photo img { width: 100%; height: 100%; object-fit: cover; }

        .thumbs { display: flex; gap: 0.6rem; margin-bottom: 0.5rem; }
        .thumb {
            width: 2.75rem;
            height: 1.75rem;
            border-radius: 0.35rem;
            border: 2px solid rgba(255,255,255,0.15);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.06);
        }
        .thumb img { width: 100%; height: 100%; object-fit: cover; }
        .thumb.is-done { border-color: var(--success); }
        .thumb i { color: rgba(255,255,255,0.3); font-size: 0.75rem; }

        .action-row { display: flex; gap: 0.75rem; width: min(88vw, 380px); }
        .btn-secondary, .btn-primary-soft {
            flex: 1;
            border: none;
            padding: 0.8rem 1rem;
            border-radius: 0.65rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-secondary:hover { background: rgba(255,255,255,0.16); }
        .btn-primary-soft { background: var(--primary); color: #fff; }
        .btn-primary-soft:hover { background: var(--primary-dark); }

        .btn-capture {
            flex: none;
            width: min(88vw, 380px);
            border: none;
            padding: 1rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 1.05rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }
        .btn-capture:disabled { opacity: 0.45; cursor: not-allowed; }

        .consent-box {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            text-align: left;
            max-width: 24rem;
            font-size: 0.82rem;
            line-height: 1.45;
            color: rgba(255,255,255,0.8);
            cursor: pointer;
        }
        .consent-box input { width: 1.25rem; height: 1.25rem; margin-top: 0.15rem; flex: none; accent-color: var(--primary); }
        .consent-box a { color: #fff; text-decoration: underline; }

        .footer-note { font-size: 0.75rem; color: rgba(255,255,255,0.45); text-align: center; max-width: 22rem; }

        .state-panel {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            text-align: center;
            flex: 1;
        }
        .state-panel.is-visible { display: flex; }
        .state-panel i { font-size: 2.5rem; }
        .state-panel h2 { margin: 0; font-size: 1.15rem; }
        .state-panel p { margin: 0; color: rgba(255,255,255,0.65); font-size: 0.9rem; max-width: 20rem; }

        .spinner {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: var(--primary);
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        [hidden] { display: none !important; }
    </style>
</head>
<body>

    <div class="screen" id="captureScreen">
        <div class="brand"><i class="fa-solid fa-shield-halved"></i> SAGAPASS</div>

        <div style="width:100%; max-width: 26rem; display:flex; flex-direction:column; align-items:center;">
            <div class="steps" id="steps">
                <div class="step-dot is-active" data-step="0"></div>
                @if ($documentType === 'national_id')
                    <div class="step-dot" data-step="1"></div>
                @endif
            </div>

            <div class="instruction">
                <h1 id="instructionTitle">Photographiez le recto de votre pièce</h1>
                <p id="instructionSubtitle">Placez-la bien dans le cadre, à plat</p>
            </div>

            <div class="camera-wrap" id="cameraWrap">
                <video id="video" class="camera-video" autoplay playsinline muted></video>
                <canvas id="overlayCanvas" class="overlay-canvas"></canvas>
                <div class="card-guide" id="cardGuide"></div>
            </div>

            <div class="review-photo" id="reviewPhoto" hidden>
                <img id="reviewImg" src="" alt="Photo capturée">
            </div>

            <div class="thumbs">
                <div class="thumb" id="thumb0"><i class="fa-solid fa-id-card"></i></div>
                @if ($documentType === 'national_id')
                    <div class="thumb" id="thumb1"><i class="fa-solid fa-id-card"></i></div>
                @endif
            </div>
        </div>

        <div style="display:flex; flex-direction:column; align-items:center; gap:0.6rem; width:100%; max-width:26rem;">
            <div class="action-row" id="reviewActions" hidden>
                <button type="button" class="btn-secondary" id="retakeBtn">Reprendre</button>
                <button type="button" class="btn-primary-soft" id="nextBtn">Suivant</button>
            </div>
            <button type="button" class="btn-primary-soft btn-capture" id="captureBtn" disabled>
                <i class="fa-solid fa-camera"></i> Capturer
            </button>
            <p class="footer-note">Vos photos servent uniquement à vérifier votre identité pour {{ config('app.name', 'notre partenaire') }}.</p>
        </div>
    </div>

    <div class="state-panel" id="stateConsent">
        <i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i>
        <h2>Vérification d'identité</h2>
        <p>Nous allons photographier votre pièce d'identité puis votre visage pour confirmer votre identité et vous protéger contre l'usurpation.</p>
        <label class="consent-box">
            <input type="checkbox" id="consentCheckbox">
            <span>
                J'ai lu et j'accepte les
                <a href="{{ route('terms') }}" target="_blank" rel="noopener">conditions d'utilisation</a>
                et la
                <a href="{{ route('privacy') }}" target="_blank" rel="noopener">politique de confidentialité</a>.
                Je consens à la capture de ma pièce d'identité et de mon visage, à leur conservation par SagaPass
                et à leur comparaison avec d'autres vérifications pour prévenir la fraude.
            </span>
        </label>
        <button type="button" class="btn-primary-soft btn-capture" id="consentStartBtn" disabled>Commencer</button>
    </div>

    <div class="state-panel" id="statePermission">
        <i class="fa-solid fa-camera" style="color: rgba(255,255,255,0.5);"></i>
        <h2>Autorisez l'accès à la caméra</h2>
        <p>Votre navigateur va vous demander la permission d'utiliser la caméra pour photographier votre pièce d'identité.</p>
    </div>

    <div class="state-panel" id="stateModelLoading">
        <div class="spinner"></div>
        <h2>Préparation de la caméra…</h2>
        <p>Quelques secondes, le temps de charger la détection de document.</p>
    </div>

    <div class="state-panel" id="stateDenied">
        <i class="fa-solid fa-camera-slash" style="color: var(--danger);"></i>
        <h2>Caméra indisponible</h2>
        <p>Vérifiez que vous avez autorisé l'accès à la caméra dans les réglages de votre navigateur, puis rechargez cette page.</p>
        <button type="button" class="btn-primary-soft" onclick="window.location.reload()">Réessayer</button>
    </div>

    <div class="state-panel" id="stateUploading">
        <div class="spinner"></div>
        <h2>Envoi en cours…</h2>
        <p>Merci de patienter quelques secondes, n'actualisez pas cette page.</p>
    </div>

    @if (isset($errors) && $errors->any())
        <div class="state-panel is-visible" id="stateError">
            <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i>
            <h2>Une erreur est survenue</h2>
            <p>{{ $errors->first() }}</p>
            <button type="button" class="btn-primary-soft" onclick="window.location.reload()">Réessayer</button>
        </div>
    @endif

    <form id="idCaptureForm" method="POST" action="{{ route('capture.submit-id', $token) }}" enctype="multipart/form-data" hidden>
        @csrf
        <input type="hidden" name="consent" id="consentField" value="">
        <input type="file" name="id_front" id="fileFront">
        @if ($documentType === 'national_id')
            <input type="file" name="id_back" id="fileBack">
        @endif
    </form>

    <script src="{{ asset('js/opencv.js') }}"></script>
    <script>
        const DOCUMENT_TYPE = @json($documentType);
        const STEPS = DOCUMENT_TYPE === 'national_id'
            ? [
                { key: 'front', title: "Photographiez le recto de votre pièce", subtitle: 'Placez-la bien dans le cadre, à plat' },
                { key: 'back', title: "Photographiez le verso de votre pièce", subtitle: 'Retournez la pièce, même cadrage' },
            ]
            : [
                { key: 'front', title: "Photographiez votre passeport", subtitle: 'Page principale, bien à plat dans le cadre' },
            ];

        // Aire minimale du quadrilatère détecté (proportion de la frame) pour
        // le considérer comme "la pièce bien cadrée" — évite de déclencher
        // sur un petit rectangle parasite en arrière-plan.
        const MIN_QUAD_AREA_RATIO = 0.30;
        const STABLE_TICKS_REQUIRED = 4;
        const DETECTION_INTERVAL_MS = 250;

        let currentStep = 0;
        const capturedBlobs = {};
        let stream = null;
        let detectionTimer = null;
        let stableTicks = 0;
        let capturing = false;
        let cvReady = false;
        let manualMode = false;

        const video = document.getElementById('video');
        const overlayCanvas = document.getElementById('overlayCanvas');
        const cardGuide = document.getElementById('cardGuide');
        const cameraWrap = document.getElementById('cameraWrap');
        const reviewPhoto = document.getElementById('reviewPhoto');
        const reviewImg = document.getElementById('reviewImg');
        const reviewActions = document.getElementById('reviewActions');
        const captureBtn = document.getElementById('captureBtn');
        const instructionTitle = document.getElementById('instructionTitle');
        const instructionSubtitle = document.getElementById('instructionSubtitle');

        function showState(id) {
            document.querySelectorAll('.state-panel').forEach(el => el.classList.remove('is-visible'));
            document.getElementById('captureScreen').style.display = id ? 'none' : 'flex';
            if (id) document.getElementById(id).classList.add('is-visible');
        }

        function updateStepUi() {
            const step = STEPS[currentStep];
            instructionTitle.textContent = step.title;
            instructionSubtitle.textContent = step.subtitle;
            document.querySelectorAll('.step-dot').forEach((dot, i) => {
                dot.classList.toggle('is-active', i === currentStep);
                dot.classList.toggle('is-done', i < currentStep);
            });
            cameraWrap.hidden = false;
            reviewPhoto.hidden = true;
            reviewActions.hidden = true;
            captureBtn.hidden = false;
            cardGuide.classList.remove('is-detected');
            stableTicks = 0;
        }

        async function startCamera() {
            showState('statePermission');
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 960 } },
                    audio: false,
                });
                video.srcObject = stream;
                await video.play();
            } catch (err) {
                showState('stateDenied');
                return;
            }

            // L'écran de capture s'affiche dès que la caméra est prête : le
            // bouton "Capturer" est utilisable sans attendre le chargement
            // d'OpenCV (la détection auto démarre ensuite, si elle charge).
            showState(null);
            updateStepUi();
            captureBtn.disabled = false;
            waitForOpenCv(() => {
                cvReady = true;
                startDetectionLoop();
            });
        }

        function waitForOpenCv(callback) {
            // opencv.js expose cv comme une factory tant que le runtime WASM
            // n'est pas prêt — cv['onRuntimeInitialized'] est l'accroche
            // standard une fois l'initialisation terminée.
            if (typeof cv !== 'undefined' && cv.Mat) {
                callback();
                return;
            }
            let attempts = 0;
            const check = () => {
                attempts++;
                if (typeof cv !== 'undefined') {
                    cv['onRuntimeInitialized'] = callback;
                } else if (attempts < 100) {
                    setTimeout(check, 100);
                } else {
                    // OpenCV n'a pas pu charger — le bouton "Capturer" reste
                    // disponible, seule la détection auto est désactivée.
                    cvReady = false;
                    enableManualMode();
                }
            };
            check();
        }

        function enableManualMode() {
            manualMode = true;
            if (detectionTimer) clearInterval(detectionTimer);
            instructionSubtitle.textContent = 'Touchez le bouton pour capturer';
        }

        function startDetectionLoop() {
            if (detectionTimer) clearInterval(detectionTimer);
            detectionTimer = setInterval(runDetectionTick, DETECTION_INTERVAL_MS);
        }

        function runDetectionTick() {
            if (capturing || !cvReady || video.readyState < 2) return;

            const canvas = overlayCanvas;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const captureCanvas = document.createElement('canvas');
            captureCanvas.width = video.videoWidth;
            captureCanvas.height = video.videoHeight;
            captureCanvas.getContext('2d').drawImage(video, 0, 0);

            const detection = detectDocumentQuad(captureCanvas);
            const quad = detection.quad;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (!quad) {
                stableTicks = 0;
                cardGuide.classList.remove('is-detected');
                return;
            }

            ctx.strokeStyle = '#16A34A';
            ctx.lineWidth = 4;
            ctx.beginPath();
            quad.forEach((p, i) => (i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y)));
            ctx.closePath();
            ctx.stroke();

            cardGuide.classList.add('is-detected');
            stableTicks++;

            if (stableTicks >= STABLE_TICKS_REQUIRED) {
                triggerCapture(captureCanvas);
            }
        }

        /**
         * Détecte le plus grand quadrilatère convexe plausible dans l'image
         * (niveaux de gris -> flou -> Canny -> contours -> approxPolyDP) —
         * retourne ses 4 coins ([{x,y}, ...]) ou null. Toutes les Mat
         * OpenCV sont explicitement libérées (pas de ramasse-miettes côté
         * WASM) avant de retourner.
         */
        function detectDocumentQuad(canvas) {
            const src = cv.imread(canvas);
            const gray = new cv.Mat();
            const blurred = new cv.Mat();
            const edges = new cv.Mat();
            const contours = new cv.MatVector();
            const hierarchy = new cv.Mat();

            let bestPoints = null;
            let bestArea = 0;
            const frameArea = canvas.width * canvas.height;

            try {
                cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
                cv.GaussianBlur(gray, blurred, new cv.Size(5, 5), 0);
                cv.Canny(blurred, edges, 50, 150);
                cv.findContours(edges, contours, hierarchy, cv.RETR_LIST, cv.CHAIN_APPROX_SIMPLE);

                for (let i = 0; i < contours.size(); i++) {
                    const cnt = contours.get(i);
                    const peri = cv.arcLength(cnt, true);
                    const approx = new cv.Mat();
                    cv.approxPolyDP(cnt, approx, 0.02 * peri, true);

                    if (approx.rows === 4 && cv.isContourConvex(approx)) {
                        const area = cv.contourArea(approx);
                        if (area > frameArea * MIN_QUAD_AREA_RATIO && area > bestArea) {
                            bestArea = area;
                            bestPoints = [];
                            for (let j = 0; j < 4; j++) {
                                bestPoints.push({ x: approx.data32S[j * 2], y: approx.data32S[j * 2 + 1] });
                            }
                        }
                    }
                    approx.delete();
                    cnt.delete();
                }
            } finally {
                src.delete();
                gray.delete();
                blurred.delete();
                edges.delete();
                contours.delete();
                hierarchy.delete();
            }

            return { quad: bestPoints };
        }

        function triggerCapture(captureCanvas) {
            if (capturing) return;
            capturing = true;
            if (detectionTimer) clearInterval(detectionTimer);

            captureCanvas.toBlob((blob) => {
                const step = STEPS[currentStep];
                capturedBlobs[step.key] = blob;

                const thumb = document.getElementById('thumb' + currentStep);
                thumb.classList.add('is-done');
                thumb.innerHTML = '<img src="' + URL.createObjectURL(blob) + '">';

                reviewImg.src = URL.createObjectURL(blob);
                cameraWrap.hidden = true;
                reviewPhoto.hidden = false;
                reviewActions.hidden = false;
                captureBtn.hidden = true;

                if (stream) stream.getTracks().forEach(t => t.stop());
            }, 'image/jpeg', 0.92);
        }

        captureBtn.addEventListener('click', () => {
            if (capturing || !video.videoWidth) return;
            const captureCanvas = document.createElement('canvas');
            captureCanvas.width = video.videoWidth;
            captureCanvas.height = video.videoHeight;
            captureCanvas.getContext('2d').drawImage(video, 0, 0);
            triggerCapture(captureCanvas);
        });

        document.getElementById('retakeBtn').addEventListener('click', async () => {
            capturing = false;
            manualMode = false;
            captureBtn.hidden = false;
            cameraWrap.hidden = false;
            reviewPhoto.hidden = true;
            reviewActions.hidden = true;

            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 960 } },
                    audio: false,
                });
                video.srcObject = stream;
                await video.play();
            } catch (err) {
                showState('stateDenied');
                return;
            }

            stableTicks = 0;
            if (cvReady) startDetectionLoop();
        });

        document.getElementById('nextBtn').addEventListener('click', () => {
            currentStep++;
            capturing = false;

            if (currentStep >= STEPS.length) {
                submitIdCapture();
                return;
            }

            document.getElementById('retakeBtn').click();
            updateStepUi();
        });

        function submitIdCapture() {
            showState('stateUploading');

            const dtFront = new DataTransfer();
            dtFront.items.add(new File([capturedBlobs.front], 'front.jpg', { type: 'image/jpeg' }));
            document.getElementById('fileFront').files = dtFront.files;

            @if ($documentType === 'national_id')
                const dtBack = new DataTransfer();
                dtBack.items.add(new File([capturedBlobs.back], 'back.jpg', { type: 'image/jpeg' }));
                document.getElementById('fileBack').files = dtBack.files;
            @endif

            document.getElementById('idCaptureForm').submit();
        }

        // Le consentement précède tout : la caméra ne démarre qu'après avoir
        // coché la case et touché "Commencer".
        const consentCheckbox = document.getElementById('consentCheckbox');
        const consentStartBtn = document.getElementById('consentStartBtn');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showState('stateDenied');
        } else {
            showState('stateConsent');
            consentCheckbox.addEventListener('change', () => {
                consentStartBtn.disabled = !consentCheckbox.checked;
            });
            consentStartBtn.addEventListener('click', () => {
                if (!consentCheckbox.checked) return;
                document.getElementById('consentField').value = '1';
                startCamera();
            });
        }
    </script>
</body>
</html>
