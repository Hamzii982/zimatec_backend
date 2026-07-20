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
                <!-- QUICK-TOGGLE FILTERS -->
                @php
                    $baseQuery = request()->except(['low_stock', 'empty', 'status', 'page']);
                    $indexUrl  = route('admin.tablar.index', ['lager_id' => $lager->id]);
                @endphp
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    {{-- Niedrigerbestand --}}
                    @php $lowStockOn = request()->boolean('low_stock'); @endphp
                    <a href="{{ $lowStockOn ? $indexUrl.'?'.http_build_query($baseQuery) : $indexUrl.'?'.http_build_query($baseQuery + ['low_stock' => 1]) }}"
                       class="btn btn-sm {{ $lowStockOn ? 'btn-warning active' : 'btn-outline-warning' }}">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ __('tablar.filter.low_stock') }}
                    </a>

                    {{-- Leere Materialien --}}
                    @php $emptyOn = request()->boolean('empty'); @endphp
                    <a href="{{ $emptyOn ? $indexUrl.'?'.http_build_query($baseQuery) : $indexUrl.'?'.http_build_query($baseQuery + ['empty' => 1]) }}"
                       class="btn btn-sm {{ $emptyOn ? 'btn-secondary active' : 'btn-outline-secondary' }}">
                        <i class="bi bi-box me-1"></i> {{ __('tablar.filter.empty') }}
                    </a>

                    {{-- Status (real form, GET, shareable) --}}
                    <form method="GET" action="{{ $indexUrl }}" class="d-inline-flex align-items-center">
                        @foreach($baseQuery as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endforeach
                        @if(request('low_stock'))<input type="hidden" name="low_stock" value="1">@endif
                        @if(request('empty'))<input type="hidden" name="empty" value="1">@endif
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('tablar.filter.status') }}</option>
                            @foreach(['notified','ordered','blocked','delivered'] as $s)
                                <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('tablar.status.'.$s) }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <!-- FILTERS -->
                <form id="filterForm" method="GET" action="{{ route('admin.tablar.index', $lager->id) }}">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <div class="row g-3 align-items-end">

                                <!-- Name Filter -->
                                <div class="col-md-3">
                                    <label class="form-label">Materialname</label>
                                    <input
                                        type="text"
                                        name="name"
                                        id="filterName"
                                        class="form-control"
                                        placeholder="z.B. Schraube"
                                        value="{{ request('name') }}"
                                    >
                                </div>

                                <!-- Code Filter -->
                                <div class="col-md-3">
                                    <label class="form-label">Code</label>
                                    <input
                                        type="text"
                                        name="code"
                                        id="filterCode"
                                        class="form-control"
                                        placeholder="z.B. ART-001"
                                        value="{{ request('code') }}"
                                    >
                                </div>

                                <!-- Quantity Range -->
                                <div class="col-md-3">
                                    <label class="form-label">
                                        Menge (max): <span id="qtyValue">{{ request('max_qty', $maxQuantity) }}</span>
                                    </label>
                                    <input
                                        type="range"
                                        class="form-range"
                                        min="0"
                                        max="{{ $maxQuantity }}"
                                        value="{{ request('max_qty', $maxQuantity) }}"
                                        id="filterQuantity"
                                        name="max_qty"
                                    >
                                </div>

                                <!-- Tablar -->
                                <div class="col-md-2">
                                    <label class="form-label">Regal</label>
                                    <input
                                        type="text"
                                        name="shelf"
                                        id="filterShelf"
                                        class="form-control"
                                        placeholder="z.B. A1"
                                        value="{{ request('shelf') }}"
                                    >
                                </div>

                                <!-- Submit -->
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-filter w-100" title="Filter anwenden">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>

                            </div>

                            <!-- Active filters + reset -->
                            @if(request()->hasAny(['name', 'code', 'shelf', 'max_qty', 'low_stock', 'empty', 'status']))
                            <div class="mt-2">
                                <a href="{{ route('admin.tablar.index', $lager->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i> {{ __('tablar.filter.reset') }}
                                </a>
                            </div>
                            @endif

                        </div>
                    </div>
                </form>

                <div class="table-responsive-wrapper">
                    <table class="table table-hover align-middle border-top">
                        <thead class="table-light">
                            <tr class="text-secondary text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.05em;">
                                <th></th>
                                <th>Bezeichnung</th>
                                <th>Beschreibung</th>
                                <th>Menge</th>
                                <th>Regal</th>
                                <th>Mindestbestand</th>
                                <th>Status</th>
                                <th class="text-end">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($materials as $material)
                                <tr id="material-{{ $material->id }}"
                                    data-highlight="{{ $material->id }}"
                                    class="clickable-row
                                @if(!is_null($material->threshold) && (int) $material->threshold > 0 && $material->available_total <= $material->threshold) table-danger
                                @endif
                                @if(!$material->is_active) text-muted @endif"
                                data-id="{{ $material->id }}"
                                data-name="{{ $material->name }}"
                                data-code="{{ $material->code ?? '' }}"
                                data-description="{{ $material->description ?? '' }}"
                                data-quantity="{{ $material->quantity }}"
                                data-on-hold="{{ (int) $material->on_hold_quantity }}"
                                data-order-quantity="{{ (int) $material->order_quantity }}"
                                data-available-total="{{ $material->available_total }}"
                                data-tablar="{{ $material->tablar ?? '' }}"
                                data-threshold="{{ $material->threshold ?? '' }}"
                                data-type="{{ $material->type ?? '' }}"
                                data-lager-id="{{ $material->lager_id ?? '' }}"
                                data-order-status="{{ $material->order_status ?? '' }}"
                                data-is-werkzeug="{{ $material->is_werkzeug ? '1' : '0' }}"
                                data-is-active="{{ $material->is_active ? '1' : '0' }}"
                                data-image="{{ $material->image ? asset('storage/'.$material->image) : '' }}"
                                >
                                    <td>
                                        @if($material->image)
                                            <img src="{{ asset('storage/'.$material->image) }}" alt="" width="40" height="40" class="rounded object-fit-cover">
                                        @else
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">
                                        <a href="{{ route('admin.tablar.show', ['lager_id' => $material->lager_id, 'id' => $material->id]) }}" class="text-decoration-none text-dark">
                                            {{ $material->name }}
                                            @if($material->is_werkzeug)
                                                <span class="badge bg-secondary ms-1" title="Werkzeug"><i class="bi bi-wrench"></i></span>
                                            @endif
                                            @if($material->code)
                                                <br/><code class="text-muted small">{{ $material->code }}</code>
                                            @endif
                                        </a>
                                    </td>
                                    <td class="text-muted small" style="max-width: 220px;">
                                        @if($material->description)
                                            <span class="d-inline-block text-truncate" style="max-width: 220px;">{{ $material->description }}</span>
                                        @else
                                            <span>—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border">{{ $material->quantity }} Stk.</span>
                                        @if((int) $material->on_hold_quantity > 0)
                                            <span class="badge rounded-pill bg-info-subtle text-info-emphasis border ms-1"
                                                  data-bs-toggle="tooltip"
                                                  title="Reserviert (vom Bestand abgezogen)">
                                                <i class="bi bi-clock-history me-1"></i>{{ (int) $material->on_hold_quantity }}
                                            </span>
                                        @endif
                                        @if((int) $material->order_quantity > 0)
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border ms-1"
                                                  data-bs-toggle="tooltip"
                                                  title="Bestellt (Lieferung erwartet)">
                                                <i class="bi bi-truck me-1"></i>{{ (int) $material->order_quantity }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $material->tablar }}</td>
                                    <td>
                                        @if(!is_null($material->threshold) && (int) $material->threshold > 0)
                                            <span class="badge bg-light text-dark border" data-bs-toggle="tooltip" title="0 = keine Warnung">{{ $material->threshold }}</span>
                                        @else
                                            <span class="text-muted small" data-bs-toggle="tooltip" title="0 = keine Warnung">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($material->order_status)
                                            <span class="badge bg-info-subtle text-info-emphasis" data-bs-toggle="tooltip" title="{{ $material->status_label }}">{{ $material->status_label }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-dark btn-sm me-1" onclick="openSupplierModal(this)">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
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
                <div class="d-flex justify-content-center mt-4">
                    {{ $materials->links() }}
                </div>
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
                    <form id="materialForm" enctype="multipart/form-data">
                        <input type="hidden" id="materialId">
                    
                        <!-- IMAGE -->
                        <div class="mb-3 text-center">
                            <img id="imagePreview" class="d-none rounded mb-2 object-fit-cover" style="width:100px;height:100px;">

                            <!-- Captured via camera (hidden, populated by JS) -->
                            <input type="hidden" id="imageCaptured">

                            <div class="d-flex gap-2 justify-content-center">
                                <label class="btn btn-outline-secondary btn-sm mb-0">
                                    <i class="bi bi-folder2-open"></i> Datei wählen
                                    <input type="file" id="image" class="d-none" accept="image/*" onchange="previewImage(this)">
                                </label>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCamera()">
                                    <i class="bi bi-camera"></i> Kamera
                                </button>
                            </div>
                        </div>

                        <!-- Camera Modal -->
                        <div class="modal fade" id="cameraModal" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h6 class="modal-title">Foto aufnehmen</h6>
                                        <button type="button" class="btn-close" onclick="closeCamera()"></button>
                                    </div>
                                    <div class="modal-body text-center p-2">
                                        <video id="cameraStream" autoplay playsinline class="w-100 rounded" style="max-height:300px;object-fit:cover;"></video>
                                        <canvas id="cameraCanvas" class="d-none"></canvas>
                                    </div>
                                    <div class="modal-footer justify-content-center">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="closeCamera()">Abbrechen</button>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="capturePhoto()">
                                            <i class="bi bi-circle-fill me-1"></i> Aufnehmen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                        <!-- NAME -->
                        <div class="mb-3">
                            <label class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" class="form-control" required>
                        </div>

                        <!-- CODE -->
                        <div class="mb-3">
                            <label class="form-label">
                                Artikelnummer <span class="text-muted">(optional)</span>
                            </label>
                            <input type="text" id="code" class="form-control" maxlength="64" placeholder="z.B. ART-001">
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="mb-3">
                            <label class="form-label">
                                Beschreibung <span class="text-muted">(optional)</span>
                            </label>
                            <textarea id="description" class="form-control" maxlength="2000" rows="3" placeholder="Notizen, Maße, Verwendung …"></textarea>
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
                                Regal / Tablar <span class="text-muted">(optional)</span>
                            </label>
                            <input type="text" id="tablar" class="form-control">
                        </div>

                        <!-- THRESHOLD -->
                        <div class="mb-3">
                            <label class="form-label">
                                Mindestbestand <span class="text-muted">(optional)</span>
                            </label>
                            <input type="number" id="threshold" class="form-control" min="0" placeholder="z.B. 5, standard 1">
                            <small class="text-muted">0 (Standard) = keine Niedrigbestands-Warnung</small>
                        </div>

                        <!-- TYPE -->
                        <div class="mb-3">
                            <label class="form-label">
                                Typ <span class="text-muted">(optional)</span>
                            </label>
                            <input type="text" id="type" class="form-control" placeholder="z.B. Schrauben, Kunststoff">
                        </div>

                        <!-- LAGER -->
                        <div class="mb-3">
                            <label class="form-label">Lager</label>
                            <input type="text" class="form-control bg-light" value="{{ $lager->name ?? '—' }}" disabled>
                        </div>
                    
                        <!-- ORDER STATUS -->
                        <div class="mb-3">
                            <label class="form-label">Bestellstatus <span class="text-muted">(optional)</span></label>
                            <select id="orderStatus" class="form-select">
                                <option value="">— Normal —</option>
                                <option value="notified">Bedarf gemeldet</option>
                                <option value="ordered">Bestellt</option>
                                <option value="blocked">Blockiert</option>
                                <option value="delivered">Geliefert</option>
                            </select>
                        </div>
                    
                        <!-- IS WERKZEUG -->
                        <div class="form-check mb-2">
                            <input type="checkbox" id="isWerkzeug" class="form-check-input">
                            <label class="form-check-label" for="isWerkzeug">Werkzeug</label>
                        </div>
                    
                        <!-- IS ACTIVE -->
                        <div class="form-check mb-3">
                            <input type="checkbox" id="isActive" class="form-check-input" checked>
                            <label class="form-check-label" for="isActive">Aktiv</label>
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

    <!-- SUPPLIER MODAL -->
    <div class="modal fade" id="supplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Lieferanten für: <span id="supplierModalMaterialName" class="text-muted fw-normal"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Search & Attach -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body py-3">
                            <label class="form-label small fw-bold text-muted mb-2">Bestehenden Lieferanten hinzufügen</label>

                            <!-- Search input -->
                            <div class="position-relative">
                                <input
                                    type="text"
                                    id="supplierSearchInput"
                                    class="form-control form-control-sm"
                                    placeholder="Name oder Firma suchen..."
                                    autocomplete="off"
                                >
                                <!-- Live results dropdown -->
                                <ul
                                    id="supplierSearchResults"
                                    class="list-group shadow position-absolute w-100 d-none"
                                    style="z-index: 1055; max-height: 200px; overflow-y: auto; top: 100%; left: 0;"
                                ></ul>
                            </div>

                            <!-- Selected supplier badge -->
                            <div id="supplierSearchSelected" class="mt-2 d-none">
                                <span class="badge bg-success fs-6 fw-normal py-2 px-3">
                                    <i class="bi bi-check-circle me-1"></i>
                                    <span id="supplierSearchSelectedName"></span>
                                    <button
                                        type="button"
                                        class="btn-close btn-close-white ms-2"
                                        style="font-size:0.6rem;"
                                        onclick="clearSupplierSelection()"
                                    ></button>
                                </span>
                            </div>

                            <!-- Attach button -->
                            <div class="mt-2">
                                <button class="btn btn-success btn-sm" type="button" onclick="attachSupplier()">
                                    <i class="bi bi-plus-lg me-1"></i> Zuweisen
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading spinner -->
                    <div id="supplierLoading" class="text-center py-4">
                        <div class="spinner-border text-secondary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Lieferanten werden geladen...</p>
                    </div>

                    <!-- Error -->
                    <div id="supplierError" class="alert alert-danger d-none">
                        Fehler beim Verarbeiten der Anfrage.
                    </div>

                    <!-- Empty state -->
                    <div id="supplierEmpty" class="text-center text-muted py-4 d-none">
                        <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                        Kein Lieferant für dieses Material hinterlegt.
                    </div>

                    <!-- Attached suppliers list -->
                    <ul id="supplierList" class="list-group list-group-flush d-none"></ul>

                </div>

                <div class="modal-footer justify-content-between">
                    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Neuer Lieferant
                    </a>
                    <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Schließen</button>
                </div>

            </div>
        </div>
    </div>

    <script>
        window.tablarAdmin = {
            token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            maxQuantity: {{ $maxQuantity }},
            lagerId: {{ $lager->id }}
        };

        // Initialize Bootstrap tooltips (Mindestbestand column hint)
        document.addEventListener('DOMContentLoaded', function () {
            if (window.bootstrap && bootstrap.Tooltip) {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                    new bootstrap.Tooltip(el);
                });
            }
        });
    </script>
    <script src="{{ asset('js/admin/tablar/index.js') }}?v=1.0"></script>

@endsection