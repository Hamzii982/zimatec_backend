// Full material list from PHP — flat, all shelves
const allMaterials = window.tablarData.flatList;
const storagePath = window.tablarData.storagePath ?? '/storage/';
const consumeUrl    = window.tablarData.consumeUrl;
const returnUrl     = window.tablarData.returnUrl;
const reserveUrl    = window.tablarData.reserveUrl;
const settleReservationUrl = window.tablarData.settleReservationUrl;
const orderBaseUrl  = window.tablarData.orderRequestBase;

// Group by shelf for fast lookup: { "A1": [...], "B2": [...] }
const byShelf = {};
allMaterials.forEach(m => {
    const key = m.shelf ?? 'Unbekannt';
    if (!byShelf[key]) byShelf[key] = [];
    byShelf[key].push(m);
});

let currentShelf = null;
let currentShelfMaterials = [];
let selectedMaterial = null;

const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Helper function to build image markup
function generateImageHtml(image, name) {
    if (image) {
        const fullSrc = `${storagePath}/${image}`.replace(/\/+/g, '/').replace(':/', '://');
        return `<img src="${fullSrc}" alt="${name}" width="60" height="60" class="rounded border img-thumbnail-clickable me-3" onclick="maximizeImage(event, '${fullSrc}')">`;
    }
    return `
        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center me-3" style="width:60px; height:60px; min-width:60px;">
            <i class="bi bi-box-seam"></i>
        </div>`;
}

// Helper function to build order status or order button markup
function generateOrderHtml(m) {
    if (m.order_status) {
        const statusText = window.tablarData.statusTranslations[m.order_status] ?? ucfirst(m.order_status);
        const qtyText = (m.order_status === 'ordered' && m.order_quantity)
            ? ` · ${m.order_quantity} Stk.`
            : '';
        return `<span class="badge bg-warning text-dark ms-2"><i class="bi bi-clock-history me-1"></i>${statusText}${qtyText}</span>`;
    }
    return `
        <button class="btn btn-sm btn-outline-primary ms-2" onclick="event.stopPropagation(); triggerOrder(${m.id})">
            Bestellen
        </button>`;
}

// ─── SHELF SELECTION ──────────────────────────────────────────────────────────

function filterShelves() {
    const q = document.getElementById('shelfSearch').value.toLowerCase();
    document.querySelectorAll('.shelf-tile').forEach(tile => {
        tile.style.display = tile.dataset.shelf.includes(q) ? '' : 'none';
    });
}

function selectShelf(shelf) {
    currentShelf = shelf;
    currentShelfMaterials = byShelf[shelf] ?? [];

    document.getElementById('selectedShelfLabel').innerText = shelf;
    document.getElementById('materialSearch').value = '';
    document.getElementById('shelfStep').classList.add('d-none');
    document.getElementById('materialStep').classList.remove('d-none');

    renderMaterials(currentShelfMaterials);

    // Auto-focus search after short delay (modal animation)
    setTimeout(() => document.getElementById('materialSearch').focus(), 150);
}

function goBackToShelves() {
    currentShelf = null;
    document.getElementById('materialStep').classList.add('d-none');
    document.getElementById('shelfStep').classList.remove('d-none');
    document.getElementById('shelfSearch').value = '';
    filterShelves(); // reset shelf tiles
}

// ─── MATERIAL LIST ────────────────────────────────────────────────────────────

function filterMaterials() {
    const q = document.getElementById('materialSearch').value.toLowerCase();
    const filtered = q
        ? currentShelfMaterials.filter(m => m.name.toLowerCase().includes(q))
        : currentShelfMaterials;
    renderMaterials(filtered);
}

