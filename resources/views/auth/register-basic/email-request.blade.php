@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>
                        Créer votre compte SAGAPASS
                    </h4>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-white" role="progressbar" style="width: 10%"></div>
                    </div>
                    <small class="mt-2 d-block opacity-75">Étape 1/4 : Vérification email</small>
                </div>

                <div class="card-body p-4">
                    <div class="alert alert-info">
                        <i class="fas fa-shield-check me-2"></i>
                        <strong>Sécurité renforcée</strong>
                        <p class="mb-0 mt-2 small">
                            Pour protéger votre identité, nous devons d'abord vérifier votre adresse email.
                            Vous recevrez un code de vérification à 6 chiffres.
                        </p>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.basic.send-code') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope me-1"></i>
                                Votre adresse email *
                            </label>
                            <input type="email"
                                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="exemple@email.com"
                                   required
                                   autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Utilisez un email permanent (Gmail, Yahoo, Outlook, etc.)
                            </small>
                        </div>

                        <div class="alert alert-warning small">
                            <strong><i class="fas fa-info-circle me-1"></i>Emails acceptés :</strong>
                            <p class="mb-0 mt-1">
                                Fournisseurs populaires (Gmail, Yahoo, Outlook, etc.) et emails professionnels avec domaine vérifié.
                            </p>
                            <p class="mb-0 mt-1 text-danger">
                                <i class="fas fa-ban me-1"></i>
                                Les emails temporaires ou jetables ne sont pas autorisés.
                            </p>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                Envoyer le code de vérification
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <p class="text-muted mb-0 small">
                                Vous avez déjà un compte ?
                                <a href="{{ route('login') }}">Se connecter</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3 border-0 bg-light">
                <div class="card-body small text-muted">
                    <i class="fas fa-lock me-1"></i>
                    <strong>Protection de vos données :</strong>
                    Votre email sera chiffré et utilisé uniquement pour vous contacter.
                    Nous ne le partagerons jamais avec des tiers.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
