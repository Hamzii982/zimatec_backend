@extends('admin.layouts.index')

@section('title', 'Admin Dashboard')

@php
    $currentDate = \Carbon\Carbon::now()->locale('de')->translatedFormat('l, d M Y');
@endphp

@section('content')
    <div class="container-fluid py-4">

        {{-- Dashboard Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-semi-bold">{{ $greeting }}, {{ Auth::user()->name }}</h4>
            <span class="text-muted">{{ $currentDate }}</span>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-4 mb-4">
            {{-- Projects --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-kanban-fill fs-2 text-primary me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Projekte</h5>
                            <h3 class="fw-bold">
                                <a class="text-decoration-none text-dark" href="{{ route('admin.projects') }}">
                                    {{ $projectsCount ?? 0 }}
                                </a>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Users --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-people-fill fs-2 text-success me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Benutzer</h5>
                            <h3 class="fw-bold">
                                <a class="text-decoration-none text-dark" href="{{ route('admin.users') }}">
                                    {{ $usersCount ?? 0 }}
                                </a>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tasks / Processes --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-list-task fs-2 text-warning me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Prozesse</h5>
                            <h3 class="fw-bold">
                                <a class="text-decoration-none text-dark" href="{{ route('admin.time.logs_old') }}">
                                    {{ $processesCount ?? 0 }}
                                </a>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Projektübersicht</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="projectsChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Benutzerregistrierungen</h5>
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
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header">
                        <h5 class="mb-0">Aktuelle Projekte</h5>
                    </div>
                    <div class="card-body table-responsive">
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
    const projectCtx = document.getElementById('projectsChart').getContext('2d');
    new Chart(projectCtx, {
        type: 'bar',
        data: {
            labels: @json($projectLabels),
            datasets: [{
                label: 'Prozesse pro Projekt',
                data: @json($projectData),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
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
                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                borderColor: 'rgba(255, 206, 86, 1)',
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>
@endsection
