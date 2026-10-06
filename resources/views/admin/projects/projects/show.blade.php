@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Projekt: {{ $project->name }}</h5>
            <a href="{{ route('admin.supplier-projects.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>

        <div class="card-body">

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="text-muted mb-1">Lieferant</h6>
                    <p class="fw-semibold mb-0">
                        <a href="{{ route('admin.suppliers.show', $project->offer->supplier) }}" class="text-decoration-none text-dark">
                            {{ $project->offer->supplier->name ?? '—' }}
                        </a>
                    </p>
                </div>
                <div>
                    <h6 class="text-muted mb-1">bauteil</h6>
                    <p class="fw-semibold mb-0">
                        <a href="{{ route('admin.bauteile.show', $project->offer->bauteil->id) }}" class="text-decoration-none text-dark">
                            {{ $project->offer->bauteil->name ?? '—' }} <span class="text-muted">({{ $project->offer->bauteil->project->project_name }})</span>
                        </a>
                    </p>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Leistung</h6>
                    <p class="fw-semibold mb-0">
                        <span class="badge" style="background-color: {{ $project->offer->service->color ?? 'gray' }};">{{ $project->offer->service->name ?? 'Nicht Sicher' }}</span>
                    </p>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Angebot</h6>
                    <p class="fw-semibold mb-0">
                        <a href="{{ route('admin.projects.offers.show', $project->offer->id) }}" class="text-decoration-none text-dark">
                            {{ $project->offer->offer_number ?? '—' }}
                        </a>
                    </p>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Status</h6>
                    <span class="badge" style="background-color: {{ $project->status->color ?? 'gray' }};">{{ $project->status->name ?? '—' }}</span>
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Startdatum:</strong> {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d.m.Y') : '—' }}
                </div>
                <div class="col-md-4">
                    <strong>Prüfdatum:</strong> {{ $project->checkup_date ? \Carbon\Carbon::parse($project->checkup_date)->format('d.m.Y') : '—' }}
                </div>
                <div class="col-md-4">
                    <strong>Enddatum:</strong> {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d.m.Y') : '—' }}
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Anzahl:</strong>
                        {{ $project->offer->pieces_to_develop }}
                </div>
                <div class="col-md-4">
                    <strong>Zusätzliche Kosten (€):</strong>
                    {{ number_format($project->additional_expense, 2, ',', '.') ?? '—' }}
                </div>
                <div class="col-md-4">
                    <strong>Gesamtpreis (€):</strong>
                    {{ number_format($project->gesamtpreis, 2, ',', '.') ?? '—' }}
                </div>
            </div>

            <hr>

            <div class="mb-3">
                <h6>Notizen</h6>
                <p>{{ $project->extra_note ?? 'Keine Notizen vorhanden.' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
