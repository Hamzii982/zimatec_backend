@extends('user.layouts.index')

@section('title', 'Tablar Übersicht')

@section('content')
<div class="container py-4">

    <!-- HEADER -->
    <div class="text-center mb-4">
        <h1 class="fw-bold">
            🏭 Tablar-Übersicht
        </h1>
        <p class="text-muted">Material auswählen und Verbrauch buchen</p>
    </div>

    <!-- SEARCH -->
    <div class="mb-4">
        <input 
            type="text" 
            id="searchInput" 
            class="form-control form-control-lg"
            placeholder="🔍 Suche nach Material oder Tablar..."
            onkeyup="searchMaterial()"
        >
    </div>

    <!-- SEARCH RESULTS -->
    <div id="searchResults" class="mb-4"></div>

    <!-- TABLES -->
    <div class="row" id="materialTables">
        @foreach($columns as $column)
            <div class="col-md-6">
                <table class="table table-hover shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Menge</th>
                            <th>Tablar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($column as $material)
                            @php
                                $quantity = (int)$material['quantity'];
                                $threshold = $material['threshold'] ?? 20; // Use material threshold or default 20
                                $isOutOfStock = $quantity <= 0;
                            @endphp
                            <tr class="{{ $isOutOfStock ? 'table-light text-muted' : 'clickable-row' }}"
                                @if(!$isOutOfStock)    
                                    onclick="openMaterialModal({{ $material['id'] }}, '{{ $material['name'] }}', {{ $material['quantity'] }}, '{{ $material['shelf'] }}')"
                                    style="cursor: pointer;"
                                @else    
                                    onclick="Swal.fire('Nicht verfügbar', 'Dieses Material ist momentan nicht im Tablar verfügbar. Bitte warten Sie auf Nachschub.', 'info')"
                                    style="cursor: not-allowed;"
                                @endif
                            >
                                <td class="{{ $isOutOfStock ? 'text-decoration-line-through' : '' }}">{{ $material['name'] }}</td>
                                <td>
                                    @if($isOutOfStock)
                                        <span class="badge bg-secondary text-uppercase">Kommt gleich</span>
                                    @else
                                        <span class="badge {{ $quantity > $threshold ? 'bg-success' : 'bg-danger' }}">
                                            {{ $quantity }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $material['shelf'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="materialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">

            <h3 id="modalMaterialName" class="mb-2"></h3>
            <p class="text-muted" id="modalShelf"></p>

            <!-- COUNTER -->
            <div class="d-flex justify-content-center align-items-center my-4">
                <button class="btn btn-lg btn-outline-danger" onclick="decrease()">−</button>
                <input type="number" id="counterInput" 
                    class="form-control form-control-lg mx-3 text-center fw-bold fs-2" 
                    style="width: 100px;" 
                    value="1" min="1"
                    oninput="validateManualInput(this)">
                <button class="btn btn-lg btn-outline-success" onclick="increase()">+</button>
            </div>

            <!-- ACTION BUTTON -->
            <button class="btn btn-primary btn-lg w-100" onclick="confirmConsumption()">
                ✅ Material entnommen
            </button>

        </div>
    </div>
</div>

<style>
.clickable-row {
    cursor: pointer;
    transition: 0.2s;
}
.clickable-row:hover {
    background-color: #f2f2f2;
}

#searchResults .result-item {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    cursor: pointer;
}
#searchResults .result-item:hover {
    background: #f8f9fa;
}
</style>

<script>
let selectedMaterial = null;

// OPEN MODAL
function openMaterialModal(id, name, quantity, shelf) {
    selectedMaterial = { id, name, quantity, shelf };

    document.getElementById('modalMaterialName').innerText = name;
    document.getElementById('modalShelf').innerText = "Tablar: " + shelf;
    const input = document.getElementById('counterInput');
    input.value = 1;
    input.max = quantity;

    let modal = new bootstrap.Modal(document.getElementById('materialModal'));
    modal.show();
}

// Logic for manual typing
function validateManualInput(input) {
    let val = parseInt(input.value);
    
    // Don't allow more than available
    if (val > selectedMaterial.quantity) {
        input.value = selectedMaterial.quantity;
    }
    
    // Don't allow less than 1
    if (val < 1 || isNaN(val)) {
        input.value = 1;
    }
}

// COUNTER
function increase() {
    const input = document.getElementById('counterInput');
    let currentVal = parseInt(input.value);
    
    if (currentVal < selectedMaterial.quantity) {
        input.value = currentVal + 1;
    }
}

function decrease() {
    const input = document.getElementById('counterInput');
    let currentVal = parseInt(input.value);
    
    if (currentVal > 1) {
        input.value = currentVal - 1;
    }
}

