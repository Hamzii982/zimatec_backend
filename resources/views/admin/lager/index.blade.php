@extends('admin.layouts.index')

@section('content')
<div class="zt-compare container">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Alle Lager</h5>
            <a href="{{ route('admin.lager.create') }}" class="zt-export-btn">
                <i class="bi bi-plus-circle"></i> Neues Lager
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table zt-table zt-table--excel align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Beschreibung</th>
                            <th>Aktiv</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lagers as $lager)
                            <tr>
                                <td>{{ $lager->name }}</td>
                                <td>{{ $lager->description }}</td>
                                <td>
                                    <span class="zt-badge {{ $lager->is_active ? 'zt-badge--success' : 'zt-badge--pending' }}">
                                        {{ $lager->is_active ? 'Ja' : 'Nein' }}
                                    </span>
                                </td>
                                <td>{{ $lager->status }}</td>
                                <td>{{ $lager->type }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="{{ route('admin.lager.show', $lager) }}" class="zt-icon-btn" title="Anzeigen">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.lager.edit', $lager->id) }}" class="zt-icon-btn zt-icon-btn--edit" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form action="{{ route('admin.lager.destroy', $lager->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Diesen Lager wirklich löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="zt-icon-btn zt-icon-btn--danger" title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="zt-empty text-center py-4">Keine Lager gefunden.</td>
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
        padding: .4rem .75rem; text-decoration: none;
        transition: background .15s;
    }
    .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

    .zt-empty { color: var(--zt-muted); font-size: .85rem; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; }

    .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 600; }
    .zt-badge--success { background: #E4F5EC; color: #1E7A46; }
    .zt-badge--pending { background: #EEF0F2; color: var(--zt-muted); }

    .zt-icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--zt-line);
        background: #fff; color: var(--zt-muted); text-decoration: none;
    }
    .zt-icon-btn:hover { border-color: #2E5AAC; color: #2E5AAC; }
    .zt-icon-btn--edit:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
    .zt-icon-btn--danger { color: #B3261E; }
    .zt-icon-btn--danger:hover { border-color: #B3261E; background: #FBEAE9; }
</style>
@endsection