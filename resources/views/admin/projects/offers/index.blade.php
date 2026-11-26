@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lieferantenangebote</h5>
            <a href="{{ route('admin.projects.offers.create') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Neues Angebot
            </a>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('admin.projects.offers') }}" class="mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light fw-semibold" data-bs-toggle="collapse" href="#filterCollapse" role="button" aria-expanded="true">
                        <i class="bi bi-funnel me-2"></i> Filteroptionen
                    </div>
                    <div class="collapse show" id="filterCollapse">
                        <div class="card-body">
                            <div class="row g-3">
            
                                {{-- Supplier --}}
                                <div class="col-md-3">
                                    <select name="supplier_id" id="supplier_id" class="form-select">
                                        <option value="">-- Alle Lieferanten --</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                        
                                {{-- Bauteil --}}
                                <div class="col-md-3">
                                    <select name="bauteil_id" id="bauteil_id" class="form-select">
                                        <option value="">-- Alle Bauteile --</option>
                                        @foreach($bauteile as $bauteil)
                                            <option value="{{ $bauteil->id }}" {{ request('bauteil_id') == $bauteil->id ? 'selected' : '' }}>
                                                {{ $bauteil->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                        
                                {{-- Service --}}
                                <div class="col-md-3">
                                    <select name="project_service_id" id="project_service_id" class="form-select">
                                        <option value="">-- Alle Dienstleistungen --</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}" {{ request('project_service_id') == $service->id ? 'selected' : '' }}>
                                                {{ $service->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                        
                                {{-- Date --}}
                                <div class="col-md-3">
                                    <input type="date" name="date" id="date" class="form-control" value="{{ request('date') }}">
                                </div>

                                {{-- Has Supplier Project --}}
                                <div class="col-md-3">
                                    <select name="has_project" id="has_project" class="form-select">
                                        <option value="">-- Lieferantenaufträge --</option>
                                        <option value="1" {{ request('has_project') == '1' ? 'selected' : '' }}>Mit Lieferantenauftrag</option>
                                        <option value="0" {{ request('has_project') == '0' ? 'selected' : '' }}>Ohne Lieferantenauftrag</option>
                                    </select>
                                </div>
                        
                                {{-- Buttons --}}
                                <div class="col-md-3 d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-filter">
                                        <i class="bi bi-search me-1"></i> Filtern
                                    </button>
                                    <a href="{{ route('admin.projects.offers') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle me-1"></i> Zurücksetzen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="table-responsive-wrapper">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Angebotsnr.</th>
                            <th>Lieferant</th>
                            <th>Bauteil</th>
                            <th>Lieferantenauftrag</th>
                            <th>Datum</th>
                            <th>Leistung</th>
                            <th>Preis (€)</th>
                            <th>Dauer (Tage)</th>
                            <th>Stück</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($offers as $offer)
                            <tr>
                                <td>{{ $offer->offer_number }}</td>
                                <td>
                                    <a href="{{ route('admin.suppliers.show', $offer->supplier->id) }}" class="h6 text-decoration-none text-dark">
                                        {{ $offer->supplier->name }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('admin.bauteile.show', $offer->bauteil->id) }}" class="h6 text-decoration-none text-dark">
                                        {{ $offer->bauteil->name ?? '-' }}
                                    </a>
                                </td>
                                <td>
                                    @if($offer->project)
                                        <a href="{{ route('admin.projects.projects.show', $offer->project->id) }}" class="h6 text-decoration-none text-dark">
                                            {{ $offer->project->name }}
                                        </a>
                                    @else
                                        Kein
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($offer->date)->format('d.m.Y') }}</td>
                                <td>
                                    @if($offer->service)
                                        <span class="badge" style="background-color: {{ $offer->service->color }};">{{ $offer->service->name }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ number_format($offer->price, 2, ',', '.') }}</td>
                                <td>{{ $offer->duration ?? '-' }}</td>
                                <td>{{ $offer->pieces_to_develop ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.projects.offers.show', $offer->id) }}" class="btn btn-sm btn-outline-secondary" title="Ansehen">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.projects.offers.edit', $offer->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.projects.offers.destroy', $offer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Dieses Angebot löschen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted">Keine Angebote gefunden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $offers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
