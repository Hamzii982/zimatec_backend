@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Zeit Erfassung Bearbeiten</h5>
            <a href="{{ route('admin.time.records') }}" class="btn btn-success btn-sm">
                <i class="bi bi-list-ul me-1"></i> Alle Aufzeichnungen
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.time.update', $record->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Record Information --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Bediener</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Bediener auswählen</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $record->user_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Maschine</label>
                        <select name="machine_id" class="form-select" required>
                            <option value="">Maschine auswählen</option>
                            @foreach($machines as $machine)
                                <option value="{{ $machine->id }}" {{ $record->machine_id == $machine->id ? 'selected' : '' }}>
                                    {{ $machine->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Auftrag</label>
                        <select onchange="fetchPositions()" name="project_id" class="form-select" id="project-select" required>
                            <option value="">Auftrag auswählen</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ $record->project_id == $project->id ? 'selected' : '' }}>
                                    {{ $project->project_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Position</label>
                        <select name="position_id" class="form-select" id="position-select" required>
                            <option value="">Position auswählen</option>
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}" {{ $record->position_id == $position->id ? 'selected' : '' }}>
                                    {{ $position->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="positionId" value="{{ $record->position_id }}" />
                    </div>
                </div>

                {{-- Start & End Time --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Startzeit</label>
                        <input type="datetime-local" name="start_time" value="{{ \Carbon\Carbon::parse($record->start_time)->format('Y-m-d\TH:i') }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Endzeit</label>
                        <input type="datetime-local" name="end_time" value="{{ $record->end_time ? \Carbon\Carbon::parse($record->end_time)->format('Y-m-d\TH:i') : '' }}" class="form-control">
                    </div>
                </div>

                {{-- Submit --}}
                <div class="text-end">
                    <button type="submit" class="btn btn-wechsel">
                        <i class="bi bi-save"></i> Änderungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function fetchPositions() {
        projectId = document.getElementById('project-select').value;
        positionSelector = document.getElementById('position-select');
        positionId = document.getElementById('positionId').value;
        fetch(`/admin/time/project/positions?projectId=${projectId}`, {method: "GET"})
            .then(response => response.json())
            .then(data =>{
                if('positions' in data){
                    positionSelector.innerHTML = `<option value="">Position auswählen</option>`;
                    data['positions'].forEach(position => {
                        selected = '';
                        if(position.id == positionId){
                            selected = 'selected';
                        }
                        positionSelector.innerHTML += `
                            <option value=${position.id} ${selected}>
                                ${position.name}
                            </option>
                        `;
                    });
                }
            })
    }
</script>
@endsection