@extends('user.layouts.index')

@section('content')
<div class="container mt-4 mb-5">
    @if(!$selectedUser)
        <div class="welcome-wrapper fade-in">
            <div class="text-center">
                <h1 class="fw-bold mb-3">Willkommen zur Zeiterfassung</h1>
                <p class="text-muted fs-5 mb-4">
                    Bitte wählen Sie einen Benutzer aus, um dessen Zeitaufzeichnungen anzuzeigen.
                </p>
        
                <div class="d-flex justify-content-center flex-wrap gap-3 mt-4">
                    @foreach($users as $user)
                        <a href="{{ route('time-records.list', ['user_id' => $user->id]) }}"
                        class="user-tile slide-up">
                            {{ $user->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($selectedUser)
        <div class="zt-time zt-records">
            <div class="card shadow-sm zt-records-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        <span class="fw-bold">{{ $selectedUser->name }}</span>
                        <span class="zt-header-sub">— Zeitaufzeichnungen</span>
                    </h5>

                    <div class="d-flex gap-2">
                        <a href="{{ route('time-records.list', request()->except('user_id', 'page')) }}"
                            class="zt-export-btn">
                            <i class="bi bi-arrow-left-circle"></i>
                            Benutzer wechseln
                        </a>
                        <a href="{{ route('time-records.create', ['user_id' => $selectedUser->id]) }}" class="zt-btn zt-btn--success">
                            <i class="bi bi-plus-circle"></i>
                            Neue Aufzeichnung
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ========== FILTER BAR ========== --}}
                    <form method="GET" class="zt-filter-form row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="zt-form-label">Projekt</label>
                            <select name="project_id" class="form-select zt-select">
                                <option value="">Alle Projekte</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="zt-form-label">Maschine</label>
                            <select name="machine_id" class="form-select zt-select">
                                <option value="">Alle Maschinen</option>
                                @foreach($machines as $machine)
                                    <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>
                                        {{ $machine->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="zt-form-label">Datum</label>
                            <input type="date" name="date" class="form-control zt-select" value="{{ request('date') }}">
                            <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="zt-btn zt-btn--primary w-100">
                                <i class="bi bi-funnel-fill"></i> Filtern
                            </button>
                            <a href="{{ route('time-records.list', ['user_id' => $selectedUser->id]) }}" class="zt-btn zt-btn--ghost w-100">
                                <i class="bi bi-x-circle"></i> Zurücksetzen
                            </a>
                        </div>
                    </form>
                    {{-- ================================ --}}

                    <div class="table-responsive">
                        <table class="table zt-table zt-table--excel align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    @if(!$selectedUser)
                                        <th>Bediener</th>
                                    @endif
                                    <th>Projekt</th>
                                    <th>Position</th>
                                    <th>Maschine</th>
                                    <th>Start</th>
                                    <th>Ende</th>
                                    <th>Dauer</th>
                                    <th class="text-center">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $record)
                                    <tr>
                                        <td>{{ $records->firstItem() + $index }}</td>
                                        @if(!$selectedUser)
                                            <td>{{ $record->user->name }}</td>
                                        @endif
                                        <td>
                                            <span class="fw-bold">{{ $record->project->project_name }}</span>
                                            <div class="mt-1">
                                                <span class="zt-tag">
                                                    {{ $selectedUser->company === 'ZF' ? $record->project->auftragsnummer_zf : $record->project->auftragsnummer_zt }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ $record->position->name }}</td>
                                        <td>{{ $record->machine->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($record->start_time)->format('d.m.Y H:i') }}</td>
                                        <td>
                                            @if($record->end_time)
                                                {{ \Carbon\Carbon::parse($record->end_time)->format('d.m.Y H:i') }}
                                            @else
                                                <span class="zt-badge zt-badge--running">
                                                    <span class="zt-status-dot"></span> Läuft
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->end_time)
                                                {{ \Carbon\Carbon::parse($record->start_time)->diff($record->end_time) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('time-records.show', $record->id) }}" class="zt-icon-btn zt-icon-btn--view" title="Ansehen">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <p class="zt-empty mb-0">Keine Zeitaufzeichnungen gefunden.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $records->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
<style>
    /* User Selection Tiles */
    .user-tile {
        min-width: 220px;
        padding: 20px 30px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: #fff;
        font-size: 1.25rem;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    
    .user-tile:hover {
        transform: translateY(-6px) scale(1.03);
        box-shadow: 0 18px 40px rgba(0,0,0,0.25);
        color: #fff;
    }
    
    /* Animations */
    .fade-in {
        animation: fadeIn 0.6s ease forwards;
    }
    
    .slide-up {
        animation: slideUp 0.6s ease forwards;
    }

    .welcome-wrapper {
        min-height: calc(50vh);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .user-tile {
        animation-delay: 0.1s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ========== Selected User Records Block ========== */
    .zt-records {
        --brand-blue: #002752;
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        color: var(--zt-ink);
        font-variant-numeric: tabular-nums;
    }

    .zt-records-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-records-card > .card-header { background: var(--brand-blue); color: #fff; border-bottom: none; padding: 1rem 1.25rem; }
    .zt-records-card > .card-body { background: var(--zt-bg); }
    .zt-header-sub { font-weight: 400; opacity: .85; font-size: .9rem; }

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
    .zt-select:focus { border-color: var(--brand-blue); box-shadow: none; }

    .zt-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
        border-radius: 8px; font-size: .82rem; font-weight: 500;
        padding: .4rem .9rem; border: 1px solid var(--zt-line); text-decoration: none;
    }
    .zt-btn--primary { background: var(--brand-blue); color: #fff; border-color: var(--brand-blue); }
    .zt-btn--primary:hover { background: #001a3d; color: #fff; }
    .zt-btn--success { background: #1e7e4d; color: #fff; border-color: #1e7e4d; }
    .zt-btn--success:hover { background: #176a40; color: #fff; }
    .zt-btn--ghost { background: #fff; color: var(--zt-muted); }
    .zt-btn--ghost:hover { border-color: var(--brand-blue); color: var(--brand-blue); }

    .zt-empty { color: var(--zt-muted); font-size: .85rem; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; }

    .zt-tag {
        display: inline-block; font-size: .68rem; font-weight: 500; color: var(--zt-muted);
        border: 1px solid var(--zt-line); border-radius: 5px; padding: .05rem .4rem;
        background: #FAFBFC;
    }

    .zt-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 600; }
    .zt-badge--running { background: #E6F7EE; color: #1E7A46; }
    .zt-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block; }

    .zt-icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--zt-line);
        background: #fff; color: var(--zt-muted); text-decoration: none;
    }
    .zt-icon-btn--view:hover { border-color: #1E7A46; color: #1E7A46; }
</style>
@endsection