function renderMaterials(materials) {
    const container = document.getElementById('materialList');

    if (materials.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Kein Material gefunden.
        </div>`;
        return;
    }

    container.innerHTML = materials.map(m => {
        const outOfStock = m.quantity+m.on_hold_quantity <= 0;
        const threshold  = m.threshold ?? 0;
        const onHold     = m.on_hold_quantity ?? 0;
        const orderQty   = m.order_quantity ?? 0;
        const available  = m.available_total ?? (m.quantity + onHold + orderQty);
        const isReserved = onHold > 0;
        const modalType  = isReserved ? 'openReserveModal' : 'openMaterialModal';
        const badgeClass = outOfStock
            ? 'bg-secondary'
            : available > threshold ? 'bg-success' : 'bg-danger';
        const badgeText  = outOfStock ? 'Kommt gleich' : m.quantity + ' Stk.';

        const imageTemplate = generateImageHtml(m.image, m.name);
        const orderTemplate = generateOrderHtml(m);
        const onHoldText = isReserved
            ? `<span class="badge bg-info text-dark ms-2"><i class="bi bi-clock-history me-1"></i>Reserviert: ${onHold} Stk.</span>`
            : '';
        const orderText = orderQty > 0
            ? `<span class="badge bg-warning text-dark ms-2"><i class="bi bi-truck me-1"></i>Bestellt: ${orderQty} Stk.</span>`
            : '';

        if (outOfStock) {
            return `
            <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border bg-light text-muted"
                style="cursor: not-allowed;"
                onclick="Swal.fire('Nicht verfügbar', 'Bitte warten Sie auf Nachschub.', 'info')">
                <div class="d-flex align-items-center">
                    ${imageTemplate}
                    <div>
                        <span class="text-decoration-line-through fw-semibold">${m.name}</span>
                        <div class="mt-1">${orderTemplate}</div>
                    </div>
                </div>
                <span class="badge ${badgeClass}">${badgeText}</span>
            </div>`;
        }

        return `
        <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border material-item"
            onclick="${modalType}(${m.id}, '${m.name}', ${m.quantity}, '${m.shelf}', ${onHold})">
            <div class="d-flex align-items-center">
                ${imageTemplate}
                <div>
                    <span class="fw-semibold">${m.name} ${onHoldText}${orderText}</span>
                    <div class="mt-1">${orderTemplate}</div>
                </div>
            </div>
            <span class="badge ${badgeClass} fs-6">${badgeText}</span>
        </div>`;
    }).join('');
}

// ─── MODAL ────────────────────────────────────────────────────────────────────

function openMaterialModal(id, name, quantity, shelf) {
    selectedMaterial = { id, name, quantity, shelf };

    document.getElementById('modalMaterialName').innerText = name;
    document.getElementById('modalShelf').innerText        = 'Tablar: ' + shelf;
    document.getElementById('modalAvailable').innerText    = quantity;

    const input = document.getElementById('counterInput');
    input.value = 1;
    // When returning, max configuration shouldn't block entering high amounts, so we remove programmatic max cap.
    input.removeAttribute('max'); 

    new bootstrap.Modal(document.getElementById('materialModal')).show();
}

function openReserveModal(id, name, quantity, shelf, onHoldQuantity) {
    selectedMaterial = { id, name, quantity, shelf, onHoldQuantity };

    document.getElementById('reserveModalMaterialName').innerText = name;
    document.getElementById('reserveModalShelf').innerText        = 'Tablar: ' + shelf;
    document.getElementById('reserveModalAvailable').innerText    = quantity;
    document.getElementById('reserveModalOnHold').innerText       = onHoldQuantity;

    const input = document.getElementById('reserveCounterInput');
    input.value = 0;
    input.max   = onHoldQuantity;

    updateReserveHint();

    new bootstrap.Modal(document.getElementById('reserveModal')).show();
}

function updateReserveHint() {
    const val = parseInt(document.getElementById('reserveCounterInput').value) || 0;
    const consumed = (selectedMaterial?.onHoldQuantity ?? 0) - val;
    document.getElementById('reserveModalConsumedHint').innerText =
        `${val} Stk. gehen zurück ins Lager, ${consumed} Stk. gelten als verbraucht.`;
}

function validateReserveInput(input) {
    let val = parseInt(input.value);
    const max = selectedMaterial?.onHoldQuantity ?? 0;
    if (isNaN(val) || val < 0) val = 0;
    if (val > max) val = max;
    input.value = val;
    updateReserveHint();
}

function increaseReserve() {
    const input = document.getElementById('reserveCounterInput');
    const val   = parseInt(input.value);
    if (val < selectedMaterial.onHoldQuantity) input.value = val + 1;
    updateReserveHint();
}

function decreaseReserve() {
    const input = document.getElementById('reserveCounterInput');
    const val   = parseInt(input.value);
    if (val > 0) input.value = val - 1;
    updateReserveHint();
}

async function confirmReservationSettlement() {
    const returnQty = parseInt(document.getElementById('reserveCounterInput').value);
    if (!selectedMaterial || isNaN(returnQty)) return;

    const btn = document.querySelector('#reserveModal .btn-primary');
    const originalContent = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Lädt...`;

    try {
        const res = await fetch(settleReservationUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ material_id: selectedMaterial.id, return_quantity: returnQty })
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === selectedMaterial.id);
        if (m) {
            m.quantity = data.new_quantity;
            m.on_hold_quantity = data.on_hold_quantity;
        }

        filterMaterials();
        bootstrap.Modal.getInstance(document.getElementById('reserveModal')).hide();

        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }

    } catch (e) {
        alert('Fehler beim Abschließen der Reservierung: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalContent;
    }
}

function validateManualInput(input) {
    let val = parseInt(input.value);
    if (isNaN(val) || val < 1) input.value = 1;
}

function increase() {
    const input = document.getElementById('counterInput');
    const val   = parseInt(input.value);
    if (val < selectedMaterial.quantity) input.value = val + 1;
}

function decrease() {
    const input = document.getElementById('counterInput');
    const val   = parseInt(input.value);
    if (val > 1) input.value = val - 1;
}

// ─── CONFIRM CONSUMPTION ──────────────────────────────────────────────────────

async function confirmConsumption() {
    const amountTaken = parseInt(document.getElementById('counterInput').value);
    if (!selectedMaterial || isNaN(amountTaken)) return;

    if (amountTaken > selectedMaterial.quantity) {
        alert('Es kann nicht mehr entnommen werden als verfügbar ist!');
        return;
    }

    const btn = document.querySelector('#materialModal .btn-primary');
    const originalContent = btn.innerHTML;
    btn.disabled    = true;
    btn.innerHTML   = `<span class="spinner-border spinner-border-sm me-2"></span> Wird gebucht...`;

    try {
        const res = await fetch(consumeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ material_id: selectedMaterial.id, quantity: amountTaken })
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === selectedMaterial.id);
        if (m) m.quantity = data.new_quantity;

        filterMaterials();
        bootstrap.Modal.getInstance(document.getElementById('materialModal')).hide();

        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }

    } catch (e) {
        alert('Fehler beim Buchen: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalContent;
    }
}

// ─── CONFIRM RETURN (EINLAGERN) ────────────────────────────────────────────────

async function confirmReturn() {
    const amountReturned = parseInt(document.getElementById('counterInput').value);
    if (!selectedMaterial || isNaN(amountReturned)) return;

    const btn = document.querySelector('#materialModal .btn-danger');
    const originalContent = btn.innerHTML;
    btn.disabled    = true;
    btn.innerHTML   = `<span class="spinner-border spinner-border-sm me-2"></span> Lädt...`;

    try {
        const res = await fetch(returnUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ material_id: selectedMaterial.id, quantity: amountReturned })
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === selectedMaterial.id);
        if (m) m.quantity = data.new_quantity;

        filterMaterials();
        bootstrap.Modal.getInstance(document.getElementById('materialModal')).hide();

        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }

    } catch (e) {
        alert('Fehler beim Einlagern: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalContent;
    }
}

