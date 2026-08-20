@extends('user.layouts.index')

@section('title', 'ZiMaTec GmbH - E-Mail-Adresse bestätigen')

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
                            <i class="bi bi-envelope-check fs-3 me-3"></i>
                            <span class="fs-5">{{ __('E-Mail-Bestätigung') }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="bi bi-patch-check fs-3 me-3"></i>
                            <span class="fs-5">{{ __('Konto verifizieren') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side: Email Verification Content --}}
        <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5">
            <div class="login-form-container w-100" style="max-width: 450px;">
                <div class="text-center mb-5 d-lg-none">
                    <img src="{{ asset('images/logo-team-zimmermann.png') }}" alt="ZiMaTec Logo" height="60" class="mb-3">
                    <h2 class="fw-bold text-title">{{ __('E-Mail-Adresse bestätigen') }}</h2>
                </div>
                
                <h2 class="fw-bold text-title mb-2 d-none d-lg-block">{{ __('E-Mail-Adresse bestätigen') }}</h2>
                <p class="text-muted mb-4">{{ __('Bevor Sie fortfahren, überprüfen Sie bitte Ihren Posteingang auf einen Bestätigungslink.') }}</p>

                {{-- Resend Success Alert --}}
                @if (session('resent'))
                    <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ __('Ein neuer Bestätigungslink wurde an Ihre E-Mail-Adresse gesendet.') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="p-4 bg-light rounded shadow-sm mb-4 border text-center">
                    <i class="bi bi-mailbox fs-1 text-primary mb-3 d-block"></i>
                    <p class="text-muted small mb-0">
                        {{ __('Haben Sie keine E-Mail erhalten? Überprüfen Sie auch Ihren Spam-Ordner oder fordern Sie einen neuen Link an.') }}
                    </p>
                </div>

                {{-- Resend Form --}}
                <form method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold shadow-sm">
                            {{ __('Neuen Link anfordern') }}
                        </button>
                    </div>
                </form>

                {{-- Back to Login / Logout Options --}}
                <div class="text-center mt-4 pt-2 border-top d-flex justify-content-between align-items-center">
                    <a href="{{ route('login') }}" class="fw-bold text-decoration-none text-primary small">
                        <i class="bi bi-arrow-left me-1"></i>{{ __('Zurück zur Anmeldung') }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link text-muted small p-0 text-decoration-none">
                            <i class="bi bi-box-arrow-right me-1"></i>{{ __('Abmelden') }}
                        </button>
                    </form>
                </div>
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