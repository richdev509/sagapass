<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - SAGAPASS</title>
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
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i class="fas fa-exclamation-triangle error-icon"></i>
        <h2 class="mb-3">Erreur de vérification</h2>
        <p class="text-muted mb-4">{{ $error }}</p>

        @if(isset($details))
            <div class="alert alert-danger text-start">
                <small>{{ $details }}</small>
            </div>
        @endif

        <button class="btn btn-primary" onclick="window.close()">
            <i class="fas fa-times me-2"></i>Fermer
        </button>
    </div>

    <script>
        // Notifier le parent en cas d'erreur
        if (window.opener) {
            window.opener.postMessage({
                type: 'SAGAPASS_VERIFICATION_ERROR',
                error: '{{ $error }}'
            }, '*');
        }
    </script>
</body>
</html>
