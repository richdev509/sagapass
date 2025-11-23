@extends('admin.layouts.admin')

@section('title', 'Vidéos approuvées')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">Vidéos approuvées</h1>
            <p class="text-muted mb-0">Liste des comptes avec vidéo validée</p>
        </div>
        <div>
            <a href="{{ route('admin.video-verification.index') }}" class="btn btn-outline-warning me-2">
                <i class="fas fa-clock"></i> En attente
            </a>
            <a href="{{ route('admin.video-verification.rejected') }}" class="btn btn-outline-danger">
                <i class="fas fa-times-circle"></i> Rejetées
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.video-verification.approved') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" placeholder="Nom, email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Niveau compte</label>
                    <select name="account_level" class="form-select">
                        <option value="">Tous</option>
                        <option value="basic" {{ request('account_level') === 'basic' ? 'selected' : '' }}>Basic</option>
                        <option value="verified" {{ request('account_level') === 'verified' ? 'selected' : '' }}>Verified</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date approbation (De)</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date approbation (À)</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Vidéos approuvées ({{ $users->total() }})</h5>
        </div>
        <div class="card-body p-0">
            @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Photo</th>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Niveau compte</th>
                                <th>Date approbation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>
                                    @if($user->profile_picture)
                                        <img src="{{ $user->profile_picture_url }}" alt="Photo" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $user->full_name }}</strong>
                                    <br>
                                    <small class="text-muted">ID: {{ $user->id }}</small>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->account_level === 'verified' ? 'success' : 'info' }}">
                                        {{ ucfirst($user->account_level) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $user->video_verified_at ? \Carbon\Carbon::parse($user->video_verified_at)->format('d/m/Y H:i') : '-' }}
                                    @if($user->video_verified_at)
                                    <br>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($user->video_verified_at)->diffForHumans() }}</small>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.video-verification.show', $user) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    {{ $users->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>Aucune vidéo approuvée</h5>
                    <p class="text-muted">Aucun résultat ne correspond à vos critères de recherche.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
