@extends('admin.layouts.index')

@section('content')

<div id="alert-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; width: auto; min-width: 300px;"></div>

<div class="container py-4">

    <!-- HEADER CARD -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Materialverwaltung (Werkstatt)</h4>

            <!-- ADD BUTTON -->
            <button class="btn btn-secondary" onclick="openAddModal()">
                + Neues Material
            </button>
        </div>

        <div class="card-body">
            <!-- FILTERS -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">

                        <!-- Name Filter -->
                        <div class="col-md-4">
                            <label class="form-label">Materialname</label>
                            <input type="text" id="filterName" class="form-control" placeholder="z.B. Schraube">
                        </div>

                        <!-- Quantity Range -->
                        <div class="col-md-4">
                            <label class="form-label">
                                Menge (max): <span id="qtyValue">{{ $maxQuantity }}</span>
                            </label>
                            <input type="range" class="form-range" min="0" max="{{ $maxQuantity }}" value="{{ $maxQuantity }}" id="filterQuantity">
                        </div>

                        <!-- Tablar -->
                        <div class="col-md-4">
                            <label class="form-label">Tablar</label>
                            <input type="text" id="filterShelf" class="form-control" placeholder="z.B. A1">
                        </div>

                    </div>
                </div>
            </div>

            <table class="table table-hover align-middle border-top">
                <thead class="table-light">
                    <tr class="text-secondary text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.05em;">
                        <th>Name</th>
                        <th>Menge</th>
                        <th>Fach</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materials as $material)
                        <tr class="clickable-row
                            @if(
                                ($material->threshold && $material->quantity <= $material->threshold) ||
                                (!$material->threshold && $material->quantity <= 20)
                            ) table-danger
                            @endif"
                            data-id="{{ $material->id }}"
                            data-name="{{ $material->name }}"
                            data-quantity="{{ $material->quantity }}"
                            data-tablar="{{ $material->tablar ?? '' }}"
                            data-threshold="{{ $material->threshold ?? '' }}"
                            data-type="{{ $material->type ?? '' }}"
                            >
                            <td class="fw-bold text-dark">{{ $material->name }}</td>
                            <td><span class="badge rounded-pill bg-light text-dark border">{{ $material->quantity }} Stk.</span></td>
                            <td class="text-muted small">{{ $material->tablar }}</td>
                            <td class="text-end">
                                <button class="btn btn-outline-primary btn-sm me-1" onclick="openEditModal(this)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteMaterial('{{ $material->id }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD / EDIT MODAL -->
<div class="modal fade" id="materialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="modalTitle">Material hinzufügen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="materialForm">
                    <input type="hidden" id="materialId">

                    <!-- NAME -->
                    <div class="mb-3">
                        <label class="form-label">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="name" class="form-control" required>
                    </div>

                    <!-- CURRENT QUANTITY (READ ONLY) -->
                    <div class="mb-2">
                        <label class="form-label">Aktueller Bestand</label>
                        <input type="number" id="currentQuantity" class="form-control bg-light" readonly>
                    </div>

                    <!-- ADD STOCK -->
                    <div class="mb-3">
                        <label class="form-label">
                            Hinzufügen (+ Menge) <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="addQuantity" class="form-control" min="0" value="0">
                    </div>

                    <!-- TABLAR -->
                    <div class="mb-3">
                        <label class="form-label">
                            Fach / Tablar <span class="text-muted">(optional)</span>
                        </label>
                        <input type="text" id="tablar" class="form-control">
                    </div>

                    <!-- THRESHOLD -->
                    <div class="mb-3">
                        <label class="form-label">
                            Mindestbestand <span class="text-muted">(optional)</span>
                        </label>
                        <input type="number" id="threshold" class="form-control" min="0" placeholder="z.B. 50">
                    </div>

                    <!-- TYPE -->
                    <div class="mb-3">
                        <label class="form-label">
                            Typ <span class="text-muted">(optional)</span>
                        </label>
                        <input type="text" id="type" class="form-control" placeholder="z.B. Schrauben, Kunststoff">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button class="btn btn-primary" onclick="saveMaterial()">Speichern</button>
            </div>

        </div>
    </div>
</div>

