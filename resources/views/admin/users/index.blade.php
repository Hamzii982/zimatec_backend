@extends('admin.layouts.index')

@section('title', 'Manage Users')

@section('content')
<style>
    :root {
        --brand-blue: #002752;
        --brand-blue-hover: #001a3d;
        --brand-blue-light: #e8edf3;
    }

    .users-card .card-header {
        background: linear-gradient(135deg, var(--brand-blue), #2f6cb0);
    }

    .table-responsive-wrapper table td {
        vertical-align: middle;
    }

    /* --- User cell (avatar + name/email) --- */
    .user-cell {
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .avatar-circle {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--brand-blue), #4a7fc9);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        box-shadow: 0 2px 5px rgba(0, 39, 82, .25);
    }

    .user-cell .user-name {
        font-weight: 600;
        color: #212529;
        line-height: 1.2;
    }

    .user-cell .user-email {
        font-size: .8rem;
        color: #6c757d;
    }

    /* --- Role badges --- */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .7rem;
        border-radius: 20px;
        font-size: .78rem;
        font-weight: 600;
    }

    .role-admin {
        background: #fff3cd;
        color: #8a6500;
    }

    .role-user {
        background: var(--brand-blue-light);
        color: var(--brand-blue);
    }

    /* --- Firma badges --- */
    .firma-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .7rem;
        border-radius: 6px;
        font-size: .78rem;
        font-weight: 600;
        border: 1px solid transparent;
    }

    .firma-zf {
        background: var(--brand-blue-light);
        color: var(--brand-blue);
        border-color: rgba(0, 39, 82, .15);
    }

    .firma-other {
        background: #eafaf1;
        color: #1e7e4d;
        border-color: rgba(30, 126, 77, .15);
    }

    /* --- Machine user switch --- */
    .machine-switch .form-check-input {
        width: 2.75em;
        height: 1.4em;
        cursor: pointer;
        border: 1px solid #ced4da;
    }

    .machine-switch .form-check-input:checked {
        background-color: var(--brand-blue);
        border-color: var(--brand-blue);
    }

    .machine-switch .form-check-input:focus {
        box-shadow: 0 0 0 .2rem rgba(0, 39, 82, .25);
    }

    /* --- Action buttons --- */
    .btn-outline-brand {
        border-color: var(--brand-blue);
        color: var(--brand-blue);
    }

    .btn-outline-brand:hover {
        background: var(--brand-blue);
        color: #fff;
    }
</style>

<div class="container mt-4">
    <div class="card users-card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Alle Benutzer</h5>
            <a href="{{ route('admin.users.create') }}" class="btn btn-light btn-sm fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Neue Benutzer
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive-wrapper">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Benutzer</th>
                            <th>Role</th>
                            <th>Firma</th>
                            <th>Machine Nutzer</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    <div class="user-cell">
                                        <div class="avatar-circle">
                                            {{ $user->role === 'admin' ? '🧑‍💼' : '🙂' }}
                                        </div>
                                        <div>
                                            <div class="user-name">{{ $user->name }}</div>
                                            <div class="user-email">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if($user->role === 'admin')
                                        <span class="role-badge role-admin">
                                            <i class="bi bi-shield-lock-fill"></i> Admin
                                        </span>
                                    @else
                                        <span class="role-badge role-user">
                                            <i class="bi bi-person-fill"></i> User
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span class="firma-badge {{ $user->company === 'ZF' ? 'firma-zf' : 'firma-other' }}">
                                        <i class="bi bi-building"></i> {{ ucfirst($user->getCompanyName()) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="form-check form-switch machine-switch">
                                        <input type="checkbox" class="form-check-input toggle-active"
                                            data-id="{{ $user->id }}" {{ $user->machine_user ? 'checked' : '' }}>
                                    </div>
                                </td>

                                <td>{{ $user->created_at->diffForHumans() }}</td>

                                <td>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                    class="btn btn-sm btn-outline-brand">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form action="{{ route('admin.users.delete', $user) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Delete this user?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-people d-block mb-2" style="font-size:1.5rem;"></i>
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $users->links() }}
</div>

{{-- === AJAX Script for toggle === --}}
<script>
document.querySelectorAll('.toggle-active').forEach((checkbox) => {
    checkbox.addEventListener('change', function() {
        const id = this.dataset.id;
        fetch(`/admin/users/machine-user/toggle/${id}`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const status = data.active ? 'activated' : 'deactivated';
                console.log(`Machine User ${id} ${status}`);
                showAlert(`Machine User ${status} successfully.`, 'success');
            } else {
                showAlert('Something went wrong.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Server error, please try again.', 'danger');
        });
    });
});
</script>
@endsection