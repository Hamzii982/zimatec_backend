@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Bauteil bearbeiten</h5>
            <a href="{{ route('admin.bauteile.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-list"></i> Alle Bauteile
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.bauteile.update', $bauteil) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Name + Project --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Bauteilname</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $bauteil->name) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Auftrag</label>
                        <select name="project_id" class="form-select">
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ $project->id == $bauteil->project_id ? 'selected' : '' }}>
                                    {{ $project->project_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="parent_id" class="form-label">Parent Bauteil</label>
                    <select name="parent_id" id="parent_id" class="form-select">
                        <option value="">— Kein Elternteil —</option>
                        @foreach($bauteile as $parent)
                            <option value="{{ $parent->id }}" 
                                {{ $bauteil->parent_id == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Booleans --}}
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_werkzeug" id="is_werkzeug" value="1" {{ $bauteil->is_werkzeug ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_werkzeug">Werkzeug</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_baugruppe" id="is_baugruppe" value="1" {{ $bauteil->is_baugruppe ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_baugruppe">Baugruppe</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="in_house_production" id="in_house_production" value="1" {{ $bauteil->in_house_production ? 'checked' : '' }}>
                            <label class="form-check-label" for="in_house_production">In-House Produktion</label>
                        </div>
                    </div>
                </div>

                {{-- Image --}}
                <div class="mb-3">
                    <label class="form-label">Bild ändern</label>
                    @if ($bauteil->image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $bauteil->image) }}" alt="Bauteil" width="100" class="border rounded">
                        </div>
                    @endif
                    <input type="file" name="image" class="form-control">
                </div>

                {{-- Measurements --}}
                <h6 class="fw-semibold mt-4 mb-2 text-secondary">Maße</h6>
                <div class="row">
                    @php $m = $bauteil->measurement; @endphp
                    @foreach(['height' => 'Höhe', 'width' => 'Breite', 'depth' => 'Tiefe', 'thickness' => 'Dicke', 'radius' => 'Radius', 'weight' => 'Gewicht'] as $field => $label)
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $label }}</label>
                            <input type="number" step="0.01" name="{{ $field }}" class="form-control" value="{{ old($field, $m?->$field) }}">
                        </div>
                    @endforeach
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Einheit</label>
                        <input type="text" name="unit" class="form-control" value="{{ old('unit', $m?->unit) }}">
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-wechsel"><i class="bi bi-save"></i> Aktualisieren</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
