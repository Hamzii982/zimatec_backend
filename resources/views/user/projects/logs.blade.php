@extends('user.layouts.index')

@section('title', 'Fräsmaschine Logs')

@section('content')

<div class="container mt-4">
  <div class="card">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Maschine Logs</h5>
        <button id="runLogBtn" data-url="{{ route('parse.log') }}" class="btn btn-secondary btn-sm">+ Machine Logs Importieren</button>
    </div>

    <div class="card-body">

      <form method="GET" class="mb-4">
          <div class="row g-2 align-items-center">
              <div class="col-auto">
                  <select name="project_id" id="projectFilter" class="form-select">
                      <option value="">Alle Aufträge</option>
                      @foreach($allProjects as $proj)
                          <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>
                              {{ $proj->project_name }} ({{ $proj->auftragsnummer_zt }})
                          </option>
                      @endforeach
                  </select>
              </div>

              <div class="col-auto">
                <input type="week" name="week" id="weekFilter" class="form-control" placeholder="Jahr-W[Woche]" value="{{ request('week') }}">
              </div>

              <div class="col-auto">
                <input type="date" name="day" id="dayFilter" class="form-control" value="{{ request('day') }}">
              </div>

              <div class="col-auto">
                  <button class="btn btn-filter btn-top-search">Filtern</button>
              </div>
              <div class="col-auto">
                <a href="{{ route('projects.logs') }}" class="btn btn-secondary">Zurücksetzen</a>
              </div>
          </div>
      </form>

      @foreach ($projects as $project)
        <div class="card mb-2 shadow-sm">
          <div class="card-header bg-dark text-white p-4" data-bs-toggle="collapse" data-bs-target="#project-{{ $project->id }}" style="cursor: pointer;">
            <strong>Auftragsnummer (ZimaTech):</strong> {{ $project->auftragsnummer_zt ?? 'N/A' }} |
            <strong>Auftrag:</strong> {{ $project->project_name ?? 'N/A' }} |
            <strong>Anzahl der bauteilen:</strong> {{ $project->bauteile_count ?? 'N/A' }} |
            <strong>Gesamtzeit:</strong> {{ gmdate('H:i:s', $project->gesamtzeit) ?? '00:00' }}
          </div>

          <div id="project-{{ $project->id }}" class="collapse">
            <div class="card-body">

              {{-- ===================== CASE 1: BAUTEILE EXIST ===================== --}}
              @if ($project->bauteile->count())
                <h5 class="text-secondary mb-2">Bauteile</h5>
                @foreach ($project->bauteile as $bauteil)
                  <div class="border rounded p-3 mb-3 bg-light">
                    <p class="mb-2" data-bs-toggle="collapse" data-bs-target="#bauteilTable{{ $bauteil->id }}" aria-expanded="false" aria-controls="bauteilTable{{ $bauteil->id }}" style="cursor: pointer;">
                      <strong>Bauteil:</strong> {{ $bauteil->name }} |
                      <strong>Anzahl Prozesse:</strong> {{ $bauteil->processes->count() }} |
                      <strong>Gesamtzeit:</strong> {{ gmdate('H:i:s', $bauteil->processes->sum('total_seconds')) ?? '00:00' }}
                    </p>

                    <div class="collapse mt-2" id="bauteilTable{{ $bauteil->id }}">
                      @if ($bauteil->procedures->count())
                        <h5 class="text-secondary mb-2">Prozeduren</h5>
                        @foreach ($bauteil->procedures as $procedure)
                          <div class="border rounded p-3 mb-3 bg-light">
                            <p class="mb-1" data-bs-toggle="collapse" data-bs-target="#bauProcedureTable{{ $procedure->id }}" aria-expanded="false" aria-controls="bauProcedureTable{{ $procedure->id }}" style="cursor: pointer;">
                              <strong>Start:</strong> {{ $procedure->start_time ?? '-' }} |
                              <strong>Ende:</strong> {{ $procedure->end_time ?? '-' }} |
                              <strong>Anzahl Prozesse:</strong> {{ $procedure->processes->count() }} |
                              <strong>Gesamtzeit:</strong> {{ gmdate('H:i:s', strtotime($procedure->end_time) - strtotime($procedure->start_time)) }} |
                              <strong>Gesamtzeit der prozesse:</strong> {{ gmdate('H:i:s', $procedure->processes->sum('total_seconds')) ?? '00:00' }}
                            </p>

                            @if ($procedure->processes->count())
                              <div class="collapse mt-2" id="procedureTable{{ $procedure->id }}">
                                <table class="table table-sm table-bordered">
                                  <thead class="table-secondary">
                                    <tr>
                                      <th>Prozess Name</th>
                                      <th>Start zeit</th>
                                      <th>End zeit</th>
                                      <th>Zeit</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    @foreach ($procedure->processes as $process)
                                      <tr>
                                        <td>{{ $process->name }}</td>
                                        <td>{{ $process->start_time }}</td>
                                        <td>{{ $process->end_time }}</td>
                                        <td>{{ gmdate('H:i:s', $process->total_seconds) }}</td>
                                      </tr>
                                    @endforeach
                                  </tbody>
                                </table>
                              </div>
                            @endif
                          </div>
                        @endforeach
                      @endif
                    </div>

                    @if ($bauteil->processes->whereNull('procedure_id')->count())
                      <div class="border rounded p-3 mb-3 bg-light">
                        <p class="mb-1" data-bs-toggle="collapse" data-bs-target="#bauProcessTable{{ $bauteil->id }}" aria-expanded="false" aria-controls="bauProcessTable{{ $bauteil->id }}" style="cursor: pointer;">
                          <strong>Gesamtzeit der prozesse:</strong> {{ gmdate('H:i:s', $bauteil->processes->whereNull('procedure_id')->sum('total_seconds')) ?? '00:00' }}
                        </p>

                        <div class="collapse mt-2" id="bauProcessTable{{ $bauteil->id }}">
                          <table class="table table-sm table-bordered">
                            <thead class="table-info">
                              <tr>
                                <th>Prozess Name</th>
                                <th>Start zeit</th>
                                <th>End zeit</th>
                                <th>Zeit</th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach ($bauteil->processes->whereNull('procedure_id') as $process)
                                <tr>
                                  <td>{{ $process->name }}</td>
                                  <td>{{ $process->start_time }}</td>
                                  <td>{{ $process->end_time }}</td>
                                  <td>{{ gmdate('H:i:s', $process->total_seconds) }}</td>
                                </tr>
                              @endforeach
                            </tbody>
                          </table>
                        </div>
                      </div>
                    @endif
                  </div>
                @endforeach
              @endif

              {{-- ===================== CASE 2: No Bauteile but PROCEDURES EXIST ===================== --}}
              @if ($project->procedures->count())
                <h5 class="text-secondary mb-2">Prozeduren</h5>
                @foreach ($project->procedures as $procedure)
                  <div class="border rounded p-3 mb-3 bg-light">
                    <p class="mb-1" data-bs-toggle="collapse" data-bs-target="#procedureTable{{ $procedure->id }}" aria-expanded="false" aria-controls="procedureTable{{ $procedure->id }}" style="cursor: pointer;">
                      <strong>Start:</strong> {{ $procedure->start_time ?? '-' }} |
                      <strong>Ende:</strong> {{ $procedure->end_time ?? '-' }} |
                      <strong>Anzahl Prozesse:</strong> {{ $procedure->processes->count() }} |
                      <strong>Gesamtzeit:</strong> {{ gmdate('H:i:s', strtotime($procedure->end_time) - strtotime($procedure->start_time)) }} |
                      <strong>Gesamtzeit der prozesse:</strong> {{ gmdate('H:i:s', $procedure->processes->sum('total_seconds')) ?? '00:00' }}
                    </p>

                    <div class="collapse mt-2" id="procedureTable{{ $procedure->id }}">   
                      @if ($procedure->processes->count())
                        <table class="table table-sm table-bordered">
                          <thead class="table-secondary">
                            <tr>
                              <th>Prozess Name</th>
                              <th>Start zeit</th>
                              <th>End zeit</th>
                              <th>Zeit</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach ($procedure->processes as $process)
                              <tr>
                                <td>{{ $process->name }}</td>
                                <td>{{ $process->start_time }}</td>
                                <td>{{ $process->end_time }}</td>
                                <td>{{ gmdate('H:i:s', $process->total_seconds) }}</td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      @else
                        <p class="text-muted fst-italic">Keine Prozesse für dieses Prozedur.</p>
                      @endif
                    </div>
                  </div>
                @endforeach
              @endif

              {{-- ===================== CASE 3: NO PROCEDURES/BAUTEILE BUT DIRECT PROCESSES EXIST ===================== --}}
              @if ($project->processes->whereNull('procedure_id')->whereNull('bauteil_id')->count())
                <h5 class="text-secondary mb-2">Direkte Prozesse</h5>
                <div class="border rounded p-3 mb-3 bg-light">
                  <p class="mb-1" data-bs-toggle="collapse" data-bs-target="#processTable{{ $project->id }}" aria-expanded="false" aria-controls="processTable{{ $project->id }}" style="cursor: pointer;">
                    <strong>Gesamtzeit der prozesse:</strong> {{ gmdate('H:i:s', $project->processes->whereNull('procedure_id')->whereNull('bauteil_id')->sum('total_seconds')) ?? '00:00' }}
                  </p>

                  <div class="collapse mt-2" id="processTable{{ $project->id }}">
                    <table class="table table-sm table-bordered">
                      <thead class="table-warning">
                        <tr>
                          <th>Prozess Name</th>
                          <th>Start Zeit</th>
                          <th>End Zeit</th>
                          <th>Zeit</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($project->processes->whereNull('procedure_id')->whereNull('bauteil_id') as $process)
                          <tr>
                            <td>{{ $process->name }}</td>
                            <td>{{ $process->start_time }}</td>
                            <td>{{ $process->end_time }}</td>
                            <td>{{ gmdate('H:i:s', $process->total_seconds) }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif

              {{-- ===================== CASE 4: NOTHING EXISTS ===================== --}}
              @if (!$project->processes->count())
                <p class="text-muted fst-italic">Keine Daten vorhanden.</p>
              @endif

            </div>
          </div>
        </div>
      @endforeach
    </div>

    @if ($projects->hasPages())
      <div class="card-footer">
        <div class="d-flex justify-content-center mt-3">
          {{ $projects->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
      </div>
    @endif
  </div>
</div>

@endsection