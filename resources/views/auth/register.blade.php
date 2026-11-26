@extends('user.layouts.index')

@section('title', 'ZiMaTec GmbH - Registrierung')

@section('content')
<div class="login-wrapper bg-white">
    <div class="row g-0 min-vh-100">
        {{-- Left Side: Visual/Branding --}}
        <div class="col-lg-6 d-none d-lg-block">
            <div class="login-visual h-100 d-flex align-items-center justify-content-center p-5 text-white">
                <div class="text-center">
                    <img src="{{ asset('images/logo-team-zimmermann.png') }}" alt="ZiMaTec Logo" class="img-fluid bg-white p-4 rounded shadow-lg mb-4" style="max-height: 120px;">
                    <h1 class="display-4 fw-bold">{{ __('Werden Sie Teil des Teams') }}</h1>
                    <p class="lead opacity-75">{{ __('Erstellen Sie Ihr Konto, um Zugang zum zentralen ZiMaTec Portal zu erhalten.') }}</p>
                    <div class="mt-5 text-start d-inline-block">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                            <span class="fs-5">{{ __('Einfache Zeiterfassung') }}</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                            <span class="fs-5">{{ __('Auftragsübersicht in Echtzeit') }}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                            <span class="fs-5">{{ __('Direkte Kommunikation') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side: Registration Form --}}
        <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5">
            <div class="login-form-container w-100" style="max-width: 450px;">
                <div class="text-center mb-5 d-lg-none">
                    <img src="{{ asset('images/logo-team-zimmermann.png') }}" alt="ZiMaTec Logo" height="60" class="mb-3">
                    <h2 class="fw-bold text-title">{{ __('Registrierung') }}</h2>
                </div>

                <div class="text-center p-6">
                    <h2 class="text-2xl font-bold mb-4">Registrierung deaktiviert</h2>
                    <p class="text-gray-600">
                        Eine Selbstregistrierung ist nicht möglich. Bitte **beim Admin melden**, um einen Zugang zu erhalten.
                    </p>
                    <a href="{{ route('login') }}" class="underline text-sm text-gray-600 hover:text-gray-900 mt-4 inline-block">
                        Zurück zum Login
                    </a>
                </div>
                
                {{-- <h2 class="fw-bold text-title mb-2 d-none d-lg-block">{{ __('Konto erstellen') }}</h2>
                <p class="text-muted mb-4 d-none d-lg-block">{{ __('Füllen Sie die Felder aus, um sich zu registrieren.') }}</p>

                <form method="POST" action="{{ route('register') }}" class="mt-4"> --}}
                    @csrf

                    {{-- Name Field --}}
                    {{-- <div class="form-floating mb-3">
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                               name="name" value="{{ old('name') }}" placeholder="Ihr Name" 
                               required autocomplete="name" autofocus>
                        <label for="name">{{ __('Vollständiger Name') }}</label>
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div> --}}

                    {{-- Email Field --}}
                    {{-- <div class="form-floating mb-3">
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                               name="email" value="{{ old('email') }}" placeholder="name@example.com" 
                               required autocomplete="email">
                        <label for="email">{{ __('E-Mail-Adresse') }}</label>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div> --}}

                    {{-- Password Field --}}
                    {{-- <div class="form-floating mb-3">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                               name="password" placeholder="Passwort" required autocomplete="new-password">
                        <label for="password">{{ __('Passwort') }}</label>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div> --}}

                    {{-- Confirm Password Field --}}
                    {{-- <div class="form-floating mb-4">
                        <input id="password-confirm" type="password" class="form-control" 
                               name="password_confirmation" placeholder="Passwort bestätigen" 
                               required autocomplete="new-password">
                        <label for="password-confirm">{{ __('Passwort bestätigen') }}</label>
                    </div> --}}

                    {{-- Register Button --}}
                    {{-- <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold shadow-sm">
                            {{ __('Registrieren') }}
                        </button>
                    </div> --}}

                    {{-- Login Link --}}
                    {{-- <div class="text-center mt-4 pt-2 border-top">
                        <p class="text-muted small mb-0">
                            {{ __('Bereits ein Konto?') }} 
                            <a href="{{ route('login') }}" class="fw-bold text-decoration-none text-primary ms-1">
                                {{ __('Hier anmelden') }}
                            </a>
                        </p>
                    </div>
                </form> --}}
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
        animation: fadeInLeft 0.8s ease-out;
    }

    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
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
