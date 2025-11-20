<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Connexion OAuth - SAGAPASS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .oauth-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
            flex-direction: row-reverse; /* Logo à droite, formulaire à gauche */
            min-height: 500px;
        }

        /* Section Logo SAGAPASS (à droite sur PC) */
        .logo-section {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .logo-section::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }

        .logo-section::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
        }

        .saga-logo {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .saga-logo i {
            font-size: 80px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .saga-logo h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .saga-logo p {
            font-size: 16px;
            opacity: 0.95;
            margin-bottom: 30px;
        }

        .app-info {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .app-info img {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .app-info .app-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .app-info h5 {
            font-size: 18px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .app-info small {
            opacity: 0.9;
            font-size: 13px;
        }

        /* Section Formulaire (à gauche sur PC) */
        .form-section {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 30px;
        }

        .form-header h2 {
            font-size: 28px;
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #718096;
            font-size: 14px;
        }

        .request-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .request-info i {
            color: #3b82f6;
            margin-right: 8px;
        }

        .request-info p {
            margin: 0;
            color: #1e40af;
            font-size: 14px;
            line-height: 1.5;
        }

        .request-info strong {
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            padding: 5px;
            font-size: 18px;
        }

        .password-toggle:hover {
            color: #2d3748;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me input {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .remember-me label {
            color: #4a5568;
            font-size: 14px;
            cursor: pointer;
            margin: 0;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
        }

        .footer-links {
            margin-top: 25px;
            text-align: center;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .security-badge {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .security-badge p {
            color: #718096;
            font-size: 13px;
            margin: 0;
        }

        .security-badge i {
            color: #10b981;
            margin-right: 5px;
        }

        /* Responsive pour mobile */
        @media (max-width: 768px) {
            .oauth-container {
                flex-direction: column; /* Empiler verticalement */
                max-width: 100%;
            }

            .logo-section {
                padding: 30px 20px;
                min-height: auto;
            }

            .saga-logo i {
                font-size: 60px;
            }

            .saga-logo h1 {
                font-size: 32px;
            }

            .saga-logo p {
                font-size: 14px;
            }

            .app-info {
                margin-top: 20px;
            }

            .form-section {
                padding: 30px 20px;
            }

            .form-header h2 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .oauth-container {
                border-radius: 12px;
            }

            .form-section {
                padding: 25px 20px;
            }

            .logo-section {
                padding: 25px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="oauth-container">
        <!-- Section Logo SAGAPASS (à droite sur PC) -->
        <div class="logo-section">
            <div class="saga-logo">
                <i class="fas fa-shield-alt"></i>
                <h1>SAGAPASS</h1>
                <p>Système d'Authentification et de Gestion d'Accès</p>
            </div>

            @if(session('oauth_app'))
            <div class="app-info">
                @if(session('oauth_app')['logo'])
                    <img src="{{ session('oauth_app')['logo'] }}" alt="{{ session('oauth_app')['name'] }}">
                @else
                    <i class="fas fa-cube app-icon"></i>
                @endif
                <h5>{{ session('oauth_app')['name'] }}</h5>
                <small><i class="fas fa-globe"></i> {{ session('oauth_app')['website'] }}</small>
            </div>
            @endif
        </div>

        <!-- Section Formulaire (à gauche sur PC) -->
        <div class="form-section">
            <div class="form-header">
                <h2>Connexion</h2>
                <p>Connectez-vous à votre compte SAGAPASS</p>
            </div>

            @if(session('oauth_app'))
            <div class="request-info">
                <i class="fas fa-info-circle"></i>
                <p>
                    <strong>{{ session('oauth_app')['name'] }}</strong> demande l'accès à votre compte SAGAPASS.
                </p>
            </div>
            @endif

            @if($errors->any())
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                @foreach($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('oauth.login.submit') }}">
                @csrf

                <!-- Préserver les paramètres OAuth -->
                <input type="hidden" name="client_id" value="{{ old('client_id', request('client_id')) }}">
                <input type="hidden" name="redirect_uri" value="{{ old('redirect_uri', request('redirect_uri')) }}">
                <input type="hidden" name="response_type" value="{{ old('response_type', request('response_type')) }}">
                <input type="hidden" name="scope" value="{{ old('scope', request('scope')) }}">
                <input type="hidden" name="state" value="{{ old('state', request('state')) }}">
                <input type="hidden" name="code_challenge" value="{{ old('code_challenge', request('code_challenge')) }}">
                <input type="hidden" name="code_challenge_method" value="{{ old('code_challenge_method', request('code_challenge_method')) }}">

                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="fas fa-envelope"></i> Adresse e-mail
                    </label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="votre@email.com"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            required
                            placeholder="••••••••"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <div class="footer-links">
                <a href="{{ route('register') }}">
                    <i class="fas fa-user-plus"></i> Créer un compte
                </a>
                <span style="color: #cbd5e0;">|</span>
                <a href="{{ route('password.request') }}">
                    <i class="fas fa-key"></i> Mot de passe oublié ?
                </a>
            </div>

            <div class="security-badge">
                <p>
                    <i class="fas fa-shield-check"></i>
                    Connexion sécurisée • Vos données sont protégées
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
