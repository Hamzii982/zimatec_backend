<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Fräsmaschine Logs')</title>
    <link rel="icon" href="{{ asset('images/zimmermann-logo-192.png') }}" type="image/x-icon" />
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
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
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
                <ul class="navbar-nav ms-auto align-items-center">
                    {{-- Home --}}
                    <li class="nav-item mx-2">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active fw-bold text-navitem' : '' }}">
                            <i class="bi bi-house-door-fill me-1"></i> Home
                        </a>
                    </li>

                    {{-- Mann Zeiten --}}
                    <li class="nav-item mx-2">
                        <a href="{{ route('time-records.list') }}" class="nav-link {{ request()->routeIs('time-records.*') ? 'active fw-bold text-navitem' : '' }}">
                            <i class="bi bi-clock-history me-1"></i> Mann Zeiten
                        </a>
                    </li>

                    {{-- Machine Logs --}}
                    <li class="nav-item mx-2">
                        <a href="{{ route('projects.logs') }}" class="nav-link {{ request()->routeIs('projects.*') ? 'active fw-bold text-navitem' : '' }}">
                            <i class="bi bi-cpu-fill me-1"></i> Machine Logs
                        </a>
                    </li>

                    {{-- Divider --}}
                    <li class="nav-item mx-2 text-muted">|</li>

                    {{-- Auth --}}
                    @auth
                        <li class="nav-item mx-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-login btn-sm d-flex align-items-center">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </button>
                            </form>
                        </li>
                    @endauth

                    @guest
                        <li class="nav-item mx-2">
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

    {{-- ========== MAIN CONTENT ========== --}}
    <main class="container flex-grow-1 py-4">
        @yield('content')
    </main>

    {{-- ========== FOOTER ========== --}}
    <footer class="bg-dark text-white text-center py-3 mt-4">
        &copy; {{ date('Y') }} ZiMaTec. Alle Rechte vorbehalten.
    </footer>

    <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script> 
    @stack('scripts')
</body>
</html>