<script>
let editMode = false;
let currentId = null;

// CSRF helper
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// OPEN ADD
function openAddModal() {
    editMode = false;
    currentId = null;

    document.getElementById('modalTitle').innerText = "Neues Material";
    document.getElementById('materialForm').reset();

    new bootstrap.Modal(document.getElementById('materialModal')).show();
}

// OPEN EDIT
function openEditModal(button) {
    editMode = true;

    const row = button.closest('.clickable-row');
    currentId = row.getAttribute('data-id');

    document.getElementById('modalTitle').innerText = "Material bearbeiten";

    document.getElementById('name').value = row.getAttribute('data-name');
    document.getElementById('currentQuantity').value = row.getAttribute('data-quantity');
    document.getElementById('addQuantity').value = 0;

    document.getElementById('tablar').value = row.getAttribute('data-tablar') ?? '';
    document.getElementById('threshold').value = row.getAttribute('data-threshold') ?? '';
    document.getElementById('type').value = row.getAttribute('data-type') ?? '';

    new bootstrap.Modal(document.getElementById('materialModal')).show();
}

// HELPER: Show floating error for 5 seconds
function showAlert(message, type = 'danger') {
    const container = document.getElementById('alert-container');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} shadow-lg border-0 fade show`;
    alert.innerHTML = `<strong>${message}</strong>`;
    container.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 500);
    }, 5000);
}

/// SAVE (CREATE + UPDATE)
async function saveMaterial() {
    const btn = event.target;
    const originalText = btn.innerHTML;

    const addQty = parseInt(document.getElementById('addQuantity')?.value || 0);
    const currentQty = parseInt(document.getElementById('currentQuantity')?.value || 0);

    const data = {
        name: document.getElementById('name').value,
        quantity: editMode ? (currentQty + addQty) : addQty,
        tablar: document.getElementById('tablar').value,
        threshold: document.getElementById('threshold').value || null,
        type: document.getElementById('type').value || null
    };

    if (!data.name) {
        showAlert("Bitte alle Felder korrekt ausfüllen");
        return;
    }

    if (editMode && addQty < 0) {
        showAlert("Ungültige Menge");
        return;
    }

    // Start Loading Transition
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Speichern...`;

    let url = '/admin/tablar';
    let method = 'POST';

    if (editMode) {
        url = `/admin/tablar/${currentId}`;
        method = 'PUT';
    }

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(data)
        });

        if (!res.ok) throw new Error();

        location.reload(); // simple + reliable for MVP

    } catch (e) {
        showAlert("Fehler beim Speichern - Bitte erneut versuchen");
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// DELETE (SIMULATED)
async function deleteMaterial(id) {
    if (!confirm("Wirklich löschen?")) return;

    try {
        const res = await fetch(`/admin/tablar/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token
            }
        });

        if (!res.ok) throw new Error();

        location.reload();

    } catch {
        alert("Fehler beim Löschen");
    }
}

// FILTER ELEMENTS
const filterName = document.getElementById('filterName');
const filterQuantity = document.getElementById('filterQuantity');
const filterShelf = document.getElementById('filterShelf');
const qtyValue = document.getElementById('qtyValue');

// UPDATE SLIDER LABEL
filterQuantity.addEventListener('input', () => {
    qtyValue.innerText = filterQuantity.value;
    applyFilters();
});

// INPUT EVENTS
filterName.addEventListener('keyup', applyFilters);
filterShelf.addEventListener('keyup', applyFilters);

// MAIN FILTER FUNCTION
function applyFilters() {
    const name = filterName.value.toLowerCase();
    const maxQty = parseInt(filterQuantity.value);
    const shelf = filterShelf.value.toLowerCase();

    const rows = document.querySelectorAll('.clickable-row');

    rows.forEach(row => {
        const rowName = (row.dataset.name).toLowerCase();
        const rowQty = parseInt(row.dataset.quantity);
        const rowShelf = (row.dataset.tablar).toLowerCase();

        const matchName = rowName.includes(name);
        const matchQty = rowQty <= maxQty;
        const matchShelf = rowShelf.includes(shelf);

        if (matchName && matchQty && matchShelf) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

@endsection