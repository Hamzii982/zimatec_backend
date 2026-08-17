@extends('user.layouts.index')

@section('title', 'ZiMaTec GmbH - Ihr Partner für Industrie-Lösungen')

@section('content')
    
    {{-- === Hero Section === --}}
    <div class="hero-section position-relative overflow-hidden mb-5 rounded-4 shadow-sm">
        <div class="abstract-blur"></div>
        
        <div class="container position-relative z-index-2 py-5">
            <div class="row align-items-center py-4">
                <div class="col-lg-8 text-center text-md-start">
                    <span class="badge bg-white bg-opacity-10 text-cyan mb-3 px-3 py-2 rounded-pill border border-white border-opacity-10 tracking-wider text-uppercase fs-7">
                        <i class="bi bi-cpu me-1"></i> Central Workspace
                    </span>
                    
                    <h1 class="display-4 fw-black text-white mb-3 tracking-tight">
                        {{ __('Exzellenz in jedem Arbeitsschritt') }}
                    </h1>
                    
                    <p class="lead text-white-50 mb-4 max-w-xl">
                        {{ __('Willkommen in Ihrem zentralen Arbeitsportal. Gemeinsam setzen wir modernste Mess- und CAD-Systeme ein, um präzise Lösungen für unsere Kunden zu schaffen.') }}
                    </p>
                    
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-start align-items-center">
                        @guest
                            <a href="{{ route('login') }}" class="btn btn-modern-primary px-4 py-2-5 shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Anmelden
                            </a>
                            <a href="{{ route('register') }}" class="btn btn-modern-outline px-4 py-2-5">
                                <i class="bi bi-person-plus me-2"></i>Registrieren
                            </a>
                        @else
                            <a href="{{ route('projects.logs') }}" class="btn btn-modern-primary px-4 py-2-5 shadow-sm">
                                <i class="bi bi-grid-1x2-fill me-2"></i>{{ __('Projekte Anzeigen') }}
                            </a>
                            <a href="{{ route('printer-problems.index') }}" class="btn btn-modern-secondary px-4 py-2-5">
                                <i class="bi bi-printer-fill me-2"></i>{{ __('Druckprobleme melden') }}
                            </a>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- === Services Section === --}}
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-title display-6">{{ __('Unsere Leistungen') }}</h2>
            <div class="mx-auto rounded-pill" style="width: 60px; height: 4px; background-color: #002752;"></div>
            <p class="text-muted mt-3">{{ __('Effiziente Werkzeuge für Ihren Arbeitsalltag') }}</p>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            @foreach ($leistungen as $leistung)
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 service-card">
                        <div class="service-img-wrapper overflow-hidden">
                            <img src="{{ asset($leistung['image']) }}" class="card-img-top" alt="{{ $leistung['name'] }}" height="200" style="object-fit: cover;">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-semibold text-title">{{ $leistung['name'] }}</h5>
                            <p class="card-text text-muted small">
                                {{ $leistung['description'] }}
                            </p>
                            @isset($leistung['url'])
                                <a href="{{ $leistung['url'] }}" rel="noopener noreferrer" class="stretched-link"></a>
                            @else
                                <a href="{{ route($leistung['route']) }}" class="stretched-link"></a>
                            @endisset
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- === About Us Section === --}}
    <div class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="{{ asset('images/logo-team-zimmermann.png') }}" alt="ZiMaTec Team" class="img-fluid rounded shadow-sm p-4 bg-white mb-4" style="max-height: 120px">
                    <h2 class="fw-bold text-title">{{ __('Unser Anspruch an Qualität') }}</h2>
                    <p class="lead text-muted">
                        {{ __('Wir bündeln unsere Kompetenzen in Technik, Messtechnik und Konstruktion.') }}
                    </p>
                    <p class="text-muted">
                        {{ __('Dieses Portal unterstützt uns dabei, zielführende Lösungen für komplexe Probleme zu finden egal ob in der Fertigung oder Konstruktion. Nutzen Sie die verfügbaren Daten und Tools, um Projekte effizient abzuwickeln und unsere langjährige Erfahrung gemeinsam optimal einzusetzen. Vom Spritzgussverfahren bis zur optischen Vermessung.') }}
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-4 bg-white rounded shadow-sm text-center h-100 feature-box">
                                <i class="bi bi-shield-check text-success fs-1"></i>
                                <h6 class="fw-bold mt-2">Zuverlässig</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-4 bg-white rounded shadow-sm text-center h-100 feature-box">
                                <i class="bi bi-lightning-charge text-warning fs-1"></i>
                                <h6 class="fw-bold mt-2">Effizient</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-4 bg-white rounded shadow-sm text-center h-100 feature-box">
                                <i class="bi bi-gear text-primary fs-1"></i>
                                <h6 class="fw-bold mt-2">Innovativ</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-4 bg-white rounded shadow-sm text-center h-100 feature-box">
                                <i class="bi bi-graph-up text-danger fs-1"></i>
                                <h6 class="fw-bold mt-2">Wachstum</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- === Statistics Section === --}}
    <div class="container py-5 text-center">
        <div class="row g-4 justify-content-center py-4">
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <h2 class="fw-bold text-title display-5">{{ $stats['projects'] ?? 0 }}</h2>
                    <p class="text-muted text-uppercase small fw-bold mb-0">Projekte</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <h2 class="fw-bold text-title display-5">{{ $stats['machines'] ?? 0 }}</h2>
                    <p class="text-muted text-uppercase small fw-bold mb-0">Maschinen</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <h2 class="fw-bold text-title display-5">{{ $stats['users'] ?? 0 }}</h2>
                    <p class="text-muted text-uppercase small fw-bold mb-0">Benutzer</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <h2 class="fw-bold text-title display-5">{{ $stats['processes'] ?? 0 }}</h2>
                    <p class="text-muted text-uppercase small fw-bold mb-0">Prozesse</p>
                </div>
            </div>
        </div>
    </div>

    {{-- === CTA Section === --}}
    <div class="cta-section text-white py-5 shadow-lg mb-0">
        <div class="container text-center py-4">
            <h3 class="fw-bold mb-3 display-6">{{ __('Gemeinsam besser werden') }}</h3>
            <p class="mb-4 opacity-75 fs-5">{{ __('Haben Sie Feedback zum System oder Verbesserungsvorschläge für unsere internen Prozesse? Lassen Sie es uns wissen.') }}</p>
            <a href="{{ env('FEEDBACK_FORM_URL', '#') }}" class="btn btn-primary btn-lg px-5 shadow-lg">{{ __('Feedback geben') }}</a>
        </div>
    </div>

