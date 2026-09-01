@extends('admin.layouts.index')

@section('content')
<div class="zt-compare container">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Alle Lieferanten</h5>
            <a href="{{ route('admin.suppliers.create') }}" class="zt-export-btn">
                <i class="bi bi-plus-circle"></i> Neuer Lieferant
            </a>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('admin.suppliers.index') }}" class="zt-filter-form row g-3 mb-4 align-items-end">
                <div class="col-md-4">
                    <label class="zt-form-label">Suche</label>
                    <input type="text" name="search" class="form-control zt-select" placeholder="Suche nach Name oder Firma..."
                           value="{{ request('search') }}">
                </div>

                <div class="col-md-4">
                    <label class="zt-form-label">Dienstleistung</label>
                    <select name="service_id" class="form-select zt-select">
                        <option value="">-- Alle Dienstleistungen --</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>
                                {{ $service->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="zt-btn zt-btn--primary">
                        <i class="bi bi-search"></i> Filtern
                    </button>

                    <a href="{{ route('admin.suppliers.index') }}" class="zt-btn zt-btn--ghost">
                        <i class="bi bi-x-circle"></i> Zurücksetzen
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table zt-table zt-table--excel align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Firma</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Website</th>
                            <th>Dienstleistungen</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td>{{ $supplier->name }}</td>
                                <td>{{ $supplier->company }}</td>
                                <td>{{ $supplier->email }}</td>
                                <td>{{ $supplier->phone_number }}</td>
                                <td>
                                    @if ($supplier->website)
                                        <a href="{{ $supplier->website }}" target="_blank" class="zt-week-link">{{ parse_url($supplier->website, PHP_URL_HOST) }}</a>
                                    @endif
                                </td>
                                <td>
                                    @foreach ($supplier->services as $service)
                                        <span class="zt-badge" style="background-color: {{ $service->color }}1A; color: {{ $service->color }};">
                                            {{ $service->name }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="{{ route('admin.suppliers.show', $supplier) }}" class="zt-icon-btn" title="Anzeigen">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.suppliers.edit', $supplier->id) }}" class="zt-icon-btn zt-icon-btn--edit" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Diesen Lieferanten wirklich löschen?')">
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
                                <td colspan="7" class="zt-empty text-center py-4">Keine Lieferanten gefunden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 zt-pagination">
                {{ $suppliers->links() }}
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

    .zt-filter-form { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; padding: 1rem; }
    .zt-form-label { font-size: .72rem; font-weight: 600; color: var(--zt-muted); margin-bottom: .3rem; display: block; }
    .zt-select, select.zt-select, input.zt-select {
        border: 1px solid var(--zt-line); border-radius: 8px; font-size: .84rem; background: #fff; color: var(--zt-ink);
    }
    .zt-select:focus { border-color: var(--zt-ink); box-shadow: none; }

    .zt-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
        border-radius: 8px; font-size: .82rem; font-weight: 500;
        padding: .4rem .9rem; border: 1px solid var(--zt-line); text-decoration: none;
    }
    .zt-btn--primary { background: var(--zt-ink); color: #fff; border-color: var(--zt-ink); }
    .zt-btn--primary:hover { background: #000; color: #fff; }
    .zt-btn--ghost { background: #fff; color: var(--zt-muted); }
    .zt-btn--ghost:hover { border-color: var(--zt-ink); color: var(--zt-ink); }

    .zt-empty { color: var(--zt-muted); font-size: .85rem; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; }

    .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 600; margin-right: .25rem; }

    .zt-week-link { color: var(--zt-ink); font-weight: 500; text-decoration: none; }
    .zt-week-link:hover { text-decoration: underline; }

    .zt-icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--zt-line);
        background: #fff; color: var(--zt-muted); text-decoration: none;
    }
    .zt-icon-btn:hover { border-color: #2E5AAC; color: #2E5AAC; }
    .zt-icon-btn--edit:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
    .zt-icon-btn--danger { color: #B3261E; }
    .zt-icon-btn--danger:hover { border-color: #B3261E; background: #FBEAE9; }

    .zt-pagination .pagination { margin-bottom: 0; }
    .zt-pagination .page-link { border: 1px solid var(--zt-line); color: var(--zt-ink); border-radius: 6px; margin: 0 .15rem; }
    .zt-pagination .page-item.active .page-link { background: var(--zt-ink); border-color: var(--zt-ink); }
</style>
@endsection