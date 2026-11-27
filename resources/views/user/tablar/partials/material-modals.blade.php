<div class="modal fade" id="reserveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <h3 id="reserveModalMaterialName" class="mb-1 fs-4"></h3>
            <code class="text-muted small d-none mb-2 d-block" id="reserveModalMaterialCode"></code>
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
            <code class="text-muted small d-none mb-2 d-block" id="modalMaterialCode"></code>
            <img id="modalMaterialImage" class="img-thumbnail rounded mb-3 d-none mx-auto" style="max-width: 180px; max-height: 180px; object-fit: contain;" alt="">
            <p id="modalMaterialDescription" class="text-muted small mb-2 d-none"></p>
            <p class="text-muted small mb-1" id="modalLager"></p>
            <p class="text-muted mb-1" id="modalShelf"></p>
            <p class="text-muted small mb-1">
                Verfügbar: <strong id="modalAvailable"></strong>
            </p>
            <p class="text-muted small mb-3 d-none" id="modalReservedRow">
                Reserviert: <strong id="modalReserved"></strong>
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
                    <button class="btn btn-danger btn-lg w-100" onclick="confirmReturn()">📥 Einlagern</button>
                </div>
                <div class="col-6">
                    <button class="btn btn-primary btn-lg w-100" onclick="confirmConsumption()">✅ Entnehmen</button>
                </div>
            </div>

            <button class="btn btn-secondary mt-2" onclick="confirmReservation()">
                <i class="bi bi-calendar-check me-2"></i> Reservieren
            </button>
            <button type="button" class="btn btn-link btn-sm mt-2 d-none" id="manageReservedButton" onclick="openReservationManagementModal()">
                <i class="bi bi-box-arrow-up-right me-1"></i> Reservierte Menge verwalten
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