@endsection

@push('styles')
<style>
    :root {
        --brand-dark: #0a192f;
        --brand-accent: #00f2fe;
        --brand-purple: #4facfe;
    }

    .hero-section {
        /* Next-gen dark tech gradient instead of a heavy image overlay */
        background: radial-gradient(circle at top right, rgba(79, 172, 254, 0.15), transparent 50%),
                    linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        min-height: 400px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Futuristic background glow effect */
    .abstract-blur {
        position: absolute;
        top: -20%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: linear-gradient(45deg, var(--brand-purple), var(--brand-accent));
        filter: blur(120px);
        opacity: 0.25;
        border-radius: 50%;
        pointer-events: none;
    }

    /* Typography refinements */
    .fw-black { font-weight: 850; }
    .tracking-tight { letter-spacing: -0.03em; }
    .tracking-wider { letter-spacing: 0.08em; font-size: 0.75rem; }
    .text-cyan { color: #22d3ee; }
    .max-w-xl { max-width: 600px; }
    .z-index-2 { z-index: 2; }

    /* Modern Button UI (Moving away from default Bootstrap colors) */
    .btn-modern-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #fff;
        border: none;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .btn-modern-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4) !important;
        color: #fff;
    }

    .btn-modern-secondary {
        background: rgba(255, 255, 255, 0.06);
        color: #f8fafc;
        border: 1px solid rgba(255, 255, 255, 0.1);
        font-weight: 600;
        border-radius: 8px;
        backdrop-filter: blur(8px);
        transition: all 0.2s ease;
    }
    .btn-modern-secondary:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.2);
    }

    .btn-modern-outline {
        background: transparent;
        color: #94a3b8;
        border: 1px solid #334155;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .btn-modern-outline:hover {
        color: #fff;
        border-color: #64748b;
        background: rgba(255, 255, 255, 0.02);
    }
    
    .text-title {
        color: #002752 !important;
    }
    
    .service-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important;
    }
    
    .service-img-wrapper img {
        transition: transform 0.6s ease;
    }
    
    .service-card:hover .service-img-wrapper img {
        transform: scale(1.1);
    }
    
    .feature-box {
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .feature-box:hover {
        background-color: #f8f9fa !important;
        transform: scale(1.05);
    }
    
    .cta-section {
        background: linear-gradient(135deg, #002752 0%, #001a3d 100%);
    }
    
    .stat-item {
        padding: 20px;
        border-right: 1px solid #dee2e6;
    }
    
    .stat-item:last-child {
        border-right: none;
    }
    
    .btn-primary {
        background-color: #002752;
        border-color: #002752;
    }
    
    .btn-primary:hover {
        background-color: #001a3d;
        border-color: #001a3d;
    }
    
    @media (max-width: 768px) {
        .stat-item {
            border-right: none;
            border-bottom: 1px solid #dee2e6;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .hero-section {
            min-height: auto;
            padding: 60px 0;
        }
    }
</style>
@endpush
