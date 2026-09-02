@extends('admin.layouts.index')

@section('content')
<div class="zt-compare container">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Alle Projekte</h5>
            <a href="{{ route('admin.projects.create') }}" class="zt-export-btn">
                <i class="bi bi-plus-circle"></i> Projekt erstellen
            </a>
        </div>

        <div class="card-body">

            {{-- ========== FILTER BAR ========== --}}
            <form method="GET" action="{{ route('admin.projects') }}" class="zt-filter-form row g-3 mb-4">
                <div class="col-md-5">
                    <label for="search" class="zt-form-label">Suchen (Name, ZT, ZF)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text zt-input-icon"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="search" class="form-control zt-select"
                               placeholder="Projektname oder Auftragsnummer..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="status_id" class="zt-form-label">Status</label>
                    <select name="status_id" id="status_id" class="form-select zt-select">
                        <option value="">-- Alle Status --</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" {{ request('status_id') == $status->id ? 'selected' : '' }}>
                                {{ ucfirst($status->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="zt-btn zt-btn--primary w-100">
                        <i class="bi bi-funnel-fill"></i> Filtern
                    </button>
                    @if(request()->filled('search') || request()->filled('status_id'))
                        <a href="{{ route('admin.projects') }}" class="zt-btn zt-btn--ghost w-100">
                            <i class="bi bi-x-circle"></i> Zurücksetzen
                        </a>
                    @endif
                </div>
            </form>
            {{-- ================================ --}}

            @if($projects->isEmpty())
                <p class="zt-empty text-center mb-0">Keine Projekte gefunden.</p>
            @else
                <div class="table-responsive">
                    <table class="table zt-table zt-table--excel align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Projektname</th>
                                <th>Erstellt am</th>
                                <th>Positionen</th>
                                <th>Bauteile</th>
                                <th>Status</th>
                                <th class="text-center">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $index => $project)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $project->project_name }}</span>
                                        <div class="mt-1">
                                            @if($project->auftragsnummer_zt)
                                                <span class="zt-tag">ZT: {{ $project->auftragsnummer_zt }}</span>
                                            @endif
                                            @if($project->auftragsnummer_zf)
                                                <span class="zt-tag">ZF: {{ $project->auftragsnummer_zf }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $project->created_at->format('d M Y') }}</td>
                                    <td>{{ $project->positions->count() }}</td>
                                    <td>{{ $project->bauteile->count() }}</td>
                                    <td>
                                        <div class="dropdown zt-status-dropdown" data-project-id="{{ $project->id }}">
                                            <button type="button"
                                                    class="zt-badge zt-status-btn dropdown-toggle"
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    style="background-color: {{ $project->status?->color ?? '#667085' }}1A; color: {{ $project->status?->color ?? '#667085' }};">
                                                {{ $project->status ? ucfirst($project->status->name) : 'Pending' }}
                                            </button>
                                            <ul class="dropdown-menu zt-status-menu">
                                                @foreach($statuses as $status)
                                                    <li>
                                                        <button type="button" class="dropdown-item zt-status-option"
                                                                data-status-id="{{ $status->id }}"
                                                                data-status-name="{{ $status->name }}"
                                                                data-status-color="{{ $status->color }}">
                                                            <span class="zt-status-dot" style="background-color: {{ $status->color }};"></span>
                                                            {{ ucfirst($status->name) }}
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="{{ route('admin.projects.positions.index', $project) }}" class="zt-icon-btn zt-icon-btn--view" title="Positionen">
                                                <i class="bi bi-list-ul"></i>
                                            </a>
                                            <a href="{{ route('admin.projects.show', $project) }}" class="zt-icon-btn" title="Anzeigen">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.projects.edit', $project) }}" class="zt-icon-btn zt-icon-btn--edit" title="Bearbeiten">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="zt-icon-btn zt-icon-btn--danger" onclick="return confirm('Projekt wirklich löschen?')" title="Löschen">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
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

    .zt-filter-form {
        background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; padding: 1rem;
    }
    .zt-form-label { font-size: .72rem; font-weight: 600; color: var(--zt-muted); margin-bottom: .3rem; display: block; }
    .zt-select, select.zt-select, input.zt-select {
        border: 1px solid var(--zt-line); border-radius: 8px; font-size: .84rem; background: #fff; color: var(--zt-ink);
    }
    .zt-select:focus { border-color: var(--zt-ink); box-shadow: none; }
    .zt-input-icon { background: #fff; border: 1px solid var(--zt-line); color: var(--zt-muted); }

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

    .zt-tag {
        display: inline-block; font-size: .68rem; font-weight: 500; color: var(--zt-muted);
        border: 1px solid var(--zt-line); border-radius: 5px; padding: .05rem .4rem; margin-right: .3rem;
        background: #FAFBFC;
    }

    .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 600; }
    .zt-badge--pending { background: #EEF0F2; color: var(--zt-muted); }

    .zt-icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--zt-line);
        background: #fff; color: var(--zt-muted); text-decoration: none;
    }
    .zt-icon-btn--view:hover { border-color: #1E7A46; color: #1E7A46; }
    .zt-icon-btn--edit:hover { border-color: #2E5AAC; color: #2E5AAC; }
    .zt-icon-btn--danger { color: #B3261E; }
    .zt-icon-btn--danger:hover { border-color: #B3261E; background: #FBEAE9; }

    .zt-status-btn {
        border: none; cursor: pointer;
    }
    .zt-status-btn:disabled { opacity: .6; cursor: wait; }
    .zt-status-menu { min-width: 160px; padding: .35rem; border: 1px solid var(--zt-line); border-radius: 8px; }
    .zt-status-option {
        display: flex; align-items: center; gap: .5rem;
        font-size: .82rem; border-radius: 6px; padding: .4rem .5rem;
    }
    .zt-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
</style>

<script>
    document.addEventListener('click', function (e) {
        const option = e.target.closest('.zt-status-option');
        if (!option) return;
    
        const dropdown = option.closest('.zt-status-dropdown');
        const btn = dropdown.querySelector('.zt-status-btn');
        const projectId = dropdown.dataset.projectId;
        const statusId = option.dataset.statusId;
    
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = '...';
    
        fetch(`/admin/projects/${projectId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ project_status_id: statusId }),
        })
            .then(res => {
                if (!res.ok) throw new Error();
                return res.json();
            })
            .then(data => {
                const name = data.name.charAt(0).toUpperCase() + data.name.slice(1);
                btn.style.backgroundColor = data.color + '1A';
                btn.style.color = data.color;
                btn.textContent = name;
                btn.disabled = false;
            })
            .catch(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert('Status konnte nicht aktualisiert werden.');
            });
    });
</script>
@endsection