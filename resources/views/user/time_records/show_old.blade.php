@extends('user.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Activ Zeit Erfassung</h5>
            <a href="{{ route('time-records.list') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Alle Aufzeichnung
            </a>
        </div>
        <div class="card-body">

            <div class="d-flex flex-wrap align-items-center gap-4 mb-3 text-secondary">

                <div>
                    <i class="bi bi-person-fill me-1 text-dark"></i>
                    <strong>Bediener:</strong> {{ $record->user->name }}
                </div>
            
                <div>
                    <i class="bi bi-folder-fill me-1 text-primary"></i>
                    <strong>Auftrag:</strong> {{ $record->project->project_name }}
                </div>
            
                <div>
                    <i class="bi bi-cpu-fill me-1 text-success"></i>
                    <strong>Maschine:</strong> {{ $record->machine->name }}
                </div>
            
                <div>
                    <i class="bi bi-clock-fill me-1 text-danger"></i>
                    <strong>Anfang um:</strong> {{ $record->start_time }}
                </div>

                @if(!$currentLog)
                    <div>
                        <i class="bi bi-clock-fill me-1 text-warning"></i>
                        <strong>Beendet um:</strong> {{ $record->end_time }}
                    </div>
                @endif
            
            </div>

            @if($currentLog)
                <hr>
                <h5 class="mb-3">Status Wechseln</h5>

                <form action="{{ route('time-records.switch', $currentLog->id) }}" method="POST" class="d-flex align-items-center justify-content-between flex-wrap gap-3">
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
                        <form action="{{ route('time-records.end', $record->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger">End Session</button>
                        </form>
                    @endif
                    <a href="{{ route('time-records.change-request', $record->id) }}" class="btn btn-wechsel ms-auto">
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
@endsection
