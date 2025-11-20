@extends('layouts.app')

@section('title', 'Vérification Email')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header gradient-bg text-white">
                    <i class="fas fa-envelope-open-text me-2"></i>Vérification de votre Adresse Email
                </div>

                <div class="card-body text-center py-5">
                    @if (session('resent'))
                        <div class="alert alert-success mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            Un nouveau lien de vérification a été envoyé à votre adresse email.
                        </div>
                    @endif

                    <div class="mb-4">
                        <i class="fas fa-envelope fa-4x text-primary mb-3"></i>
                        <h4>Vérifiez votre Email</h4>
                    </div>

                    <p class="mb-4">
                        Avant de continuer, veuillez vérifier votre boîte email pour un lien de vérification.<br>
                        Un email a été envoyé à <strong>{{ auth()->user()->email }}</strong>
                    </p>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Conseil :</strong> Vérifiez également votre dossier spam/courrier indésirable.
                    </div>

                    <div class="mt-4">
                        <p class="text-muted">Si vous n'avez pas reçu l'email :</p>
                        <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-paper-plane me-2"></i>Renvoyer l'Email de Vérification
                            </button>
                        </form>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour au Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
