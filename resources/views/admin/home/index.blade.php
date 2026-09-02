@extends('admin.layouts.index')

@section('title', 'Admin Dashboard')

@php
    $currentDate = \Carbon\Carbon::now()->locale('de')->translatedFormat('l, d M Y');
@endphp

@section('content')
<style>
    :root {
        --brand-blue: #002752;
        --brand-blue-hover: #001a3d;
        --brand-blue-light: #e8edf3;
    }

    /* --- Entrance animation --- */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .fade-in-up {
        opacity: 0;
        animation: fadeInUp .5s ease forwards;
    }

    .delay-1 { animation-delay: .05s; }
    .delay-2 { animation-delay: .12s; }
    .delay-3 { animation-delay: .19s; }
    .delay-4 { animation-delay: .26s; }
    .delay-5 { animation-delay: .33s; }
    .delay-6 { animation-delay: .40s; }

    /* --- Stat cards --- */
    .stat-card {
        border: 0;
        border-radius: .9rem;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .75rem 1.5rem rgba(0, 39, 82, .12) !important;
    }

    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .stat-icon.bg-blue-soft   { background: var(--brand-blue-light); color: var(--brand-blue); }
    .stat-icon.bg-success-soft{ background: #e6f7ee; color: #1e7e4d; }
    .stat-icon.bg-warning-soft{ background: #fff6e0; color: #a17400; }
    .stat-icon.bg-info-soft   { background: #e5f6fb; color: #0d7490; }
    .stat-icon.bg-danger-soft { background: #fdeaea; color: #b3261e; }

    .stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1c1c1c;
    }

    .stat-label {
        font-size: .82rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    /* --- Low stock list --- */
    .low-stock-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .6rem .25rem;
        border-bottom: 1px solid #f1f3f5;
    }

    .low-stock-item:last-child { border-bottom: none; }

    .low-stock-pill {
        font-size: .72rem;
        font-weight: 700;
        padding: .25rem .6rem;
        border-radius: 20px;
        background: #fdeaea;
        color: #b3261e;
    }

    .low-stock-pill.critical {
        background: #b3261e;
        color: #fff;
    }

    .card-header-clean {
        background: #fff;
        border-bottom: 1px solid #f1f3f5;
    }

    /* --- Chart card headers --- */
    .chart-card .card-header {
        background: linear-gradient(135deg, var(--brand-blue), var(--brand-blue-hover));
        color: #fff;
    }

    /* --- Heatmap --- */
    .heatmap-cell {
        width: 24px;
        height: 24px;
        border-radius: 3px;
        padding: 0;
    }
</style>

<div class="container-fluid py-4">

    {{-- Dashboard Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
        <div>
            <h4 class="fw-semibold mb-0">{{ $greeting }}, {{ Auth::user()->name }}</h4>
            <span class="text-muted small">Hier ist dein Überblick für heute</span>
        </div>
        <span class="text-muted">{{ $currentDate }}</span>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card shadow-sm fade-in-up delay-1">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-blue-soft"><i class="bi bi-kanban-fill"></i></div>
                    <div>
                        <div class="stat-label">Projekte</div>
                        <a class="text-decoration-none" href="{{ route('admin.projects') }}">
                            <div class="stat-value counter" data-target="{{ $projectsCount ?? 0 }}">0</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card stat-card shadow-sm fade-in-up delay-2">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-soft"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-label">Benutzer</div>
                        <a class="text-decoration-none" href="{{ route('admin.users') }}">
                            <div class="stat-value counter" data-target="{{ $usersCount ?? 0 }}">0</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card stat-card shadow-sm fade-in-up delay-3">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-soft"><i class="bi bi-list-task"></i></div>
                    <div>
                        <div class="stat-label">Prozesse</div>
                        <a class="text-decoration-none" href="{{ route('admin.time.logs_old') }}">
                            <div class="stat-value counter" data-target="{{ $processesCount ?? 0 }}">0</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card stat-card shadow-sm fade-in-up delay-4">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info-soft"><i class="bi bi-boxes"></i></div>
                    <div>
                        <div class="stat-label">Materialien</div>
                        <div class="stat-value counter" data-target="{{ $materialsCount ?? 0 }}">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card stat-card shadow-sm fade-in-up delay-5">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-blue-soft"><i class="bi bi-diagram-3-fill"></i></div>
                    <div>
                        <div class="stat-label">Lager</div>
                        <div class="stat-value counter" data-target="{{ $lagersCount ?? 0 }}">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="card stat-card shadow-sm fade-in-up delay-6">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-danger-soft"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div>
                        <div class="stat-label">Niedriger Bestand</div>
                        <div class="stat-value counter" data-target="{{ $lowStockCount ?? 0 }}">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Activity Summary Cards (Last 10 Days) + Low Stock --}}
    <div class="row g-4 mb-4">
        {{-- Most Active Machine --}}
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100 fade-in-up delay-1">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-semibold mb-2">
                                <i class="bi bi-speedometer2 text-info me-1"></i> Aktivste Maschine
                            </h6>
                            @if($mostActiveMachine)
                                <h5 class="fw-bold mb-1">{{ $mostActiveMachine->machine->name ?? 'N/A' }}</h5>
                                <span class="badge bg-light text-dark border">
                                    {{ number_format($mostActiveMachine->hours, 2) }} Std. (10 Tage)
                                </span>
                            @else
                                <p class="text-muted mb-0 small">Keine Aktivität</p>
                            @endif
                        </div>
                        <a href="{{ route('admin.activity-timeline') }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Most Active User --}}
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100 fade-in-up delay-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-semibold mb-2">
                                <i class="bi bi-person-check text-success me-1"></i> Aktivster Nutzer
                            </h6>
                            @if($mostActiveUser)
                                <h5 class="fw-bold mb-1">{{ $mostActiveUser->user->name ?? 'N/A' }}</h5>
                                <span class="badge bg-light text-dark border">
                                    {{ number_format($mostActiveUser->hours, 2) }} Std. (10 Tage)
                                </span>
                            @else
                                <p class="text-muted mb-0 small">Keine Aktivität</p>
                            @endif
                        </div>
                        <a href="{{ route('admin.activity-timeline') }}" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Low Stock Materials --}}
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100 fade-in-up delay-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-2">
                        <i class="bi bi-exclamation-triangle text-danger me-1"></i> Niedriger Materialbestand
                    </h6>

                    @if(($lowStockMaterials ?? collect())->isNotEmpty())
                        @foreach($lowStockMaterials as $material)
                            <div class="low-stock-item">
                                <span class="small">{{ Str::limit($material->name, 22) }}</span>
                                <span class="low-stock-pill {{ $material->quantity <= 0 ? 'critical' : '' }}">
                                    {{ $material->quantity }} / {{ $material->threshold ?? 0 }} {{ $material->unit }}
                                </span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0 small">Alle Bestände im grünen Bereich ✅</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Machine Utilization Heatmap --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3 fade-in-up delay-4">
                <div class="card-header card-header-clean">
                    <h5 class="mb-0"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Maschinenauslastung (letzte 10 Tage)</h5>
                </div>
                <div class="card-body table-responsive-wrapper">
                    @if(empty($utilizationHeatmap['machines']))
                        <p class="text-muted mb-0 small">Keine Aktivität im Zeitraum</p>
                    @else
                        <table class="table table-sm mb-0 heatmap-table">
                            <thead>
                                <tr>
                                    <th>Maschine</th>
                                    @for($h = 0; $h < 24; $h++)
                                        <th class="text-center small text-muted">{{ $h }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($utilizationHeatmap['machines'] as $i => $name)
                                    @php $rowMax = max($utilizationHeatmap['data'][$i]) ?: 1; @endphp
                                    <tr>
                                        <td class="fw-semibold small">{{ Str::limit($name, 18) }}</td>
                                        @foreach($utilizationHeatmap['data'][$i] as $hours)
                                            @php $intensity = $hours > 0 ? max(0.15, min(1, $hours / $rowMax)) : 0; @endphp
                                            <td class="heatmap-cell" style="background: rgba(0,39,82,{{ $intensity * 0.85 }});"
                                                title="{{ number_format($hours, 2) }} Std.">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Time Change Requests + Upcoming Deadlines --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card stat-card shadow-sm h-100 fade-in-up delay-5">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-clock-history text-warning me-1"></i> Offene Zeitänderungen</h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark">{{ $pendingTimeChangeRequestsCount }}</span>
                            <a href="{{ route('admin.time.change') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    @forelse($pendingTimeChangeRequests as $req)
                        <div class="low-stock-item">
                            <span class="small">{{ $req->requestedBy->name ?? 'Unbekannt' }}</span>
                            <span class="text-muted small">{{ $req->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0 small">Keine offenen Anfragen ✅</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card stat-card shadow-sm h-100 fade-in-up delay-6">
                <div class="card-body">
                    <h6 class="fw-semibold mb-2"><i class="bi bi-calendar-event text-info me-1"></i> Anstehende Fristen (14 Tage)</h6>
                    @forelse($upcomingDeadlines as $project)
                        <div class="low-stock-item">
                            <span class="small">{{ Str::limit($project->project_name, 22) }}</span>
                            <span class="badge bg-light text-dark border">{{ $project->end_time->format('d M') }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0 small">Keine Fristen in den nächsten 14 Tagen</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Project Status Chart + Overdue/At-Risk Projects --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card chart-card shadow-sm border-0 h-100 fade-in-up delay-1">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-pie-chart-fill me-2"></i>Projektstatus</h5></div>
                <div class="card-body"><canvas id="statusChart" height="220"></canvas></div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3 h-100 fade-in-up delay-2">
                <div class="card-header card-header-clean"><h5 class="mb-0">Überfällige & gefährdete Projekte</h5></div>
                <div class="card-body table-responsive-wrapper">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Projekt</th><th>Ende</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($overdueAndAtRiskProjects as $project)
                                <tr>
                                    <td>{{ Str::limit($project->project_name, 28) }}</td>
                                    <td>{{ $project->end_time->format('d M Y') }}</td>
                                    <td>
                                        @if($project->end_time->isPast())
                                            <span class="badge bg-danger">Überfällig · {{ $project->end_time->diffForHumans(null, true) }}</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Fällig in {{ now()->diffInDays($project->end_time) }} Tg.</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted small">Keine überfälligen oder gefährdeten Projekte ✅</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card chart-card shadow-sm border-0 fade-in-up delay-1">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Projektübersicht</h5>
                </div>
                <div class="card-body">
                    <canvas id="projectsChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card chart-card shadow-sm border-0 fade-in-up delay-2">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Benutzerregistrierungen</h5>
                </div>
                <div class="card-body">
                    <canvas id="usersChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Projects Table --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3 fade-in-up delay-3">
                <div class="card-header card-header-clean">
                    <h5 class="mb-0">Aktuelle Projekte</h5>
                </div>
                <div class="card-body table-responsive-wrapper">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Projekt Name</th>
                                <th>Start</th>
                                <th>Ende</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentProjects as $project)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ Str::limit($project->project_name, 30) }}</td>
                                <td>{{ $project->start_time?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $project->end_time?->format('d M Y') ?? '-' }}</td>
                                <td>
                                    @if($project->status == 'completed')
                                        <span class="badge bg-success">Vollendet</span>
                                    @elseif($project->status == 'in_progress')
                                        <span class="badge bg-warning">läuft derzeit</span>
                                    @else
                                        <span class="badge bg-secondary">Ausstehend</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- Animated number counters ---
    document.querySelectorAll('.counter').forEach((el) => {
        const target = parseInt(el.dataset.target, 10) || 0;
        const duration = 900;
        const start = performance.now();

        function step(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            el.textContent = Math.floor(eased * target).toLocaleString('de-DE');
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = target.toLocaleString('de-DE');
            }
        }
        requestAnimationFrame(step);
    });

    const brandBlue = '#002752';

    const projectCtx = document.getElementById('projectsChart').getContext('2d');
    new Chart(projectCtx, {
        type: 'bar',
        data: {
            labels: @json($projectLabels),
            datasets: [{
                label: 'Prozesse pro Projekt',
                data: @json($projectData),
                backgroundColor: 'rgba(0, 39, 82, 0.65)',
                borderColor: brandBlue,
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1100, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    const userCtx = document.getElementById('usersChart').getContext('2d');
    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: @json($userLabels),
            datasets: [{
                label: 'Benutzerregistrierungen',
                data: @json($userData),
                fill: true,
                backgroundColor: 'rgba(0, 39, 82, 0.12)',
                borderColor: brandBlue,
                pointBackgroundColor: brandBlue,
                pointRadius: 4,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1100, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: @json($projectStatusDistribution->pluck('name')),
            datasets: [{
                data: @json($projectStatusDistribution->pluck('projects_count')),
                backgroundColor: @json($projectStatusDistribution->pluck('color')),
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>
@endsection