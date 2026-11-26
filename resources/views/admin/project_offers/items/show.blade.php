@extends('admin.layouts.index')

@section('content')

<div class="container mt-4">

    <div class="card shadow-sm">

        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
                <i class="bi bi-eye"></i>
                Kalkulation: {{ $calculation->designation }}
            </h5>
        </div>

        <div class="card-body">

            {{-- Calculation Summary --}}
            <h6 class="fw-bold text-primary mb-3">Zusammenfassung</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><strong>Bezeichnung:</strong> {{ $calculation->designation }}</div>
                <div class="col-md-3"><strong>Stunden:</strong> {{ $calculation->hours }}</div>
                <div class="col-md-3"><strong>Kosten:</strong> €{{ number_format($calculation->cost, 2) }}</div>
                <div class="col-md-3"><strong>Materialkosten:</strong> €{{ number_format($calculation->material_cost, 2) }}</div>
                <div class="col-md-3"><strong>Fremdleistungen:</strong> €{{ number_format($calculation->external_cost, 2) }}</div>
                <div class="col-md-3"><strong>Zusätzliche Steuer (%):</strong> {{ $calculation->extra_tax ?? 0 }}</div>
                <div class="col-md-3"><strong>Angebotsprozentsatz (%):</strong> {{ $calculation->final_offer ?? 0 }}</div>
                <div class="col-md-3"><strong>Stück:</strong> {{ $calculation->pieces }}</div>
                <div class="col-md-3"><strong>Gesamtkosten:</strong> €{{ number_format($calculation->total_cost, 2) }}</div>
                <div class="col-md-3"><strong>Angebotskosten:</strong> €{{ number_format($calculation->offer_cost, 2) }}</div>
                <div class="col-md-6"><strong>Notizen:</strong> {{ $calculation->notes }}</div>
                <div class="col-md-3"><strong>Erstellt von:</strong> {{ $calculation->user->name ?? '–' }}</div>
            </div>

            <hr>

            {{-- Calculation Items --}}
            <h6 class="fw-bold text-primary mb-3">Positionen</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Auftragsleistung</th>
                            <th>Stunden</th>
                            <th>€/Std.</th>
                            <th>Stück</th>
                            <th>Einzelpreis (€)</th>
                            <th>Kostenart</th>
                            <th>Kommentar</th>
                            <th>Gesamt (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($calculation->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item->service->name ?? '–' }}</td>
                                <td>{{ $item->hours }}</td>
                                <td>€{{ number_format($item->price_per_hour, 2) }}</td>
                                <td>{{ $item->pieces }}</td>
                                <td>€{{ number_format($item->price_per_unit, 2) }}</td>
                                <td>{{ ucfirst($item->cost_type) }}</td>
                                <td>{{ $item->comment }}</td>
                                <td>€{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <a href="{{ route('admin.project_offers.calculations', $offer->id) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
            </div>

        </div>
    </div>
</div>

@endsection
