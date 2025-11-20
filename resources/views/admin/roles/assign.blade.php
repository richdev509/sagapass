@extends('admin.layouts.admin')

@section('title', 'Attribuer des Rôles')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-user-shield me-2"></i>
                        Attribuer des Rôles
                    </h2>
                    <p class="text-muted mb-0">
                        Administrateur: <strong>{{ $admin->name }}</strong>
                    </p>
                </div>
                <a href="{{ route('admin.admins.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    Retour
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.admins.assign-roles', $admin) }}">
                        @csrf

                        {{-- Info admin --}}
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Sélectionnez un ou plusieurs rôles pour cet administrateur. Les permissions seront cumulées.
                        </div>

                        {{-- Rôles disponibles --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">
                                <i class="fas fa-user-tag me-2"></i>
                                Rôles Disponibles
                            </label>

                            @foreach($roles as $role)
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="roles[]"
                                                   value="{{ $role->name }}"
                                                   id="role_{{ $role->id }}"
                                                   {{ in_array($role->name, old('roles', $adminRoles)) ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="role_{{ $role->id }}">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong class="d-block mb-1">
                                                            @if($role->name === 'super-admin')
                                                                <i class="fas fa-crown text-warning me-2"></i>
                                                            @else
                                                                <i class="fas fa-user-tag text-primary me-2"></i>
                                                            @endif
                                                            {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                                        </strong>
                                                        @if($role->description)
                                                            <small class="text-muted">{{ $role->description }}</small>
                                                        @endif
                                                    </div>
                                                    <span class="badge bg-secondary">
                                                        {{ $role->permissions->count() }} permissions
                                                    </span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Boutons --}}
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.admins.index') }}" class="btn btn-light">
                                <i class="fas fa-times me-1"></i>
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
