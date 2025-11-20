@extends('layouts.app')

@section('title', 'Détails du Document')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="mb-3">
                <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-id-card me-2"></i>
                            {{ $document->document_type === 'cni' ? 'Carte Nationale d\'Identité' : 'Passeport' }}
                        </h4>
                        @if($document->verification_status === 'verified')
                            <span class="badge badge-status-verified">
                                <i class="fas fa-check-circle"></i> Vérifié
                            </span>
                        @elseif($document->verification_status === 'pending')
                            <span class="badge badge-status-pending">
                                <i class="fas fa-clock"></i> En attente de vérification
                            </span>
                        @else
                            <span class="badge badge-status-rejected">
                                <i class="fas fa-times-circle"></i> Rejeté
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Informations du document -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Informations</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="40%"><strong>Numéro :</strong></td>
                                    <td>{{ $document->document_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type :</strong></td>
                                    <td>
                                        @if($document->document_type === 'cni')
                                            <i class="fas fa-id-card text-primary me-1"></i>CNI
                                        @else
                                            <i class="fas fa-passport text-success me-1"></i>Passeport
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Date de délivrance :</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Date d'expiration :</strong></td>
                                    <td class="{{ \Carbon\Carbon::parse($document->expiry_date)->isPast() ? 'text-danger' : '' }}">
                                        {{ \Carbon\Carbon::parse($document->expiry_date)->format('d/m/Y') }}
                                        @if(\Carbon\Carbon::parse($document->expiry_date)->isPast())
                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                            <span class="badge bg-danger ms-1">Expiré</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Statut</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="40%"><strong>Ajouté le :</strong></td>
                                    <td>{{ $document->created_at->format('d/m/Y à H:i') }}</td>
                                </tr>
                                @if($document->verified_at)
                                    <tr>
                                        <td><strong>Vérifié le :</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($document->verified_at)->format('d/m/Y à H:i') }}</td>
                                    </tr>
                                @endif
                                @if($document->verified_by)
                                    <tr>
                                        <td><strong>Vérifié par :</strong></td>
                                        <td>{{ $document->verifiedBy->name ?? 'Admin' }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Dernière modification :</strong></td>
                                    <td>{{ $document->updated_at->format('d/m/Y à H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($document->verification_status === 'rejected' && $document->rejection_reason)
                        <div class="alert alert-danger mb-4">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Raison du rejet :</h6>
                            <p class="mb-0">{{ $document->rejection_reason }}</p>
                        </div>
                    @endif

                    @if($document->verification_status === 'verified')
                        <div class="alert alert-info mb-4">
                            <h6><i class="fas fa-shield-alt me-2"></i>Document Protégé</h6>
                            <p class="mb-0">
                                Ce document a été vérifié et validé officiellement. Pour des raisons de sécurité,
                                de traçabilité et de conformité légale, les documents vérifiés ne peuvent pas être
                                supprimés ou modifiés. Si vous avez besoin d'aide, veuillez contacter notre support.
                            </p>
                        </div>
                    @endif

                    @if($document->verification_status === 'pending')
                        <div class="alert alert-warning mb-4">
                            <h6><i class="fas fa-hourglass-half me-2"></i>Document en Cours de Vérification</h6>
                            <p class="mb-0">
                                Ce document est actuellement en attente de vérification par notre équipe.
                                Pour éviter toute perte de données et garantir le bon traitement de votre demande,
                                vous ne pouvez pas supprimer ce document pendant la vérification.
                                Si vous souhaitez annuler votre demande, veuillez contacter notre support.
                            </p>
                        </div>
                    @endif

                    <!-- Photos du document -->
                    <h6 class="text-muted mb-3">Photos du Document</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <small><strong>Recto (Face avant)</strong></small>
                                </div>
                                <div class="card-body text-center p-2">
                                    @if($document->front_photo_path)
                                        <img src="{{ route('documents.image', ['id' => $document->id, 'type' => 'front']) }}"
                                             alt="Photo recto"
                                             class="img-fluid rounded"
                                             style="max-height: 300px; cursor: pointer;"
                                             onclick="openImageModal(this.src)">
                                    @else
                                        <div class="text-muted py-5">
                                            <i class="fas fa-image fa-3x mb-2 opacity-50"></i>
                                            <p>Aucune image</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($document->back_photo_path)
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <small><strong>Verso (Face arrière)</strong></small>
                                    </div>
                                    <div class="card-body text-center p-2">
                                        <img src="{{ route('documents.image', ['id' => $document->id, 'type' => 'back']) }}"
                                             alt="Photo verso"
                                             class="img-fluid rounded"
                                             style="max-height: 300px; cursor: pointer;"
                                             onclick="openImageModal(this.src)">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            @if($document->verification_status === 'pending')
                                <a href="{{ route('documents.edit', $document->id) }}"
                                   class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Modifier
                                </a>
                            @endif
                        </div>
                        <div>
                            @if($document->verification_status === 'rejected')
                                <form method="POST"
                                      action="{{ route('documents.destroy', $document->id) }}"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash me-2"></i>Supprimer
                                    </button>
                                </form>
                            @else
                                <button type="button"
                                        class="btn btn-secondary"
                                        disabled
                                        title="{{ $document->verification_status === 'verified' ? 'Les documents vérifiés ne peuvent pas être supprimés' : 'Documents en attente protégés' }}">
                                    <i class="fas fa-lock me-2"></i>Document Protégé
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour agrandir l'image -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aperçu du Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="Document">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}
</script>
@endpush
