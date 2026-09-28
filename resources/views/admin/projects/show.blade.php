@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-folder2-open me-2"></i> {{ $project->project_name }}
            </h5>
            <a href="{{ route('admin.projects') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left-circle"></i> Zurück
            </a>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <h6 class="text-muted">Auftragsnummer</h6>
                    <p class="fw-semibold">
                        {{ $project->auftragsnummer_zt ? "ZT: ".$project->auftragsnummer_zt : '' }} 
                        {{ $project->auftragsnummer_zf ? "ZF: ".$project->auftragsnummer_zf : '' }}
                    </p>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Status:</h6>
                    @if($project->status)
                        <span class="badge" style="background-color: {{ $project->status->color }};">{{ $project->status->name }}</span>
                    @else
                        <span class="badge bg-secondary">Nicht zugewiesen</span>
                    @endif
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Startzeit:</h6>
                    <p>{{ $project->start_time ? \Carbon\Carbon::parse($project->start_time)->format('d.m.Y H:i') : '—' }}</p>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Endzeit:</h6>
                    <p>{{ $project->end_time ? \Carbon\Carbon::parse($project->end_time)->format('d.m.Y H:i') : '—' }}</p>
                </div>
            </div>

            <hr>

            <h5 class="mb-3"><i class="bi bi-cpu me-2"></i> Zugehörige Positionen<span class="text-muted"> - {{ $project->positions->count() ?? '0' }}</span></h5>
            @if($project->positions->isEmpty())
                <p class="text-muted">Keine Positionen gefunden.</p>
            @else
                <div class="row">
                    @foreach($project->positions as $position)
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        {{-- Bauteil image or placeholder --}}
                                        @if($position->image)
                                            <img src="{{ asset('storage/' . $position->image) }}" 
                                                alt="{{ $position->name }}" 
                                                class="rounded me-3" 
                                                width="70" height="70"
                                                style="object-fit: cover;">
                                        @else
                                            <div class="bg-light border rounded me-3 d-flex align-items-center justify-content-center"
                                                style="width:70px;height:70px;">
                                                <i class="bi bi-box-fill fs-3 text-secondary"></i>
                                            </div>
                                        @endif
                        
                                        <div class="flex-grow-1">
                                            {{-- Bauteil Name --}}
                                            <a href="{{ route('admin.projects.positions.edit', [$project->id, $position->id]) }}" 
                                            class="fw-semibold text-decoration-none text-dark">
                                                {{ $position->name }}
                                            </a>
                        
                                            {{-- Type icons --}}
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-box-fill text-secondary" title="Position"></i> Position                        
                                            </div>
                        
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <hr>

            <h5 class="mb-3"><i class="bi bi-cpu me-2"></i> Zugehörige Bauteile<span class="text-muted"> - {{ $project->bauteile->count() ?? '0' }}</span></h5>
            @if($project->bauteile->isEmpty())
                <p class="text-muted">Keine Bauteile gefunden.</p>
            @else
                <div class="row">
                    @foreach($project->bauteile as $bauteil)
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        {{-- Bauteil image or placeholder --}}
                                        @if($bauteil->image)
                                            <img src="{{ asset('storage/' . $bauteil->image) }}" 
                                                alt="{{ $bauteil->name }}" 
                                                class="rounded me-3" 
                                                width="70" height="70"
                                                style="object-fit: cover;">
                                        @else
                                            <div class="bg-light border rounded me-3 d-flex align-items-center justify-content-center"
                                                style="width:70px;height:70px;">
                                                <i class="bi bi-box fs-3 text-secondary"></i>
                                            </div>
                                        @endif
                        
                                        <div class="flex-grow-1">
                                            {{-- Bauteil Name --}}
                                            <a href="{{ route('admin.bauteile.show', $bauteil->id) }}" 
                                            class="fw-semibold text-decoration-none text-dark">
                                                {{ $bauteil->name }}
                                            </a>
                        
                                            {{-- Type icons --}}
                                            <div class="small text-muted mt-1">
                                                @if($bauteil->is_werkzeug)
                                                    <i class="bi bi-tools text-primary" title="Werkzeug"></i> Werkzeug
                                                @elseif($bauteil->is_baugruppe)
                                                    <i class="bi bi-diagram-3 text-info" title="Baugruppe"></i> Baugruppe
                                                @else
                                                    <i class="bi bi-box text-secondary" title="Bauteil"></i> Bauteil
                                                @endif
                        
                                                {{-- In-House production --}}
                                                @if($bauteil->in_house_production)
                                                    <span class="ms-2 text-success" title="In-House Produktion">
                                                        <i class="bi bi-house-fill"></i>
                                                    </span>
                                                @endif
                                            </div>
                        
                                            {{-- Lieferantenproject status --}}
                                            @php
                                                $latestProject = $bauteil->supplierProjects()->latest()->first();
                                            @endphp
                        
                                            @if($latestProject && $latestProject->status)
                                                <div class="mt-2">
                                                    <a href="{{ route('admin.suppliers.projects.show', $latestProject->id) }}" class="text-decoration-none">
                                                        <span class="badge" style="background-color: {{ $latestProject->status->color }};">
                                                            {{ $latestProject->status->name }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="mt-2">
                                                    <span class="badge bg-light text-dark">Kein Projektstatus</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                        
                                    {{-- Optional: Add meta info line --}}
                                    @if($latestProject)
                                        <div class="text-end small text-muted">
                                            Liefertermin am {{ $latestProject->end_date }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <hr>

            <div class="text-end">
                <a href="{{ route('admin.projects.edit', $project->id) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>
                </a>
                @if(auth()->user()?->canDeleteModule('projects'))
                    <form action="{{ route('admin.projects.destroy', $project->id) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger" onclick="return confirm('Dieses Projekt löschen?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
