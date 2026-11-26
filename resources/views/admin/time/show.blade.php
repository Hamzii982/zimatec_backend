@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Activ Zeit Erfassung</h5>
            <a href="{{ route('admin.time.records') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Alle Aufzeichnung
            </a>
        </div>
        <div class="card-body">

            <div class="row align-items-center mb-4">

                {{-- LEFT: TIMER --}}
                <div class="col-md-3 text-center border-end">
            
                    <div class="d-flex flex-column align-items-center gap-2">
            
                        <i class="bi bi-hourglass-split hourglass-icon {{ $record->end_time ? 'stopped' : '' }}"></i>
            
                        <div class="timer-display" id="running-timer">
                            --:--:--
                        </div>
            
                        <small class="text-muted">
                            {{ $record->end_time ? 'Gesamtdauer' : 'Laufzeit' }}
                        </small>
            
                    </div>
                </div>
            
                {{-- RIGHT: RECORD DETAILS --}}
                <div class="col-md-9">
            
                    <div class="row g-3 fs-5">
            
                        <div class="col-md-6">
                            <i class="bi bi-person-fill me-1 text-dark"></i>
                            <strong>Bediener</strong><br>
                            {{ $record->user->name }}
                        </div>
            
                        <div class="col-md-6">
                            <i class="bi bi-kanban me-1 text-dark"></i>
                            <strong>Auftrag</strong><br>
                            {{ $record->project->project_name }}
                        </div>
            
                        <div class="col-md-6">
                            <i class="bi bi-layers-fill me-1 text-dark"></i>
                            <strong>Position</strong><br>
                            {{ $record->position->name ?? '—' }}
                        </div>

                        <div class="col-md-6">
                            <i class="bi bi-cpu-fill me-1 text-dark"></i>
                            <strong>Maschine:</strong><br> 
                            {{ $record->machine->name }}
                        </div>
            
                        <div class="col-md-6">
                            <i class="bi bi-clock-fill me-1 text-dark"></i>
                            <strong>Anfang</strong><br>
                            {{ \Carbon\Carbon::parse($record->start_time)->format('d.m.Y H:i:s') }}
                        </div>
            
                        @if(!$currentLog && $record->end_time)
                            <div class="col-md-6">
                                <i class="bi bi-clock-fill me-1 text-dark"></i>
                                <strong>Beendet</strong><br>
                                {{ \Carbon\Carbon::parse($record->end_time)->format('d.m.Y H:i:s') }}
                            </div>
                        @endif
            
                    </div>
                </div>
            </div>            

            @if($currentLog)
                <hr>
                <h5 class="mb-3">Status Wechseln</h5>

                <form action="{{ route('admin.time.switch', $currentLog->id) }}" method="POST" class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    @csrf

                    <div class="btn-group" role="group" aria-label="Status selection">
                        @foreach($statuses as $status)
                            <input type="radio"
                                class="btn-check"
                                name="status_id"
                                id="status-{{ $status->id }}"
                                value="{{ $status->id }}"
                                autocomplete="off"
                                {{ $currentLog->machine_status_id == $status->id ? 'checked' : '' }}>

                            <label class="btn btn-outline-dark"
                                for="status-{{ $status->id }}">
                                <i class="bi bi-circle me-1"></i> {{ $status->name }}
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-wechsel ms-auto">
                        <i class="bi bi-arrow-repeat me-1"></i> Status Wechseln
                    </button>
                </form>
            @endif

                <hr>

                <div class="d-flex flex-wrap align-items-center gap-4">
                    @if($currentLog)
                        <form action="{{ route('admin.time.end', $record->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger">End Session</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.time.change-logs', $record->id) }}" class="btn btn-wechsel ms-auto">
                        <i class="bi bi-check-circle me-1"></i> Nachtrag Request
                    </a>
                </div>
            <hr>

            <h5>Aktuelle Logs</h5>
            <ul>
                @foreach($record->logs as $log)
                    <li>
                        {{ optional($log->status)->name ?? 'Unbekannt' }} :
                        {{ $log->start_time }} - {{ $log->end_time ?? 'Laufend' }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<style>
    .hourglass-icon {
        font-size: 3rem;
        color: #6c757d;
        animation: spinHourglass 2.5s linear infinite;
    }

    .hourglass-icon.stopped {
        animation: none;
    }
    
    @keyframes spinHourglass {
        0%   { transform: rotate(0deg); }
        50%  { transform: rotate(180deg); }
        100% { transform: rotate(360deg); }
    }
    
    .timer-display {
        font-size: 2rem;
        font-weight: 600;
        letter-spacing: 1px;
    }
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
</script>
@endsection
