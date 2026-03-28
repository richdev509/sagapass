@extends('layouts.app')

@section('title', 'Contactez-nous')

@push('styles')
<style>
    .contact-section {
        padding: 6rem 0;
        background-color: var(--bg-light);
    }
    .contact-form {
        background-color: #fff;
        padding: 3rem;
        border-radius: 0.75rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .form-label {
        font-weight: 600;
        color: var(--text-dark);
    }
</style>
@endpush

@section('content')
<section class="contact-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold">Contactez-nous</h1>
                    <p class="lead text-muted">Une question ? Un projet ? Remplissez le formulaire et notre équipe vous répondra rapidement.</p>
                </div>

                <div class="contact-form">
                    <form action="#" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="business_name" class="form-label">Entreprise</label>
                                <input type="text" class="form-control" id="business_name" name="business_name">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description de votre besoin</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary btn-lg">Envoyer le message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

