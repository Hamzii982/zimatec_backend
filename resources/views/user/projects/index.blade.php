@extends('user.layouts.index')

@section('content')
    <div class="container mt-4 mb-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-kanban-fill me-2"></i>Alle Aufträge
                </h5>
                @if(!$projects->isEmpty())
                    <span class="badge project-total-count">{{ $projects->count() }} {{ $projects->count() === 1 ? 'Auftrag' : 'Aufträge' }}</span>
                @endif
            </div>

            <div class="card-body">
                @if($projects->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                        <p class="mb-0">Keine Aufträge gefunden.</p>
                    </div>
                @else
                    <div class="project-filters mb-4">
                        <label for="project-search" class="form-label fw-semibold">{{ __('fraesdata.project_search') }}</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-primary"></i></span>
                            <input
                                type="search"
                                id="project-search"
                                class="form-control"
                                placeholder="{{ __('fraesdata.project_search_hint') }}"
                                autocomplete="off"
                            >
                        </div>
                        <div class="d-flex flex-wrap gap-2" role="group" aria-label="{{ __('fraesdata.filter_by_status') }}">
                            <button
                                type="button"
                                class="btn btn-sm project-status-filter active"
                                data-status-filter="all"
                                aria-pressed="true"
                                style="--status-color: #002752"
                            >
                                {{ __('fraesdata.all_statuses') }}
                                <span class="badge ms-1">{{ $projects->count() }}</span>
                            </button>
                            @foreach($statuses as $status)
                                @php($statusCount = $projects->where('project_status_id', $status->id)->count())
                                <button
                                    type="button"
                                    class="btn btn-sm project-status-filter"
                                    data-status-filter="{{ $status->id }}"
                                    aria-pressed="false"
                                    style="--status-color: {{ $status->color ?: '#002752' }}"
                                >
                                    {{ $status->name }}
                                    <span class="badge ms-1">{{ $statusCount }}</span>
                                </button>
                            @endforeach
                            @php($withoutStatusCount = $projects->whereNull('project_status_id')->count())
                            @if($withoutStatusCount > 0)
                                <button
                                    type="button"
                                    class="btn btn-sm project-status-filter"
                                    data-status-filter="none"
                                    aria-pressed="false"
                                    style="--status-color: #6c757d"
                                >
                                    {{ __('fraesdata.no_status') }}
                                    <span class="badge ms-1">{{ $withoutStatusCount }}</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div id="no-project-matches" class="alert alert-light border text-center d-none" role="status">
                        {{ __('fraesdata.no_project_matches') }}
                    </div>

                    <div class="row g-3" id="project-cards">
                        @foreach($projects as $project)
                            <div
                                class="col-12 col-sm-6 col-lg-4 project-card-item"
                                data-project-search="{{ implode(' ', array_filter([$project->project_name, $project->auftragsnummer_zt, $project->auftragsnummer_zf])) }}"
                                data-project-status="{{ $project->project_status_id ?? 'none' }}"
                            >
                                <div class="card h-100 project-card border-0 shadow-sm">
                                    <div class="project-card-accent"></div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-start justify-content-between mb-2 flex-wrap gap-2">
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
                                            <span
                                                class="badge status-badge"
                                                style="--status-color: {{ $project->status?->color ?: '#002752' }}"
                                            >
                                                <i class="bi bi-circle-fill me-1"></i>{{ $project->status?->name ?? __('fraesdata.no_status') }}
                                            </span>
                                        </div>

                                        <h5 class="card-title mb-3 project-title">
                                            <a
                                                href="{{ route('projects.cnc-data', $project) }}"
                                                class="text-decoration-none text-reset"
                                            >
                                                {{ $project->project_name }}
                                            </a>
                                        </h5>
                                        <small class="project-created text-muted mb-3">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            {{ __('fraesdata.created_at') }} {{ $project->created_at->format('d.m.Y') }}
                                        </small>

                                        <div class="project-counts d-flex flex-wrap align-items-center gap-2 mb-3">
                                            <span>
                                                <i class="bi bi-list-ol me-1"></i>
                                                {{ $project->positions_count }} {{ trans_choice('fraesdata.positions_count', $project->positions_count) }}
                                            </span>
                                            <span class="project-count-divider" aria-hidden="true"></span>
                                            <span>
                                                <i class="bi bi-box-seam me-1"></i>
                                                {{ $project->bauteile_count }} {{ trans_choice('fraesdata.bauteile_count', $project->bauteile_count) }}
                                            </span>
                                        </div>

                                        <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between gap-2">
                                            @if($project->fraesdata_max_created_at)
                                                @php($latestUpload = \Carbon\Carbon::parse($project->fraesdata_max_created_at))
                                                <span class="badge cnc-data-badge cnc-data-available" title="{{ __('fraesdata.recent_upload') }}: {{ $latestUpload->format('d.m.Y H:i') }}">
                                                    <i class="bi bi-check-circle-fill me-1"></i>{{ __('fraesdata.cnc_data_recent', ['date' => $latestUpload->format('d.m.Y')]) }}
                                                </span>
                                            @else
                                                <span class="badge cnc-data-badge cnc-data-missing">
                                                    <i class="bi bi-exclamation-circle-fill me-1"></i>{{ __('fraesdata.no_cnc_data') }}
                                                </span>
                                            @endif
                                            @if($project->fraesdata_max_created_at)
                                                <a href="{{ route('projects.cnc-data', $project) }}" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-cpu me-1"></i>{{ __('fraesdata.view_documents') }}
                                                </a>
                                            @endif
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
                background: linear-gradient(
                    135deg,
                    color-mix(in srgb, var(--status-color, #002752) 12%, #fff),
                    color-mix(in srgb, var(--status-color, #002752) 24%, #fff)
                );
                color: var(--status-color, #002752);
            }

            .status-badge i {
                font-size: 0.5rem;
                color: var(--status-color, #002752);
            }

            .project-total-count,
            .project-status-filter.active {
                background: linear-gradient(
                    135deg,
                    color-mix(in srgb, var(--status-color, #002752) 18%, #fff),
                    color-mix(in srgb, var(--status-color, #002752) 30%, #fff)
                );
                border-color: color-mix(in srgb, var(--status-color, #002752) 55%, #fff);
                color: var(--status-color, #002752);
            }

            .project-status-filter {
                border: 1px solid color-mix(in srgb, var(--status-color, #002752) 25%, #fff);
                border-radius: 999px;
                padding: 0.45rem 0.9rem;
                color: var(--status-color, #002752);
                background: linear-gradient(
                    135deg,
                    color-mix(in srgb, var(--status-color, #002752) 10%, #fff),
                    color-mix(in srgb, var(--status-color, #002752) 20%, #fff)
                );
                box-shadow: 0 0.1rem 0.3rem rgba(0, 39, 82, 0.08);
                transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
            }

            .project-status-filter:hover,
            .project-status-filter:focus-visible {
                color: var(--status-color, #002752);
                filter: brightness(0.98);
                transform: translateY(-1px);
                box-shadow: 0 0.2rem 0.5rem rgba(0, 39, 82, 0.12);
            }

            .project-status-filter.active {
                box-shadow: 0 0 0 2px color-mix(in srgb, var(--status-color, #002752) 30%, #fff);
            }

            .project-status-filter .badge {
                background-color: rgba(255, 255, 255, 0.7);
                color: var(--status-color, #002752);
            }

            .project-counts {
                color: #002752;
                font-size: 0.85rem;
                font-weight: 500;
            }

            .project-count-divider {
                width: 1px;
                height: 1rem;
                background-color: rgba(0, 39, 82, 0.22);
            }

            .cnc-data-badge {
                font-size: 0.75rem;
                font-weight: 600;
                padding: 0.5em 0.7em;
                border-radius: 0.5rem;
            }

            .cnc-data-available {
                color: #075d3a;
                background-color: #e5f4ec;
            }

            .cnc-data-missing {
                color: #6b4c00;
                background-color: #fff2cc;
            }

            .project-created {
                font-size: 0.82rem;
            }

            .project-title {
                font-weight: 600;
                color: #212529;
                line-height: 1.35;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const countLabels = @json([
                    'one' => __('fraesdata.project_count_one'),
                    'many' => __('fraesdata.project_count_many'),
                ]);
                const searchInput = document.getElementById('project-search');
                const filters = Array.from(document.querySelectorAll('.project-status-filter'));
                const cards = Array.from(document.querySelectorAll('.project-card-item'));
                const noMatches = document.getElementById('no-project-matches');
                const totalCount = document.querySelector('.project-total-count');
                let selectedStatus = 'all';

                if (!searchInput || cards.length === 0) {
                    return;
                }

                const normalize = (value) => value
                    .toLocaleLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');

                function filterProjects() {
                    const query = normalize(searchInput.value.trim());
                    let visibleCount = 0;

                    cards.forEach((card) => {
                        const matchesSearch = normalize(card.dataset.projectSearch || '').includes(query);
                        const matchesStatus = selectedStatus === 'all'
                            || card.dataset.projectStatus === selectedStatus;
                        const visible = matchesSearch && matchesStatus;
                        card.classList.toggle('d-none', !visible);
                        visibleCount += visible ? 1 : 0;
                    });

                    noMatches.classList.toggle('d-none', visibleCount > 0);
                    if (totalCount) {
                        totalCount.textContent = `${visibleCount} ${visibleCount === 1 ? countLabels.one : countLabels.many}`;
                    }
                }

                searchInput.addEventListener('input', filterProjects);
                filters.forEach((filter) => {
                    filter.addEventListener('click', () => {
                        selectedStatus = filter.dataset.statusFilter;
                        filters.forEach((button) => {
                            const active = button === filter;
                            button.classList.toggle('active', active);
                            button.setAttribute('aria-pressed', String(active));
                        });
                        filterProjects();
                    });
                });
            });
        </script>
    @endpush
@endsection