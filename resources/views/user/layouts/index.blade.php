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
        <div class="container flex-wrap">

            {{-- Brand / Logo --}}
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <img src="{{ asset('images/logo-team-zimmermann.png') }}" alt="Company Logo" height="40" class="me-2">
            </a>

            {{-- Right-side cluster: pill + hamburger. order-lg-3 keeps it last on desktop;
                on mobile it stays on the same row as the brand (never hides inside the collapse). --}}
            <div class="d-flex align-items-center order-lg-3 gap-2">

                {{-- ===== Account / Language Pill ===== --}}
                <div class="navbar-account-pill">

                    @auth
                        {{-- Logged-in: avatar + dropdown --}}
                        <div class="dropdown">
                            <a class="pill-account-trigger" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="pill-avatar">{{ Auth::user()->initials() }}</span>
                                <i class="bi bi-chevron-down pill-caret"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                @if(Auth::user()->role === 'admin')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                            <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.profile') }}">
                                            <i class="bi bi-person-badge me-1"></i> Profil
                                        </a>
                                    </li>
                                @else
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile') }}">
                                            <i class="bi bi-person-gear me-1"></i> Profil
                                        </a>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                                        @csrf
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-box-arrow-right me-1"></i> Abmelden
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth

                    @guest
                        {{-- Guest: login link, same pill padding so nothing jumps --}}
                        <a class="pill-guest-trigger" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span class="pill-guest-label">Anmelden</span>
                        </a>
                    @endguest

                    <span class="pill-divider"></span>

                    {{-- ===== Language toggle (always visible, both auth states) ===== --}}
                    <div class="lang-toggle dropdown">
                        {{-- Desktop: inline EN/DE segmented control --}}
                        <a href="{{ route('language.switch', 'en') }}"
                        class="lang-option d-none d-md-inline-block {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                        <a href="{{ route('language.switch', 'de') }}"
                        class="lang-option d-none d-md-inline-block {{ app()->getLocale() === 'de' ? 'active' : '' }}">DE</a>

                        {{-- Mobile: single globe icon that opens a tiny popover --}}
                        <a href="#" class="lang-globe d-md-none" id="langDropdownMobile"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-globe2"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end d-md-none" aria-labelledby="langDropdownMobile">
                            <li>
                                <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}"
                                href="{{ route('language.switch', 'en') }}">English</a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ app()->getLocale() === 'de' ? 'active' : '' }}"
                                href="{{ route('language.switch', 'de') }}">Deutsch</a>
                            </li>
                        </ul>
                    </div>
                </div>
                {{-- ===== End Pill ===== --}}

                {{-- Navbar Toggler (for mobile) --}}
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            {{-- Main nav links — order-lg-2 puts this between brand and pill on desktop;
                on mobile flex-wrap drops it to its own full-width row below. --}}
            <div class="collapse navbar-collapse order-lg-2" id="navbarMenu">
                <ul class="navbar-nav ms-auto w-100 justify-content-end text-start align-items-lg-center mt-3 mt-lg-0">

                    {{-- Home --}}
                    <li class="nav-item mx-2 my-1 my-lg-0">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active fw-bold text-navitem' : '' }}">
                            <i class="bi bi-house-door-fill me-1"></i> Home
                        </a>
                    </li>

                    {{-- Projekte --}}
                    <li class="nav-item dropdown mx-2 my-1 my-lg-0">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs(['projects', 'workflow.*']) ? 'active fw-bold text-navitem' : '' }}" href="#" id="projectDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-kanban-fill me-1"></i> Projekte
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="projectDropdown">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('projects') ? 'active fw-bold' : '' }}" href="{{ route('projects') }}">
                                    <i class="bi bi-kanban-fill me-1"></i> Übersicht
                                </a>
                            </li>
                            @auth
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('workflow.*') ? 'active fw-bold' : '' }}" href="{{ route('workflow.index') }}">
                                        <i class="bi bi-diagram-3 me-1"></i> Workflows
                                    </a>
                                </li>
                            @endauth
                        </ul>
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
    {{-- ========== PILL STYLES ========== --}}
    <style>
        /* Keep brand + pill + toggler on one row on mobile; nav links wrap to their own row */
        .navbar .container.flex-wrap {
            row-gap: .5rem;
        }
    
        .navbar-account-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f1f3f5;
            border-radius: 999px;
            padding: 4px 8px 4px 12px;
        }
    
        .pill-divider {
            width: 1px;
            height: 18px;
            background: #dee2e6;
            border-radius: 0; /* single-sided line, never round this */
        }
    
        /* Logged-in trigger */
        .pill-account-trigger {
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            cursor: pointer;
        }
        .pill-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #1F4E5F;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .pill-caret {
            font-size: .7rem;
            color: #6c757d;
        }
    
        /* Guest trigger */
        .pill-guest-trigger {
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: .8rem;
            font-weight: 500;
            color: #212529;
            white-space: nowrap;
        }
        .pill-guest-label {
            display: inline-block;
        }
    
        /* Language segmented control (desktop) */
        .lang-toggle {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .lang-option {
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            color: #6c757d;
        }
        .lang-option.active {
            background: #fff;
            border: 1px solid #dee2e6;
            color: #212529;
        }
    
        /* Language globe (mobile) */
        .lang-globe {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            color: #6c757d;
            text-decoration: none;
            font-size: 1rem;
        }
    
        /* Small-screen tightening so the pill never forces horizontal scroll */
        @media (max-width: 400px) {
            .navbar-account-pill {
                gap: 6px;
                padding: 4px 6px 4px 8px;
            }
            .pill-avatar {
                width: 24px;
                height: 24px;
            }
        }
    </style>
</body>
</html>