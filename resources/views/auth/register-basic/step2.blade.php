@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Créer votre SagaPass Basic</h4>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 66%"></div>
                    </div>
                    <small class="text-muted">Étape 2/3 : Photo de profil</small>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-camera"></i> <strong>Photo de profil</strong>
                        <p class="mb-0 mt-2">Prenez une photo claire de votre visage avec votre webcam. Cette photo apparaîtra sur votre profil.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="photo-capture-container">
                        <!-- Webcam Video -->
                        <div id="webcam-container" class="text-center mb-3">
                            <video id="webcam-video" width="100%" height="auto" autoplay style="max-width: 500px; border-radius: 10px; border: 3px solid #ddd; display: none;"></video>

                            <div id="countdown" class="mt-3" style="display: none;">
                                <h2 class="text-primary mb-0"><span id="countdown-number">3</span></h2>
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
                                <button type="button" class="btn btn-outline-secondary" onclick="window.location='{{ route('register.basic.step1') }}'">
                                    <i class="fas fa-arrow-left"></i> Retour
                                </button>
                            </div>
                        </div>

                        <!-- Preview Photo -->
                        <div id="photo-preview-container" style="display: none;" class="text-center">
                            <h5 class="mb-3">Aperçu de votre photo</h5>
                            <canvas id="photo-canvas" style="max-width: 500px; width: 100%; border-radius: 10px; border: 3px solid #28a745;"></canvas>

                            <div class="mt-3">
                                <button type="button" class="btn btn-success btn-lg" id="validate-photo-btn">
                                    <i class="fas fa-check"></i> Valider cette photo
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="retake-photo-btn">
                                    <i class="fas fa-redo"></i> Reprendre
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire caché -->
                    <form method="POST" action="{{ route('register.basic.step2.submit') }}" id="photo-form" style="display: none;">
                        @csrf
                        <input type="hidden" name="photo" id="photo-data">
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
    let webcamVideo = document.getElementById('webcam-video');
    let photoCanvas = document.getElementById('photo-canvas');
    let photoData = '';

    // Fonction pour démarrer la webcam (appelée directement depuis onclick)
    window.startCamera = async function() {
        try {
            videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user'
                }
            });
            webcamVideo.srcObject = videoStream;

            // Afficher la vidéo et masquer le bouton de démarrage
            webcamVideo.style.display = 'block';
            document.getElementById('start-camera-btn-container').style.display = 'none';
            document.getElementById('capture-buttons').style.display = 'block';
        } catch (error) {
            console.error('Erreur webcam:', error);

            let errorMsg = 'Impossible d\'accéder à la webcam.';
            if (error.name === 'NotAllowedError') {
                errorMsg = 'Vous avez refusé l\'accès à la webcam. Veuillez autoriser l\'accès dans les paramètres de votre navigateur et actualiser la page.';
            } else if (error.name === 'NotFoundError') {
                errorMsg = 'Aucune webcam détectée sur votre appareil.';
            } else if (error.name === 'NotReadableError') {
                errorMsg = 'Votre webcam est déjà utilisée par une autre application.';
            }

            alert(errorMsg);
        }
    };

    // Fonction pour arrêter la webcam
    window.stopWebcam = function() {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
            videoStream = null;
        }
        webcamVideo.style.display = 'none';
        document.getElementById('capture-buttons').style.display = 'none';
        document.getElementById('start-camera-btn-container').style.display = 'block';
    };

    // Bouton démarrer countdown
    document.getElementById('start-countdown-btn').addEventListener('click', () => {
        startCountdown();
    });

    // Countdown avant capture
    function startCountdown() {
        let countdownElement = document.getElementById('countdown');
        let countdownNumber = document.getElementById('countdown-number');
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

    // Capturer la photo
    function capturePhoto() {
        // Configurer le canvas avec les dimensions de la vidéo
        photoCanvas.width = webcamVideo.videoWidth;
        photoCanvas.height = webcamVideo.videoHeight;

        // Dessiner l'image de la vidéo sur le canvas
        let context = photoCanvas.getContext('2d');
        context.drawImage(webcamVideo, 0, 0, photoCanvas.width, photoCanvas.height);

        // Convertir en base64
        photoData = photoCanvas.toDataURL('image/jpeg', 0.9);

        // Arrêter la webcam
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }

        // Afficher le preview
        document.getElementById('webcam-container').style.display = 'none';
        document.getElementById('photo-preview-container').style.display = 'block';
    }

    // Bouton valider
    document.getElementById('validate-photo-btn').addEventListener('click', () => {
        document.getElementById('photo-data').value = photoData;
        document.getElementById('photo-form').submit();
    });

    // Bouton reprendre
    document.getElementById('retake-photo-btn').addEventListener('click', async () => {
        // Réafficher la webcam
        document.getElementById('photo-preview-container').style.display = 'none';
        document.getElementById('webcam-container').style.display = 'block';
        document.getElementById('capture-buttons').style.display = 'block';

        // Redémarrer la webcam
        try {
            videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user'
                }
            });
            webcamVideo.srcObject = videoStream;
        } catch (error) {
            alert('Impossible de redémarrer la webcam.');
            console.error('Erreur webcam:', error);
        }
    });

    // Arrêter la webcam si l'utilisateur quitte la page
    window.addEventListener('beforeunload', () => {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }
    });
});
</script>
@endpush
@endsection
