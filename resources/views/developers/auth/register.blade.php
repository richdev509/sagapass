<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devenir Développeur - SAGAPASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            max-width: 650px;
            margin: 50px auto;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .register-header p {
            margin: 0;
            opacity: 0.9;
        }
        .register-body {
            padding: 40px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            width: 100%;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .info-box {
            background: #f0f7ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .info-box h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .info-box ul {
            margin: 0;
            padding-left: 20px;
        }
        .info-box li {
            color: #666;
            margin-bottom: 5px;
        }
        .section-divider {
            border-top: 2px solid #e0e0e0;
            margin: 30px 0;
            position: relative;
        }
        .section-divider span {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 0 15px;
            color: #667eea;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <h1><i class="fas fa-code"></i> Devenir Développeur</h1>
                    <p>Transformez votre compte SAGAPASS en compte développeur</p>
                </div>

                <div class="register-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-circle"></i> Erreur :</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="info-box">
                        <h6><i class="fas fa-info-circle me-2"></i>Prérequis</h6>
                        <ul>
                            <li>Vous devez avoir un <strong>compte SAGAPASS vérifié</strong></li>
                            <li>Utilisez vos identifiants SAGAPASS pour créer votre profil développeur</li>
                            <li>Ajoutez les informations de votre entreprise/projet</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('developers.register.store') }}">
                        @csrf

                        <!-- Authentification SAGAPASS -->
                        <h6 class="mb-3" style="color: #667eea; font-weight: 600;">
                            <i class="fas fa-shield-alt me-2"></i>Authentification SAGAPASS
                        </h6>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email SAGAPASS *
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}"
                                   placeholder="votre@email.com" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">L'email de votre compte SAGAPASS existant</small>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Mot de passe SAGAPASS *
                            </label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" placeholder="••••••••" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Le mot de passe de votre compte SAGAPASS</small>
                        </div>

                        <!-- Divider -->
                        <div class="section-divider">
                            <span>Informations Professionnelles</span>
                        </div>

                        <div class="mb-3">
                            <label for="company_name" class="form-label">
                                <i class="fas fa-building"></i> Nom de l'entreprise / Projet *
                            </label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror"
                                   id="company_name" name="company_name" value="{{ old('company_name') }}"
                                   placeholder="Ex: MonEntreprise SARL" required>
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Le nom qui apparaîtra sur vos applications OAuth</small>
                        </div>

                        <div class="mb-3">
                            <label for="developer_website" class="form-label">
                                <i class="fas fa-globe"></i> Site web
                            </label>
                            <input type="url" class="form-control @error('developer_website') is-invalid @enderror"
                                   id="developer_website" name="developer_website" value="{{ old('developer_website') }}"
                                   placeholder="https://www.exemple.com">
                            @error('developer_website')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="developer_bio" class="form-label">
                                <i class="fas fa-align-left"></i> Description de votre activité
                            </label>
                            <textarea class="form-control @error('developer_bio') is-invalid @enderror"
                                      id="developer_bio" name="developer_bio" rows="4"
                                      placeholder="Décrivez brièvement votre activité, vos projets ou l'utilisation que vous comptez faire de SAGAPASS...">{{ old('developer_bio') }}</textarea>
                            @error('developer_bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maximum 1000 caractères</small>
                        </div>

                        <!-- Terms and conditions -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('terms') is-invalid @enderror"
                                       type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms" style="color: #666;">
                                    J'accepte les <a href="#" target="_blank">conditions d'utilisation développeur</a>
                                    et la <a href="#" target="_blank">politique de confidentialité</a>
                                </label>
                                @error('terms')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register">
                            <i class="fas fa-rocket"></i> Créer mon profil développeur
                        </button>
                    </form>

                    <div class="login-link">
                        Vous avez déjà un profil développeur ?
                        <a href="{{ route('developers.login') }}">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </a>
                    </div>

                    <div class="login-link mt-2">
                        Pas encore de compte SAGAPASS ?
                        <a href="{{ route('register') }}">
                            <i class="fas fa-user-plus"></i> Créer un compte citoyen
                        </a>
                    </div>

                    <div class="login-link mt-2">
                        <a href="{{ url('/') }}">
                            <i class="fas fa-arrow-left"></i> Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
