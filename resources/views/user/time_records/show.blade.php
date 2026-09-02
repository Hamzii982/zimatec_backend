@extends('user.layouts.index')

@section('content')
<div class="container mt-4 mb-5 zt-time">
    <div class="card shadow-sm zt-time-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-stopwatch me-2"></i>Aktive Zeiterfassung</h5>
            <a href="{{ route('time-records.list') }}" class="zt-export-btn">
                <i class="bi bi-list-ul"></i> Alle Aufzeichnungen
            </a>
        </div>

        <div class="card-body">

            {{-- ========== TIMER + RECORD OVERVIEW ========== --}}
            <div class="row align-items-center mb-4 g-4">

                {{-- LEFT: TIMER --}}
                <div class="col-md-3">
                    <div class="zt-timer-panel text-center">
                        <i class="bi bi-hourglass-split hourglass-icon {{ $record->end_time ? 'stopped' : '' }}"></i>
                        <div class="timer-display" id="running-timer">--:--:--</div>
                        <small class="text-muted d-block mb-2">
                            {{ $record->end_time ? 'Gesamtdauer' : 'Laufzeit' }}
                        </small>
                        @if($currentLog && $currentLog->status)
                            <span class="zt-status-pill" style="background-color: {{ $currentLog->status->color ?? '#667085' }}1A; color: {{ $currentLog->status->color ?? '#667085' }};">
                                <span class="zt-status-dot" style="background-color: {{ $currentLog->status->color ?? '#667085' }};"></span>
                                {{ $currentLog->status->name }}
                            </span>
                        @elseif($record->end_time)
                            <span class="zt-status-pill zt-status-pill--muted">
                                <i class="bi bi-check-circle me-1"></i>Beendet
                            </span>
                        @endif
                    </div>
                </div>

                {{-- RIGHT: RECORD DETAILS --}}
                <div class="col-md-9">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="zt-info-tile">
                                <i class="bi bi-person-fill"></i>
                                <div>
                                    <span class="zt-info-label">Bediener</span>
                                    <span class="zt-info-value">{{ $record->user->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="zt-info-tile">
                                <i class="bi bi-kanban"></i>
                                <div>
                                    <span class="zt-info-label">Projekt</span>
                                    <span class="zt-info-value">{{ $record->project->project_name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="zt-info-tile">
                                <i class="bi bi-layers-fill"></i>
                                <div>
                                    <span class="zt-info-label">Position</span>
                                    <span class="zt-info-value">{{ $record->position->name ?? '—' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="zt-info-tile">
                                <i class="bi bi-cpu-fill"></i>
                                <div>
                                    <span class="zt-info-label">Maschine</span>
                                    <span class="zt-info-value">{{ $record->machine->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="zt-info-tile">
                                <i class="bi bi-clock-fill"></i>
                                <div>
                                    <span class="zt-info-label">Anfang</span>
                                    <span class="zt-info-value">{{ \Carbon\Carbon::parse($record->start_time)->format('d.m.Y H:i:s') }}</span>
                                </div>
                            </div>
                        </div>

                        @if(!$currentLog && $record->end_time)
                            <div class="col-md-6">
                                <div class="zt-info-tile">
                                    <i class="bi bi-clock-fill"></i>
                                    <div>
                                        <span class="zt-info-label">Beendet</span>
                                        <span class="zt-info-value">{{ \Carbon\Carbon::parse($record->end_time)->format('d.m.Y H:i:s') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- =============================================== --}}

            @if($currentLog)
                <div class="zt-section">
                    <h6 class="zt-section-title"><i class="bi bi-arrow-repeat me-1"></i>Status Wechseln</h6>

                    <form action="{{ route('time-records.switch', $currentLog->id) }}" method="POST" class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        @csrf

                        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Status selection">
                            @foreach($statuses as $status)
                                <input type="radio"
                                    class="btn-check status-radio"
                                    name="status_id"
                                    id="status-{{ $status->id }}"
                                    value="{{ $status->id }}"
                                    data-name="{{ strtolower($status->name) }}"
                                    autocomplete="off"
                                    {{ $currentLog->machine_status_id == $status->id ? 'checked' : '' }}>

                                <label class="zt-status-option"
                                    style="--zt-status-color: {{ $status->color ?? '#667085' }};"
                                    for="status-{{ $status->id }}">
                                    <span class="zt-status-dot"></span>
                                    {{ $status->name }}
                                </label>
                            @endforeach
                        </div>

                        <div id="manual-process-wrap" class="d-none d-flex align-items-center gap-2">
                            <div class="form-check m-0">
                                <input class="form-check-input"
                                    type="checkbox"
                                    id="manual-process-checkbox"
                                    name="manual_process"
                                    value="1">
                                <label class="form-check-label ms-1">Manueller Prozess</label>
                            </div>
                            <input type="text"
                                class="form-control form-control-sm d-none"
                                id="manual-process-name"
                                name="manual_process_name"
                                placeholder="Prozess Name">
                        </div>

                        <button type="submit" class="btn btn-wechsel ms-auto">
                            <i class="bi bi-arrow-repeat me-1"></i> Status Wechseln
                        </button>
                    </form>
                </div>
            @endif

            <div class="zt-section d-flex flex-wrap align-items-center gap-3">
                @if($currentLog)
                    <form action="{{ route('time-records.end', $record->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-stop-circle me-1"></i>End Session
                        </button>
                    </form>
                @endif
                <a href="{{ route('time-records.change-request', $record->id) }}" class="btn btn-wechsel ms-auto">
                    <i class="bi bi-check-circle me-1"></i> Nachtrag Request
                </a>
            </div>

            <div class="zt-section">
                <h6 class="zt-section-title"><i class="bi bi-clock-history me-1"></i>Aktuelle Logs</h6>
                @forelse($record->logs as $log)
                    <div class="zt-log-row">
                        <span class="zt-status-dot" style="background-color: {{ optional($log->status)->color ?? '#667085' }};"></span>
                        <span class="zt-log-status">{{ optional($log->status)->name ?? 'Unbekannt' }}</span>
                        <span class="zt-log-time text-muted">
                            {{ $log->start_time }} – {{ $log->end_time ?? 'Laufend' }}
                        </span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Keine Logs vorhanden.</p>
                @endforelse
            </div>

            <div class="zt-section">
                <h6 class="zt-section-title"><i class="bi bi-tools me-1"></i>Manuelle Prozesse</h6>

                @forelse($record->processes as $process)
                    <div class="zt-process-row">
                        <div>
                            <strong>{{ $process->name }}</strong>
                            <div class="text-muted small">
                                {{ $process->start_time }} – {{ $process->end_time ?? 'Laufend' }}
                            </div>
                        </div>

                        @if(!$process->end_time)
                            <form action="{{ route('time-records.processes.end', $process->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger">
                                    Beenden
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-muted small mb-0">Keine manuellen Prozesse vorhanden.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
    .zt-time {
        --brand-blue: #002752;
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        color: var(--zt-ink);
    }

    .zt-time-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-time-card > .card-header { background: var(--brand-blue); color: #fff; border-bottom: none; padding: 1rem 1.25rem; }
    .zt-time-card > .card-body { background: var(--zt-bg); padding: 1.5rem; }

    .zt-export-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: transparent;
        color: #fff; font-size: .8rem; font-weight: 500;
        padding: .4rem .75rem; text-decoration: none;
        transition: background .15s;
    }
    .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

    /* --- Timer panel --- */
    .zt-timer-panel {
        background: #fff; border: 1px solid var(--zt-line); border-radius: 10px;
        padding: 1.5rem 1rem;
    }
    .hourglass-icon {
        font-size: 2.6rem;
        color: var(--brand-blue);
        animation: spinHourglass 2.5s linear infinite;
        display: inline-block;
    }
    .hourglass-icon.stopped { animation: none; color: var(--zt-muted); }
    @keyframes spinHourglass {
        0%   { transform: rotate(0deg); }
        50%  { transform: rotate(180deg); }
        100% { transform: rotate(360deg); }
    }
    .timer-display {
        font-size: 1.8rem; font-weight: 700; letter-spacing: 1px;
        font-variant-numeric: tabular-nums;
        margin: .35rem 0;
    }
    .zt-status-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .78rem; font-weight: 600;
        padding: .3rem .7rem; border-radius: 20px;
    }
    .zt-status-pill--muted { background: #EEF0F2; color: var(--zt-muted); }
    .zt-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }

    /* --- Info tiles --- */
    .zt-info-tile {
        display: flex; align-items: flex-start; gap: .65rem;
        background: #fff; border: 1px solid var(--zt-line); border-radius: 8px;
        padding: .75rem .9rem; height: 100%;
    }
    .zt-info-tile i { font-size: 1.1rem; color: var(--brand-blue); margin-top: .15rem; }
    .zt-info-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: var(--zt-muted); display: block; }
    .zt-info-value { font-size: .95rem; font-weight: 600; color: var(--zt-ink); display: block; }

    /* --- Sections --- */
    .zt-section {
        background: #fff; border: 1px solid var(--zt-line); border-radius: 8px;
        padding: 1rem 1.1rem; margin-top: 1rem;
    }
    .zt-section-title {
        font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
        color: var(--zt-muted); margin-bottom: .75rem;
    }

    /* --- Status radio buttons with color --- */
    .zt-status-option {
        display: inline-flex; align-items: center; gap: .45rem;
        border: 1px solid var(--zt-line); border-radius: 20px;
        padding: .45rem 1rem; font-size: .85rem; font-weight: 600;
        color: var(--zt-muted); cursor: pointer; background: #fff;
        transition: all .15s ease;
    }
    .zt-status-option .zt-status-dot { background-color: var(--zt-status-color); }
    .zt-status-option:hover { border-color: var(--zt-status-color); color: var(--zt-status-color); }
    .status-radio:checked + .zt-status-option {
        background-color: color-mix(in srgb, var(--zt-status-color) 12%, #fff);
        border-color: var(--zt-status-color);
        color: var(--zt-status-color);
    }

    .btn-wechsel {
        background: var(--brand-blue); color: #fff; border: none; border-radius: 8px;
        padding: .5rem 1.1rem; font-weight: 600; font-size: .85rem;
    }
    .btn-wechsel:hover { background: #001a3d; color: #fff; }

    /* --- Logs --- */
    .zt-log-row {
        display: flex; align-items: center; gap: .6rem;
        padding: .5rem 0; border-bottom: 1px solid var(--zt-line); font-size: .85rem;
    }
    .zt-log-row:last-child { border-bottom: none; }
    .zt-log-status { font-weight: 600; min-width: 110px; }
    .zt-log-time { margin-left: auto; font-size: .8rem; }

    /* --- Manual processes --- */
    .zt-process-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: .6rem 0; border-bottom: 1px solid var(--zt-line);
    }
    .zt-process-row:last-child { border-bottom: none; }
</style>

<script>
    (function () {

        const startTimestamp = {{ \Carbon\Carbon::parse($record->start_time)->timestamp }};
        const endTimestamp   = {{ $record->end_time
            ? \Carbon\Carbon::parse($record->end_time)->timestamp
            : 'null'
        }};
        const display = document.getElementById('running-timer');

        function format(seconds) {
            const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            return `${h}:${m}:${s}`;
        }

        function updateTimer() {
            const now = endTimestamp ?? Math.floor(Date.now() / 1000);
            const diff = Math.max(0, now - startTimestamp);
            display.textContent = format(diff);
        }

        updateTimer();

        if (!endTimestamp) {
            setInterval(updateTimer, 1000);
        }

    })();

    document.addEventListener('DOMContentLoaded', function () {

        const radios = document.querySelectorAll('.status-radio');
        const wrap = document.getElementById('manual-process-wrap');
        const checkbox = document.getElementById('manual-process-checkbox');
        const input = document.getElementById('manual-process-name');

        radios.forEach(radio => {
            radio.addEventListener('change', function () {

                if (this.dataset.name === 'mit aufsicht') {
                    wrap.classList.remove('d-none');
                } else {
                    wrap.classList.add('d-none');
                    checkbox.checked = false;
                    input.classList.add('d-none');
                    input.required = false;
                    input.value = '';
                }
            });
        });

        checkbox.addEventListener('change', function () {
            if (this.checked) {
                input.classList.remove('d-none');
                input.required = true;
            } else {
                input.classList.add('d-none');
                input.required = false;
                input.value = '';
            }
        });
    });
</script>
@endsection