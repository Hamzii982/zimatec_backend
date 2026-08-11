@extends('admin.layouts.index')

@section('title', 'Settings - Material Schwellenwerte')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Material Schwellenwerte</h5>
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <p class="text-muted">
                Schwellenwert = der verfügbare Bestand (Menge + Reserviert + Bestellt),
                bei dem oder unter dem eine Warnung ausgelöst wird.
                Leer lassen = keine Überwachung.
            </p>

            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Material</th>
                        <th>Code</th>
                        <th>Aktueller Bestand</th>
                        <th>Verfügbar</th>
                        <th>Schwellenwert</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $material)
                        <tr id="row-{{ $material->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $material->name }}</td>
                            <td>{{ $material->code ?? '—' }}</td>
                            <td>{{ $material->quantity }} {{ $material->unit ?? '' }}</td>
                            <td>{{ $material->available_total }} {{ $material->unit ?? '' }}</td>
                            <td>
                                @if (is_null($material->threshold))
                                    <span class="text-muted">—</span>
                                @else
                                    {{ $material->threshold }}
                                @endif
                            </td>
                            <td>
                                @if (!is_null($material->threshold) && $material->threshold > 0)
                                    <span class="badge bg-{{ $material->status === 'low' ? 'danger' : 'success' }}">
                                        {{ $material->status === 'low' ? 'Niedrig' : 'OK' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Keine Überwachung</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.settings.material-thresholds.show', $material->id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                @if (!is_null($material->threshold))
                                    <form action="{{ route('admin.settings.material-thresholds.destroy', $material->id) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Schwellenwert wirklich entfernen?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                Keine Materialien vorhanden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
