@extends('user.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Neue Zeit Erfassung</h5>
            <a href="{{ route('time-records.list') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Alle Aufzeichnung
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('time-records.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Bediener</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Bediener auswählen</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Auftrag</label>
                        <select name="project_id" class="form-select" required>
                            <option value="">Auftrag auswählen</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Machine</label>
                        <select name="machine_id" class="form-select" required>
                            <option value="">Machine auswählen</option>
                            @foreach($machines as $machine)
                                <option value="{{ $machine->id }}">{{ $machine->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="btn-group" role="group" aria-label="Status selection">
                            @foreach($statuses as $status)
                                <input type="radio"
                                    class="btn-check"
                                    name="status_id"
                                    id="status-{{ $status->id }}"
                                    value="{{ $status->id }}"
                                    autocomplete="off">
    
                                <label class="btn btn-outline-dark"
                                    for="status-{{ $status->id }}">
                                    <i class="bi bi-circle me-1"></i> {{ $status->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Startzeit</button>
            </form>
        </div>
    </div>
</div>
@endsection