function showAlert(message) {
    let alertDiv = document.createElement('div');
    alertDiv.className = "alert alert-danger alert-dismissible fade show";
    alertDiv.role = "alert";
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    document.querySelector('.container').prepend(alertDiv);
}

// CONFIRM ACTION
async function confirmConsumption() {
    const input = document.getElementById('counterInput');
    const amountTaken = parseInt(input.value);
    
    if (!selectedMaterial || isNaN(amountTaken)) return;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const res = await fetch('/tablar/consume', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                material_id: selectedMaterial.id,
                quantity: amountTaken
            })
        });

        if (!res.ok) {
            const err = await res.text();
            throw new Error(err);
        }

        const data = await res.json();

        // Update UI with backend truth (NOT local calculation)
        updateTableUI(selectedMaterial.name, data.new_quantity);

        selectedMaterial.quantity = data.new_quantity;

        let modalElement = document.getElementById('materialModal');
        let modalInstance = bootstrap.Modal.getInstance(modalElement);
        modalInstance.hide();

    } catch (e) {
        showAlert("Fehler: " + e.message);
    }
}

function updateTableUI(name, newQuantity) {
    // Find all table rows
    const threshold = 20; // Matches your Blade default
    const rows = document.querySelectorAll('.clickable-row');
    
    rows.forEach(row => {
        // If the first cell (Name) matches our material
        // Find row by matching the first cell (Name)
        if (row.cells[0] && row.cells[0].innerText.trim() === name) {
            const qtyCell = row.cells[1];
            const nameCell = row.cells[0];
            const shelf = row.cells[2].innerText;
            const materialId = selectedMaterial.id; // Ensure this variable is available globally

            // 1. Update the Quantity Cell with Badge/Coming Soon
            if (newQuantity <= 0) {
                qtyCell.innerHTML = `<span class="badge bg-secondary text-uppercase">Coming Soon</span>`;
                
                // Add "Out of Stock" styling
                row.classList.add('table-light', 'text-muted');
                row.classList.remove('clickable-row');
                nameCell.classList.add('text-decoration-line-through');
                row.style.cursor = "not-allowed";

                // Update onclick to the "Warning" popup
                row.onclick = function() {
                    Swal.fire('Nicht verfügbar', 'Dieses Material ist momentan nicht im Tablar verfügbar.', 'info');
                };
            } else {
                // Determine Badge Color
                const badgeClass = newQuantity > threshold ? 'bg-success' : 'bg-danger';
                qtyCell.innerHTML = `<span class="badge ${badgeClass}">${newQuantity}</span>`;

                // Restore "In Stock" styling
                row.classList.remove('table-light', 'text-muted');
                row.classList.add('clickable-row');
                nameCell.classList.remove('text-decoration-line-through');
                row.style.cursor = "pointer";

                // Update onclick to allow the modal again
                row.onclick = function() {
                    openMaterialModal(materialId, name, newQuantity, shelf);
                };
            }
            
            // Visual feedback: flash the row green
            row.style.transition = "background-color 0.5s";
            row.classList.add('table-success');
            setTimeout(() => row.classList.remove('table-success'), 1000);
        }
    });
}

// SEARCH (client-side simulation for now)
function searchMaterial() {
    let query = document.getElementById('searchInput').value.toLowerCase();
    let resultsDiv = document.getElementById('searchResults');
    resultsDiv.innerHTML = "";

    if (query.length === 0) return;

    // FIX: Use values() and flat() to keep the object structure
    // @json($columns) gives us [[{obj}, {obj}], [{obj}, {obj}]]
    // let materials = @json($columns).flat();
    // Direct use of the clean list from PHP
    let materials = @json($flatList);

    let filtered = materials.filter(m => {
        // Check if name or shelf exists and contains the query
        return (m.name && m.name.toLowerCase().includes(query)) || 
               (m.shelf && m.shelf.toLowerCase().includes(query));
    });

    // Handle results
    if (filtered.length === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-warning">Kein Material gefunden.</div>';
        return;
    }

    filtered.forEach(m => {
        let div = document.createElement('div');
        div.className = "p-2 border-bottom search-result-item";
        div.style.cursor = "pointer";
        div.innerHTML = `<strong>${m.name}</strong> <small class="text-muted">(Menge: ${m.quantity} | Tablar: ${m.shelf})</small>`;
        
        // Add click event to open your existing modal
        div.onclick = () => openMaterialModal(m.id, m.name, m.quantity, m.shelf);
        
        resultsDiv.appendChild(div);
    });
}
</script>
@endsection