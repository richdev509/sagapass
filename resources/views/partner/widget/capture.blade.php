<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vérification SAGAPASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .widget-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        .widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .widget-body {
            padding: 30px;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .video-preview, .photo-preview {
            width: 100%;
            max-height: 400px;
            border-radius: 10px;
            margin: 20px 0;
            background: #000;
        }
        .capture-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .capture-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .progress-bar-custom {
            height: 6px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        .countdown {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="widget-container">
        <div class="widget-header">
            <h2><i class="fas fa-shield-check me-2"></i>Vérification SAGAPASS</h2>
            <p class="mb-0">Vérification sécurisée pour {{ $partner->name }}</p>
        </div>

        <div class="widget-body">
            <!-- Progress Bar -->
            <div class="progress-bar-custom mb-4">
                <div class="progress-fill" id="progressBar" style="width: 0%"></div>
            </div>

            <!-- Étape 1: Info -->
            <div class="step active" id="step-info">
                <div class="text-center">
                    <i class="fas fa-user-shield fa-4x text-primary mb-3"></i>
                    <h4>Bonjour {{ $firstName }} {{ $lastName }} !</h4>
                    <p class="text-muted">{{ $partner->name }} souhaite vérifier votre identité via SAGAPASS.</p>
                    <p><strong>Email :</strong> {{ $email }}</p>
                </div>

                <div class="alert alert-info mt-4">
                    <h6><i class="fas fa-info-circle me-2"></i>Ce processus comprend :</h6>
                    <ol class="mb-0">
                        <li>Informations personnelles et date de naissance</li>
                        <li>Une photo de votre visage</li>
                        <li>Photos de votre pièce d'identité (recto/verso)</li>
                        <li>Une courte vidéo de vérification (15 secondes)</li>
                    </ol>
                </div>

                <div class="alert alert-success">
                    <i class="fas fa-lock me-2"></i>
                    <strong>Sécurisé :</strong> Vos données sont cryptées et protégées.
                </div>

                <div class="d-grid">
                    <button class="capture-btn" onclick="nextStep(1)">
                        <i class="fas fa-arrow-right me-2"></i>Commencer la vérification
                    </button>
                </div>
            </div>

            <!-- Étape 1: Informations Personnelles -->
            <div class="step" id="step-personal">
                <h5 class="text-center mb-3">
                    <i class="fas fa-user me-2"></i>Étape 1/4 : Informations Personnelles
                </h5>

                <form id="personalInfoForm">
                    <div class="mb-3">
                        <label class="form-label">Date de Naissance *</label>
                        <input type="date" class="form-control" id="dateOfBirth" required
                               max="{{ date('Y-m-d', strtotime('-18 years')) }}">
                        <small class="text-muted">Vous devez avoir au moins 18 ans</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Téléphone (optionnel)</label>
                        <input type="tel" class="form-control" id="phone" placeholder="+509 XX XX XXXX">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse (optionnel)</label>
                        <textarea class="form-control" id="address" rows="2" placeholder="Votre adresse complète"></textarea>
                    </div>
                </form>

                <div class="d-grid">
                    <button class="btn btn-success" onclick="savePersonalInfo()">
                        <i class="fas fa-arrow-right me-2"></i>Continuer
                    </button>
                </div>
            </div>

            <!-- Étape 2: Capture Photo de profil -->
            <div class="step" id="step-photo">
                <h5 class="text-center mb-3">
                    <i class="fas fa-camera me-2"></i>Étape 2/4 : Photo de profil
                </h5>

                <div class="text-center">
                    <video id="photoVideo" class="photo-preview" autoplay playsinline></video>
                    <canvas id="photoCanvas" style="display:none;"></canvas>
                    <img id="photoPreview" class="photo-preview" style="display:none;" />
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Conseil :</strong> Assurez-vous d'être dans un endroit bien éclairé et regardez la caméra.
                </div>

                <div class="d-grid gap-2">
                    <button class="capture-btn" id="capturePhotoBtn" onclick="capturePhoto()">
                        <i class="fas fa-camera me-2"></i>Prendre la photo
                    </button>
                    <button class="btn btn-secondary" id="retakePhotoBtn" style="display:none;" onclick="retakePhoto()">
                        <i class="fas fa-redo me-2"></i>Reprendre
                    </button>
                    <button class="btn btn-success" id="confirmPhotoBtn" style="display:none;" onclick="nextStep(3)">
                        <i class="fas fa-check me-2"></i>Confirmer et continuer
                    </button>
                </div>
            </div>

            <!-- Étape 4: Document Recto ET Verso -->
            <div class="step" id="step-document-front">
                <h5 class="text-center mb-3">
                    <i class="fas fa-id-card me-2"></i>Étape 3/4 : Informations et Photos du Document
                </h5>

                <form id="documentInfoForm">
                    <div class="mb-3">
                        <label class="form-label">Type de Document *</label>
                        <select class="form-control" id="documentType" required onchange="toggleCardNumber()">
                            <option value="">-- Sélectionner --</option>
                            <option value="CNI">Carte Nationale d'Identité</option>
                        </select>
                    </div>

                    <div class="mb-3" id="cardNumberGroup" style="display:none;">
                        <label class="form-label">Numéro de Carte (9 caractères) *</label>
                        <input type="text" class="form-control" id="cardNumber" placeholder="Ex: 001234567" maxlength="9" pattern="[A-Za-z0-9]{9}" oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '')">
                        <small class="text-muted">9 caractères (lettres ou chiffres)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">NINU (10 chiffres) *</label>
                        <input type="text" class="form-control" id="documentNumber" required placeholder="Ex: 1234567890" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <small class="text-muted">Numéro d'Identification Nationale Unique (10 chiffres)</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date d'émission *</label>
                            <input type="date" class="form-control" id="issueDate" required max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date d'expiration *</label>
                            <input type="date" class="form-control" id="expiryDate" required min="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3"><i class="fas fa-id-card me-2"></i>Photo RECTO du document *</h6>
                    <div class="text-center mb-3">
                        <video id="docFrontVideo" class="photo-preview" autoplay playsinline style="display:none;"></video>
                        <canvas id="docFrontCanvas" style="display:none;"></canvas>
                        <img id="docFrontPreview" class="photo-preview" style="display:none;" />
                        <div id="docFrontPlaceholder" class="alert alert-secondary">
                            <i class="fas fa-camera fa-3x mb-2"></i>
                            <p>Cliquez pour capturer le recto de votre document</p>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-4">
                        <button type="button" class="btn btn-primary" id="captureDocFrontBtn" onclick="startDocFrontCapture()">
                            <i class="fas fa-camera me-2"></i>Capturer le RECTO
                        </button>
                        <button type="button" class="btn btn-secondary" id="retakeDocFrontBtn" style="display:none;" onclick="retakeDocFront()">
                            <i class="fas fa-redo me-2"></i>Reprendre le RECTO
                        </button>
                    </div>

                    <hr>

                    <h6 class="mb-3"><i class="fas fa-id-card me-2"></i>Photo VERSO du document *</h6>
                    <div class="text-center mb-3">
                        <video id="docBackVideo" class="photo-preview" autoplay playsinline style="display:none;"></video>
                        <canvas id="docBackCanvas" style="display:none;"></canvas>
                        <img id="docBackPreview" class="photo-preview" style="display:none;" />
                        <div id="docBackPlaceholder" class="alert alert-secondary">
                            <i class="fas fa-camera fa-3x mb-2"></i>
                            <p>Cliquez pour capturer le verso de votre document</p>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-primary" id="captureDocBackBtn" onclick="startDocBackCapture()">
                            <i class="fas fa-camera me-2"></i>Capturer le VERSO
                        </button>
                        <button type="button" class="btn btn-secondary" id="retakeDocBackBtn" style="display:none;" onclick="retakeDocBack()">
                            <i class="fas fa-redo me-2"></i>Reprendre le VERSO
                        </button>
                    </div>
                </form>

                <div class="d-grid">
                    <button class="btn btn-success" onclick="saveDocumentInfo()">
                        <i class="fas fa-arrow-right me-2"></i>Continuer
                    </button>
                </div>
            </div>

            <!-- Étape 5: Capture Vidéo -->
            <div class="step" id="step-video">
                <h5 class="text-center mb-3">
                    <i class="fas fa-video me-2"></i>Étape 4/4 : Vidéo de vérification
                </h5>

                <div class="text-center">
                    <video id="videoPreview" class="video-preview" autoplay playsinline muted></video>
                    <video id="recordedVideo" class="video-preview" style="display:none;" controls></video>
                    <div class="countdown" id="countdown" style="display:none;">3</div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Instructions :</strong>
                    <ul class="mb-0 mt-2">
                        <li>Tournez lentement votre tête de gauche à droite</li>
                        <li>Gardez votre visage visible pendant 15 secondes</li>
                        <li>Évitez les mouvements brusques</li>
                    </ul>
                </div>

                <div class="d-grid gap-2">
                    <button class="capture-btn" id="startRecordBtn" onclick="startRecording()">
                        <i class="fas fa-video me-2"></i>Commencer l'enregistrement
                    </button>
                    <button class="btn btn-danger" id="stopRecordBtn" style="display:none;" onclick="stopRecording()">
                        <i class="fas fa-stop me-2"></i>Arrêter
                    </button>
                    <button class="btn btn-secondary" id="retakeVideoBtn" style="display:none;" onclick="retakeVideo()">
                        <i class="fas fa-redo me-2"></i>Reprendre
                    </button>
                    <button class="btn btn-success" id="confirmVideoBtn" style="display:none;" onclick="submitVerification()">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer la vérification
                    </button>
                </div>

                <div id="loadingSubmit" class="text-center mt-3" style="display:none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Envoi en cours...</span>
                    </div>
                    <p class="mt-2">Envoi de vos données...</p>
                </div>
            </div>

            <!-- Étape 7: Succès -->
            <div class="step" id="step-success">
                <div class="text-center">
                    <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                    <h3>Vérification réussie !</h3>
                    <p class="text-muted">Votre compte SAGAPASS a été créé avec succès.</p>

                    <div class="alert alert-success">
                        <i class="fas fa-envelope me-2"></i>
                        Un email contenant vos identifiants a été envoyé à <strong>{{ $email }}</strong>
                    </div>

                    <p class="small text-muted mt-4">Vous pouvez fermer cette fenêtre. Redirection automatique dans <span id="redirectCountdown">3</span> secondes...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 0;
        let photoStream = null;
        let videoStream = null;
        let docFrontStream = null;
        let docBackStream = null;
        let mediaRecorder = null;
        let recordedChunks = [];

        // Données capturées
        let capturedPhoto = null;
        let capturedVideo = null;
        let capturedDocFront = null;
        let capturedDocBack = null;
        let personalData = {};
        let documentData = {};

        const widgetData = {
            partnerId: '{{ $partner->client_id }}',
            email: '{{ $email }}',
            firstName: '{{ $firstName }}',
            lastName: '{{ $lastName }}',
            callbackUrl: '{{ $callbackUrl ?? "" }}',
            token: '{{ $token }}'
        };

        // Navigation entre étapes
        function nextStep(step) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            currentStep = step;

            const progressValues = {
                1: '0%',    // Personal info
                2: '33%',   // Photo
                3: '66%',   // Document (recto + verso)
                4: '100%'   // Video
            };

            document.getElementById('progressBar').style.width = progressValues[step] || '0%';

            if (step === 1) {
                document.getElementById('step-personal').classList.add('active');
            } else if (step === 2) {
                document.getElementById('step-photo').classList.add('active');
                startPhotoCapture();
            } else if (step === 3) {
                document.getElementById('step-document-front').classList.add('active');
            } else if (step === 4) {
                document.getElementById('step-video').classList.add('active');
                startVideoCapture();
            } else if (step === 5) {
                document.getElementById('step-success').classList.add('active');
                autoRedirect();
            }
        }

        // === ÉTAPE 1: INFOS PERSONNELLES ===
        function savePersonalInfo() {
            const dob = document.getElementById('dateOfBirth').value;
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('address').value;

            if (!dob) {
                alert('La date de naissance est obligatoire');
                return;
            }

            // Vérifier 18+
            const birthDate = new Date(dob);
            const age = (new Date() - birthDate) / (1000 * 60 * 60 * 24 * 365);
            if (age < 18) {
                alert('Vous devez avoir au moins 18 ans');
                return;
            }

            personalData = { date_of_birth: dob, phone, address };
            nextStep(2);
        }

        // === ÉTAPE 2: PHOTO CAPTURE ===
        async function startPhotoCapture() {
            try {
                photoStream = await navigator.mediaDevices.getUserMedia({ video: true });
                document.getElementById('photoVideo').srcObject = photoStream;
            } catch (error) {
                alert('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
            }
        }

        function capturePhoto() {
            const video = document.getElementById('photoVideo');
            const canvas = document.getElementById('photoCanvas');
            const preview = document.getElementById('photoPreview');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            capturedPhoto = canvas.toDataURL('image/jpeg');
            preview.src = capturedPhoto;

            video.style.display = 'none';
            preview.style.display = 'block';

            document.getElementById('capturePhotoBtn').style.display = 'none';
            document.getElementById('retakePhotoBtn').style.display = 'block';
            document.getElementById('confirmPhotoBtn').style.display = 'block';

            if (photoStream) {
                photoStream.getTracks().forEach(track => track.stop());
            }
        }

        function retakePhoto() {
            document.getElementById('photoVideo').style.display = 'block';
            document.getElementById('photoPreview').style.display = 'none';
            document.getElementById('capturePhotoBtn').style.display = 'block';
            document.getElementById('retakePhotoBtn').style.display = 'none';
            document.getElementById('confirmPhotoBtn').style.display = 'none';
            startPhotoCapture();
        }

        // === ÉTAPE 3: DOCUMENT INFO + RECTO ===
        function toggleCardNumber() {
            const docType = document.getElementById('documentType').value;
            const cardNumGroup = document.getElementById('cardNumberGroup');

            if (docType === 'CNI') {
                cardNumGroup.style.display = 'block';
                document.getElementById('cardNumber').required = true;
            } else {
                cardNumGroup.style.display = 'none';
                document.getElementById('cardNumber').required = false;
            }
        }

        async function startDocFrontCapture() {
            try {
                // Utiliser la caméra arrière pour mobile (meilleure qualité pour documents)
                docFrontStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                const video = document.getElementById('docFrontVideo');
                video.srcObject = docFrontStream;
                video.style.display = 'block';
                document.getElementById('docFrontPlaceholder').style.display = 'none';

                // Attendre et capturer automatiquement après 3 secondes
                setTimeout(() => captureDocFront(), 3000);
            } catch (error) {
                alert('Impossible d\'accéder à la caméra.');
            }
        }

        function captureDocFront() {
            const video = document.getElementById('docFrontVideo');
            const canvas = document.getElementById('docFrontCanvas');
            const preview = document.getElementById('docFrontPreview');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            capturedDocFront = canvas.toDataURL('image/jpeg');
            preview.src = capturedDocFront;

            video.style.display = 'none';
            preview.style.display = 'block';

            document.getElementById('captureDocFrontBtn').style.display = 'none';
            document.getElementById('retakeDocFrontBtn').style.display = 'block';

            if (docFrontStream) {
                docFrontStream.getTracks().forEach(track => track.stop());
            }
        }

        function retakeDocFront() {
            document.getElementById('docFrontVideo').style.display = 'none';
            document.getElementById('docFrontPreview').style.display = 'none';
            document.getElementById('docFrontPlaceholder').style.display = 'block';
            document.getElementById('captureDocFrontBtn').style.display = 'block';
            document.getElementById('retakeDocFrontBtn').style.display = 'none';
        }

        function saveDocumentInfo() {
            const docType = document.getElementById('documentType').value;
            const docNum = document.getElementById('documentNumber').value;
            const cardNum = document.getElementById('cardNumber').value;
            const issueDate = document.getElementById('issueDate').value;
            const expiryDate = document.getElementById('expiryDate').value;

            if (!docType || !docNum || !issueDate || !expiryDate || !capturedDocFront) {
                alert('Veuillez remplir tous les champs et capturer le recto du document');
                return;
            }

            // Validation NINU: 10 chiffres uniquement
            if (!/^[0-9]{10}$/.test(docNum)) {
                alert('Le NINU doit contenir exactement 10 chiffres');
                return;
            }

            // Validation numéro de carte pour CNI: 9 caractères alphanumériques
            if (docType === 'CNI') {
                if (!cardNum) {
                    alert('Le numéro de carte est obligatoire pour la Carte Nationale d\'Identité');
                    return;
                }
                if (!/^[A-Za-z0-9]{9}$/.test(cardNum)) {
                    alert('Le numéro de carte doit contenir exactement 9 caractères (lettres ou chiffres)');
                    return;
                }
            }

            documentData = {
                documentType: docType,
                documentNumber: docNum,
                cardNumber: cardNum,
                issueDate: issueDate,
                expiryDate: expiryDate
            };

            nextStep(4);
        }

        // === ÉTAPE 4: DOCUMENT VERSO (CNI uniquement) ===
        async function startDocBackCapture() {
            try {
                // Utiliser la caméra arrière pour mobile (meilleure qualité pour documents)
                docBackStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                const video = document.getElementById('docBackVideo');
                video.srcObject = docBackStream;
                video.style.display = 'block';
                document.getElementById('docBackPlaceholder').style.display = 'none';

                // Attendre et capturer automatiquement après 3 secondes
                setTimeout(() => captureDocBack(), 3000);
            } catch (error) {
                alert('Impossible d\'accéder à la caméra.');
            }
        }

        function captureDocBack() {
            const video = document.getElementById('docBackVideo');
            const canvas = document.getElementById('docBackCanvas');
            const preview = document.getElementById('docBackPreview');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            capturedDocBack = canvas.toDataURL('image/jpeg');
            preview.src = capturedDocBack;

            video.style.display = 'none';
            preview.style.display = 'block';

            document.getElementById('captureDocBackBtn').style.display = 'none';
            document.getElementById('retakeDocBackBtn').style.display = 'block';
            document.getElementById('confirmDocBackBtn').style.display = 'block';

            if (docBackStream) {
                docBackStream.getTracks().forEach(track => track.stop());
            }
        }

        function retakeDocBack() {
            document.getElementById('docBackVideo').style.display = 'none';
            document.getElementById('docBackPreview').style.display = 'none';
            document.getElementById('docBackPlaceholder').style.display = 'block';
            document.getElementById('captureDocBackBtn').style.display = 'block';
            document.getElementById('retakeDocBackBtn').style.display = 'none';
            document.getElementById('confirmDocBackBtn').style.display = 'none';
        }

        // === VIDEO CAPTURE ===
        async function startVideoCapture() {
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                document.getElementById('videoPreview').srcObject = videoStream;
            } catch (error) {
                alert('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
            }
        }

        function startRecording() {
            recordedChunks = [];
            const countdown = document.getElementById('countdown');
            countdown.style.display = 'block';

            let count = 3;
            const interval = setInterval(() => {
                count--;
                countdown.textContent = count;
                if (count === 0) {
                    clearInterval(interval);
                    countdown.style.display = 'none';
                    actuallyStartRecording();
                }
            }, 1000);
        }

        function actuallyStartRecording() {
            mediaRecorder = new MediaRecorder(videoStream);

            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    recordedChunks.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                const blob = new Blob(recordedChunks, { type: 'video/webm' });
                const videoUrl = URL.createObjectURL(blob);
                document.getElementById('recordedVideo').src = videoUrl;

                // Convertir en base64
                const reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = () => {
                    capturedVideo = reader.result;
                };

                document.getElementById('videoPreview').style.display = 'none';
                document.getElementById('recordedVideo').style.display = 'block';
            };

            mediaRecorder.start();

            document.getElementById('startRecordBtn').style.display = 'none';
            document.getElementById('stopRecordBtn').style.display = 'block';

            // Auto-stop après 15 secondes
            setTimeout(() => {
                if (mediaRecorder.state === 'recording') {
                    stopRecording();
                }
            }, 15000);
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                if (videoStream) {
                    videoStream.getTracks().forEach(track => track.stop());
                }

                document.getElementById('stopRecordBtn').style.display = 'none';
                document.getElementById('retakeVideoBtn').style.display = 'block';
                document.getElementById('confirmVideoBtn').style.display = 'block';
            }
        }

        function retakeVideo() {
            document.getElementById('videoPreview').style.display = 'block';
            document.getElementById('recordedVideo').style.display = 'none';
            document.getElementById('startRecordBtn').style.display = 'block';
            document.getElementById('retakeVideoBtn').style.display = 'none';
            document.getElementById('confirmVideoBtn').style.display = 'none';
            startVideoCapture();
        }

        // === SUBMISSION ===
        async function submitVerification() {
            if (!capturedPhoto || !capturedVideo || !capturedDocFront) {
                alert('Veuillez compléter toutes les étapes (photo, document, vidéo).');
                return;
            }

            if (documentData.documentType === 'CNI' && !capturedDocBack) {
                alert('Veuillez capturer le verso de votre CNI.');
                return;
            }

            document.getElementById('confirmVideoBtn').disabled = true;
            document.getElementById('loadingSubmit').style.display = 'block';

            try {
                const response = await fetch('{{ route("partner.widget.submit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        partner_id: widgetData.partnerId,
                        email: widgetData.email,
                        first_name: widgetData.firstName,
                        last_name: widgetData.lastName,
                        date_of_birth: personalData.date_of_birth,
                        phone: personalData.phone,
                        address: personalData.address,
                        photo: capturedPhoto,
                        video: capturedVideo,
                        document: {
                            document_type: documentData.documentType,
                            document_number: documentData.documentNumber,
                            card_number: documentData.cardNumber,
                            issue_date: documentData.issueDate,
                            expiry_date: documentData.expiryDate,
                            front_photo: capturedDocFront,
                            back_photo: capturedDocBack
                        },
                        callback_url: widgetData.callbackUrl
                    })
                });

                const result = await response.json();

                if (result.success) {
                    nextStep(6);

                    // Notifier le parent (site partenaire)
                    if (window.opener) {
                        window.opener.postMessage({
                            type: 'SAGAPASS_VERIFICATION_SUCCESS',
                            citizenId: result.citizen_id,
                            email: widgetData.email
                        }, '*');
                    }
                } else {
                    alert('Erreur : ' + result.error);
                    document.getElementById('confirmVideoBtn').disabled = false;
                    document.getElementById('loadingSubmit').style.display = 'none';
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion. Veuillez réessayer.');
                document.getElementById('confirmVideoBtn').disabled = false;
                document.getElementById('loadingSubmit').style.display = 'none';
            }
        }        // Redirection automatique
        function autoRedirect() {
            let count = 3;
            const countdownEl = document.getElementById('redirectCountdown');

            const interval = setInterval(() => {
                count--;
                countdownEl.textContent = count;

                if (count === 0) {
                    clearInterval(interval);
                    if (widgetData.callbackUrl) {
                        window.location.href = widgetData.callbackUrl;
                    } else if (window.opener) {
                        window.close();
                    }
                }
            }, 1000);
        }
    </script>
</body>
</html>
