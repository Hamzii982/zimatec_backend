@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $bauteil->is_werkzeug ? 'Werkzeug' : ($bauteil->is_baugruppe ? 'Baugruppe' : 'Bauteil') }} Details</h5>
            <a href="{{ route('admin.bauteile.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-list"></i> Alle Bauteile
            </a>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>Name:</strong> {{ $bauteil->name }}
                    </div>
                    
                    <div class="mb-3">
                        <strong>Auftrag:</strong>
                        <a href="{{ route('admin.projects.show', $bauteil->project->id) }}" class="text-decoration-none text-dark">
                            {{ $bauteil->project->project_name ?? '—' }}
                        </a>
                    </div>

                    @if($bauteil->supplierProjects)
                    <div class="mb-3">
                        <strong>Lieferantenauftrag:</strong>
                        @foreach ($bauteil->supplierProjects as $sProject)
                            <a href="{{ route('admin.suppliers.projects.show', $sProject->id) }}" class="text-decoration-none text-dark">
                                {{ $sProject->name }} <span class="badge" style="background-color: {{ $sProject->status->color ?? 'gray' }};">{{ $sProject->status->name }}</span>
                            </a>
                        @endforeach
                    </div>
                    @endif
                    
                    @if ($bauteil->parent)
                        <div class="mb-3">
                            <strong>Parent Bauteil:</strong>
                            <div class="list-group-item mb-2 p-2 border rounded shadow-sm d-flex align-items-center justify-content-between flex-wrap">
            
                                <div class="d-flex align-items-center gap-3">
                                    {{-- Image --}}
                                    @if($bauteil->image)
                                        <img src="{{ asset('storage/' . $bauteil->parent->image) }}" alt="{{ $bauteil->parent->name }}" width="60" height="60" class="rounded border">
                                    @else
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width:60px; height:60px;">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    @endif
                    
                                    {{-- Name & link --}}
                                    <div>
                                        <a href="{{ route('admin.bauteile.show', $bauteil->parent->id) }}" class="h6 text-decoration-none text-dark">
                                            {{ $bauteil->parent->name }}
                                        </a>
                    
                                        {{-- Badges --}}
                                        @if($bauteil->parent->is_werkzeug)
                                            <span class="badge bg-primary ms-1">Werkzeug</span>
                                        @endif
                                        @if($bauteil->parent->is_baugruppe)
                                            <span class="badge bg-success ms-1">Baugruppe</span>
                                        @endif
                                        {{-- Home icon for in-house production --}}
                                        @if($bauteil->parent->in_house_production)
                                            <i class="bi bi-house-door-fill text-warning ms-2" title="In-house Produktion"></i>
                                        @endif
                                    </div>
                                </div>
                    
                                {{-- Optional arrow for expandable children --}}
                                @if($bauteil->parent->children->count())
                                    <span class="badge bg-secondary">{{ $bauteil->parent->children->count() }} Kinder</span>
                                @endif
                            </div> 
                        </div>
                    @endif
                    
                    @if($bauteil->children->count())
                        <div class="mb-3">
                            <strong>Child Bauteile:</strong>
                            {{-- Recursive Tree --}}
                            @include('admin.components.bauteil-tree', ['bauteile' => $bauteil->children])
                            
                        </div>
                    @endif                    
                </div>

                <div class="col-md-6 text-end">
                    @if ($bauteil->image)
                        <img src="{{ asset('storage/' . $bauteil->image) }}" alt="Bauteil" class="img-fluid rounded shadow" style="max-height: 180px;">
                    @endif
                </div>
            </div>

            <hr>

            <h6 class="fw-semibold mb-3 text-secondary">Maße</h6>
            @if ($bauteil->measurement)
                <div class="table-responsive-wrapper">
                    <table class="table table-bordered">
                        <tr><th>Höhe</th><td>{{ $bauteil->measurement->height ?? '—' }}</td></tr>
                        <tr><th>Breite</th><td>{{ $bauteil->measurement->width ?? '—' }}</td></tr>
                        <tr><th>Tiefe</th><td>{{ $bauteil->measurement->depth ?? '—' }}</td></tr>
                        <tr><th>Dicke</th><td>{{ $bauteil->measurement->thickness ?? '—' }}</td></tr>
                        <tr><th>Radius</th><td>{{ $bauteil->measurement->radius ?? '—' }}</td></tr>
                        <tr><th>Gewicht</th><td>{{ $bauteil->measurement->weight ?? '—' }}</td></tr>
                        <tr><th>Einheit</th><td>{{ $bauteil->measurement->unit ?? '—' }}</td></tr>
                    </table>
                </div>
            @else
                <p class="text-muted">Keine Maße vorhanden.</p>
            @endif
        </div>
    </div>
</div>
@endsection
