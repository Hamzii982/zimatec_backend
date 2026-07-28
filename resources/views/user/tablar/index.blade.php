@extends('user.layouts.index')

@section('title', 'Tablar Übersicht')

@section('content')
<div class="container py-4" style="max-width: 720px;">

    <div class="text-center mb-4">
        <h1 class="fw-bold fs-2">🏭 {{ $lager->name }} - Materialerfassung</h1>
        <p class="text-muted mb-0">Tablar wählen, oder Name suchen</p>
    </div>

    {{-- Back link --}}
    <div class="mb-3">
        <a href="{{ route('lager.select') }}" class="btn btn-sm btn-outline-secondary">
            ← Lager wechseln
        </a>
    </div>

    <ul class="nav nav-pills nav-fill mb-4" id="searchModeTabs">
        <li class="nav-item">
            <button class="nav-link active" id="tabShelf" onclick="switchMode('shelf')">
                <i class="bi bi-grid-3x3-gap me-1"></i> Nach Tablar
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tabName" onclick="switchMode('name')">
                <i class="bi bi-search me-1"></i> Nach Name
            </button>
        </li>
    </ul>

    <div id="shelfStep">
        <p class="text-muted small text-uppercase fw-semibold mb-2 ms-1">Tablar wählen</p>
        <input
            type="text"
            id="shelfSearch"
            class="form-control mb-3"
            placeholder="Tablar suchen... z.B. A1"
            oninput="filterShelves()"
        >
        <div id="shelfGrid" class="row g-2">
            @foreach($shelves as $shelf)
                <div class="col-4 col-md-3 shelf-tile" data-shelf="{{ strtolower($shelf) }}">
                    <button
                        class="btn btn-outline-secondary w-100 py-3 fw-bold fs-5"
                        onclick="selectShelf('{{ $shelf }}')"
                    >
                        {{ $shelf }}
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <div id="nameStep" class="d-none">
        <p class="text-muted small text-uppercase fw-semibold mb-2 ms-1">Nach Materialname suchen</p>
        <input
            type="text"
            id="globalNameSearch"
            class="form-control mb-3"
            placeholder="z.B. Schraube, Kabel..."
            oninput="filterByName()"
            autofocus
        >
        <div id="globalNameResults"></div>
    </div>


    <div id="materialStep" class="d-none">

        <div class="d-flex align-items-center mb-3">
            <button class="btn btn-sm btn-outline-secondary me-3" onclick="goBackToShelves()">
                ← Zurück
            </button>
            <div>
                <span class="text-muted small text-uppercase fw-semibold">Tablar</span>
                <span id="selectedShelfLabel" class="fs-5 fw-bold ms-2"></span>
            </div>
        </div>

        <input
            type="text"
            id="materialSearch"
            class="form-control mb-3"
            placeholder="Material suchen..."
            oninput="filterMaterials()"
            autofocus
        >

        <div id="materialList"></div>

    </div>

</div>

<div class="modal fade" id="reserveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">

            <h3 id="reserveModalMaterialName" class="mb-1 fs-4"></h3>
            <p class="text-muted mb-1" id="reserveModalShelf"></p>
            <p class="text-muted small mb-1">
                Verfügbar: <strong id="reserveModalAvailable"></strong>
            </p>
            <p class="text-muted small mb-4">
                Reserviert: <strong id="reserveModalOnHold"></strong>
            </p>

            <p class="small text-muted mb-2">Wie viel davon wird zurückgelegt?</p>

            <div class="d-flex justify-content-center align-items-center mb-2">
                <button class="btn btn-lg btn-outline-danger px-4" onclick="decreaseReserve()">−</button>
                <input
                    type="number"
                    id="reserveCounterInput"
                    class="form-control form-control-lg mx-3 text-center fw-bold fs-2"
                    style="width: 110px;"
                    value="0"
                    min="0"
                    oninput="validateReserveInput(this)"
                >
                <button class="btn btn-lg btn-outline-success px-4" onclick="increaseReserve()">+</button>
            </div>

            <p class="text-muted small mb-4" id="reserveModalConsumedHint"></p>

            <button class="btn btn-primary btn-lg w-100" onclick="confirmReservationSettlement()">
                <i class="bi bi-check2-circle me-2"></i> Bestätigen
            </button>

        </div>
    </div>
</div>

<div class="modal fade" id="materialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">

            <h3 id="modalMaterialName" class="mb-1 fs-4"></h3>
            <p class="text-muted mb-1" id="modalShelf"></p>
            <p class="text-muted small mb-4">
                Verfügbar: <strong id="modalAvailable"></strong>
            </p>

            <div class="d-flex justify-content-center align-items-center mb-4">
                <button class="btn btn-lg btn-outline-danger px-4" onclick="decrease()">−</button>
                <input
                    type="number"
                    id="counterInput"
                    class="form-control form-control-lg mx-3 text-center fw-bold fs-2"
                    style="width: 110px;"
                    value="1"
                    min="1"
                    oninput="validateManualInput(this)"
                >
                <button class="btn btn-lg btn-outline-success px-4" onclick="increase()">+</button>
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <button class="btn btn-danger btn-lg w-100" onclick="confirmReturn()">
                        📥 Einlagern
                    </button>
                </div>
                <div class="col-6">
                    <button class="btn btn-primary btn-lg w-100" onclick="confirmConsumption()">
                        ✅ Entnehmen
                    </button>
                </div>
            </div>

            <button class="btn btn-secondary mt-2" onclick="confirmReservation()">
                <i class="bi bi-calendar-check me-2"></i> Reservieren
            </button>

        </div>
    </div>
</div>

<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white text-center p-2 position-relative">
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="p-3">
                <img id="lightboxImage" src="" alt="Maximized view" class="img-fluid rounded" style="max-height: 80vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script>
    window.tablarData = {
        flatList: @json($flatList),
        storagePath: "{{ asset('storage/') }}",
        statusTranslations: @json($statusTranslations),
        lagerId:            {{ $lager->id }},
        consumeUrl:         "{{ route('tablar.consume', $lager->id) }}",
        returnUrl:          "{{ route('tablar.return', $lager->id) }}",
        reserveUrl:         "{{ route('tablar.reserve', $lager->id) }}",
        settleReservationUrl: "{{ route('tablar.reserve.settle', $lager->id) }}",
        orderRequestBase:   "/lager/{{ $lager->id }}/tablar/order-request",
        cancelNotificationBase: "/lager/{{ $lager->id }}/tablar/materials",
        confirmDeliveryBase:    "/lager/{{ $lager->id }}/tablar/materials",
    };

    // Helper JavaScript function to open up the lightbox modal
    function maximizeImage(event, src) {
        event.stopPropagation(); // Prevents opening the material consumption modal when clicking the image
        document.getElementById('lightboxImage').src = src;
        const lightbox = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
        lightbox.show();
    }

    function maximizeImage(event, src) {
        event.stopPropagation();
        document.getElementById('lightboxImage').src = src;
        const lightbox = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
        lightbox.show();
    }
</script>

<script src="{{ asset('js/user/tablar/index.js') }}"></script>

<style>
.material-item-container {
    max-height: 60px;
}
.material-item {
    cursor: pointer;
    transition: background-color 0.15s;
}
.material-item:hover {
    background-color: #f0f4ff;
}
.img-thumbnail-clickable {
    cursor: zoom-in;
    transition: transform 0.2s;
}
.img-thumbnail-clickable:hover {
    transform: scale(1.05);
}
</style>

@endsection