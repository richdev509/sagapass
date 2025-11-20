@extends('admin.layouts.admin')

@section('title', 'Gestion des Administrateurs')
@section('page-title', 'Gestion des Administrateurs')
@section('page-subtitle', 'Gérer les comptes administrateurs et leurs permissions')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-users-cog me-2"></i>Liste des Administrateurs</span>
        <a href="{{ route('admin.admins.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Ajouter un Admin
        </a>
    </div>

    <div class="card-body p-0">
        @if($admins->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Nom</th>
                        <th width="20%">Email</th>
                        <th width="15%">Rôle</th>
                        <th width="10%">Statut</th>
                        <th width="15%">Date Création</th>
                        <th width="15%" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($admins as $admin)
                    <tr>
                        <td><strong>#{{ $admin->id }}</strong></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-2" style="width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px;">
                                    {{ strtoupper(substr($admin->name, 0, 1)) }}{{ strtoupper(substr($admin->name, 1, 1)) }}
                                </div>
                                <div>
                                    <strong>{{ $admin->name }}</strong>
                                </div>
                            </div>
                        </td>
                        <td>{{ $admin->email }}</td>
                        <td>
                            @if($admin->roles->isNotEmpty())
                                @foreach($admin->roles as $role)
                                <span class="badge bg-primary">{{ $role->name }}</span>
                                @endforeach
                            @else
                            <span class="badge bg-secondary">Aucun rôle</span>
                            @endif
                        </td>
                        <td>
                            @if($admin->status === 'active')
                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Actif</span>
                            @else
                            <span class="badge bg-danger"><i class="fas fa-ban"></i> Inactif</span>
                            @endif
                        </td>
                        <td>{{ $admin->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.admins.permissions', $admin) }}" class="btn btn-sm btn-outline-info" title="Gérer Permissions">
                                    <i class="fas fa-shield-alt"></i>
                                </a>

                                <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>

                                @if($admin->id !== Auth::guard('admin')->id())
                                <form method="POST" action="{{ route('admin.admins.toggle-status', $admin) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-{{ $admin->status === 'active' ? 'warning' : 'success' }}"
                                            title="{{ $admin->status === 'active' ? 'Désactiver' : 'Activer' }}"
                                            onclick="return confirm('Êtes-vous sûr de vouloir {{ $admin->status === 'active' ? 'désactiver' : 'activer' }} cet administrateur ?')">
                                        <i class="fas fa-{{ $admin->status === 'active' ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Supprimer"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ? Cette action est irréversible.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @else
                                <button class="btn btn-sm btn-outline-secondary" disabled title="Vous ne pouvez pas vous modifier vous-même">
                                    <i class="fas fa-lock"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($admins->hasPages())
        <div class="card-footer bg-white">
            {{ $admins->links() }}
        </div>
        @endif
        @else
        <div class="p-5 text-center">
            <i class="fas fa-users-cog fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun administrateur</h5>
        </div>
        @endif
    </div>
</div>
@endsection
