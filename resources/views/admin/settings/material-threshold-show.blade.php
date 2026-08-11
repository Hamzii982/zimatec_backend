@extends('admin.layouts.index')

@section('title', 'Edit Schwellenwert: '.$material->name)

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Schwellenwert bearbeiten — {{ $material->name }}</h5>
            <a href="{{ route('admin.settings.material-thresholds') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Alle Materialien
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.settings.material-thresholds.update', $material->id) }}" method="POST">
                @csrf
                @method('POST')

                {{-- Read-only summary --}}
                <div class="row mb-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Code</small>
                        <strong>{{ $material->code ?? '—' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Aktueller Bestand</small>
                        <strong>{{ $material->quantity }} {{ $material->unit ?? '' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Verfügbar (mit Reserviert + Bestellt)</small>
                        <strong>{{ $material->available_total }} {{ $material->unit ?? '' }}</strong>
                    </div>
                </div>

                <hr>

                {{-- Threshold input --}}
                <div class="mb-3">
                    <label for="threshold" class="form-label fw-semibold">Schwellenwert</label>
                    <input type="number" min="0" name="threshold" id="threshold"
                           class="form-control"
                           value="{{ old('threshold', $material->threshold) }}"
                           placeholder="z. B. 5">
                    <small class="form-text text-muted">
                        Leer lassen = keine Schwellenwert-Überwachung.
                        Wenn der verfügbare Bestand (Menge + Reserviert + Bestellt)
                        diesen Wert erreicht oder unterschreitet, wird eine
                        Warnbenachrichtigung ausgelöst.
                    </small>
                    @error('threshold')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-wechsel">
                        <i class="bi bi-save me-1"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
