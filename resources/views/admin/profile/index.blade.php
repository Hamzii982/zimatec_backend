@extends('admin.layouts.index')

@section('title', 'Mein Profil')

@section('content')
<style>
    :root {
        --brand-blue: #002752;
        --brand-blue-hover: #001a3d;
        --brand-blue-light: #e8edf3;
    }

    .user-form-card {
        max-width: 760px;
        margin: 0 auto;
        overflow: hidden;
    }

    .user-form-card .card-header {
        background: linear-gradient(135deg, var(--brand-blue), var(--brand-blue-hover));
    }

    .user-avatar-lg {
        width: 88px;
        height: 88px;
        margin: -44px auto 0;
        border-radius: 50%;
        background: #fff;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0, 39, 82, .25);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 2;
    }

    .user-avatar-lg i {
        font-size: 3rem;
        color: var(--brand-blue);
    }

    .form-label {
        font-weight: 600;
        font-size: .85rem;
        color: #495057;
    }

    .user-form-card .input-group-text {
        background: var(--brand-blue-light);
        color: var(--brand-blue);
        border-right: none;
    }

    .user-form-card .form-control,
    .user-form-card .form-select {
        border-left: none;
    }

    .user-form-card .input-group:focus-within .input-group-text {
        border-color: var(--brand-blue);
    }

    .user-form-card .input-group:focus-within .form-control,
    .user-form-card .input-group:focus-within .form-select {
        border-color: var(--brand-blue);
        box-shadow: none;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--brand-blue);
        box-shadow: 0 0 0 .2rem rgba(0, 39, 82, .15);
    }

    .btn-brand {
        background-color: var(--brand-blue);
        border-color: var(--brand-blue-hover);
        color: #fff;
        font-weight: 600;
        padding: .55rem 1.75rem;
    }

    .btn-brand:hover {
        background-color: var(--brand-blue-hover);
        border-color: var(--brand-blue-hover);
        color: #fff;
    }

    .section-divider {
        border-top: 1px dashed #dee2e6;
        margin: 1.5rem 0;
    }
</style>

<div class="container py-4">
    <div class="card user-form-card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <i class="bi bi-person-badge me-2"></i>Mein Profil
            </h5>
        </div>

        <div class="user-avatar-lg">
            <i class="bi bi-person-fill-gear"></i>
        </div>

        <div class="card-body p-4 pt-3">
            <p class="text-center text-muted small mb-4">
                Administrator <span class="fw-semibold text-dark">{{ $user->name }}</span>
            </p>

            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   required>
                        </div>
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label class="form-label">E-Mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   required>
                        </div>
                        @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    {{-- Role (Read-only) --}}
                    <div class="col-md-6">
                        <label class="form-label">Rolle</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="text"
                                   class="form-control bg-light"
                                   value="{{ ucfirst($user->role) }}"
                                   disabled>
                        </div>
                    </div>

                    {{-- Company --}}
                    <div class="col-md-6">
                        <label class="form-label">Firma</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                            <select name="company" class="form-select @error('company') is-invalid @enderror">
                                <option value="">Firma auswählen</option>
                                <option value="ZF" @selected(old('company', $user->company) === 'ZF')>ZF</option>
                                <option value="ZT" @selected(old('company', $user->company) === 'ZT')>ZT</option>
                            </select>
                        </div>
                        @error('company') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    {{-- Password --}}
                    <div class="col-md-6">
                        <label class="form-label">
                            Neues Passwort <span class="text-muted fw-normal">(leer lassen, um unverändert zu lassen)</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password"
                                   name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="••••••••">
                        </div>
                        @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    {{-- Password Confirmation --}}
                    <div class="col-md-6">
                        <label class="form-label">Passwort bestätigen</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password"
                                   name="password_confirmation"
                                   class="form-control"
                                   placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-brand">
                        <i class="bi bi-save me-1"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection