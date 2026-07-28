const token = window.tablarAdmin.token;
const lagerId = window.tablarAdmin.lagerId;

const urls = {
    store:         () => `/admin/lager/${lagerId}/tablar`,
    update:        (id) => `/admin/lager/${lagerId}/tablar/${id}`,
    destroy:       (id) => `/admin/lager/${lagerId}/tablar/${id}`,
    suppliers:     (materialId) => `/admin/lager/${lagerId}/tablar/${materialId}/suppliers`,
    supplierDetach:(materialId, supplierId) => `/admin/lager/${lagerId}/tablar/${materialId}/suppliers/${supplierId}`,
};

let editMode = false;
let currentId = null;
let currentMaterialId = null;
let selectedSupplierId = null;
let searchTimeout = null;
let filterTimeout = null;

// ─── CAMERA ──────────────────────────────────────────────────────────────────

let cameraStream   = null;
let cameraModalInst = null;

async function openCamera() {
    cameraModalInst = new bootstrap.Modal(document.getElementById('cameraModal'));
    cameraModalInst.show();

    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }   // rear cam on mobile
        });
        document.getElementById('cameraStream').srcObject = cameraStream;
    } catch (err) {
        closeCamera();
        showAlert("Kamera konnte nicht geöffnet werden. Bitte Berechtigung prüfen.");
    }
}

function closeCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(t => t.stop());
        cameraStream = null;
    }
    cameraModalInst?.hide();
}

function capturePhoto() {
    const video  = document.getElementById('cameraStream');
    const canvas = document.getElementById('cameraCanvas');

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

    // Show preview
    const preview = document.getElementById('imagePreview');
    preview.src = dataUrl;
    preview.classList.remove('d-none');

    // Store base64 for upload; clear file input so it doesn't override
    document.getElementById('imageCaptured').value = dataUrl;
    document.getElementById('image').value = '';

    closeCamera();
}

// ─── FILE INPUT PREVIEW (existing — keep as-is) ──────────────────────────────

function previewImage(input) {
    if (!input.files?.length) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('imagePreview');
        preview.src = e.target.result;
        preview.classList.remove('d-none');
    };
    reader.readAsDataURL(input.files[0]);

    // Clear any previously captured camera image
    document.getElementById('imageCaptured').value = '';
}

// ─── OPEN ADD MODAL ───────────────────────────────────────────────────────────

function openAddModal() {
    editMode = false;
    currentId = null;

    document.getElementById('modalTitle').innerText = "Neues Material";
    document.getElementById('materialForm').reset();
    document.getElementById('imagePreview').classList.add('d-none');
    document.getElementById('isActive').checked = true;

    new bootstrap.Modal(document.getElementById('materialModal')).show();
}

// ─── OPEN EDIT MODAL ──────────────────────────────────────────────────────────

function openEditModal(button) {
    editMode = true;

    const row = button.closest('.clickable-row');
    currentId = row.getAttribute('data-id');

    document.getElementById('modalTitle').innerText    = "Material bearbeiten";
    document.getElementById('name').value              = row.getAttribute('data-name');
    document.getElementById('code').value              = row.getAttribute('data-code') ?? '';
    document.getElementById('description').value      = row.getAttribute('data-description') ?? '';
    document.getElementById('currentQuantity').value   = row.getAttribute('data-quantity');
    document.getElementById('addQuantity').value       = 0;
    document.getElementById('tablar').value            = row.getAttribute('data-tablar') ?? '';
    document.getElementById('threshold').value         = row.getAttribute('data-threshold') ?? '';
    document.getElementById('type').value              = row.getAttribute('data-type') ?? '';
    document.getElementById('unit').value              = row.getAttribute('data-unit') ?? '';
    document.getElementById('orderStatus').value        = row.getAttribute('data-order-status') ?? '';
    document.getElementById('isWerkzeug').checked       = row.getAttribute('data-is-werkzeug') === '1';
    document.getElementById('isActive').checked         = row.getAttribute('data-is-active') === '1';

    const img = row.getAttribute('data-image');
    const preview = document.getElementById('imagePreview');
    if (img) {
        preview.src = img;
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }

    new bootstrap.Modal(document.getElementById('materialModal')).show();
}

// ─── SAVE MATERIAL (CREATE + UPDATE) ─────────────────────────────────────────

async function saveMaterial() {
    const btn         = event.target;
    const originalText = btn.innerHTML;

    const addQty     = parseInt(document.getElementById('addQuantity')?.value || 0);
    const currentQty = parseInt(document.getElementById('currentQuantity')?.value || 0);
    const name       = document.getElementById('name').value;

    if (!name) {
        showAlert("Bitte alle Felder korrekt ausfüllen");
        return;
    }

    if (editMode && addQty < 0) {
        showAlert("Ungültige Menge");
        return;
    }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('code', document.getElementById('code').value || '');
    formData.append('description', document.getElementById('description').value || '');
    formData.append('quantity', editMode ? (currentQty + addQty) : addQty);
    formData.append('tablar', document.getElementById('tablar').value);
    formData.append('threshold', document.getElementById('threshold').value || '');
    formData.append('type', document.getElementById('type').value || '');
    formData.append('unit', document.getElementById('unit').value || 'Stück');
    formData.append('order_status', document.getElementById('orderStatus').value || '');
    formData.append('is_werkzeug', document.getElementById('isWerkzeug').checked ? '1' : '0');
    formData.append('is_active', document.getElementById('isActive').checked ? '1' : '0');

    const imageFile = document.getElementById('image').files[0];
    const imageCaptured = document.getElementById('imageCaptured').value;

    if (imageFile) {
        // Normal file-picker upload
        formData.append('image', imageFile);

    } else if (imageCaptured) {
        // Camera capture — convert base64 → Blob → File
        const blob = await (await fetch(imageCaptured)).blob();
        formData.append('image', new File([blob], 'capture.jpg', { type: 'image/jpeg' }));
    }

    if (editMode) {
        formData.append('_method', 'PUT');
    }

    btn.disabled  = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Speichern...`;

    const url = editMode ? urls.update(currentId) : urls.store();

    console.log("Form Data:", Array.from(formData.entries()));

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token },
            body: formData
        });

        console.log("Response:", res);

        if (!res.ok) throw new Error();

        location.reload();

    } catch (e) {
        showAlert("Fehler beim Speichern - Bitte erneut versuchen");
        btn.disabled  = false;
        btn.innerHTML = originalText;
    }
}

// ─── DELETE MATERIAL ──────────────────────────────────────────────────────────

async function deleteMaterial(id) {
    if (!confirm("Wirklich löschen?")) return;

    try {
        const res = await fetch(urls.destroy(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': token }
        });

        if (!res.ok) throw new Error();

        location.reload();

    } catch {
        alert("Fehler beim Löschen");
    }
}

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        preview.src = URL.createObjectURL(input.files[0]);
        preview.classList.remove('d-none');
    }
}

// ─── OPEN SUPPLIER MODAL ──────────────────────────────────────────────────────

async function openSupplierModal(button) {
    const row = button.closest('.clickable-row');
    currentMaterialId = row.getAttribute('data-id');
    const name = row.getAttribute('data-name');

    document.getElementById('supplierModalMaterialName').innerText = name;
    document.getElementById('supplierLoading').classList.remove('d-none');
    document.getElementById('supplierError').classList.add('d-none');
    document.getElementById('supplierEmpty').classList.add('d-none');
    document.getElementById('supplierList').classList.add('d-none');
    document.getElementById('supplierList').innerHTML = '';
    clearSupplierSelection();

    new bootstrap.Modal(document.getElementById('supplierModal')).show();

    try {
        await loadAttachedSuppliers();
    } catch (e) {
        document.getElementById('supplierLoading').classList.add('d-none');
        document.getElementById('supplierError').classList.remove('d-none');
    }
}

// ─── SUPPLIER SEARCH INPUT ────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {

    document.getElementById('supplierSearchInput').addEventListener('input', function () {
        const q = this.value.trim();

        selectedSupplierId = null;
        document.getElementById('supplierSearchSelected').classList.add('d-none');

        clearTimeout(searchTimeout);

        if (q.length < 1) {
            document.getElementById('supplierSearchResults').classList.add('d-none');
            return;
        }

        searchTimeout = setTimeout(() => searchSuppliers(q), 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#supplierSearchInput') && !e.target.closest('#supplierSearchResults')) {
            document.getElementById('supplierSearchResults').classList.add('d-none');
        }
    });

    // ─── FILTERS ──────────────────────────────────────────────────────────────

    const filterName     = document.getElementById('filterName');
    const filterCode     = document.getElementById('filterCode');
    const filterQuantity = document.getElementById('filterQuantity');
    const filterShelf    = document.getElementById('filterShelf');
    const qtyValue       = document.getElementById('qtyValue');
    const filterForm     = document.getElementById('filterForm');

    filterQuantity.addEventListener('input', () => {
        qtyValue.innerText = filterQuantity.value;
    });

    filterQuantity.addEventListener('change', () => {
        filterForm.submit();
    });

    function debounceSubmit() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => filterForm.submit(), 1500);
    }

    [filterName, filterCode, filterShelf].forEach(input => {
        input.addEventListener('input', debounceSubmit);

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(filterTimeout);
                filterForm.submit();
            }
        });

        input.addEventListener('blur', () => {
            clearTimeout(filterTimeout);
            filterForm.submit();
        });
    });

    // ─── HIGHLIGHT + SCROLL (deep-link from show page) ────────────────────────
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    if (highlightId) {
        const row = document.getElementById('material-' + highlightId);
        if (row) {
            row.classList.add('table-warning');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => row.classList.remove('table-warning'), 3000);
        }
        // Strip the marker so the back button is clean.
        urlParams.delete('highlight');
        const qs = urlParams.toString();
        const clean = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
        window.history.replaceState({}, '', clean);
    }

});

// ─── SEARCH SUPPLIERS ─────────────────────────────────────────────────────────

async function searchSuppliers(q) {
    try {
        const res = await fetch(`/admin/suppliers/search?q=${encodeURIComponent(q)}`, {
            headers: { 'X-CSRF-TOKEN': token }
        });
        if (!res.ok) throw new Error();

        const suppliers = await res.json();
        renderSearchResults(suppliers);
    } catch (e) {
        console.error('Supplier search failed', e);
    }
}

// ─── RENDER SEARCH RESULTS ────────────────────────────────────────────────────

function renderSearchResults(suppliers) {
    const list = document.getElementById('supplierSearchResults');
    list.innerHTML = '';

    if (suppliers.length === 0) {
        list.innerHTML = `<li class="list-group-item text-muted small py-2">Kein Ergebnis gefunden</li>`;
        list.classList.remove('d-none');
        return;
    }

    suppliers.forEach(s => {
        const li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';
        li.style.cursor = 'pointer';
        li.innerHTML = `
            <div>
                <span class="fw-semibold">${s.name}</span>
                ${s.company ? `<span class="text-muted ms-2 small">${s.company}</span>` : ''}
            </div>
            ${s.email ? `<span class="text-muted small">${s.email}</span>` : ''}
        `;
        li.addEventListener('click', () => selectSupplier(s));
        list.appendChild(li);
    });

    list.classList.remove('d-none');
}

// ─── SELECT SUPPLIER ──────────────────────────────────────────────────────────

function selectSupplier(s) {
    selectedSupplierId = s.id;

    document.getElementById('supplierSearchInput').value = '';
    document.getElementById('supplierSearchResults').classList.add('d-none');
    document.getElementById('supplierSearchSelectedName').innerText =
        s.company ? `${s.name} (${s.company})` : s.name;
    document.getElementById('supplierSearchSelected').classList.remove('d-none');
}

// ─── CLEAR SUPPLIER SELECTION ─────────────────────────────────────────────────

function clearSupplierSelection() {
    selectedSupplierId = null;
    document.getElementById('supplierSearchInput').value          = '';
    document.getElementById('supplierSearchResults').classList.add('d-none');
    document.getElementById('supplierSearchSelected').classList.add('d-none');
    document.getElementById('supplierSearchSelectedName').innerText = '';
}

// ─── LOAD ATTACHED SUPPLIERS ──────────────────────────────────────────────────

async function loadAttachedSuppliers() {
    document.getElementById('supplierLoading').classList.remove('d-none');
    document.getElementById('supplierList').classList.add('d-none');
    document.getElementById('supplierEmpty').classList.add('d-none');
    document.getElementById('supplierList').innerHTML = '';

    const res = await fetch(urls.suppliers(currentMaterialId), {
        headers: { 'X-CSRF-TOKEN': token }
    });

    if (!res.ok) throw new Error('Could not load attached suppliers.');

    const suppliers = await res.json();
    document.getElementById('supplierLoading').classList.add('d-none');

    if (suppliers.length === 0) {
        document.getElementById('supplierEmpty').classList.remove('d-none');
        return;
    }

    const list = document.getElementById('supplierList');

    suppliers.forEach(s => {
        const item = document.createElement('li');
        item.className = 'list-group-item d-flex justify-content-between align-items-center px-0';
        item.innerHTML = `
            <div>
                <span class="fw-semibold">${s.name}</span>
                ${s.company     ? `<span class="text-muted ms-2 small">${s.company}</span>` : ''}
                ${s.is_current  ? `<span class="badge bg-success ms-2"><i class="bi bi-star-fill me-1"></i>Aktueller Lieferant</span>` : ''}
                <div class="text-muted small mt-1">
                    ${s.email        ? `<i class="bi bi-envelope me-1"></i>${s.email}` : ''}
                    ${s.phone_number ? `<span class="ms-3"><i class="bi bi-telephone me-1"></i>${s.phone_number}</span>` : ''}
                    ${s.website      ? `<span class="ms-3"><i class="bi bi-globe me-1"></i><a href="${s.website}" target="_blank" class="text-decoration-none">${s.website}</a></span>` : ''}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="/admin/suppliers/edit/${s.id}" class="btn btn-outline-primary btn-sm" title="Bearbeiten">
                    <i class="bi bi-pencil"></i>
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm" title="Vom Material entfernen" onclick="detachSupplier(${s.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        list.appendChild(item);
    });

    list.classList.remove('d-none');
}

// ─── ATTACH SUPPLIER ──────────────────────────────────────────────────────────

async function attachSupplier() {
    if (!selectedSupplierId) {
        showAlert('Bitte zuerst einen Lieferanten auswählen.', 'warning');
        return;
    }

    document.getElementById('supplierLoading').classList.remove('d-none');

    try {
        const res = await fetch(urls.suppliers(currentMaterialId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ supplier_id: selectedSupplierId })
        });

        if (!res.ok) throw new Error();

        clearSupplierSelection();
        await loadAttachedSuppliers();
    } catch (e) {
        document.getElementById('supplierLoading').classList.add('d-none');
        document.getElementById('supplierError').classList.remove('d-none');
    }
}

// ─── DETACH SUPPLIER ──────────────────────────────────────────────────────────

async function detachSupplier(supplierId) {
    if (!confirm('Möchten Sie diesen Lieferanten wirklich von diesem Material entfernen?')) return;

    document.getElementById('supplierLoading').classList.remove('d-none');

    try {
        const res = await fetch(`/admin/tablar/${currentMaterialId}/suppliers/${supplierId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': token }
        });

        if (!res.ok) throw new Error();

        await loadAttachedSuppliers();
    } catch (e) {
        document.getElementById('supplierLoading').classList.add('d-none');
        document.getElementById('supplierError').classList.remove('d-none');
    }
}

// ─── SHOW ALERT ───────────────────────────────────────────────────────────────

function showAlert(message, type = 'danger') {
    const container = document.getElementById('alert-container');
    const alert     = document.createElement('div');
    alert.className = `alert alert-${type} shadow-lg border-0 fade show`;
    alert.innerHTML = `<strong>${message}</strong>`;
    container.appendChild(alert);

    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 500);
    }, 5000);
}