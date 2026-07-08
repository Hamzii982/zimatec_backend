<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link rel="icon" href="{{ asset('images/zimmermann-logo-192.png') }}" type="image/x-icon" />

    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#002752">
    
    <!-- iOS support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ZiMaTec">
    <link rel="apple-touch-icon" href="{{ asset('images/zimmermann-logo-192.png') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap 5 -->
    <link href="{{ asset('bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Add this inside your <head> -->
    <link rel="stylesheet" href="{{ asset('bootstrap/icons/bootstrap-icons.css') }}">
    {{-- Custom CSS for personalized layout --}}
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('styles')

</head>
<body>

    <!-- Sidebar -->
    @include('admin.partials.sidebar')

    {{-- Mobile sidebar backdrop --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Content -->
    <div class="content">
        @include('admin.partials.navbar')

        <div class="container-fluid mt-4">
            {{-- ✅ Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" id="logAlert"></button>
                </div>
            @elseif(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" id="logAlert"></button>
                </div>
            @else
                <div id="logAlert">
                </div>
            @endif
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
    @stack('scripts')
    
    <!-- PWA Service Worker Registration -->
    <script>

        window.isAdmin = {{ auth()->check() && auth()->user()->isAdmin() ? 'true' : 'false' }};

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Admin SW registered successfully!', reg))
                    .catch(err => console.error('Admin SW registration failed:', err));
            });
        }

        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        function openSidebar() {
            sidebar.classList.add('open');
            backdrop.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }

        sidebarToggle?.addEventListener('click', openSidebar);
        backdrop?.addEventListener('click', closeSidebar);

        // Mobile search
        const mobileSearchToggle = document.getElementById('mobileSearchToggle');
        const mobileSearchOverlay = document.getElementById('mobileSearchOverlay');
        const mobileSearchClose = document.getElementById('mobileSearchClose');
        const mobileSearchInput = document.getElementById('mobileSearchInput');

        mobileSearchToggle?.addEventListener('click', () => {
            mobileSearchOverlay.classList.add('active');
            mobileSearchInput?.focus();
        });

        mobileSearchClose?.addEventListener('click', () => {
            mobileSearchOverlay.classList.remove('active');
        });
    </script>

    <!-- Laravel Echo + Pusher via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/pusher-js/dist/web/pusher.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo/dist/echo.iife.js"></script>

    <script>
        console.log('Echo type:', typeof window.Echo);
        console.log('Echo value:', window.Echo);
        console.log('Echo keys:', Object.keys(window.Echo || {}));
    </script>

    <script>
        // The IIFE bundle auto-creates window.Echo — we just configure it
        window.Pusher = Pusher;
    
        // Re-initialize with your Reverb config
        window.Echo = new window.Echo.default({
            broadcaster: 'reverb',
            key:         '{{ env("REVERB_APP_KEY") }}',
            wsHost:      '{{ env("REVERB_HOST", "localhost") }}',
            wsPort:      {{ env("REVERB_PORT", 8080) }},
            wssPort:     {{ env("REVERB_PORT", 8080) }},
            forceTLS:    false,
            enabledTransports: ['ws', 'wss'],
        });
    
        setTimeout(() => {
            console.log('Echo state:', window.Echo.connector.pusher.connection.state);
        }, 2000);
    </script>

    <script src="{{ asset('js/admin/notifications.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!('Notification' in window) || !('serviceWorker' in navigator)) return;
            if (!window.isAdmin) return;

            const VAPID_PUBLIC_KEY = '{{ env("VAPID_PUBLIC_KEY") }}';

            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                const raw     = window.atob(base64);
                return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
            }

            async function subscribeToPush() {
                const registration = await navigator.serviceWorker.ready;

                const existing = await registration.pushManager.getSubscription();
                if (existing) return; // already subscribed

                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    console.warn('Push permission denied');
                    return;
                }

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly:      true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
                });

                console.log(subscription.toJSON());

                const subJson = subscription.toJSON();

                await fetch('/push/subscribe', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        endpoint: subJson.endpoint,
                        keys:     subJson.keys,
                    }),
                });

                console.log('Push subscription saved.');
            }

            subscribeToPush();
        });
    </script>

</body>
</html>