async function confirmReservation() {
    const amountReserved = parseInt(document.getElementById('counterInput').value);
    if (!selectedMaterial || isNaN(amountReserved)) return;

    const btn = document.querySelector('#materialModal .btn-secondary');
    const originalContent = btn.innerHTML;
    btn.disabled    = true;
    btn.innerHTML   = `<span class="spinner-border spinner-border-sm me-2"></span> Lädt...`;

    try {
        const res = await fetch(`${reserveUrl}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ material_id: selectedMaterial.id, quantity: amountReserved })
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === selectedMaterial.id);
        if (m) {
            m.on_hold_quantity = data.on_hold_quantity;
            m.quantity = data.new_quantity;
        }

        filterMaterials();
        bootstrap.Modal.getInstance(document.getElementById('materialModal')).hide();

        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }
        
    } catch (e) {
        alert('Fehler beim Reservieren: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalContent;
    }
}

// ─── ORDER TRIGGER PLUG ───────────────────────────────────────────────────────

async function triggerOrder(materialId) {
    try{
        const res = await fetch(`${orderBaseUrl}/${materialId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === materialId);
        if (m) m.order_status = data.order_status;

        filterMaterials();
        
        alert('Admin hat Bestellung request gestellt. Bitte warten Sie auf Bestätigung.');

    } catch (e) {
        alert('Fehler beim mitteillen Admin: ' + e.message);
    }
}

// ─── MODE SWITCH ──────────────────────────────────────────────────────────────

function switchMode(mode) {
    if (mode === 'shelf') {
        document.getElementById('shelfStep').classList.remove('d-none');
        document.getElementById('materialStep').classList.add('d-none');
        document.getElementById('nameStep').classList.add('d-none');
        document.getElementById('tabShelf').classList.add('active');
        document.getElementById('tabName').classList.remove('active');
    } else {
        document.getElementById('nameStep').classList.remove('d-none');
        document.getElementById('shelfStep').classList.add('d-none');
        document.getElementById('materialStep').classList.add('d-none');
        document.getElementById('tabName').classList.add('active');
        document.getElementById('tabShelf').classList.remove('active');
        setTimeout(() => document.getElementById('globalNameSearch').focus(), 150);
    }
}

// ─── GLOBAL NAME SEARCH ───────────────────────────────────────────────────────

function filterByName() {
    const q = document.getElementById('globalNameSearch').value.toLowerCase().trim();
    const container = document.getElementById('globalNameResults');

    if (q.length < 1) {
        container.innerHTML = '';
        return;
    }

    const filtered = allMaterials.filter(m => m.name.toLowerCase().includes(q));

    if (filtered.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Kein Material gefunden.
        </div>`;
        return;
    }

    container.innerHTML = filtered.map(m => {
        const outOfStock = m.quantity + m.on_hold_quantity <= 0;
        const threshold  = m.threshold ?? 0;
        const on_hold = m.on_hold_quantity ?? 0;
        const orderQty = m.order_quantity ?? 0;
        const available  = m.available_total ?? (m.quantity + on_hold + orderQty);
        const isReserved = m.on_hold_quantity > 0;
        const badgeClass = outOfStock ? 'bg-secondary'
            : available > threshold ? 'bg-success' : 'bg-danger';
        const modalType = isReserved ? 'openReserveModal' : 'openMaterialModal';
        const badgeText  = outOfStock ? 'Kommt gleich' : m.quantity + ' Stk.';

        const shelfHint = m.shelf
            ? `<span class="text-muted small ms-2"><i class="bi bi-geo-alt me-1"></i>${m.shelf}</span>`
            : '';

        const imageTemplate = generateImageHtml(m.image, m.name);
        const orderTemplate = generateOrderHtml(m);

        const onHoldText = isReserved ? `<span class="badge bg-info text-dark ms-2"><i class="bi bi-clock-history me-1"></i>Reserviert: ${on_hold} Stk.</span>` : '';
        const orderText = orderQty > 0
            ? `<span class="badge bg-warning text-dark ms-2"><i class="bi bi-truck me-1"></i>Bestellt: ${orderQty} Stk.</span>`
            : '';

        if (outOfStock) {
            return `
            <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border bg-light text-muted"
                style="cursor: not-allowed;"
                onclick="Swal.fire('Nicht verfügbar', 'Bitte warten Sie auf Nachschub.', 'info')">
                <div class="d-flex align-items-center">
                    ${imageTemplate}
                    <div>
                        <span class="text-decoration-line-through fw-semibold">${m.name}</span>
                        ${shelfHint}
                        <div class="mt-1">${orderTemplate}</div>
                    </div>
                </div>
                <span class="badge ${badgeClass}">${badgeText}</span>
            </div>`;
        }

        return `
        <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border material-item"
                onclick="${modalType}(${m.id}, '${m.name}', ${m.quantity}, '${m.shelf ?? ''}', ${on_hold})">
            <div class="d-flex align-items-center">
                ${imageTemplate}
                <div>
                    <span class="fw-semibold">${m.name} ${onHoldText}${orderText}</span>
                    ${shelfHint}
                    <div class="mt-1">${orderTemplate}</div>
                </div>
            </div>
            <span class="badge ${badgeClass} fs-6">${badgeText}</span>
        </div>`;
    }).join('');
}

// ─── EXPOSE FUNCTIONS CALLED FROM HTML ATTRIBUTES ────────────────────────────
window.filterShelves       = filterShelves;
window.selectShelf         = selectShelf;
window.goBackToShelves     = goBackToShelves;
window.filterMaterials     = filterMaterials;
window.openMaterialModal   = openMaterialModal;
window.openReserveModal              = openReserveModal;
window.increaseReserve               = increaseReserve;
window.decreaseReserve               = decreaseReserve;
window.validateReserveInput          = validateReserveInput;
window.confirmReservationSettlement  = confirmReservationSettlement;
window.validateManualInput = validateManualInput;
window.increase            = increase;
window.decrease            = decrease;
window.confirmConsumption  = confirmConsumption;
window.confirmReturn       = confirmReturn;
window.triggerOrder        = triggerOrder;
window.switchMode          = switchMode;
window.filterByName        = filterByName;