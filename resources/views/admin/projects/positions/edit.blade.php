@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
                Position bearbeiten – {{ $project->project_name }}
            </h5>
        </div>

        <div class="card-body">
            <form method="POST"
                  action="{{ route('admin.projects.positions.update', [$project, $position]) }}"
                  >
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Positionname</label>
                        <input type="text"
                            name="name"
                            class="form-control"
                            value="{{ old('name', $position->name) }}"
                            required>
                        @error('name')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Auftragsleistung</label>
                        <select name="project_service_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}"
                                    @selected(old('project_service_id', $position->project_service_id) == $service->id)>
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button class="btn btn-wechsel">
                    <i class="bi bi-save"></i> Aktualisieren
                </button>

                <a href="{{ route('admin.projects.positions.index', $project) }}"
                   class="btn btn-secondary">
                    Cancel
                </a>
            </form>
        </div>
    </div>
</div>
@endsection
