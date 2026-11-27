@extends('user.layouts.index')

@section('title', 'Lagerverwaltung')

@section('content')
<div class="container py-4" style="max-width: 720px;">
    <section id="globalMaterialSearch" class="lager-global-search">
        <button
            type="button"
            id="globalMaterialSearchTrigger"
            class="btn btn-link text-muted text-decoration-none p-0 d-block mx-auto lager-search-trigger"
            aria-controls="globalMaterialSearchPanel"
            aria-expanded="false"
        >
            <i class="bi bi-search me-2"></i>Material in allen Lagern suchen
        </button>

        <div id="globalMaterialSearchPanel" class="lager-global-search-panel d-none">
            <label for="globalMaterialSearchInput" class="form-label fw-semibold mb-2">
                <i class="bi bi-search me-2"></i>Material in allen Lagern suchen
            </label>
            <div class="input-group input-group-lg lager-search-input">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-primary"></i>
                </span>
                <input
                    type="search"
                    id="globalMaterialSearchInput"
                    class="form-control border-start-0"
                    placeholder="Name, Artikelnummer oder Beschreibung"
                    autocomplete="off"
                    aria-controls="globalMaterialSearchResults"
                >
                <button type="button" class="btn btn-outline-secondary d-none" id="clearGlobalMaterialSearch" aria-label="Suche löschen">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div id="globalMaterialSearchResults" class="lager-global-search-results d-none" role="region" aria-live="polite">
                <p class="text-muted small mb-0">Suche nach Name, Artikelnummer oder Beschreibung.</p>
            </div>
        </div>
    </section>

    <div id="globalMaterialSearchBackdrop" class="lager-search-backdrop d-none"></div>

    <main id="lagerLandingContent">
        <div class="text-center mb-4">
            <h1 class="fw-bold fs-2">🏭 Lagerverwaltung</h1>
            <p class="text-muted mb-0">Bitte wählen Sie ein Lager aus</p>
        </div>

        @if($lagers->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                Keine Lager verfügbar.
            </div>
        @else
            <div class="row g-3">
                @foreach($lagers as $lager)
                    <div class="col-12 col-md-6">
                        <a href="{{ route('tablar.index', $lager->id) }}"
                           class="card card-body text-decoration-none border shadow-sm h-100 lager-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="fs-1">🗄️</div>
                                <div>
                                    <div class="fw-bold fs-5 text-dark">{{ $lager->name }}</div>
                                    @if($lager->description)
                                        <div class="text-muted small">{{ $lager->description }}</div>
                                    @endif
                                    <div class="text-muted small mt-1">
                                        <i class="bi bi-boxes me-1"></i>
                                        {{ $lager->materials_count ?? $lager->materials()->count() }} Materialien
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right ms-auto text-muted"></i>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </main>
</div>

@include('user.tablar.partials.material-modals')

<script>
    window.tablarData = {
        flatList: [],
        storagePath: @json(asset('storage/')),
        statusTranslations: @json($statusTranslations),
        isGlobalSearch: true,
        isHolzLager: false,
        globalSearchUrl: @json(route('tablar.material-search')),
    };
</script>
<script src="{{ asset('js/user/tablar/index.js') }}"></script>
@endsection

@push('styles')
<style>
.lager-card {
    transition: box-shadow 0.2s, transform 0.15s;
    cursor: pointer;
}
.lager-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.10) !important;
    transform: translateY(-2px);
}
.lager-global-search {
    position: relative;
    z-index: 1;
    margin-bottom: 1.5rem;
}
.lager-search-trigger {
    font-weight: 500;
    color: #6c757d;
    transition: color 0.15s ease;
}
.lager-search-trigger:hover,
.lager-search-trigger:focus-visible {
    color: #002752 !important;
}
.lager-global-search-panel {
    padding: 1rem;
    border: 1px solid #dfe3e8;
    border-radius: 0.85rem;
    background: #fff;
    box-shadow: 0 0.25rem 0.9rem rgba(0, 39, 82, 0.06);
    transition: box-shadow 0.2s ease;
}
.lager-search-input .form-control,
.lager-search-input .input-group-text {
    border-color: #d7dfe7;
}
.lager-search-input .form-control:focus {
    box-shadow: none;
}
.lager-search-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1040;
    background: rgba(15, 32, 48, 0.28);
    backdrop-filter: blur(2px);
}
.lager-global-search-results {
    max-height: 65vh;
    overflow-y: auto;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e8edf2;
}
body.lager-global-search-open {
    overflow: hidden;
}
body.lager-global-search-open .lager-global-search {
    position: fixed;
    top: min(8vh, 5rem);
    left: 50%;
    z-index: 1050;
    width: min(760px, calc(100vw - 2rem));
    max-height: 84vh;
    overflow-y: auto;
    transform: translateX(-50%);
}
body.lager-global-search-open .lager-search-trigger {
    display: none;
}
body.lager-global-search-open .lager-global-search-panel {
    box-shadow: 0 1rem 3rem rgba(0, 23, 48, 0.24);
}
body.lager-global-search-open #lagerLandingContent {
    filter: blur(4px);
    pointer-events: none;
    user-select: none;
}
@media (max-width: 575.98px) {
    body.lager-global-search-open .lager-global-search {
        top: 0.75rem;
        width: calc(100vw - 1.5rem);
        max-height: calc(100vh - 1.5rem);
    }
}
</style>
@endpush
