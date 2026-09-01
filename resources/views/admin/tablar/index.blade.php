@extends('admin.layouts.index')

@section('content')

    <div id="alert-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; width: auto; min-width: 300px;"></div>

    <div class="zt-compare container py-4">

        <!-- HEADER CARD -->
        <div class="card shadow-sm zt-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Materialverwaltung (Werkstatt)</h4>

                <!-- ADD BUTTON -->
                <button class="zt-export-btn" onclick="openAddModal()">
                    <i class="bi bi-plus-circle"></i> Neues Material
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
                       class="zt-toggle-btn {{ $lowStockOn ? 'zt-toggle-btn--warning-on' : 'zt-toggle-btn--warning' }}">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ __('tablar.filter.low_stock') }}
                    </a>

                    {{-- Leere Materialien --}}
                    @php $emptyOn = request()->boolean('empty'); @endphp
                    <a href="{{ $emptyOn ? $indexUrl.'?'.http_build_query($baseQuery) : $indexUrl.'?'.http_build_query($baseQuery + ['empty' => 1]) }}"
                       class="zt-toggle-btn {{ $emptyOn ? 'zt-toggle-btn--on' : '' }}">
                        <i class="bi bi-box me-1"></i> {{ __('tablar.filter.empty') }}
                    </a>

                    {{-- Status (real form, GET, shareable) --}}
                    <form method="GET" action="{{ $indexUrl }}" class="d-inline-flex align-items-center">
                        @foreach($baseQuery as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endforeach
                        @if(request('low_stock'))<input type="hidden" name="low_stock" value="1">@endif
                        @if(request('empty'))<input type="hidden" name="empty" value="1">@endif
                        <select name="status" class="form-select form-select-sm zt-select" onchange="this.form.submit()">
                            <option value="">{{ __('tablar.filter.status') }}</option>
                            @foreach(['notified','ordered','blocked','delivered'] as $s)
                                <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('tablar.status.'.$s) }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <!-- FILTERS -->
                <form id="filterForm" method="GET" action="{{ route('admin.tablar.index', $lager->id) }}">
                    <div class="zt-filter-form mb-4">
                        <div class="row g-3 align-items-end">

                            <!-- Name Filter -->
                            <div class="col-md-3">
                                <label class="zt-form-label">Materialname</label>
                                <input
                                    type="text"
                                    name="name"
                                    id="filterName"
                                    class="form-control zt-select"
                                    placeholder="z.B. Schraube"
                                    value="{{ request('name') }}"
                                >
                            </div>

                            <!-- Code Filter -->
                            <div class="col-md-3">
                                <label class="zt-form-label">Code</label>
                                <input
                                    type="text"
                                    name="code"
                                    id="filterCode"
                                    class="form-control zt-select"
                                    placeholder="z.B. ART-001"
                                    value="{{ request('code') }}"
                                >
                            </div>

                            <!-- Quantity Range -->
                            <div class="col-md-3">
                                <label class="zt-form-label">
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
                                <label class="zt-form-label">Regal</label>
                                <input
                                    type="text"
                                    name="shelf"
                                    id="filterShelf"
                                    class="form-control zt-select"
                                    placeholder="z.B. A1"
                                    value="{{ request('shelf') }}"
                                >
                            </div>

                            <!-- Submit -->
                            <div class="col-md-1">
                                <button type="submit" class="zt-btn zt-btn--primary w-100" title="Filter anwenden">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>

                        </div>

                        <!-- Active filters + reset -->
                        @if(request()->hasAny(['name', 'code', 'shelf', 'max_qty', 'low_stock', 'empty', 'status']))
                        <div class="mt-2">
                            <a href="{{ route('admin.tablar.index', $lager->id) }}" class="zt-btn zt-btn--ghost">
                                <i class="bi bi-x-circle"></i> {{ __('tablar.filter.reset') }}
                            </a>
                        </div>
                        @endif

                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table zt-table zt-table--excel align-middle mb-0">
                        <thead>
                            <tr>
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
                                @if(!is_null($material->threshold) && (int) $material->threshold > 0 && $material->available_total <= $material->threshold) zt-row--danger
                                @endif
                                @if(!$material->is_active) zt-row--muted @endif"
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
                                data-unit="{{ $material->unit ?? '' }}"
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
                                            <div class="zt-thumb-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="fw-bold">
                                        <a href="{{ route('admin.tablar.show', ['lager_id' => $material->lager_id, 'id' => $material->id]) }}" class="zt-week-link">
                                            {{ $material->name }}
                                            @if($material->is_werkzeug)
                                                <span class="zt-badge zt-badge--pending" title="Werkzeug"><i class="bi bi-wrench"></i></span>
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
                                        <span class="zt-tag">{{ $material->quantity }} {{ $material->unit ?? "stk." }}</span>
                                        @if((int) $material->on_hold_quantity > 0)
                                            <span class="zt-badge zt-badge--info"
                                                  data-bs-toggle="tooltip"
                                                  title="Reserviert (vom Bestand abgezogen)">
                                                <i class="bi bi-clock-history me-1"></i>{{ (int) $material->on_hold_quantity }} {{ $material->unit ?? "stk." }}
                                            </span>
                                        @endif
                                        @if((int) $material->order_quantity > 0)
                                            <span class="zt-badge zt-badge--warning"
                                                  data-bs-toggle="tooltip"
                                                  title="Bestellt (Lieferung erwartet)">
                                                <i class="bi bi-truck me-1"></i>{{ (int) $material->order_quantity }} {{ $material->unit ?? "stk." }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $material->tablar }}</td>
                                    <td>
                                        @if(!is_null($material->threshold) && (int) $material->threshold > 0)
                                            <span class="zt-tag" data-bs-toggle="tooltip" title="0 = keine Warnung">{{ $material->threshold }}</span>
                                        @else
                                            <span class="text-muted small" data-bs-toggle="tooltip" title="0 = keine Warnung">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($material->order_status)
                                            <span class="zt-badge zt-badge--info" data-bs-toggle="tooltip" title="{{ $material->status_label }}">{{ $material->status_label }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button class="zt-icon-btn" onclick="openSupplierModal(this)">
                                                <i class="bi bi-info-circle"></i>
                                            </button>
                                            <button class="zt-icon-btn zt-icon-btn--edit" onclick="openEditModal(this)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="zt-icon-btn zt-icon-btn--danger" onclick="deleteMaterial('{{ $material->id }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-4 zt-pagination">
                    {{ $materials->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- ADD / EDIT MODAL -->
    <div class="modal fade" id="materialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content zt-modal">

                <div class="modal-header zt-modal-header">
                    <h5 id="modalTitle">Material hinzufügen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                                <label class="zt-btn zt-btn--ghost mb-0">
                                    <i class="bi bi-folder2-open"></i> Datei wählen
                                    <input type="file" id="image" class="d-none" accept="image/*" onchange="previewImage(this)">
                                </label>
                                <button type="button" class="zt-btn zt-btn--ghost" onclick="openCamera()">
                                    <i class="bi bi-camera"></i> Kamera
                                </button>
                            </div>
                        </div>

                        <!-- Camera Modal -->
                        <div class="modal fade" id="cameraModal" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content zt-modal">
                                    <div class="modal-header zt-modal-header">
                                        <h6 class="modal-title">Foto aufnehmen</h6>
                                        <button type="button" class="btn-close btn-close-white" onclick="closeCamera()"></button>
                                    </div>
                                    <div class="modal-body text-center p-2">
                                        <video id="cameraStream" autoplay playsinline class="w-100 rounded" style="max-height:300px;object-fit:cover;"></video>
                                        <canvas id="cameraCanvas" class="d-none"></canvas>
                                    </div>
                                    <div class="modal-footer justify-content-center">
                                        <button type="button" class="zt-btn zt-btn--ghost" onclick="closeCamera()">Abbrechen</button>
                                        <button type="button" class="zt-btn zt-btn--primary" onclick="capturePhoto()">
                                            <i class="bi bi-circle-fill me-1"></i> Aufnehmen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NAME -->
                        <div class="mb-3">
                            <label class="zt-form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" class="form-control zt-select" required>
                        </div>

                        <!-- CODE -->
                        <div class="mb-3">
                            <label class="zt-form-label">
                                Artikelnummer <span class="text-muted">(optional)</span>
                            </label>
                            <input type="text" id="code" class="form-control zt-select" maxlength="64" placeholder="z.B. ART-001">
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="mb-3">
                            <label class="zt-form-label">
                                Beschreibung <span class="text-muted">(optional)</span>
                            </label>
                            <textarea id="description" class="form-control zt-select" maxlength="2000" rows="3" placeholder="Notizen, Maße, Verwendung …"></textarea>
                        </div>

                        <!-- CURRENT QUANTITY (READ ONLY) -->
                        <div class="mb-2">
                            <label class="zt-form-label">Aktueller Bestand</label>
                            <input type="number" id="currentQuantity" class="form-control zt-select" style="background:#FAFBFC;" readonly>
                        </div>

                        <!-- ADD STOCK -->
                        <div class="mb-3">
                            <label class="zt-form-label">
                                Hinzufügen (+ Menge) <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="addQuantity" class="form-control zt-select" min="0" value="0">
                        </div>

                        <!-- UNIT -->
                        <div class="mb-3">
                            <label class="zt-form-label">
                                Einheit <span class="text-muted">(optional)</span>
                            </label>
                            <input type="text" id="unit" class="form-control zt-select" placeholder="z.B. Stk, m, kg, Rolle" value="Stück">
                        </div>

                        <!-- TABLAR -->
                        <div class="mb-3">
                            <label class="zt-form-label">
                                Regal / Tablar <span class="text-muted">(optional)</span>
                            </label>
                            <input type="text" id="tablar" class="form-control zt-select">
                        </div>

                        {{-- SHEET SIZE (Holz lager only) --}}
                        @if(($lager->type ?? null) === 'holz')
                        <div id="sheetSizeFields" class="zt-subcard mb-3" style="border-radius: 8px;">
                            <p class="text-muted small text-uppercase fw-semibold mb-2">Plattengröße (neue volle Platten)</p>
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="zt-form-label mb-1">Länge (mm)</label>
                                    <input type="number" id="sheetLengthMm" class="form-control form-control-sm zt-select" min="1">
                                </div>
                                <div class="col-4">
                                    <label class="zt-form-label mb-1">Breite (mm)</label>
                                    <input type="number" id="sheetWidthMm" class="form-control form-control-sm zt-select" min="1">
                                </div>
                                <div class="col-4">
                                    <label class="zt-form-label mb-1">Dicke (mm)</label>
                                    <input type="number" id="sheetThicknessMm" class="form-control form-control-sm zt-select" min="0.1" step="0.1">
                                </div>
                            </div>
                            <small class="text-muted">Nur nötig, wenn "Hinzufügen (+ Menge)" &gt; 0 ist.</small>
                        </div>
                        @endif

                        <!-- THRESHOLD -->
                        <div class="mb-3">
                            <label class="zt-form-label">
                                Mindestbestand <span class="text-muted">(optional)</span>
                            </label>
                            <input type="number" id="threshold" class="form-control zt-select" min="0" placeholder="z.B. 5, standard 1">
                            <small class="text-muted">0 (Standard) = keine Niedrigbestands-Warnung</small>
                        </div>

                        <!-- TYPE -->
                        <div class="mb-3">
                            <label class="zt-form-label">
                                Typ <span class="text-muted">(optional)</span>
                            </label>
                            <input type="text" id="type" class="form-control zt-select" placeholder="z.B. Schrauben, Kunststoff">
                        </div>

                        <!-- LAGER -->
                        <div class="mb-3">
                            <label class="zt-form-label">Lager</label>
                            <input type="text" class="form-control zt-select" style="background:#FAFBFC;" value="{{ $lager->name ?? '—' }}" disabled>
                        </div>

                        <!-- ORDER STATUS -->
                        <div class="mb-3">
                            <label class="zt-form-label">Bestellstatus <span class="text-muted">(optional)</span></label>
                            <select id="orderStatus" class="form-select zt-select">
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
                    <button class="zt-btn zt-btn--ghost" data-bs-dismiss="modal">Abbrechen</button>
                    <button class="zt-btn zt-btn--primary" onclick="saveMaterial()">Speichern</button>
                </div>

            </div>
        </div>
    </div>

    <!-- SUPPLIER MODAL -->
    <div class="modal fade" id="supplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content zt-modal">

                <div class="modal-header zt-modal-header">
                    <h5 class="modal-title">
                        Lieferanten für: <span id="supplierModalMaterialName" class="fw-normal" style="opacity:.75;"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Search & Attach -->
                    <div class="zt-subcard mb-4" style="border-radius: 8px;">
                        <label class="zt-form-label mb-2">Bestehenden Lieferanten hinzufügen</label>

                        <!-- Search input -->
                        <div class="position-relative">
                            <input
                                type="text"
                                id="supplierSearchInput"
                                class="form-control form-control-sm zt-select"
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
                            <span class="zt-badge zt-badge--success" style="font-size:.85rem; padding:.35rem .6rem;">
                                <i class="bi bi-check-circle me-1"></i>
                                <span id="supplierSearchSelectedName"></span>
                                <button
                                    type="button"
                                    class="btn-close ms-2"
                                    style="font-size:0.6rem;"
                                    onclick="clearSupplierSelection()"
                                ></button>
                            </span>
                        </div>

                        <!-- Attach button -->
                        <div class="mt-2">
                            <button class="zt-btn zt-btn--success" type="button" onclick="attachSupplier()">
                                <i class="bi bi-plus-lg me-1"></i> Zuweisen
                            </button>
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
                    <div id="supplierEmpty" class="text-center zt-empty py-4 d-none">
                        <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                        Kein Lieferant für dieses Material hinterlegt.
                    </div>

                    <!-- Attached suppliers list -->
                    <ul id="supplierList" class="list-group list-group-flush d-none"></ul>

                </div>

                <div class="modal-footer justify-content-between">
                    <a href="{{ route('admin.suppliers.create') }}" class="zt-btn zt-btn--success">
                        <i class="bi bi-plus-circle me-1"></i> Neuer Lieferant
                    </a>
                    <button class="zt-btn zt-btn--ghost" data-bs-dismiss="modal">Schließen</button>
                </div>

            </div>
        </div>
    </div>

    <style>
        .zt-compare {
            --zt-bg: #F5F6F8;
            --zt-ink: #1B1F24;
            --zt-muted: #667085;
            --zt-line: #DFE3E8;
            color: var(--zt-ink);
            font-variant-numeric: tabular-nums;
        }

        .zt-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
        .zt-card > .card-header { background: var(--zt-ink); color: #fff; border-bottom: none; }
        .zt-card > .card-body { background: var(--zt-bg); }

        .zt-export-btn {
            display: inline-flex; align-items: center; gap: .4rem;
            border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: transparent;
            color: #fff; font-size: .8rem; font-weight: 500;
            padding: .4rem .75rem;
            transition: background .15s;
        }
        .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

        .zt-toggle-btn {
            display: inline-flex; align-items: center; border-radius: 8px; font-size: .8rem; font-weight: 500;
            padding: .35rem .75rem; border: 1px solid var(--zt-line); background: #fff; color: var(--zt-muted); text-decoration: none;
        }
        .zt-toggle-btn:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
        .zt-toggle-btn--warning { color: #92650B; border-color: #F3D9A6; }
        .zt-toggle-btn--warning-on { background: #FFF1D6; border-color: #F3D9A6; color: #92650B; }
        .zt-toggle-btn--on { background: var(--zt-ink); border-color: var(--zt-ink); color: #fff; }

        .zt-filter-form { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; padding: 1rem; }
        .zt-form-label { font-size: .72rem; font-weight: 600; color: var(--zt-muted); margin-bottom: .3rem; display: block; }
        .zt-select, select.zt-select, input.zt-select, textarea.zt-select {
            border: 1px solid var(--zt-line); border-radius: 8px; font-size: .84rem; background: #fff; color: var(--zt-ink);
        }
        .zt-select:focus { border-color: var(--zt-ink); box-shadow: none; }

        .zt-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
            border-radius: 8px; font-size: .82rem; font-weight: 500;
            padding: .4rem .9rem; border: 1px solid var(--zt-line); text-decoration: none; cursor: pointer;
        }
        .zt-btn--primary { background: var(--zt-ink, #1B1F24); color: #fff; border-color: var(--zt-ink, #1B1F24); }
        .zt-btn--primary:hover { background: #000; color: #fff; }
        .zt-btn--ghost { background: #fff; color: var(--zt-muted, #667085); }
        .zt-btn--ghost:hover { border-color: var(--zt-ink, #1B1F24); color: var(--zt-ink, #1B1F24); }
        .zt-btn--success { background: #1E7A46; border-color: #1E7A46; color: #fff; }
        .zt-btn--success:hover { background: #17603a; color: #fff; }

        .zt-empty { color: var(--zt-muted); font-size: .85rem; }

        .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
        .zt-table--excel thead th {
            font-size: .72rem; font-weight: 600; color: var(--zt-muted); text-transform: uppercase; letter-spacing: .03em;
            border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
        }
        .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; }
        .zt-row--danger { background: #FEF5F4; }
        .zt-row--muted { color: var(--zt-muted); }

        .zt-thumb-placeholder {
            width: 40px; height: 40px; border-radius: 6px; background: #EEF0F2;
            display: flex; align-items: center; justify-content: center; color: var(--zt-muted);
        }

        .zt-tag {
            display: inline-block; font-size: .72rem; font-weight: 500; color: var(--zt-ink);
            border: 1px solid var(--zt-line); border-radius: 999px; padding: .15rem .55rem;
            background: #FAFBFC;
        }

        .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
        .zt-badge--pending { background: #EEF0F2; color: var(--zt-muted); }
        .zt-badge--info { background: #E7EEFF; color: #2E5AAC; border: 1px solid #DCE6FA; }
        .zt-badge--warning { background: #FFF1D6; color: #92650B; border: 1px solid #F3D9A6; }
        .zt-badge--success { background: #E4F5EC; color: #1E7A46; }

        .zt-week-link { color: var(--zt-ink); text-decoration: none; }
        .zt-week-link:hover { text-decoration: underline; }

        .zt-icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--zt-line);
            background: #fff; color: var(--zt-muted);
        }
        .zt-icon-btn:hover { border-color: #2E5AAC; color: #2E5AAC; }
        .zt-icon-btn--edit:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
        .zt-icon-btn--danger { color: #B3261E; }
        .zt-icon-btn--danger:hover { border-color: #B3261E; background: #FBEAE9; }

        .zt-subcard { background: #FAFBFC; border: 1px solid var(--zt-line); padding: 1rem; }

        .zt-modal { border-radius: 10px; overflow: hidden; border: 1px solid #DFE3E8; }
        .zt-modal-header {
            background: #1B1F24;
            color: #fff;
            border-bottom: none;
        }
        .zt-modal-header .modal-title,
        .zt-modal-header h5,
        .zt-modal-header h6 {
            color: #fff;
        }

        .zt-pagination .pagination { margin-bottom: 0; }
        .zt-pagination .page-link { border: 1px solid var(--zt-line); color: var(--zt-ink); border-radius: 6px; margin: 0 .15rem; }
        .zt-pagination .page-item.active .page-link { background: var(--zt-ink); border-color: var(--zt-ink); }
    </style>

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