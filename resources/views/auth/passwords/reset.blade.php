@extends('user.layouts.index')

@section('title', 'ZiMaTec GmbH - Neues Passwort festlegen')

@section('content')
<div class="login-wrapper bg-white">
    <div class="row g-0 min-vh-100">
        {{-- Left Side: Visual/Branding --}}
        <div class="col-lg-6 d-none d-lg-block">
            <div class="login-visual h-100 d-flex align-items-center justify-content-center p-5 text-white">
                <div class="text-center">
                    <img src="{{ asset('images/logo-team-zimmermann.png') }}" alt="ZiMaTec Logo" class="img-fluid bg-white p-4 rounded shadow-lg mb-4" style="max-height: 120px;">
                    <h1 class="display-4 fw-bold">{{ __('ZiMaTec Portal') }}</h1>
                    <p class="lead opacity-75">{{ __('Ihre zentrale Plattform für technische Exzellenz und Prozessmanagement.') }}</p>
                    <div class="mt-5">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <i class="bi bi-shield-check fs-3 me-3"></i>
                            <span class="fs-5">{{ __('Passwort aktualisieren') }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="bi bi-lock fs-3 me-3"></i>
                            <span class="fs-5">{{ __('Sicheres Passwort wählen') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side: Reset Password Form --}}
        <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5">
            <div class="login-form-container w-100" style="max-width: 450px;">
                <div class="text-center mb-5 d-lg-none">
                    <img src="{{ asset('images/logo-team-zimmermann.png') }}" alt="ZiMaTec Logo" height="60" class="mb-3">
                    <h2 class="fw-bold text-title">{{ __('Passwort zurücksetzen') }}</h2>
                </div>
                
                <h2 class="fw-bold text-title mb-2 d-none d-lg-block">{{ __('Neues Passwort festlegen') }}</h2>
                <p class="text-muted mb-4">{{ __('Geben Sie Ihre E-Mail-Adresse und Ihr neues Passwort ein, um die Änderung abzuschließen.') }}</p>

                <form method="POST" action="{{ route('password.update') }}" class="mt-4">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    {{-- Email Field --}}
                    <div class="form-floating mb-3">
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                               name="email" value="{{ $email ?? old('email') }}" placeholder="name@example.com" 
                               required autocomplete="email" autofocus>
                        <label for="email">{{ __('E-Mail-Adresse') }}</label>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- New Password Field --}}
                    <div class="form-floating mb-3">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                               name="password" placeholder="Neues Passwort" required autocomplete="new-password">
                        <label for="password">{{ __('Neues Passwort') }}</label>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Confirm Password Field --}}
                    <div class="form-floating mb-4">
                        <input id="password-confirm" type="password" class="form-control" 
                               name="password_confirmation" placeholder="Passwort bestätigen" required autocomplete="new-password">
                        <label for="password-confirm">{{ __('Passwort bestätigen') }}</label>
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold shadow-sm">
                            {{ __('Passwort zurücksetzen') }}
                        </button>
                    </div>

                    {{-- Back to Login Link --}}
                    <div class="text-center mt-4 pt-2 border-top">
                        <p class="text-muted small mb-0">
                            <a href="{{ route('login') }}" class="fw-bold text-decoration-none text-primary">
                                <i class="bi bi-arrow-left me-1"></i>{{ __('Zurück zur Anmeldung') }}
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .login-wrapper {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    .login-visual {
        background: linear-gradient(rgba(0, 39, 82, 0.9), rgba(0, 39, 82, 0.8)), 
                    url('/images/hero-bg.jpg') center/cover no-repeat;
        background-color: #002752;
    }

    .text-title {
        color: #002752 !important;
    }

    .form-floating > .form-control:focus ~ label::after,
    .form-floating > .form-control:not(:placeholder-shown) ~ label::after {
        background-color: transparent !important;
    }

    .btn-primary {
        background-color: #002752;
        border-color: #002752;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #001a3d;
        border-color: #001a3d;
        transform: translateY(-2px);
    }

    .login-form-container {
        animation: fadeInRight 0.8s ease-out;
    }

    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    nav {
        margin-bottom: 0 !important;
    }
</style>
@endpush
@endsection