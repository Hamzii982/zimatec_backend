@extends('admin.layouts.index')

@section('content')
<div class="container-fluid min-vh-100 bg-light">
    <div class="row">

        {{-- SIDEBAR --}}
        <div class="col-3 border-end bg-white p-3 vh-100 overflow-auto">

            <div class="mb-4">
                <h5 class="fw-bold text-primary mb-1">KI-Bedienfeld</h5>
                <small class="text-muted">Filter-Feedback-Intelligenz</small>
            </div>
        
            <form method="GET" class="d-flex flex-column gap-4">
        
                {{-- AI toggle card --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2">AI Filter</h6>
        
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="ai_only"
                                   value="1"
                                   onchange="this.form.submit()"
                                   {{ request('ai_only') ? 'checked' : '' }}>
                            <label class="form-check-label">
                                Nur KI-Lösung möglich
                            </label>
                        </div>
                    </div>
                </div>
        
                {{-- Machine filters --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Maschinen/Prozesse</h6>
        
                        @php
                            $machines = \App\Models\Feedback::select('machine')->distinct()->pluck('machine');
                        @endphp
        
                        <div class="d-flex flex-column gap-2">
                            @foreach($machines as $machine)
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="machine[]"
                                           value="{{ $machine }}"
                                           onchange="this.form.submit()"
                                           {{ collect(request('machine'))->contains($machine) ? 'checked' : '' }}>
                                    <label class="form-check-label text-truncate">
                                        {{ $machine }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
        
            </form>
        </div>

        {{-- MAIN --}}
        <div class="col-9 p-4 overflow-auto">

            {{-- COMMAND CENTER --}}
            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6>KI-Lösung</h6>
                            <canvas id="aiChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6>Machine/Prozesse Feedbacks</h6>
                            <canvas id="heatmapChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6>Prioritätsimpuls</h6>
                            <canvas id="priorityChart"></canvas>
                        </div>
                    </div>
                </div>

            </div>

            {{-- FEEDBACK + DETAIL --}}
            <div class="row">

                {{-- FEED --}}
                <div class="col-md-7">

                    @foreach($feedbacks as $fb)
                        <div class="card mb-3 shadow-sm border-start border-4
                            @if($fb->priority == 'hoch') border-danger
                            @elseif($fb->priority == 'mittel') border-warning
                            @else border-success @endif"
                            onclick="openDetail(
                                {{ $fb->id }},
                                '{{ addslashes($fb->machine) }}',
                                '{{ addslashes($fb->type) }}',
                                '{{ addslashes($fb->priority) }}',
                                `{{ addslashes($fb->description) }}`,
                                '{{ $fb->attachment ? asset('storage/' .$fb->attachment) : '' }}'
                            )"
                            style="cursor:pointer;">

                            <div class="card-body">

                                <div class="d-flex justify-content-between">
                                    <span class="badge
                                        @if($fb->type == 'fehler') bg-danger
                                        @elseif($fb->type == 'vorschlag') bg-primary
                                        @else bg-secondary @endif">
                                        {{ $fb->type }}
                                    </span>

                                    @if($fb->attachment)
                                        <span>📎</span>
                                    @endif
                                </div>

                                <h6 class="mt-2">{{ $fb->machine }}</h6>

                                <p class="text-muted small mb-0">
                                    {{ Str::limit(strip_tags($fb->description), 120) }}
                                </p>

                            </div>
                        </div>
                    @endforeach

                </div>

                {{-- DETAIL PANE --}}
                <div class="col-md-5">

                    <div id="detailPane" class="card shadow-sm sticky-top" style="top:20px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Feedback-Details</h5>
                                <button class="btn btn-sm btn-outline-secondary" onclick="closeDetail()">✕</button>
                            </div>
                    
                            <div id="detailContent" class="d-flex flex-column gap-3 text-sm">
                                <div class="text-muted">Eine Feedback auswählen.</div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    new Chart(document.getElementById('aiChart'), {
        type: 'doughnut',
        data: {
            labels: ['Ja', 'Nein', 'Naja', 'N/A'],
            datasets: [{
                data: [{{ $aiStats['ready'] }}, {{ $aiStats['partial'] }}, {{ $aiStats['not_ready'] }}, {{ $aiStats['unknown']  }}],
            }]
        }
    });

    new Chart(document.getElementById('heatmapChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($heatmap->pluck('machine')) !!},
            datasets: [{
                label: 'Feedback',
                data: {!! json_encode($heatmap->pluck('count')) !!},
            }]
        }
    });

    new Chart(document.getElementById('priorityChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($priority->keys()) !!},
            datasets: [{
                label: 'Anzahl',
                data: {!! json_encode($priority->values()) !!},
            }]
        }
    });

});

function openDetail(id, machine, type, priority, description, attachment) {

    const pane = document.getElementById('detailPane');
    const content = document.getElementById('detailContent');

    let badgeClass = 'bg-secondary';
    if (type === 'fehler') badgeClass = 'bg-danger';
    if (type === 'vorschlag') badgeClass = 'bg-primary';

    let priorityColor = 'text-success';
    if (priority === 'hoch') priorityColor = 'text-danger';
    if (priority === 'mittel') priorityColor = 'text-warning';

    content.innerHTML = `
        <span class="badge ${badgeClass} align-self-start">${type}</span>

        <h6 class="mt-2">Maschine: ${machine}</h6>

        <div class="${priorityColor} fw-semibold">
            Priority: ${priority}
        </div>

        <div class="border rounded p-2 bg-light">
            ${description}
        </div>

        ${attachment ? `
            <div>
                <h6 class="mb-1">Attachment</h6>
                <a href="${attachment}" target="_blank">
                    📎 View file
                </a>
            </div>
        ` : ''}
    `;

    pane.classList.remove('d-none');
}

function closeDetail() {
    document.getElementById('detailPane').classList.add('d-none');
}
</script>