@extends('layouts.app')

@section('title', 'Recapture de la vérification')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Alerte de rejet -->
            <div class="alert alert-danger mb-4">
                <div class="d-flex align-items-start">
                    <i class="fas fa-exclamation-triangle fa-3x me-3"></i>
                    <div>
                        <h4 class="alert-heading">Vidéo de vérification rejetée</h4>
                        <p class="mb-2"><strong>Raison du rejet :</strong> {{ $user->video_rejection_reason ?? 'Non spécifiée' }}</p>
                        <hr>
                        <p class="mb-0">Vous devez reprendre votre photo de profil et votre vidéo de vérification. Veuillez suivre attentivement les instructions ci-dessous.</p>
                    </div>
                </div>
            </div>

            <!-- Étape 1: Photo de profil -->
            <div class="card mb-4" id="photo-section">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-camera me-2"></i>Étape 1/2 : Nouvelle photo de profil
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Instructions :</strong>
                        <ul class="mb-0 mt-2">
                            <li>Prenez une photo claire de votre visage</li>
                            <li>Regardez directement la caméra</li>
                            <li>Assurez-vous d'avoir un bon éclairage</li>
                            <li>Retirez lunettes de soleil, chapeau, masque</li>
                        </ul>
                    </div>

                    <div id="photo-capture-container">
                        <!-- Webcam Video -->
                        <div id="webcam-container" class="text-center mb-3">
                            <video id="webcam-video" width="100%" height="auto" autoplay style="max-width: 500px; border-radius: 10px; border: 3px solid #ddd; display: none;"></video>

                            <div id="countdown-photo" class="mt-3" style="display: none;">
                                <h2 class="text-primary mb-0"><span id="countdown-photo-number">3</span></h2>
                            </div>

                            <div class="mt-3" id="capture-buttons" style="display: none;">
                                <button type="button" class="btn btn-primary btn-lg" id="start-countdown-btn">
                                    <i class="fas fa-camera"></i> Prendre la photo
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="stopWebcam()">
                                    <i class="fas fa-times"></i> Annuler
                                </button>
                            </div>

                            <!-- Bouton initial pour démarrer -->
                            <div class="mt-3" id="start-camera-btn-container">
                                <button type="button" class="btn btn-primary btn-lg" onclick="startCamera()">
                                    <i class="fas fa-camera"></i> Activer la webcam
                                </button>
                            </div>
                        </div>

                        <!-- Preview Photo -->
                        <div id="photo-preview-container" style="display: none;" class="text-center">
                            <h5 class="mb-3">Aperçu de votre photo</h5>
                            <canvas id="photo-canvas" style="max-width: 500px; width: 100%; border-radius: 10px; border: 3px solid #28a745;"></canvas>

                            <div class="mt-3">
                                <button type="button" class="btn btn-success btn-lg" id="validate-photo-btn">
                                    <i class="fas fa-check"></i> Valider et passer à la vidéo
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="retake-photo-btn">
                                    <i class="fas fa-redo"></i> Reprendre
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 2: Vidéo de vérification -->
            <div class="card mb-4" id="video-section" style="display: none;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-video me-2"></i>Étape 2/2 : Nouvelle vidéo de vérification
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> <strong>Instructions importantes :</strong>
                        <ul class="mb-0 mt-2">
                            <li>Dites votre <strong>nom complet</strong> à voix haute</li>
                            <li>Tournez lentement votre <strong>visage vers la gauche</strong></li>
                            <li>Tournez lentement votre <strong>visage vers la droite</strong></li>
                            <li>La vidéo dure maximum <strong>15 secondes</strong></li>
                            <li>Assurez-vous d'avoir un <strong>bon éclairage</strong></li>
                        </ul>
                    </div>

                    <div id="video-capture-container">
                        <!-- Webcam Video pour enregistrement -->
                        <div id="webcam-video-container" class="text-center mb-3">
                            <video id="webcam-video-record" width="100%" height="auto" autoplay muted style="max-width: 500px; border-radius: 10px; border: 3px solid #ddd; display: none;"></video>

                            <div id="recording-indicator" style="display: none;" class="mt-3">
                                <span class="badge bg-danger fs-5">
                                    <i class="fas fa-circle blink"></i> ENREGISTREMENT EN COURS
                                </span>
                                <div class="mt-2">
                                    <h3 class="text-danger mb-0">
                                        <span id="recording-timer">0</span>s / 15s
                                    </h3>
                                </div>
                            </div>

                            <div class="mt-3" id="start-video-buttons">
                                <button type="button" class="btn btn-danger btn-lg" onclick="requestMediaAccess()">
                                    <i class="fas fa-video"></i> Démarrer l'enregistrement
                                </button>
                            </div>
                        </div>

                        <!-- Preview Vidéo -->
                        <div id="video-preview-container" style="display: none;" class="text-center">
                            <h5 class="mb-3">Aperçu de votre vidéo</h5>
                            <video id="preview-video" controls style="max-width: 500px; width: 100%; border-radius: 10px; border: 3px solid #28a745;"></video>

                            <div class="mt-3 mb-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changePlaybackSpeed(0.5)">x0.5</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changePlaybackSpeed(1)">x1</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changePlaybackSpeed(1.5)">x1.5</button>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="consent-checkbox">
                                <label class="form-check-label" for="consent-checkbox">
                                    Je certifie que cette vidéo a été enregistrée par moi-même et que les informations sont exactes.
                                </label>
                            </div>

                            <div class="mt-3">
                                <button type="button" class="btn btn-success btn-lg" id="validate-video-btn" disabled>
                                    <i class="fas fa-check"></i> Soumettre ma nouvelle vérification
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="retake-video-btn">
                                    <i class="fas fa-redo"></i> Reprendre la vidéo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @keyframes blink {
        0%, 50% { opacity: 1; }
        51%, 100% { opacity: 0; }
    }
    .blink {
        animation: blink 1s infinite;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let photoStream = null;
    let videoStream = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let videoBlob = null;
    let photoData = '';
    let recordingTimer = null;
    let recordingSeconds = 0;

    const webcamVideo = document.getElementById('webcam-video');
    const photoCanvas = document.getElementById('photo-canvas');
    const webcamVideoRecord = document.getElementById('webcam-video-record');
    const previewVideo = document.getElementById('preview-video');
    const consentCheckbox = document.getElementById('consent-checkbox');
    const validateVideoBtn = document.getElementById('validate-video-btn');

    // ==================== PARTIE PHOTO ====================

    window.startCamera = async function() {
        try {
            photoStream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' }
            });
            webcamVideo.srcObject = photoStream;
            webcamVideo.style.display = 'block';
            document.getElementById('start-camera-btn-container').style.display = 'none';
            document.getElementById('capture-buttons').style.display = 'block';
        } catch (error) {
            console.error('Erreur webcam:', error);
            alert('Impossible d\'accéder à la webcam. Veuillez autoriser l\'accès.');
        }
    };

    window.stopWebcam = function() {
        if (photoStream) {
            photoStream.getTracks().forEach(track => track.stop());
            photoStream = null;
        }
        webcamVideo.style.display = 'none';
        document.getElementById('capture-buttons').style.display = 'none';
        document.getElementById('start-camera-btn-container').style.display = 'block';
    };

    document.getElementById('start-countdown-btn').addEventListener('click', () => {
        startCountdown();
    });

    function startCountdown() {
        let countdownElement = document.getElementById('countdown-photo');
        let countdownNumber = document.getElementById('countdown-photo-number');
        let count = 3;

        countdownElement.style.display = 'block';
        document.getElementById('capture-buttons').style.display = 'none';

        let interval = setInterval(() => {
            count--;
            countdownNumber.textContent = count;

            if (count === 0) {
                clearInterval(interval);
                countdownElement.style.display = 'none';
                capturePhoto();
            }
        }, 1000);
    }

    function capturePhoto() {
        photoCanvas.width = webcamVideo.videoWidth;
        photoCanvas.height = webcamVideo.videoHeight;

        let context = photoCanvas.getContext('2d');
        context.drawImage(webcamVideo, 0, 0, photoCanvas.width, photoCanvas.height);

        photoData = photoCanvas.toDataURL('image/jpeg', 0.9);

        if (photoStream) {
            photoStream.getTracks().forEach(track => track.stop());
        }

        document.getElementById('webcam-container').style.display = 'none';
        document.getElementById('photo-preview-container').style.display = 'block';
    }

    document.getElementById('validate-photo-btn').addEventListener('click', () => {
        // Passer à l'étape vidéo
        document.getElementById('photo-section').style.display = 'none';
        document.getElementById('video-section').style.display = 'block';

        // Scroll vers la section vidéo
        document.getElementById('video-section').scrollIntoView({ behavior: 'smooth' });
    });

    document.getElementById('retake-photo-btn').addEventListener('click', async () => {
        document.getElementById('photo-preview-container').style.display = 'none';
        document.getElementById('webcam-container').style.display = 'block';
        document.getElementById('capture-buttons').style.display = 'block';

        try {
            photoStream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' }
            });
            webcamVideo.srcObject = photoStream;
            webcamVideo.style.display = 'block';
        } catch (error) {
            alert('Impossible de redémarrer la webcam.');
            console.error('Erreur webcam:', error);
        }
    });

    // ==================== PARTIE VIDÉO ====================

    window.requestMediaAccess = async function() {
        try {
            videoStream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
                audio: true
            });

            webcamVideoRecord.srcObject = videoStream;
            webcamVideoRecord.style.display = 'block';
            document.getElementById('start-video-buttons').style.display = 'none';

            // Démarrer l'enregistrement automatiquement après 2 secondes
            setTimeout(() => {
                startRecording();
            }, 2000);

        } catch (error) {
            console.error('Erreur accès média:', error);
            alert('Impossible d\'accéder à la caméra et au microphone. Veuillez autoriser l\'accès.');
        }
    };

    function startRecording() {
        recordedChunks = [];
        recordingSeconds = 0;

        try {
            const options = { mimeType: 'video/webm;codecs=vp8,opus' };
            mediaRecorder = new MediaRecorder(videoStream, options);

            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    recordedChunks.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                videoBlob = new Blob(recordedChunks, { type: 'video/webm' });
                previewVideo.src = URL.createObjectURL(videoBlob);
                console.log('Vidéo enregistrée:', videoBlob.size, 'bytes');
                showPreview();
            };

            mediaRecorder.start();
            document.getElementById('recording-indicator').style.display = 'block';

            // Timer d'enregistrement
            recordingTimer = setInterval(() => {
                recordingSeconds++;
                document.getElementById('recording-timer').textContent = recordingSeconds;

                if (recordingSeconds >= 15) {
                    stopRecording();
                }
            }, 1000);

        } catch (error) {
            console.error('Erreur MediaRecorder:', error);
            alert('Erreur lors du démarrage de l\'enregistrement.');
        }
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        if (recordingTimer) {
            clearInterval(recordingTimer);
        }
        document.getElementById('recording-indicator').style.display = 'none';
    }

    function showPreview() {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }
        document.getElementById('webcam-video-container').style.display = 'none';
        document.getElementById('video-preview-container').style.display = 'block';
    }

    window.changePlaybackSpeed = function(speed) {
        previewVideo.playbackRate = speed;
    };

    consentCheckbox.addEventListener('change', function() {
        validateVideoBtn.disabled = !this.checked;
    });

    document.getElementById('retake-video-btn').addEventListener('click', () => {
        videoBlob = null;
        recordedChunks = [];
        consentCheckbox.checked = false;
        validateVideoBtn.disabled = true;

        document.getElementById('video-preview-container').style.display = 'none';
        document.getElementById('webcam-video-container').style.display = 'block';
        document.getElementById('start-video-buttons').style.display = 'block';
        webcamVideoRecord.style.display = 'none';
    });

    // Soumettre la photo ET la vidéo
    validateVideoBtn.addEventListener('click', async () => {
        if (!consentCheckbox.checked || !videoBlob || !photoData) {
            alert('Veuillez accepter le consentement et vous assurer que la photo et la vidéo sont capturées.');
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('photo', photoData);
        formData.append('video', videoBlob, 'verification-video.webm');
        formData.append('consent', '1');

        validateVideoBtn.disabled = true;
        validateVideoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

        try {
            const response = await fetch('{{ route("video.recapture.submit") }}', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                window.location.href = '{{ route("dashboard") }}';
            } else {
                const data = await response.json();
                throw new Error(data.message || 'Erreur lors de l\'envoi');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'envoi. Veuillez réessayer.');
            validateVideoBtn.disabled = false;
            validateVideoBtn.innerHTML = '<i class="fas fa-check"></i> Soumettre ma nouvelle vérification';
        }
    });

    // Nettoyer les streams en quittant
    window.addEventListener('beforeunload', () => {
        if (photoStream) photoStream.getTracks().forEach(track => track.stop());
        if (videoStream) videoStream.getTracks().forEach(track => track.stop());
    });
});
</script>
@endpush
@endsection
