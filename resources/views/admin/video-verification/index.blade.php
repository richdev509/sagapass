@extends('admin.layouts.admin')

@section('title', 'Vérification des vidéos - En attente')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">Vérification des vidéos</h1>
            <p class="text-muted mb-0">Comptes Basic en attente de validation vidéo</p>
        </div>
        <div>
            <a href="{{ route('admin.video-verification.approved') }}" class="btn btn-outline-success me-2">
                <i class="fas fa-check-circle"></i> Approuvées
            </a>
            <a href="{{ route('admin.video-verification.rejected') }}" class="btn btn-outline-danger">
                <i class="fas fa-times-circle"></i> Rejetées
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">En attente</h6>
                            <h3 class="mb-0">{{ $stats['pending'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">Approuvées</h6>
                            <h3 class="mb-0">{{ $stats['approved'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">Rejetées</h6>
                            <h3 class="mb-0">{{ $stats['rejected'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar-day fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">Aujourd'hui</h6>
                            <h3 class="mb-0">{{ $stats['today'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.video-verification.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" placeholder="Nom, email, téléphone..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date de soumission (De)</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date de soumission (À)</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des vidéos -->
    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-video"></i> Vidéos en attente ({{ $users->total() }})</h5>
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
                                <th>Téléphone</th>
                                <th>Date inscription</th>
                                <th>Niveau compte</th>
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
                                <td>{{ $user->phone ?? '-' }}</td>
                                <td>
                                    {{ $user->created_at->format('d/m/Y H:i') }}
                                    <br>
                                    <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <i class="fas fa-user"></i> {{ ucfirst($user->account_level) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.video-verification.show', $user) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Vérifier
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
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>Aucune vidéo en attente</h5>
                    <p class="text-muted">Toutes les vidéos ont été traitées !</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
