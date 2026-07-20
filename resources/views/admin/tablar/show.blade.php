@extends('admin.layouts.index')

@section('content')

<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Material Details — {{ $material->name }}</h5>
            <a href="{{ $backToListUrl }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-list"></i> {{ __('tablar.filter.reset') === 'Filter zurücksetzen' ? 'Alle Materialien' : 'Alle Materialien' }}
            </a>
        </div>

        <div class="card-body">
            <div class="row g-4">

                {{-- LEFT: image + meta --}}
                <div class="col-md-5">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body text-center">
                            @if($material->image)
                                <img src="{{ asset('storage/'.$material->image) }}" alt="{{ $material->name }}" class="rounded object-fit-cover mb-3" style="width:100%; max-height:280px;">
                            @else
                                <div class="bg-white rounded d-flex align-items-center justify-content-center mx-auto mb-3" style="width:100%; max-height:280px; min-height:200px;">
                                    <i class="bi bi-box-seam text-muted" style="font-size: 4rem;"></i>
                                </div>
                            @endif

                            <h5 class="fw-bold text-dark mb-1">{{ $material->name }}</h5>
                            @if($material->code)
                                <code class="text-muted small">{{ $material->code }}</code>
                            @endif

                            <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                                @if($material->is_werkzeug)
                                    <span class="badge bg-secondary"><i class="bi bi-wrench me-1"></i> Werkzeug</span>
                                @endif
                                @if($material->is_active)
                                    <span class="badge bg-success-subtle text-success-emphasis">Aktiv</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Inaktiv</span>
                                @endif
                            </div>
                        </div>
                        <ul class="list-group list-group-flush border-top">
                            @if($material->description)
                                <li class="list-group-item bg-white">
                                    <small class="text-muted text-uppercase d-block">Beschreibung</small>
                                    <span>{{ $material->description }}</span>
                                </li>
                            @endif
                            <li class="list-group-item bg-white">
                                <small class="text-muted text-uppercase d-block">Typ</small>
                                <span>{{ $material->type ?? '—' }}</span>
                            </li>
                            <li class="list-group-item bg-white">
                                <small class="text-muted text-uppercase d-block">Regal</small>
                                <span class="font-monospace">{{ $material->tablar ?? '—' }}</span>
                            </li>
                            <li class="list-group-item bg-white">
                                <small class="text-muted text-uppercase d-block">{{ __('tablar.show.threshold') }}</small>
                                <span>{{ $material->threshold ?? '—' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- RIGHT: status + supplier + stock + audit adjust + back link --}}
                <div class="col-md-7">

                    {{-- Status + change status + recent supplier --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted text-uppercase fw-bold">Status</small>
                                @if($material->status_label)
                                    <span class="badge bg-info-subtle text-info-emphasis fs-6" id="statusLabelBadge">{{ $material->status_label }}</span>
                                @else
                                    <span class="text-muted" id="statusLabelBadge">—</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="changeStatus" class="form-label small text-muted mb-1">{{ __('tablar.show.change_status') }}</label>
                                <select id="changeStatus" class="form-select form-select-sm">
                                    <option value="">— Normal —</option>
                                    <option value="notified"  @selected($material->order_status === 'notified')>{{ __('tablar.status.notified') }}</option>
                                    <option value="ordered"   @selected($material->order_status === 'ordered')>{{ __('tablar.status.ordered') }}</option>
                                    <option value="blocked"   @selected($material->order_status === 'blocked')>{{ __('tablar.status.blocked') }}</option>
                                    <option value="delivered" @selected($material->order_status === 'delivered')>{{ __('tablar.status.delivered') }}</option>
                                </select>
                            </div>
                            
                            <div class="mb-3 {{ $material->order_status === 'ordered' ? '' : 'd-none' }}" id="orderQuantityWrapper">
                                <label for="orderQuantityInput" class="form-label small text-muted mb-1">Bestellte Menge</label>
                                <div class="d-flex gap-2">
                                    <input type="number" id="orderQuantityInput" class="form-control form-control-sm" min="1"
                                        value="{{ $material->order_quantity ?: '' }}" placeholder="z.B. 20">
                                    <button type="button" id="confirmOrderQuantity" class="btn btn-sm btn-filter">Übernehmen</button>
                                </div>
                                <small class="text-muted">Wird bei „Geliefert" automatisch zum Bestand addiert.</small>
                            </div>

                            <hr>

                            <small class="text-muted text-uppercase fw-bold d-block mb-2">{{ __('tablar.show.recent_supplier') }}</small>
                            @if($recentSupplier)
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $recentSupplier->name }}</div>
                                        @if($recentSupplier->company)
                                            <div class="text-muted small">{{ $recentSupplier->company }}</div>
                                        @endif
                                        <div class="text-muted small mt-1">
                                            @if($recentSupplier->email)
                                                <i class="bi bi-envelope me-1"></i>{{ $recentSupplier->email }}
                                            @endif
                                            @if($recentSupplier->phone_number)
                                                <span class="ms-3"><i class="bi bi-telephone me-1"></i>{{ $recentSupplier->phone_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($supplierListUrl)
                                        <a href="{{ $supplierListUrl }}" class="btn btn-sm btn-outline-primary shrink-0">
                                            {{ __('tablar.show.all_from_supplier') }}
                                            <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="text-muted small">{{ __('tablar.show.no_supplier') }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Add stock (relative: current + add) --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted text-uppercase fw-bold">{{ __('tablar.show.current_stock') }}</small>
                                <span class="badge bg-light text-dark border fs-6" id="currentStockBadge">{{ $material->quantity }} Stk.</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-info-subtle text-info-emphasis border" data-bs-toggle="tooltip" title="Reserviert: bereits entnommen, aber noch nicht verbraucht">
                                    <i class="bi bi-clock-history me-1"></i> Reserviert: {{ (int) $material->on_hold_quantity }} Stk.
                                </span>
                                @if((int) $material->order_quantity > 0)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border" data-bs-toggle="tooltip" title="Bestellt: Lieferung erwartet">
                                        <i class="bi bi-truck me-1"></i> Bestellt: {{ (int) $material->order_quantity }} Stk.
                                    </span>
                                @endif
                                <span class="badge bg-light text-muted border" data-bs-toggle="tooltip" title="Gesamt: Bestand + Reserviert + Bestellt">
                                    <i class="bi bi-stack me-1"></i> Verfügbar (gesamt): {{ $material->available_total }} Stk.
                                </span>
                            </div>

                            <div class="row g-2 align-items-end">
                                <div class="col-7">
                                    <label for="addQuantity" class="form-label small text-muted mb-1">{{ __('tablar.show.add') }}</label>
                                    <input type="number" id="addQuantity" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-5">
                                    <button type="button" id="saveQuantity" class="btn btn-filter w-100">
                                        <i class="bi bi-check2 me-1"></i> {{ __('tablar.show.save') }}
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    {{ __('tablar.show.threshold') }}:
                                    <span class="fw-semibold text-dark">{{ $material->threshold ?? '—' }}</span>
                                </small>
                                @if($material->status === 'low')
                                    <span class="badge bg-warning text-dark" id="lowStockChip">
                                        <i class="bi bi-exclamation-triangle me-1"></i> {{ __('tablar.show.low_stock_warning') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Audit adjust (absolute set, overrides the DB to the counted value) --}}
                    <div class="card mb-3 border-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted text-uppercase fw-bold">{{ __('tablar.show.audit_adjust') }}</small>
                                <span class="badge bg-warning-subtle text-warning-emphasis">{{ __('tablar.show.audit_note') }}</span>
                            </div>
                            <p class="small text-muted mb-2">{{ __('tablar.show.audit_hint') }}</p>
                            <div class="row g-2 align-items-end">
                                <div class="col-7">
                                    <label for="auditQuantity" class="form-label small text-muted mb-1">{{ __('tablar.show.audit_actual') }}</label>
                                    <input type="number" id="auditQuantity" class="form-control" min="0" value="{{ $material->quantity }}">
                                </div>
                                <div class="col-5">
                                    <button type="button" id="auditSave" class="btn btn-warning w-100 text-dark">
                                        <i class="bi bi-clipboard-check me-1"></i> {{ __('tablar.show.audit_save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="{{ $backToListUrl }}" class="btn btn-link text-muted text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Material in der Liste anzeigen
                        </a>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Verlauf</h6>
                            <span class="text-muted small">{{ $logs->total() }} Einträge</span>
                        </div>
            
                        <div class="card-body p-0">
                            @if($logs->isEmpty())
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history fs-3 d-block mb-2"></i>
                                    Noch keine Bewegungen erfasst.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr class="text-secondary text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.05em;">
                                                <th>Datum</th>
                                                <th>Typ</th>
                                                <th class="text-end">Menge</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($logs as $log)
                                                <tr>
                                                    <td class="text-muted small">{{ $log->consumption_time?->format('d.m.Y H:i') ?? '—' }}</td>
                                                    <td><span class="badge {{ $log->type_badge_class }}">{{ $log->type_label }}</span></td>
                                                    <td class="text-end fw-semibold">{{ $log->quantity }} Stk.</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
            
                        @if($logs->hasPages())
                            <div class="card-footer bg-white d-flex justify-content-center">
                                {{ $logs->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const lagerId = {{ (int) $lager->id }};
    const materialId = {{ (int) $material->id }};
    const quantityUrl = `/admin/lager/${lagerId}/tablar/${materialId}/quantity`;
    const statusUrl   = `/admin/lager/${lagerId}/tablar/${materialId}/status`;
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token };

    // ─── ADD STOCK (current + add) ────────────────────────────────────────────
    const addBtn = document.getElementById('saveQuantity');
    if (addBtn) {
        const addOriginal = addBtn.innerHTML;
        addBtn.addEventListener('click', async () => {
            const current = parseInt((document.getElementById('currentStockBadge').textContent || '0').replace(/\D+/g, ''), 10) || 0;
            const add = parseInt(document.getElementById('addQuantity').value || 0);
            const total = current + add;
            if (isNaN(total) || total < 0) return;

            addBtn.disabled = true;
            addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
            try {
                const res = await fetch(quantityUrl, { method: 'PATCH', headers, body: JSON.stringify({ quantity: total, reason: 'add' }) });
                if (!res.ok) throw new Error();
                setTimeout(() => location.reload(), 250);
            } catch (e) {
                addBtn.disabled = false;
                addBtn.innerHTML = addOriginal;
                alert("{{ __('tablar.show.quantity_error') }}");
            }
        });
    }

    // ─── AUDIT ADJUST (absolute set) ──────────────────────────────────────────
    const auditBtn = document.getElementById('auditSave');
    if (auditBtn) {
        const auditOriginal = auditBtn.innerHTML;
        auditBtn.addEventListener('click', async () => {
            const actual = parseInt(document.getElementById('auditQuantity').value);
            if (isNaN(actual) || actual < 0) {
                alert("{{ __('tablar.show.quantity_error') }}");
                return;
            }
            if (!confirm("{{ __('tablar.show.audit_confirm') }}")) return;

            auditBtn.disabled = true;
            auditBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
            try {
                const res = await fetch(quantityUrl, { method: 'PATCH', headers, body: JSON.stringify({ quantity: actual, reason: 'audit' }) });
                if (!res.ok) throw new Error();
                setTimeout(() => location.reload(), 250);
            } catch (e) {
                auditBtn.disabled = false;
                auditBtn.innerHTML = auditOriginal;
                alert("{{ __('tablar.show.quantity_error') }}");
            }
        });
    }

    // ─── CHANGE STATUS ────────────────────────────────────────────────────────
    const statusSel = document.getElementById('changeStatus');
    const statusBadge = document.getElementById('statusLabelBadge');
    const orderQtyWrapper = document.getElementById('orderQuantityWrapper');
    const orderQtyInput = document.getElementById('orderQuantityInput');
    const confirmOrderQtyBtn = document.getElementById('confirmOrderQuantity');
    const currentStockBadge = document.getElementById('currentStockBadge');

    async function pushStatus(newStatus, orderQuantity = null) {
        const body = { order_status: newStatus || null };
        if (orderQuantity !== null) body.order_quantity = orderQuantity;

        const res = await fetch(statusUrl, { method: 'PATCH', headers, body: JSON.stringify(body) });
        if (!res.ok) throw new Error();
        return res.json();
    }

    if (statusSel) {
        statusSel.addEventListener('change', async () => {
            const newStatus = statusSel.value;

            // "ordered" needs a quantity first — reveal the field, don't save yet
            if (newStatus === 'ordered') {
                orderQtyWrapper.classList.remove('d-none');
                orderQtyInput.focus();
                return;
            }

            orderQtyWrapper.classList.add('d-none');
            statusSel.disabled = true;
            try {
                const data = await pushStatus(newStatus);
                statusBadge.textContent = data.status_label || '—';
                if (data.quantity !== undefined) {
                    currentStockBadge.textContent = data.quantity + ' Stk.';
                }
            } catch (e) {
                alert("{{ __('tablar.show.status_error') }}");
            } finally {
                statusSel.disabled = false;
            }
        });
    }

    if (confirmOrderQtyBtn) {
        confirmOrderQtyBtn.addEventListener('click', async () => {
            const qty = parseInt(orderQtyInput.value);
            if (isNaN(qty) || qty < 1) {
                alert('Bitte eine gültige Bestellmenge angeben.');
                return;
            }
            confirmOrderQtyBtn.disabled = true;
            try {
                const data = await pushStatus('ordered', qty);
                statusBadge.textContent = data.status_label || '—';
            } catch (e) {
                alert("{{ __('tablar.show.status_error') }}");
            } finally {
                confirmOrderQtyBtn.disabled = false;
            }
        });
    }
})();
</script>

@endsection
