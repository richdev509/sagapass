@extends('admin.layouts.admin')

@section('title', 'Liste des Permissions')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-list-check me-2"></i>
                Liste des Permissions
            </h2>
            <p class="text-muted mb-0">
                Toutes les permissions disponibles dans le système
            </p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Retour aux rôles
        </a>
    </div>

    {{-- Liste des permissions par catégorie --}}
    @foreach($permissions as $category => $categoryPermissions)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-capitalize">
                    <i class="fas fa-folder-open me-2"></i>
                    {{ ucfirst($category) }}
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Permission</th>
                                <th>Description</th>
                                <th style="width: 20%;" class="text-center">Nombre de rôles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryPermissions as $permission)
                                <tr>
                                    <td>
                                        <code>{{ $permission->name }}</code>
                                    </td>
                                    <td>
                                        @if($permission->description)
                                            {{ $permission->description }}
                                        @else
                                            <span class="text-muted fst-italic">Aucune description</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            {{ $permission->roles->count() }} rôle(s)
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
