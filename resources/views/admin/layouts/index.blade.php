<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link rel="icon" href="{{ asset('images/zimmermann-logo-192.png') }}" type="image/x-icon" />

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
</body>
</html>
