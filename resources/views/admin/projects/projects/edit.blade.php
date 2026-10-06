@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Edit Lieferantenprojekt</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.supplier-projects.update', $project->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Projektname</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $project->name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="project_status_id" class="form-select" required>
                            <option value="">-- Status wählen --</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" {{ old('project_status_id', $project->project_status_id) == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Angebot</label>
                        <select name="supplier_offer_id" class="form-select" required>
                            <option value="">-- Angebot wählen --</option>
                            @foreach ($offers as $offer)
                                <option value="{{ $offer->id }}" selected="{{ old('supplier_offer_id', $project->supplier_offer_id) == $offer->id ? 'selected' : '' }}">
                                    {{ $offer->supplier->name }} — {{ $offer->offer_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Startdatum</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $project->start_date) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Prüfdatum</label>
                        <input type="date" name="checkup_date" class="form-control" value="{{ old('checkup_date', $project->checkup_date) }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Enddatum</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $project->end_date) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Zusätzliche Kosten (€)</label>
                        <input type="number" step="0.01" name="additional_expense" class="form-control" value="{{ old('additional_expense', $project->additional_expense) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Extra Notizen</label>
                    <textarea name="extra_note" class="form-control" rows="3">{{ old('extra_note', $project->extra_note) }}</textarea>
                </div>

                <button type="submit" class="btn btn-wechsel">Speichern</button>
                <a href="{{ route('admin.supplier-projects.index') }}" class="btn btn-secondary">Abbrechen</a>
            </form>
        </div>
    </div>
</div>
@endsection
