@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lieferantenprojekte</h5>
            <a href="{{ route('admin.supplier-projects.create') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Neues Projekt
            </a>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('admin.supplier-projects.index') }}" class="mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light fw-semibold" data-bs-toggle="collapse" href="#filterCollapse" role="button" aria-expanded="true">
                        <i class="bi bi-funnel me-2"></i> Filteroptionen
                    </div>
                    <div class="collapse show" id="filterCollapse">
                        <div class="card-body">
                            <div class="row g-3">
            
                                {{-- Project Status --}}
                                <div class="col-md-3">
                                    <label class="form-label">Projekt Status</label>
                                    <select name="project_status_id" class="form-select">
                                        <option value="">-- Alle Status --</option>
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}" {{ request('project_status_id') == $status->id ? 'selected' : '' }}>
                                                {{ $status->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
            
                                {{-- Supplier --}}
                                <div class="col-md-3">
                                    <label class="form-label">Lieferant</label>
                                    <select name="supplier_id" class="form-select">
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
                                    <label class="form-label">Bauteil</label>
                                    <select name="bauteil_id" class="form-select">
                                        <option value="">-- Alle Bauteile --</option>
                                        @foreach($bauteile as $bauteil)
                                            <option value="{{ $bauteil->id }}" {{ request('bauteil_id') == $bauteil->id ? 'selected' : '' }}>
                                                {{ $bauteil->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
            
                                {{-- Start Date Range --}}
                                <div class="col-md-3">
                                    <label class="form-label">Startdatum (Von - Bis)</label>
                                    <div class="d-flex gap-2">
                                        <input type="date" name="start_date_from" class="form-control" value="{{ request('start_date_from') }}">
                                        <input type="date" name="start_date_to" class="form-control" value="{{ request('start_date_to') }}">
                                    </div>
                                </div>
            
                                {{-- End Date Range --}}
                                <div class="col-md-3">
                                    <label class="form-label">Enddatum (Von - Bis)</label>
                                    <div class="d-flex gap-2">
                                        <input type="date" name="end_date_from" class="form-control" value="{{ request('end_date_from') }}">
                                        <input type="date" name="end_date_to" class="form-control" value="{{ request('end_date_to') }}">
                                    </div>
                                </div>
            
                                {{-- Past Projects --}}
                                <div class="col-md-2">
                                    <label class="form-label">Abgelaufene Projekte</label>
                                    <select name="past_projects" class="form-select">
                                        <option value="">-- Alle --</option>
                                        <option value="1" {{ request('past_projects') == '1' ? 'selected' : '' }}>Nur Vergangene</option>
                                    </select>
                                </div>
            
                                {{-- Price Range --}}
                                <div class="col-md-3">
                                    <label class="form-label">Gesamtpreis (€)</label>
                                    <div class="d-flex gap-2">
                                        <input type="number" step="0.01" name="price_min" class="form-control" placeholder="Min" value="{{ request('price_min') }}">
                                        <input type="number" step="0.01" name="price_max" class="form-control" placeholder="Max" value="{{ request('price_max') }}">
                                    </div>
                                </div>
            
                                {{-- Buttons --}}
                                <div class="col-md-12 d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-filter">
                                        <i class="bi bi-search me-1"></i> Filtern
                                    </button>
                                    <a href="{{ route('admin.supplier-projects.index') }}" class="btn btn-outline-secondary">
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
                            <th>Name</th>
                            <th>Angebot</th>
                            <th>Status</th>
                            <th>Start</th>
                            <th>Prüfung</th>
                            <th>Ende</th>
                            <th>Extra Kosten (€)</th>
                            <th>Gesamtpreis (€)</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                            <tr>
                                <td>{{ $project->name }}</td>
                                <td>
                                    <a href="{{ route('admin.projects.offers.show', $project->offer->id) }}" class="text-decoration-none text-dark">
                                        {{ $project->offer->supplier->name ?? '-' }}
                                        <small class="text-muted">({{ $project->offer->offer_number ?? '' }})</small>
                                    </a>
                                </td>
                                <td><span class="badge" style="background-color: {{ $project->status->color ?? 'gray' }}">{{ $project->status->name ?? '-' }}</span></td>
                                <td>{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d.m.Y') : '-' }}</td>
                                <td>{{ $project->checkup_date ? \Carbon\Carbon::parse($project->checkup_date)->format('d.m.Y') : '-' }}</td>
                                <td>{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d.m.Y') : '-' }}</td>
                                <td>{{ number_format($project->additional_expense, 2, ',', '.') ?? '-' }}</td>
                                <td>{{ $project->gesamtpreis }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.projects.projects.show', $project->id) }}" class="btn btn-sm btn-outline-secondary" title="Ansehen">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.projects.projects.edit', $project->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.projects.projects.destroy', $project->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Projekt löschen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">Keine Projekte gefunden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($projects instanceof \Illuminate\Pagination\AbstractPaginator && $projects->hasPages())
                <div class="mt-3">{{ $projects->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
