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
            letter-spacing: 0.01em;
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

        .instruction {
            text-align: center;
            margin-top: 0.75rem;
        }
        .instruction h1 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 0.25rem;
        }
        .instruction p {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.65);
            margin: 0;
        }

        .camera-wrap {
            position: relative;
            width: min(78vw, 320px);
            aspect-ratio: 3 / 4;
            margin: 1.25rem 0;
        }
        .camera-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 999px / 55%;
            background: #000;
            transform: scaleX(-1); /* effet miroir, plus naturel pour l'utilisateur */
        }
        .camera-guide {
            position: absolute;
            inset: 0;
            border-radius: 999px / 55%;
            border: 3px solid rgba(255,255,255,0.35);
            pointer-events: none;
            transition: border-color 0.2s ease;
        }
        .camera-guide.is-ready { border-color: var(--primary); }
        .camera-guide.is-captured { border-color: var(--success); }

        .camera-turn-arrow {
            position: absolute;
            top: 50%;
            font-size: 1.75rem;
            color: var(--primary);
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .camera-turn-arrow.is-visible { opacity: 1; animation: pulse-arrow 1.1s ease-in-out infinite; }
        .camera-turn-arrow.left { left: -2.25rem; }
        .camera-turn-arrow.right { right: -2.25rem; }
        @keyframes pulse-arrow {
            0%, 100% { transform: translateY(-50%) translateX(0); opacity: 0.5; }
            50% { transform: translateY(-50%) translateX(-4px); opacity: 1; }
        }
        .camera-turn-arrow.right { animation-direction: reverse; }

        .thumbs {
            display: flex;
            gap: 0.6rem;
            margin-bottom: 0.5rem;
        }
        .thumb {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.15);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.06);
        }
        .thumb img { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        .thumb.is-done { border-color: var(--success); }
        .thumb i { color: rgba(255,255,255,0.3); font-size: 0.9rem; }

        .capture-btn {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 50%;
            border: 4px solid #fff;
            background: transparent;
            position: relative;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .capture-btn::after {
            content: '';
            position: absolute;
            inset: 5px;
            border-radius: 50%;
            background: #fff;
            transition: transform 0.1s ease;
        }
        .capture-btn:active::after { transform: scale(0.85); }
        .capture-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        .footer-note {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.45);
            text-align: center;
            max-width: 22rem;
        }

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

        .btn-primary-soft {
            border: none;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            padding: 0.7rem 1.5rem;
            border-radius: 0.65rem;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .btn-primary-soft:hover { background: var(--primary-dark); }

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
                <div class="step-dot" data-step="1"></div>
                <div class="step-dot" data-step="2"></div>
            </div>

            <div class="instruction">
                <h1 id="instructionTitle">Tournez la tête vers la gauche</h1>
                <p id="instructionSubtitle">Gardez votre visage dans le cadre</p>
            </div>

            <div class="camera-wrap">
                <video id="video" class="camera-video" autoplay playsinline muted></video>
                <div class="camera-guide" id="cameraGuide"></div>
                <i class="fa-solid fa-chevron-left camera-turn-arrow left" id="arrowLeft"></i>
                <i class="fa-solid fa-chevron-right camera-turn-arrow right" id="arrowRight"></i>
            </div>

            <div class="thumbs">
                <div class="thumb" id="thumb0"><i class="fa-solid fa-arrow-left"></i></div>
                <div class="thumb" id="thumb1"><i class="fa-solid fa-face-smile"></i></div>
                <div class="thumb" id="thumb2"><i class="fa-solid fa-arrow-right"></i></div>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
            <button type="button" class="capture-btn" id="captureBtn" disabled></button>
            <p class="footer-note">Vos photos servent uniquement à vérifier votre identité pour {{ config('app.name', 'notre partenaire') }} et sont supprimées après analyse.</p>
        </div>
    </div>

    <div class="state-panel" id="statePermission">
        <i class="fa-solid fa-camera" style="color: rgba(255,255,255,0.5);"></i>
        <h2>Autorisez l'accès à la caméra</h2>
        <p>Votre navigateur va vous demander la permission d'utiliser la caméra avant-plant pour la vérification de vivacité.</p>
    </div>

    <div class="state-panel" id="stateDenied">
        <i class="fa-solid fa-camera-slash" style="color: var(--danger);"></i>
        <h2>Caméra indisponible</h2>
        <p>Vérifiez que vous avez autorisé l'accès à la caméra dans les réglages de votre navigateur, puis rechargez cette page.</p>
        <button type="button" class="btn-primary-soft" onclick="window.location.reload()">Réessayer</button>
    </div>

    <div class="state-panel" id="stateUploading">
        <div class="spinner"></div>
        <h2>Analyse en cours…</h2>
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

    <form id="captureForm" method="POST" action="{{ route('capture.submit', $token) }}" enctype="multipart/form-data" hidden>
        @csrf
        <input type="file" name="selfie_left" id="fileLeft">
        <input type="file" name="selfie_center" id="fileCenter">
        <input type="file" name="selfie_right" id="fileRight">
    </form>

    <script>
        const STEPS = [
            { key: 'left', title: 'Tournez la tête vers la gauche', subtitle: 'Gardez votre visage dans le cadre', arrow: 'arrowLeft' },
            { key: 'center', title: 'Regardez bien la caméra', subtitle: 'Visage centré, sans lunettes ni masque', arrow: null },
            { key: 'right', title: 'Tournez la tête vers la droite', subtitle: 'Gardez votre visage dans le cadre', arrow: 'arrowRight' },
        ];

        let currentStep = 0;
        const capturedBlobs = { left: null, center: null, right: null };
        let stream = null;

        const video = document.getElementById('video');
        const captureBtn = document.getElementById('captureBtn');
        const cameraGuide = document.getElementById('cameraGuide');
        const instructionTitle = document.getElementById('instructionTitle');
        const instructionSubtitle = document.getElementById('instructionSubtitle');

        function showState(id) {
            document.querySelectorAll('.state-panel').forEach(el => el.classList.remove('is-visible'));
            document.getElementById('captureScreen').style.display = 'none';
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

            document.getElementById('arrowLeft').classList.toggle('is-visible', step.arrow === 'arrowLeft');
            document.getElementById('arrowRight').classList.toggle('is-visible', step.arrow === 'arrowRight');
            cameraGuide.classList.remove('is-captured');
            cameraGuide.classList.add('is-ready');
        }

        async function startCamera() {
            showState('statePermission');
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 960 } },
                    audio: false,
                });
                video.srcObject = stream;
                showState(null);
                captureBtn.disabled = false;
                updateStepUi();
            } catch (err) {
                showState('stateDenied');
            }
        }

        function captureFrame() {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            // Le flux vidéo est affiché en miroir (CSS) pour le confort de
            // l'utilisateur, mais l'image envoyée à l'analyse ne doit PAS
            // l'être : on ne remiroir donc rien ici, canvas capture le flux
            // brut de la caméra tel quel.
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            return new Promise(resolve => canvas.toBlob(blob => resolve(blob), 'image/jpeg', 0.9));
        }

        captureBtn.addEventListener('click', async () => {
            captureBtn.disabled = true;
            cameraGuide.classList.add('is-captured');
            cameraGuide.classList.remove('is-ready');

            const blob = await captureFrame();
            const step = STEPS[currentStep];
            capturedBlobs[step.key] = blob;

            const thumb = document.getElementById('thumb' + currentStep);
            thumb.classList.add('is-done');
            thumb.innerHTML = '<img src="' + URL.createObjectURL(blob) + '">';

            currentStep += 1;

            if (currentStep >= STEPS.length) {
                submitCapture();
                return;
            }

            updateStepUi();
            setTimeout(() => { captureBtn.disabled = false; }, 400);
        });

        function blobToFile(blob, name) {
            return new File([blob], name, { type: 'image/jpeg' });
        }

        function submitCapture() {
            showState('stateUploading');

            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }

            const dtLeft = new DataTransfer();
            dtLeft.items.add(blobToFile(capturedBlobs.left, 'left.jpg'));
            document.getElementById('fileLeft').files = dtLeft.files;

            const dtCenter = new DataTransfer();
            dtCenter.items.add(blobToFile(capturedBlobs.center, 'center.jpg'));
            document.getElementById('fileCenter').files = dtCenter.files;

            const dtRight = new DataTransfer();
            dtRight.items.add(blobToFile(capturedBlobs.right, 'right.jpg'));
            document.getElementById('fileRight').files = dtRight.files;

            document.getElementById('captureForm').submit();
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showState('stateDenied');
        } else {
            startCamera();
        }
    </script>
</body>
</html>
