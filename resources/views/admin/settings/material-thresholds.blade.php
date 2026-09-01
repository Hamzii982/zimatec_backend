@extends('admin.layouts.index')

@section('title', 'Settings - Material Schwellenwerte')

@section('content')
<div class="zt-compare container mt-4">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Material Schwellenwerte</h5>
        </div>

        <div class="card-body">

            <p class="zt-hint">
                Schwellenwert = der verfügbare Bestand (Menge + Reserviert + Bestellt),
                bei dem oder unter dem eine Warnung ausgelöst wird.
                Leer lassen = keine Überwachung.
            </p>

            <div class="table-responsive">
                <table class="table zt-table zt-table--excel align-middle mb-0">
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
                                <td class="fw-bold">{{ $material->name }}</td>
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
                                        <span class="zt-badge {{ $material->status === 'low' ? 'zt-badge--danger' : 'zt-badge--success' }}">
                                            {{ $material->status === 'low' ? 'Niedrig' : 'OK' }}
                                        </span>
                                    @else
                                        <span class="zt-badge zt-badge--pending">Keine Überwachung</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.settings.material-thresholds.show', $material->id) }}"
                                           class="zt-icon-btn zt-icon-btn--edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        @if (!is_null($material->threshold))
                                            <form action="{{ route('admin.settings.material-thresholds.destroy', $material->id) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="zt-icon-btn zt-icon-btn--danger"
                                                        onclick="return confirm('Schwellenwert wirklich entfernen?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="zt-empty text-center py-4">
                                    Keine Materialien vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
        color: var(--zt-ink, #1B1F24);
        font-variant-numeric: tabular-nums;
    }

    .zt-card { border: 1px solid var(--zt-line, #DFE3E8); border-radius: 10px; overflow: hidden; }
    .zt-card > .card-header { background: var(--zt-ink, #1B1F24); color: #fff; border-bottom: none; }
    .zt-card > .card-body { background: var(--zt-bg, #F5F6F8); }

    .zt-hint { color: var(--zt-muted, #667085); font-size: .85rem; margin-bottom: 1.25rem; }
    .zt-empty { color: var(--zt-muted, #667085); font-size: .85rem; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line, #DFE3E8); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted, #667085);
        border-bottom: 1px solid var(--zt-line, #DFE3E8); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line, #DFE3E8); font-size: .84rem; }

    .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 600; }
    .zt-badge--danger { background: #FBEAE9; color: #B3261E; }
    .zt-badge--success { background: #E4F5EC; color: #1E7A46; }
    .zt-badge--pending { background: #EEF0F2; color: var(--zt-muted, #667085); }

    .zt-icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--zt-line, #DFE3E8);
        background: #fff; color: var(--zt-muted, #667085); text-decoration: none;
    }
    .zt-icon-btn--edit:hover { border-color: var(--zt-ink, #1B1F24); color: var(--zt-ink, #1B1F24); }
    .zt-icon-btn--danger { color: #B3261E; }
    .zt-icon-btn--danger:hover { border-color: #B3261E; background: #FBEAE9; }
</style>
@endsection