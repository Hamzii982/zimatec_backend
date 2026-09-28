@extends('admin.layouts.index')

@section('title', 'Role Management')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Roles</h5>
            <a href="{{ route('admin.settings.roles.create') }}" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Neue Rolle
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Label</th>
                            <th>Admin</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td>{{ $role->label }}</td>
                                <td>
                                    @if($role->is_admin)
                                        <span class="badge bg-success">Ja</span>
                                    @else
                                        <span class="badge bg-secondary">Nein</span>
                                    @endif
                                </td>
                                <td>{{ $role->permissions->count() }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.settings.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.settings.roles.destroy', $role) }}" onsubmit="return confirm('Delete role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No roles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
