@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Angebotsdetails</h5>
            <a href="{{ route('admin.projects.offers') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <h6 class="text-muted">Lieferant</h6>
                    <a href="{{ route('admin.suppliers.show', $offer->supplier->id) }}" class="text-decoration-none text-dark">
                        <p class="mb-1 fw-semibold">{{ $offer->supplier->name ?? '—' }}</p>
                        <p class="text-muted small">{{ $offer->supplier->company ?? '' }}</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Bauteil</h6>
                    <a href="{{ route('admin.bauteile.show', $offer->bauteil->id) }}" class="text-decoration-none text-dark">
                        <p class="fw-semibold">{{ $offer->bauteil->name ?? '—' }}</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Lieferantenauftrag</h6>
                    @if($offer->project)
                        <a href="{{ route('admin.projects.projects.show', $offer->project->id) }}" class="h6 text-decoration-none text-dark">
                            <p class="fw-semibold">{{ $offer->project->name }}</p>
                        </a>
                    @else
                        <p class="fw-semibold">Kein</p>
                    @endif
                    
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Offer Leistung</h6>
                    <p class="badge" style="background-color: {{ $offer->service->color ?? 'gray' }};">{{ $offer->service->name ?? 'kein' }}</p>
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Angebotsnummer:</strong> {{ $offer->offer_number }}
                </div>
                <div class="col-md-4">
                    <strong>Datum:</strong> {{ \Carbon\Carbon::parse($offer->date)->format('d.m.Y') }}
                </div>
                <div class="col-md-4">
                    <strong>Preis:</strong> {{ number_format($offer->price, 2, ',', '.') }} €
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Dauer:</strong> {{ $offer->duration ?? '—' }}
                </div>
                <div class="col-md-4">
                    <strong>Stückzahl:</strong> {{ $offer->pieces_to_develop ?? '—' }}
                </div>
                <div class="col-md-4">
                    <strong>Elternangebot:</strong>
                    @if ($offer->parentOffer)
                        <a href="{{ route('admin.projects.offers.show', $offer->parentOffer->id) }}" 
                        class="text-decoration-none">
                            {{ $offer->parentOffer->offer_number }}
                        </a>
                    @else
                        —
                    @endif
                </div>
            </div>

            <hr>

            <div class="mb-3">
                <h6>Beschreibung</h6>
                <p>{{ $offer->description ?? 'Keine Beschreibung verfügbar.' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
