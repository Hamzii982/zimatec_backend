@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Neues Lieferantenprojekt</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.supplier-projects.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Projektname</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="project_status_id" class="form-select" required>
                            <option value="">-- Status wählen --</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
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
                                <option value="{{ $offer->id }}">
                                    {{ $offer->supplier->name }} — {{ $offer->offer_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Startdatum</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Prüfdatum</label>
                        <input type="date" name="checkup_date" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Enddatum</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Zusätzliche Kosten (€)</label>
                        <input type="number" step="0.01" name="additional_expense" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Extra Notizen</label>
                    <textarea name="extra_note" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-wechsel">Speichern</button>
                <a href="{{ route('admin.supplier-projects.index') }}" class="btn btn-secondary">Abbrechen</a>
            </form>
        </div>
    </div>
</div>
@endsection
