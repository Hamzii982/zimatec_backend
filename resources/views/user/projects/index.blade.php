@extends('user.layouts.index')

@section('content')
    <div class="container mt-4 mb-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-kanban-fill me-2"></i>Alle Projekten
                </h5>
                @if(!$projects->isEmpty())
                    <span class="badge bg-secondary">{{ $projects->count() }} {{ $projects->count() === 1 ? 'Projekt' : 'Projekte' }}</span>
                @endif
            </div>

            <div class="card-body">
                @if($projects->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                        <p class="mb-0">Keine Projekte gefunden.</p>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach($projects as $project)
                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="card h-100 project-card border-0 shadow-sm">
                                    <div class="project-card-accent"></div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-start justify-content-between mb-2 flex-wrap gap-1">
                                            <div class="d-flex flex-wrap gap-1">
                                                @if($project->auftragsnummer_zf)
                                                    <span class="badge project-badge" title="Auftragsnummer ZF">
                                                        <i class="bi bi-hash"></i>{{ $project->auftragsnummer_zf }}
                                                    </span>
                                                @endif
                                                @if($project->auftragsnummer_zt)
                                                    <span class="badge project-badge" title="Auftragsnummer ZT">
                                                        <i class="bi bi-hash"></i>{{ $project->auftragsnummer_zt }}
                                                    </span>
                                                @endif
                                                @if(!$project->auftragsnummer_zf && !$project->auftragsnummer_zt)
                                                    <span class="badge project-badge text-muted">
                                                        <i class="bi bi-hash"></i>—
                                                    </span>
                                                @endif
                                            </div>

                                            @if($project->status)
                                                <span class="badge status-badge" style="background-color: {{ $project->status->color ?? '#6c757d' }}22; color: {{ $project->status->color ?? '#6c757d' }};">
                                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>{{ $project->status->name }}
                                                </span>
                                            @endif
                                        </div>

                                        <h5 class="card-title mb-3 project-title">{{ $project->project_name }}</h5>

                                        <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                            <small class="text-muted d-flex align-items-center">
                                                <i class="bi bi-calendar3 me-2"></i>
                                                Erstellt am {{ $project->created_at->format('d M Y') }}
                                            </small>
                                            <small class="text-muted d-flex align-items-center">
                                                <i class="bi bi-list-ol me-1"></i>
                                                {{ $project->positions_count }} {{ $project->positions_count === 1 ? 'Position' : 'Positionen' }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .project-card {
                position: relative;
                overflow: hidden;
                border-radius: 0.75rem;
                transition: transform 0.18s ease, box-shadow 0.18s ease;
            }

            .project-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 0.75rem 1.5rem rgba(0, 39, 82, 0.12) !important;
            }

            .project-card-accent {
                height: 5px;
                background: linear-gradient(90deg, #002752, #0d5ca8);
            }

            .project-badge {
                background-color: rgba(0, 39, 82, 0.08);
                color: #002752;
                font-weight: 600;
                font-size: 0.8rem;
                padding: 0.4em 0.65em;
                border-radius: 0.5rem;
            }

            .project-badge i {
                margin-right: 0.15rem;
            }

            .status-badge {
                font-weight: 600;
                font-size: 0.78rem;
                padding: 0.4em 0.65em;
                border-radius: 0.5rem;
            }

            .project-title {
                font-weight: 600;
                color: #212529;
                line-height: 1.35;
            }
        </style>
    @endpush
@endsection