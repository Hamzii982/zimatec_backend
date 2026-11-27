@extends('user.layouts.index')

@section('title', 'Material scannen')

@section('content')
<div class="container py-4 material-scan-page">
    <header class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h1 class="h4 fw-bold mb-1">{{ $material->name }}</h1>
            @if($material->code)
                <p class="text-muted small mb-1">Artikelnummer: {{ $material->code }}</p>
            @endif
            <p class="text-muted mb-0">
                {{ $material->lager?->name }}
                @if($material->tablar)
                    &middot; Regal {{ $material->tablar }}
                @endif
            </p>
        </div>

        <div class="row g-0 border-top">
            <div class="col-6 p-3 border-end">
                <span class="d-block text-muted small">Aktueller Bestand</span>
                <strong id="materialQuantity">{{ $material->quantity }} {{ $material->unit ?? 'Stk.' }}</strong>
            </div>
            <div class="col-6 p-3">
                <span class="d-block text-muted small">Reserviert</span>
                <strong id="reservedQuantity">{{ (int) $material->on_hold_quantity }} {{ $material->unit ?? 'Stk.' }}</strong>
            </div>
        </div>
    </header>

    <div id="actionResult" class="alert d-none" role="alert" aria-live="polite"></div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <form action="{{ route('tablar.consume', ['lager_id' => $material->lager_id]) }}" method="POST" class="material-action-form h-100" data-success-message="Material wurde entnommen.">
                @csrf
                <input type="hidden" name="material_id" value="{{ $material->id }}">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-bold">Material entnehmen</h2>
                        <label for="takeQuantity" class="form-label small">Menge</label>
                        <div class="input-group">
                            <input type="number" id="takeQuantity" name="quantity" class="form-control" min="1" max="{{ max((int) $material->quantity, 1) }}" value="1" required>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i> Entnehmen</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-md-6">
            <form action="{{ route('tablar.return', ['lager_id' => $material->lager_id]) }}" method="POST" class="material-action-form h-100" data-success-message="Material wurde zurückgegeben.">
                @csrf
                <input type="hidden" name="material_id" value="{{ $material->id }}">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-bold">Material zurückgeben</h2>
                        <label for="returnQuantity" class="form-label small">Menge</label>
                        <div class="input-group">
                            <input type="number" id="returnQuantity" name="quantity" class="form-control" min="1" value="1" required>
                            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-down-left me-1"></i> Zurückgeben</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-md-6">
            <form action="{{ route('tablar.reserve', ['lager_id' => $material->lager_id]) }}" method="POST" class="material-action-form h-100" data-success-message="Material wurde reserviert.">
                @csrf
                <input type="hidden" name="material_id" value="{{ $material->id }}">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-bold">{{ $material->is_werkzeug ? 'Werkzeug reservieren' : 'Material reservieren' }}</h2>
                        <label for="reserveQuantity" class="form-label small">Menge</label>
                        <div class="input-group">
                            <input type="number" id="reserveQuantity" name="quantity" class="form-control" min="1" max="{{ max((int) $material->quantity, 1) }}" value="1" required>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-check me-1"></i> Reservieren</button>
                        </div>
                        <p class="form-text mb-0 mt-2">Reserviertes Material wird dem verfügbaren Bestand entzogen.</p>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-md-6">
            <form
                id="releaseReservationForm"
                action="{{ route('tablar.reserve.release', ['lager_id' => $material->lager_id]) }}"
                method="POST"
                class="material-action-form h-100"
                data-success-message="Reserviertes Material wurde zurückgelegt."
            >
                @csrf
                <input type="hidden" name="material_id" value="{{ $material->id }}">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 fw-bold">{{ $material->is_werkzeug ? 'Reserviertes Werkzeug zurücklegen' : 'Reserviertes Material zurücklegen' }}</h2>
                        <label for="releaseReservationQuantity" class="form-label small">Menge</label>
                        <div class="input-group">
                            <input
                                type="number"
                                id="releaseReservationQuantity"
                                name="quantity"
                                class="form-control"
                                min="1"
                                max="{{ max((int) $material->on_hold_quantity, 1) }}"
                                value="1"
                                @disabled((int) $material->on_hold_quantity < 1)
                                required
                            >
                            <button type="submit" id="releaseReservationButton" class="btn btn-outline-primary" @disabled((int) $material->on_hold_quantity < 1)>
                                <i class="bi bi-arrow-return-left me-1"></i> Zurücklegen
                            </button>
                        </div>
                        <p class="form-text mb-0 mt-2">Gibt die reservierte Menge wieder für alle frei.</p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (() => {
        const result = document.getElementById('actionResult');
        const quantityDisplay = document.getElementById('materialQuantity');
        const reservedDisplay = document.getElementById('reservedQuantity');
        const releaseQuantity = document.getElementById('releaseReservationQuantity');
        const releaseButton = document.getElementById('releaseReservationButton');
        const unit = @json($material->unit ?? 'Stk.');
        const csrfToken = @json(csrf_token());

        document.querySelectorAll('.material-action-form').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = form.querySelector('button[type="submit"]');
                button.disabled = true;
                result.className = 'alert d-none';
                result.textContent = '';

                try {
                    const formData = Object.fromEntries(new FormData(form).entries());
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(formData),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Die Materialbewegung konnte nicht gespeichert werden.');
                    }

                    quantityDisplay.textContent = `${data.new_quantity} ${unit}`;
                    if (data.on_hold_quantity !== undefined) {
                        const onHoldQuantity = Number(data.on_hold_quantity);
                        reservedDisplay.textContent = `${onHoldQuantity} ${unit}`;
                        releaseQuantity.max = Math.max(onHoldQuantity, 1);
                        releaseQuantity.disabled = onHoldQuantity < 1;
                        releaseButton.disabled = onHoldQuantity < 1;
                        if (onHoldQuantity < 1) {
                            releaseQuantity.value = 1;
                        } else if (Number(releaseQuantity.value) > onHoldQuantity) {
                            releaseQuantity.value = onHoldQuantity;
                        }
                    }
                    result.className = 'alert alert-success';
                    result.textContent = form.dataset.successMessage;
                } catch (error) {
                    result.className = 'alert alert-danger';
                    result.textContent = error.message || 'Die Materialbewegung konnte nicht gespeichert werden.';
                } finally {
                    button.disabled = false;
                }
            });
        });
    })();
</script>
@endsection

@push('styles')
<style>
    .material-scan-page .card {
        border-radius: .75rem;
    }

    .material-scan-page .btn-primary {
        --bs-btn-bg: #002752;
        --bs-btn-border-color: #002752;
        --bs-btn-hover-bg: #001a3d;
        --bs-btn-hover-border-color: #001a3d;
    }

    .material-scan-page .btn-outline-primary {
        --bs-btn-color: #002752;
        --bs-btn-border-color: #002752;
        --bs-btn-hover-bg: #002752;
        --bs-btn-hover-border-color: #002752;
    }
</style>
@endpush
