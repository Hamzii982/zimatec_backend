@extends('admin.layouts.index')

@section('title', $service->exists ? 'Auftragsleistung bearbeiten' : 'Auftragsleistung hinzufügen')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $service->exists ? 'Edit '.$service->name : 'Add Neue Leistung' }}</h5>
            <a href="{{ route('admin.settings.project-service') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-book-circle me-1"></i> Alle Auftragsleistungen
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.settings.project-service.update', $service->id ?? null) }}" method="POST">
                @csrf
                @method('POST')

                {{-- Name --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Leistung Name</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $service->name) }}" required>
                    @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                {{-- Parent --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Übergeordnete Leistung (optional)</label>
                    <select name="parent_id" class="form-select">
                        <option value="">— Kein Parent —</option>
                        @foreach($parentServices as $ps)
                            <option value="{{ $ps->id }}"
                                {{ old('parent_id', $service->parent_id) == $ps->id ? 'selected' : '' }}>
                                {{ $ps->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                {{-- Color --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color</label>
                    <input type="color" name="color" class="form-control form-control-color"
                           value="{{ old('color', $service->color ?? '#000000') }}">
                    @error('color') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                {{-- Active --}}
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="active" 
                           {{ old('active', $service->active) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold">Active</label>
                </div>

                <button type="submit" class="btn btn-wechsel">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
