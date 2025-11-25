@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Créer votre SagaPass Basic</h4>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                    </div>
                    <small class="text-muted">Étape 3/3 : Vidéo de vérification</small>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-video"></i> <strong>Vidéo de vérification (15 secondes)</strong>
                        <p class="mb-2 mt-2"><strong>Instructions :</strong></p>
                        <ol class="mb-0">
                            <li>Regardez la caméra</li>
                            <li>Dites clairement : <strong>"Je suis {{ $userName }}"</strong></li>
                            <li>Tournez votre tête à <strong>gauche</strong>, puis à <strong>droite</strong></li>
                        </ol>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Message demande d'accès -->
                    <div id="permission-request" class="text-center mb-4">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h5>Accès à la webcam et au microphone requis</h5>
                            <p>Pour enregistrer votre vidéo de vérification, nous avons besoin d'accéder à votre webcam et votre microphone.</p>
                            <button type="button" class="btn btn-primary btn-lg" onclick="requestMediaAccess()">
                                <i class="fas fa-video"></i> Autoriser l'accès
                            </button>
                        </div>
                    </div>

                    <!-- Webcam pour enregistrement -->
                    <div id="recording-container" class="text-center" style="display: none;">
                        <video id="webcam-video" width="100%" height="auto" autoplay muted style="max-width: 640px; border-radius: 10px; border: 3px solid #ddd;"></video>

                        <!-- Barre de progression -->
                        <div id="progress-container" style="display: none; max-width: 640px; margin: 20px auto;">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Enregistrement en cours...</span>
                                <span class="text-primary fw-bold"><span id="timer-display">0</span>s / 15s</span>
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Countdown avant démarrage -->
                        <div id="countdown" style="display: none;" class="mt-3">
                            <h1 class="text-primary mb-0"><span id="countdown-number">3</span></h1>
                        </div>

                        <!-- Boutons -->
                        <div id="control-buttons" class="mt-3">
                            <button type="button" class="btn btn-danger btn-lg" id="start-record-btn">
                                <i class="fas fa-circle"></i> Démarrer l'enregistrement
                            </button>
                            <button type="button" class="btn btn-warning btn-lg" id="stop-record-btn" style="display: none;">
                                <i class="fas fa-stop"></i> Arrêter
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location='{{ route('register.basic.step2') }}'">
                                <i class="fas fa-arrow-left"></i> Retour
                            </button>
                        </div>
                    </div>

                    <!-- Preview vidéo -->
                    <div id="preview-container" style="display: none;" class="text-center">
                        <h5 class="mb-3">Aperçu de votre vidéo</h5>
                        <video id="preview-video" width="100%" height="auto" controls style="max-width: 640px; border-radius: 10px; border: 3px solid #28a745;"></video>

                        <div class="mt-3">
                            <button type="button" class="btn btn-success btn-lg" id="validate-video-btn">
                                <i class="fas fa-check"></i> Valider et créer mon compte
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="retake-video-btn">
                                <i class="fas fa-redo"></i> Refaire la vidéo
                            </button>
                        </div>
                    </div>

                    <!-- Consentement RGPD -->
                    <div id="consent-container" style="display: none;" class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="consent-checkbox" name="consent" required>
                            <label class="form-check-label" for="consent-checkbox">
                                J'accepte que ma photo et ma vidéo soient stockées pour vérifier mon identité.
                                Je peux demander leur suppression à tout moment via mon profil.
                                <a href="#" target="_blank">Politique de confidentialité</a>
                            </label>
                        </div>
                    </div>

                    <!-- Formulaire caché -->
                    <form method="POST" action="{{ route('register.basic.step3.submit') }}" id="video-form" enctype="multipart/form-data" style="display: none;">
                        @csrf
                        <input type="file" name="video" id="video-file" accept="video/*">
                        <input type="checkbox" name="consent" id="consent-hidden">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Attendre que le DOM soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    let videoStream = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let recordingStartTime = null;
    let timerInterval = null;
    let videoBlob = null; // Stocker le blob vidéo
    const MAX_DURATION = 15; // secondes

    const webcamVideo = document.getElementById('webcam-video');
    const previewVideo = document.getElementById('preview-video');
    const startRecordBtn = document.getElementById('start-record-btn');
    const stopRecordBtn = document.getElementById('stop-record-btn');
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const timerDisplay = document.getElementById('timer-display');

    // Fonction pour demander accès média (appelée directement depuis onclick) - GLOBALE
    window.requestMediaAccess = async function() {
        try {
            videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user'
                },
                audio: true
            });
            webcamVideo.srcObject = videoStream;

            // Masquer le message de permission et afficher la webcam
            document.getElementById('permission-request').style.display = 'none';
            document.getElementById('recording-container').style.display = 'block';
        } catch (error) {
            console.error('Erreur:', error);

            let errorMsg = 'Impossible d\'accéder à la webcam et au microphone.';
            if (error.name === 'NotAllowedError') {
                errorMsg = 'Vous avez refusé l\'accès. Veuillez autoriser l\'accès à la webcam et au microphone dans les paramètres de votre navigateur.';
            } else if (error.name === 'NotFoundError') {
                errorMsg = 'Aucune webcam ou microphone détecté sur votre appareil.';
            } else if (error.name === 'NotReadableError') {
                errorMsg = 'Votre webcam ou microphone est déjà utilisé par une autre application.';
            }

            document.getElementById('permission-request').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i>
                    <h5>Erreur d'accès aux médias</h5>
                    <p>${errorMsg}</p>
                    <button type="button" class="btn btn-primary" onclick="requestMediaAccess()">
                        <i class="fas fa-redo"></i> Réessayer
                    </button>
                    <a href="{{ route('register.basic.step2') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            `;
        }
    };

    // Démarrer l'enregistrement avec countdown
    startRecordBtn.addEventListener('click', () => {
        startCountdown();
    });

    function startCountdown() {
        let countdownElement = document.getElementById('countdown');
        let countdownNumber = document.getElementById('countdown-number');
        let count = 3;

        countdownElement.style.display = 'block';
        startRecordBtn.style.display = 'none';

        let interval = setInterval(() => {
            count--;
            countdownNumber.textContent = count;

            if (count === 0) {
                clearInterval(interval);
                countdownElement.style.display = 'none';
                startRecording();
            }
        }, 1000);
    }

    function startRecording() {
        recordedChunks = [];

        // Créer le MediaRecorder
        try {
            mediaRecorder = new MediaRecorder(videoStream, {
                mimeType: 'video/webm;codecs=vp8,opus'
            });
        } catch (e) {
            // Fallback si le codec n'est pas supporté
            mediaRecorder = new MediaRecorder(videoStream);
        }

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                recordedChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {
            // Créer le blob vidéo et le stocker
            videoBlob = new Blob(recordedChunks, { type: 'video/webm' });
            const videoURL = URL.createObjectURL(videoBlob);
            previewVideo.src = videoURL;

            console.log('Vidéo enregistrée:', videoBlob.size, 'bytes');

            // Afficher le preview
            showPreview();
        };

        // Démarrer l'enregistrement
        mediaRecorder.start();
        recordingStartTime = Date.now();

        // Afficher la barre de progression
        progressContainer.style.display = 'block';
        stopRecordBtn.style.display = 'inline-block';

        // Démarrer le timer
        timerInterval = setInterval(updateProgress, 100);

        // Arrêter automatiquement après 15 secondes
        setTimeout(() => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                stopRecording();
            }
        }, MAX_DURATION * 1000);
    }

    function updateProgress() {
        const elapsed = (Date.now() - recordingStartTime) / 1000;
        const percentage = (elapsed / MAX_DURATION) * 100;

        progressBar.style.width = percentage + '%';
        timerDisplay.textContent = Math.floor(elapsed);

        if (elapsed >= MAX_DURATION) {
            clearInterval(timerInterval);
        }
    }

    // Arrêter l'enregistrement
    stopRecordBtn.addEventListener('click', () => {
        stopRecording();
    });

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
            clearInterval(timerInterval);

            // Arrêter la webcam
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
            }
        }
    }

    function showPreview() {
        document.getElementById('recording-container').style.display = 'none';
        document.getElementById('preview-container').style.display = 'block';
        document.getElementById('consent-container').style.display = 'block';
    }

    // Valider la vidéo - ENVOYER VIA FORMDATA
    document.getElementById('validate-video-btn').addEventListener('click', () => {
        const consentCheckbox = document.getElementById('consent-checkbox');

        if (!consentCheckbox.checked) {
            alert('Veuillez accepter le consentement pour continuer.');
            return;
        }

        if (!videoBlob) {
            alert('Aucune vidéo enregistrée. Veuillez réessayer.');
            return;
        }

        // Créer FormData pour envoyer le fichier
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('video', videoBlob, 'verification-video.webm');
        formData.append('consent', '1');

        // Afficher un indicateur de chargement
        document.getElementById('validate-video-btn').disabled = true;
        document.getElementById('validate-video-btn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

        // Envoyer via fetch
        fetch('{{ route("register.basic.step3.submit") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            // Lire le contenu de la réponse
            return response.text().then(text => {
                // Essayer de parser en JSON
                try {
                    const data = JSON.parse(text);
                    if (response.ok) {
                        // Rediriger vers la page de succès
                        window.location.href = data.redirect || '{{ route("register.basic.complete") }}';
                    } else {
                        throw new Error(data.message || 'Erreur lors de l\'envoi');
                    }
                } catch (e) {
                    // Si ce n'est pas du JSON, c'est probablement une erreur serveur
                    console.error('Réponse non-JSON:', text.substring(0, 500));
                    throw new Error('Erreur serveur. Vérifiez les logs.');
                }
            });
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'envoi de la vidéo. Veuillez réessayer.\n\n' + error.message);
            document.getElementById('validate-video-btn').disabled = false;
            document.getElementById('validate-video-btn').innerHTML = '<i class="fas fa-check"></i> Valider et créer mon compte';
        });
    });

    // Refaire la vidéo
    document.getElementById('retake-video-btn').addEventListener('click', async () => {
        // Réinitialiser
        recordedChunks = [];
        videoBlob = null;
        progressBar.style.width = '0%';
        timerDisplay.textContent = '0';
        progressContainer.style.display = 'none';

        // Réafficher l'interface d'enregistrement
        document.getElementById('preview-container').style.display = 'none';
        document.getElementById('consent-container').style.display = 'none';
        document.getElementById('recording-container').style.display = 'block';
        startRecordBtn.style.display = 'inline-block';
        stopRecordBtn.style.display = 'none';

        // Redémarrer la webcam
        try {
            videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user'
                },
                audio: true
            });
            webcamVideo.srcObject = videoStream;
        } catch (error) {
            alert('Impossible de redémarrer la webcam.');
            console.error('Erreur:', error);
        }
    });

    // Nettoyer avant de quitter
    window.addEventListener('beforeunload', () => {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }
    });
});
</script>
@endpush
@endsection
