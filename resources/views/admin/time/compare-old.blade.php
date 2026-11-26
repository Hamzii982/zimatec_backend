@extends('admin.layouts.index')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Maschine & Mitarbeiter Vergleich</h5>
        </div>

        <div class="card-body">

            {{-- Filters --}}
            <form method="GET" class="row mb-4">
                <div class="col-md-4">
                    <select class="form-select" id="project_id" name="project_id">
                        <option value="">Auftrag auswählen</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" 
                                {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->project_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="date" class="form-control" id="date" name="date" value="{{ request('date') }}">
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-wechsel"><i class="bi bi-search"></i> Vergleichen</button>
                </div>
            </form>

            {{-- Comparison Table --}}
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Bediener</th>
                            <th>Aufträge</th>
                            <th>Start Zeit</th>
                            <th>End Zeit</th>
                            <th>Rustzeit</th>
                            <th>Mit Aufsicht</th>
                            <th>Ohne Aufsicht</th>
                            <th>Nacht Zeit</th>
                            <th>Gesamt Zeit</th>
                            <th>Machine Zeit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comparison as $index => $item)
                            <tr>
                                <td>{{ $item['record']->user->name }}</td>
                                <td>{{ $item['record']->project->project_name }}</td>
                                <td>{{ $item['record']->start_time }}</td>
                                <td>{{ $item['record']->end_time ?? 'Ongoing' }}</td>
                                <td>{{ $item['rustzeit'] }}</td>
                                <td>{{ $item['mit_aufsicht'] }}</td>
                                <td>{{ $item['ohne_aufsicht'] }}</td>
                                <td>{{ $item['nacht_zeit'] }}</td>
                                <td>{{ gmdate('H:i:s', \Carbon\Carbon::parse($item['record']->start_time)->diffInSeconds($item['record']->end_time)) }}</td>
                                {{-- Clickable Machine Time --}}
                                <td>
                                    <a class="btn btn-outline-dark btn-sm" 
                                        data-bs-toggle="collapse" 
                                        href="#machineDetails{{ $index }}" 
                                        role="button" 
                                        aria-expanded="false" 
                                        aria-controls="machineDetails{{ $index }}">
                                        {{ $item['machine_time'] }}
                                    </a>
                                </td>
                            </tr>
                            {{-- Collapsible Row --}}
                            <tr class="collapse bg-light" id="machineDetails{{ $index }}">
                                <td colspan="10">
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-2 text-dark">
                                            Machine Prozesse ({{ count($item['processes']) }})
                                        </h6>
                                        @if(count($item['processes']) > 0)
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-secondary">
                                                    <tr class="text-center">
                                                        <th>Prozesse</th>
                                                        <th>Prozedur</th>
                                                        <th>Bauteile</th>
                                                        <th>Start Zeit</th>
                                                        <th>Ende Zeit</th>
                                                        <th>Gesamt Zeit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($item['processes'] as $proc)
                                                        <tr class="text-center">
                                                            <td>{{ $proc['process_name'] ?? '-' }}</td>
                                                            <td>{{ $proc['procedure_name'] ?? '-' }}</td>
                                                            <td>{{ $proc['bauteil_name'] ?? '-' }}</td>
                                                            <td>{{ $proc['start_time'] }}</td>
                                                            <td>{{ $proc['end_time'] }}</td>
                                                            <td>{{ gmdate('H:i:s', \Carbon\Carbon::parse($proc['start_time'])->diffInSeconds($proc['end_time'])) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <p class="text-muted mb-0">Keine Prozesse Gefunden.</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">Keine Records Vorhanden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
