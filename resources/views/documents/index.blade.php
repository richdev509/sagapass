@extends('layouts.app')

@section('title', 'Mes Documents')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="fas fa-folder-open me-2"></i>Mes Documents
                </h2>
                @php
                    $user = auth()->user();
                    $canAddDocument = $user->email_verified_at && $user->phone && $user->date_of_birth;
                @endphp

                @if($canAddDocument)
                    <a href="{{ route('documents.create') }}" class="btn btn-primary-custom">
                        <i class="fas fa-plus me-2"></i>Ajouter un Document
                    </a>
                @else
                    <button class="btn btn-secondary" disabled
                            title="@if(!$user->email_verified_at) Vérifiez votre email @elseif(!$user->phone || !$user->date_of_birth) Complétez votre profil @endif pour ajouter des documents">
                        <i class="fas fa-lock me-2"></i>Ajouter un Document
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Messages flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Liste des documents -->
    @if($documents->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-50"></i>
                <h4 class="text-muted">Aucun document pour le moment</h4>
                <p class="text-muted mb-4">Commencez par ajouter votre premier document d'identité</p>
                @if($canAddDocument)
                    <a href="{{ route('documents.create') }}" class="btn btn-primary-custom">
                        <i class="fas fa-plus me-2"></i>Ajouter un Document
                    </a>
                @else
                    <div class="alert alert-warning d-inline-block">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        @if(!$user->email_verified_at)
                            Vérifiez votre email pour ajouter des documents
                        @else
                            Complétez votre profil (téléphone et date de naissance) pour ajouter des documents
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="row">
            @foreach($documents as $document)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-id-card text-primary me-2"></i>
                                        {{ $document->document_type === 'cni' ? 'Carte Nationale' : 'Passeport' }}
                                    </h5>
                                    <small class="text-muted">N° {{ $document->document_number }}</small>
                                </div>
                                @if($document->verification_status === 'verified')
                                    <span class="badge badge-status-verified">
                                        <i class="fas fa-check"></i> Vérifié
                                    </span>
                                @elseif($document->verification_status === 'pending')
                                    <span class="badge badge-status-pending">
                                        <i class="fas fa-clock"></i> En attente
                                    </span>
                                @else
                                    <span class="badge badge-status-rejected">
                                        <i class="fas fa-times"></i> Rejeté
                                    </span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-plus me-1"></i>Délivré le :
                                    </small>
                                    <small>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</small>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-times me-1"></i>Expire le :
                                    </small>
                                    <small class="{{ \Carbon\Carbon::parse($document->expiry_date)->isPast() ? 'text-danger' : '' }}">
                                        {{ \Carbon\Carbon::parse($document->expiry_date)->format('d/m/Y') }}
                                        @if(\Carbon\Carbon::parse($document->expiry_date)->isPast())
                                            <i class="fas fa-exclamation-triangle"></i>
                                        @endif
                                    </small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Ajouté le :
                                    </small>
                                    <small>{{ $document->created_at->format('d/m/Y à H:i') }}</small>
                                </div>
                            </div>

                            @if($document->verification_status === 'rejected' && $document->rejection_reason)
                                <div class="alert alert-danger py-2 mb-3">
                                    <small>
                                        <strong>Raison du rejet :</strong><br>
                                        {{ $document->rejection_reason }}
                                    </small>
                                </div>
                            @endif

                            <div class="d-flex gap-2">
                                <a href="{{ route('documents.show', $document->id) }}"
                                   class="btn btn-sm btn-outline-primary flex-grow-1">
                                    <i class="fas fa-eye me-1"></i>Voir
                                </a>

                                @if($document->verification_status === 'pending')
                                    <a href="{{ route('documents.edit', $document->id) }}"
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif

                                @if($document->verification_status === 'rejected')
                                    <form method="POST"
                                          action="{{ route('documents.destroy', $document->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            disabled
                                            title="{{ $document->verification_status === 'verified' ? 'Les documents vérifiés ne peuvent pas être supprimés' : 'Documents en attente de vérification protégés' }}">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $documents->links() }}
        </div>
    @endif
</div>
@endsection
