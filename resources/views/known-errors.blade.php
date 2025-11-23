<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreurs Connues - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .error-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .error-card:hover {
            transform: translateY(-5px);
        }

        .error-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .error-video { background: #fef3c7; color: #f59e0b; }
        .error-email { background: #dbeafe; color: #3b82f6; }
        .error-server { background: #fecaca; color: #ef4444; }
        .error-upload { background: #d1fae5; color: #10b981; }
        .error-badge { background: #e9d5ff; color: #a855f7; }

        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 3rem;
            margin-bottom: 2rem;
        }

        .page-title {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .whatsapp-btn {
            background: #25d366;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .whatsapp-btn:hover {
            background: #128c7e;
            color: white;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <div class="text-center mb-4">
                <h1 class="page-title">
                    <i class="fas fa-book-open"></i>
                    Erreurs Connues
                </h1>
                <p class="text-muted">Liste des problèmes courants et leurs solutions</p>
            </div>

            <!-- Erreur Vidéo -->
            <div class="error-card">
                <div class="d-flex align-items-start">
                    <div class="error-icon error-video">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-2">Problème de Vidéo de Vérification</h4>
                        <p class="text-muted mb-2">
                            <strong>Symptôme :</strong> La vidéo ne se charge pas ou est rejetée
                        </p>
                        <p class="mb-0">
                            <strong>Solutions :</strong>
                        </p>
                        <ul class="mt-2">
                            <li>Assurez-vous d'avoir un bon éclairage (lumière naturelle de préférence)</li>
                            <li>Filmez-vous dans un environnement calme sans bruit de fond</li>
                            <li>Parlez clairement et distinctement</li>
                            <li>Vérifiez que la vidéo ne dépasse pas 10 MB</li>
                            <li>Utilisez un format supporté : MP4, MOV, AVI</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Erreur Email -->
            <div class="error-card">
                <div class="d-flex align-items-start">
                    <div class="error-icon error-email">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-2">Email de Vérification Non Reçu</h4>
                        <p class="text-muted mb-2">
                            <strong>Symptôme :</strong> L'email de vérification n'arrive pas
                        </p>
                        <p class="mb-0">
                            <strong>Solutions :</strong>
                        </p>
                        <ul class="mt-2">
                            <li>Vérifiez votre dossier spam/courrier indésirable</li>
                            <li>Ajoutez contact@mykaypa.com à vos contacts</li>
                            <li>Patientez 5-10 minutes (délai de livraison possible)</li>
                            <li>Demandez un nouvel email via le bouton "Renvoyer"</li>
                            <li>Vérifiez que l'adresse email est correcte</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Erreur Serveur -->
            <div class="error-card">
                <div class="d-flex align-items-start">
                    <div class="error-icon error-server">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-2">Serveur en Maintenance</h4>
                        <p class="text-muted mb-2">
                            <strong>Symptôme :</strong> Message "Système en maintenance" ou page d'erreur 503
                        </p>
                        <p class="mb-0">
                            <strong>Solutions :</strong>
                        </p>
                        <ul class="mt-2">
                            <li>Patientez 5-10 minutes et réessayez</li>
                            <li>Videz le cache de votre navigateur (Ctrl+F5)</li>
                            <li>La maintenance est généralement courte (moins de 15 minutes)</li>
                            <li>Consultez nos réseaux sociaux pour les mises à jour</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Erreur Upload -->
            <div class="error-card">
                <div class="d-flex align-items-start">
                    <div class="error-icon error-upload">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-2">Échec de Téléversement de Document</h4>
                        <p class="text-muted mb-2">
                            <strong>Symptôme :</strong> Le document ne se télécharge pas ou reste bloqué
                        </p>
                        <p class="mb-0">
                            <strong>Solutions :</strong>
                        </p>
                        <ul class="mt-2">
                            <li>Vérifiez la taille du fichier (max 5 MB par fichier)</li>
                            <li>Utilisez les formats acceptés : PDF, JPG, PNG</li>
                            <li>Assurez-vous d'avoir une connexion internet stable</li>
                            <li>Réessayez avec un autre navigateur</li>
                            <li>Compressez vos images si elles sont trop volumineuses</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Erreur Badge QR -->
            <div class="error-card">
                <div class="d-flex align-items-start">
                    <div class="error-icon error-badge">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-2">Badge Numérique Expiré ou Invalide</h4>
                        <p class="text-muted mb-2">
                            <strong>Symptôme :</strong> Le code QR ne fonctionne pas ou est expiré
                        </p>
                        <p class="mb-0">
                            <strong>Solutions :</strong>
                        </p>
                        <ul class="mt-2">
                            <li>Les badges expirent après 12 heures</li>
                            <li>Générez un nouveau badge depuis votre tableau de bord</li>
                            <li>Vérifiez que votre compte est au niveau "Basic" minimum</li>
                            <li>Assurez-vous que votre vidéo a été approuvée</li>
                            <li>Un seul badge actif par utilisateur à la fois</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section Contact -->
            <div class="text-center mt-5">
                <h4 class="mb-3">Problème Non Résolu ?</h4>
                <p class="text-muted mb-4">
                    Si votre problème persiste, contactez notre support technique via WhatsApp
                </p>
                <a href="{{ \App\Models\SystemSetting::getWhatsAppLink() }}"
                   target="_blank"
                   class="whatsapp-btn">
                    <i class="fab fa-whatsapp me-2"></i>
                    Contacter le Support WhatsApp
                </a>
            </div>

            <!-- Bouton Retour -->
            <div class="text-center mt-4">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Retour
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
