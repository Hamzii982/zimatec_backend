<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fräsmaschine Logs')</title>
    <link rel="icon" href="{{ asset('images/zimmermann-logo-192.png') }}" type="image/x-icon" />
    
    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#002752">
    
    <!-- iOS support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ZiMaTec">
    <link rel="apple-touch-icon" href="{{ asset('images/zimmermann-logo-192.png') }}">

    <link href="{{ asset('bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Add this inside your <head> -->
    <link rel="stylesheet" href="{{ asset('bootstrap/icons/bootstrap-icons.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('styles')
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    {{-- ========== NAVBAR ========== --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm mb-0">
        <div class="container">
            {{-- Brand / Logo --}}
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <img src="{{ asset('images/logo-team-zimmermann.png') }}" alt="Company Logo" height="40" class="me-2">
            </a>

            {{-- Navbar Toggler (for mobile) --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- Navbar Links --}}
            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav ms-auto w-100 justify-content-end text-start align-items-lg-center mt-3 mt-lg-0">
                    {{-- Home --}}
                    <li class="nav-item mx-2 my-1 my-lg-0">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active fw-bold text-navitem' : '' }}">
                            <i class="bi bi-house-door-fill me-1"></i> Home
                        </a>
                    </li>

                    {{-- Projekte --}}
                    <li class="nav-item mx-2 my-1 my-lg-0">
                        <a href="{{ route('projects') }}" class="nav-link {{ request()->routeIs('projects') ? 'active fw-bold text-navitem' : '' }}">
                            <i class="bi bi-kanban-fill me-1"></i> Projekte
                        </a>
                    </li>

                    {{-- Leistungen Dropdown --}}
                    <li class="nav-item dropdown mx-2 my-1 my-lg-0">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs(['lager.*', 'time-records.*', 'printer-problems.*', 'scheduler.*']) ? 'active fw-bold text-navitem' : '' }}" href="#" id="leistungenDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear-fill me-1"></i> Leistungen
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="leistungenDropdown">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('lager.*') ? 'active fw-bold' : '' }}" href="{{ route('lager.select') }}">
                                    <i class="bi bi-box-seam me-1"></i> Lagerverwaltung
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('time-records.*') ? 'active fw-bold' : '' }}" href="{{ route('time-records.list') }}">
                                    <i class="bi bi-clock-history me-1"></i> Zeit Erfassung
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('printer-problems.*') ? 'active fw-bold' : '' }}" href="{{ route('printer-problems.index') }}">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Druckprobleme Erfassung
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('scheduler.*') ? 'active fw-bold' : '' }}" href="{{ route('scheduler.index') }}">
                                    <i class="bi bi-calendar3 me-1"></i> Resource Planung
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Platformen Dropdown --}}
                    <li class="nav-item dropdown mx-2 my-1 my-lg-0">
                        <a class="nav-link dropdown-toggle" href="#" id="platformenDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-grid-fill me-1"></i> Platformen
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="platformenDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ config('services.zimaboard.url') }}" rel="noopener">
                                    <i class="bi bi-chat-dots-fill me-1"></i> Zimaboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ config('services.zimatec_ai.url') }}" rel="noopener">
                                    <i class="bi bi-robot me-1"></i> Zimatec AI
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ config('services.feedback.url') }}" rel="noopener">
                                    <i class="bi bi-megaphone-fill me-1"></i> Feedback Portal
                                </a>
                            </li>
                        </ul>
                    </li>

                    @auth
                        @if(Auth::user()->role === 'admin')
                            <li class="nav-item mx-2 my-1 my-lg-0">
                                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                                    <i class="bi bi-speedometer me-1"></i> Admin
                                </a>
                            </li>
                        @else
                            <li class="nav-item mx-2 my-1 my-lg-0">
                                <a href="{{ route('profile') }}" class="nav-link">
                                    <i class="bi bi-person-circle me-1"></i> Profil
                                </a>
                            </li>
                        @endif
                    @endauth

                    {{-- Responsive Separator --}}
                    <li class="nav-item mx-lg-3 my-2 my-lg-0 border-top border-lg-end border-secondary-subtle" style="height: 1px; min-height: 1px;">
                        <div class="d-none d-lg-block" style="height: 24px; width: 1px;"></div>
                    </li>

                    {{-- Auth --}}
                    @auth
                        <li class="nav-item mx-2 my-1 my-lg-0 d-inline-flex justify-content-start">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-login btn-sm d-flex align-items-center">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </button>
                            </form>
                        </li>
                    @endauth

                    @guest
                        <li class="nav-item mx-2 my-1 my-lg-0">
                            <a href="{{ route('login') }}" class="btn btn-outline-login btn-sm d-flex align-items-center">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                        </li>
                    @endguest

                    {{-- Language Dropdown (Far Right) --}}
                    <li class="nav-item dropdown mx-2">
                        <button class="btn btn-sm dropdown-toggle" type="button" id="languageDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i> {{ strtoupper(app()->getLocale()) }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                            <li><a class="dropdown-item" href="{{ route('language.switch', 'en') }}">English</a></li>
                            <li><a class="dropdown-item" href="{{ route('language.switch', 'de') }}">Deutsch</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- Alert placeholder --}}
    @if(session('success') || session('error') || $errors->any())
        <div class="container mt-3">
            {{-- ✅ Success message --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" id="logAlert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- ⚠️ General error message --}}
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert" id="logAlert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" id="logAlert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>
    @endif

    {{-- ========== MAIN CONTENT ========== --}}
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <x-assistant-chat />

    {{-- ========== FOOTER ========== --}}
    <footer class="bg-dark text-white text-center py-3">
        &copy; {{ date('Y') }} ZiMaTec. Alle Rechte vorbehalten.
    </footer>

    <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script> 
    @stack('scripts')
    
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered successfully!', reg))
                    .catch(err => console.error('Service Worker registration failed:', err));
            });
        }
        setInterval(() => {
            fetch('/ping')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Heartbeat failed');
                    }
                    else {
                        console.log('Keep-alive ping successful');
                    }
                })
                .catch(error => console.warn('Keep-alive ping failed:', error));
        }, 10 * 60 * 1000);
    </script>
</body>
</html>