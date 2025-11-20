@extends('admin.layouts.admin')

@section('title', 'Gestion des Rôles')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-user-tag me-2"></i>
                Gestion des Rôles
            </h2>
            <p class="text-muted mb-0">
                Gérez les rôles et leurs permissions
            </p>
        </div>
        @can('create-roles')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Créer un rôle
            </a>
        @endcan
    </div>

    {{-- Messages --}}
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

    {{-- Liste des rôles --}}
    <div class="row g-4">
        @foreach($roles as $role)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">
                                    @if($role->name === 'super-admin')
                                        <i class="fas fa-crown text-warning me-2"></i>
                                    @else
                                        <i class="fas fa-user-tag text-primary me-2"></i>
                                    @endif
                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                </h5>
                                @if($role->name === 'super-admin')
                                    <span class="badge bg-warning text-dark">Protégé</span>
                                @endif
                            </div>
                            @if($role->name !== 'super-admin')
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('edit-roles')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.roles.edit', $role) }}">
                                                    <i class="fas fa-edit me-2"></i>Modifier
                                                </a>
                                            </li>
                                        @endcan
                                        @can('delete-roles')
                                            <li>
                                                <form method="POST" action="{{ route('admin.roles.delete', $role) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash me-2"></i>Supprimer
                                                    </button>
                                                </form>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            @endif
                        </div>

                        @if($role->description)
                            <p class="text-muted small mb-3">{{ $role->description }}</p>
                        @endif

                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-shield-alt text-success me-2"></i>
                            <span class="fw-semibold">{{ $role->permissions_count }}</span>
                            <span class="text-muted ms-1">permission(s)</span>
                        </div>

                        @if($role->name !== 'super-admin' && auth('admin')->user()->can('edit-roles'))
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary w-100 mt-3">
                                <i class="fas fa-cog me-1"></i>
                                Gérer les permissions
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Lien vers les permissions --}}
    @can('view-permissions')
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-1">
                            <i class="fas fa-list-check me-2"></i>
                            Liste des permissions
                        </h6>
                        <p class="text-muted small mb-0">Consultez toutes les permissions disponibles dans le système</p>
                    </div>
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i>
                        Voir les permissions
                    </a>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection
