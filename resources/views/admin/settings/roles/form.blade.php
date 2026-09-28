@extends('admin.layouts.index')

@section('title', isset($role->id) ? 'Edit Role' : 'Create Role')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>{{ isset($role->id) ? 'Role bearbeiten' : 'Neue Rolle' }}</h5>
            <a href="{{ route('admin.settings.roles.index') }}" class="btn btn-light btn-sm">Zurück</a>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ isset($role->id) ? route('admin.settings.roles.update', $role) : route('admin.settings.roles.store') }}">
                @csrf
                @if(isset($role->id))
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" value="{{ old('label', $role->label ?? '') }}" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_admin" value="1" class="form-check-input" {{ old('is_admin', $role->is_admin ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Admin role</label>
                        </div>
                    </div>
                </div>

                <hr>

                <h6 class="mb-3">Permissions</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Read</th>
                                <th>Write</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modules as $moduleKey => $moduleLabel)
                                @php
                                    $modulePermissions = $role->permissions ?? collect();
                                    $permissionIds = $modulePermissions->pluck('id')->all();
                                @endphp
                                <tr>
                                    <td>{{ $moduleLabel }}</td>
                                    @foreach(['read', 'write', 'delete'] as $action)
                                        @php
                                            $permission = \App\Models\Permission::where('module', $moduleKey)->where('action', $action)->first();
                                            $checked = $permission && in_array($permission->id, $permissionIds, true);
                                        @endphp
                                        <td class="text-center">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission?->id ?? '' }}" {{ $checked ? 'checked' : '' }}>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
