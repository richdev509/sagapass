@extends('admin.layouts.admin')

@section('title', 'Utilisateurs de l\'application')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('admin.oauth.show', $application) }}" class="btn btn-sm btn-secondary mb-2">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            <h2 class="mb-0">Utilisateurs de {{ $application->name }}</h2>
            <p class="text-muted mb-0">Liste des utilisateurs ayant autorisé cette application</p>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $authorizations->total() }}</h3>
                    <small>Total autorisations</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $authorizations->where('revoked_at', null)->count() }}</h3>
                    <small>Actives</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $authorizations->whereNotNull('revoked_at')->count() }}</h3>
                    <small>Révoquées</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des autorisations -->
    <div class="card">
        <div class="card-body">
            @if($authorizations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Scopes autorisés</th>
                                <th>Accordé le</th>
                                <th>Dernière utilisation</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($authorizations as $auth)
                                <tr>
                                    <td>
                                        <strong>{{ $auth->user->first_name }} {{ $auth->user->last_name }}</strong><br>
                                        <small class="text-muted">{{ $auth->user->email }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $authScopes = is_array($auth->scopes)
                                                ? $auth->scopes
                                                : json_decode($auth->scopes, true) ?? [];
                                        @endphp
                                        @foreach($authScopes as $scope)
                                            <span class="badge bg-secondary">{{ $scope }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        {{ $auth->granted_at->format('d/m/Y à H:i') }}
                                    </td>
                                    <td>
                                        @if($auth->last_used_at)
                                            {{ $auth->last_used_at->diffForHumans() }}<br>
                                            <small class="text-muted">{{ $auth->last_used_at->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">Jamais utilisée</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($auth->revoked_at)
                                            <span class="badge bg-danger">Révoquée</span><br>
                                            <small class="text-muted">{{ $auth->revoked_at->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$auth->revoked_at)
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#revokeModal{{ $auth->id }}">
                                                <i class="fas fa-ban"></i> Révoquer
                                            </button>

                                            <!-- Modal Révocation -->
                                            <div class="modal fade" id="revokeModal{{ $auth->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('admin.oauth.revoke-authorization', $auth) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">Révoquer l'autorisation</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Révoquer l'autorisation de <strong>{{ $auth->user->first_name }} {{ $auth->user->last_name }}</strong> pour cette application ?</p>
                                                                <div class="mb-3">
                                                                    <label for="revoke_reason{{ $auth->id }}" class="form-label">Raison *</label>
                                                                    <textarea name="revoke_reason"
                                                                              id="revoke_reason{{ $auth->id }}"
                                                                              class="form-control"
                                                                              rows="3"
                                                                              required
                                                                              placeholder="Ex: Activité suspecte détectée..."></textarea>
                                                                </div>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle"></i>
                                                                    L'utilisateur devra à nouveau autoriser l'application pour l'utiliser.
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" class="btn btn-danger">
                                                                    <i class="fas fa-ban"></i> Révoquer
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $authorizations->links() }}
                </div>
            @else
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i>
                    Aucun utilisateur n'a encore autorisé cette application.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
