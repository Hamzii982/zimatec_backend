@extends('admin.layouts.index')

@section('title', 'Fräsmaschine Logs')

@section('content')

<div class="container mt-4 zt-logs">
  <div class="card zt-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-cpu me-2"></i>Maschine Logs</h5>
        <button id="runLogBtn" data-url="{{ route('admin.parse.log') }}" class="zt-export-btn">
          <i class="bi bi-plus-circle"></i> Machine Logs Importieren
        </button>
    </div>

    <div class="card-body">

      <form method="GET" class="mb-4">
          <div class="row g-2 align-items-center">
              <div class="col-auto">
                  <select name="project_id" id="projectFilter" class="form-select form-select-sm">
                      <option value="">Alle Projekte</option>
                      @foreach($allProjects as $proj)
                          @php
                            $auftragsnummer = ($proj->auftragsnummer_zt && $proj->auftragsnummer_zf) ? $proj->auftragsnummer_zt ."/".$proj->auftragsnummer_zf :
                              ((!$proj->auftragsnummer_zt && $proj->auftragsnummer_zf) ? $proj->auftragsnummer_zf :
                              (($proj->auftragsnummer_zt && !$proj->auftragsnummer_zf) ? $proj->auftragsnummer_zt : ""));
                          @endphp
                          <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>
                              {{ $proj->project_name }} ({{ $auftragsnummer }})
                          </option>
                      @endforeach
                  </select>
              </div>

              <div class="col-auto">
                <input type="week" name="week" id="weekFilter" class="form-control form-control-sm" placeholder="Jahr-W[Woche]" value="{{ request('week') }}">
              </div>

              <div class="col-auto">
                <input type="date" name="day" id="dayFilter" class="form-control form-control-sm" value="{{ request('day') }}">
              </div>

              <div class="col-auto">
                  <button class="zt-btn zt-btn--primary">Filtern</button>
              </div>
              <div class="col-auto">
                <a href="{{ route('admin.time.logs_old') }}" class="zt-btn">Zurücksetzen</a>
              </div>
          </div>
      </form>

      @foreach ($projects as $project)
        @php
          $auftragsnummer = ($project->auftragsnummer_zt && $project->auftragsnummer_zf) ? $project->auftragsnummer_zt ."/".$project->auftragsnummer_zf :
            ((!$project->auftragsnummer_zt && $project->auftragsnummer_zf) ? $project->auftragsnummer_zf :
            (($project->auftragsnummer_zt && !$project->auftragsnummer_zf) ? $project->auftragsnummer_zt : ""));
          $company = ($project->auftragsnummer_zt && $project->auftragsnummer_zf) ? "ZT/ZF" :
            ((!$project->auftragsnummer_zt && $project->auftragsnummer_zf) ? "ZF" :
            (($project->auftragsnummer_zt && !$project->auftragsnummer_zf) ? "ZT" : ""));
        @endphp
        <div class="zt-project-card mb-2">
          <div class="zt-project-header" data-bs-toggle="collapse" data-bs-target="#project-{{ $project->id }}">
            <div class="zt-project-title">
              <strong>{{ $auftragsnummer ?? 'N/A' }}</strong> ({{ $company }}) · {{ $project->project_name ?? 'N/A' }}
            </div>
            <div class="zt-project-meta">
              <span class="zt-chip">Positionen: {{ $project->position_count ?? 'N/A' }}</span>
              <span class="zt-chip">Bauteile: {{ $project->bauteile_count ?? 'N/A' }}</span>
              <span class="zt-chip zt-chip--total">Gesamt: {{ gmdate('H:i:s', $project->gesamtzeit ?? 0) }}</span>
              <span class="zt-chip zt-chip--active">Aktiv: {{ gmdate('H:i:s', $project->aktive_zeit ?? 0) }}</span>
            </div>
          </div>

          <div id="project-{{ $project->id }}" class="collapse">
            <div class="zt-project-body">

              {{-- ===================== CASE 1: BAUTEILE EXIST ===================== --}}
              @if ($project->bauteile->count())
                <div class="zt-section-title">Bauteile</div>
                @foreach ($project->bauteile as $bauteil)
                  <div class="zt-subblock">
                    <p class="zt-subblock-header mb-2" data-bs-toggle="collapse" data-bs-target="#bauteilTable{{ $bauteil->id }}">
                      <strong>Position:</strong> {{ $bauteil->processes[0]->position->name ?? 'N/A' }} ·
                      <strong>Bauteil:</strong> {{ $bauteil->name }} ·
                      <strong>Prozesse:</strong> {{ $bauteil->processes->count() }} ·
                      <span class="zt-chip zt-chip--total">Gesamt: {{ gmdate('H:i:s', $bauteil->processes->sum('total_seconds')) }}</span>
                      <span class="zt-chip zt-chip--active">Aktiv: {{ gmdate('H:i:s', $bauteil->processes->sum('active_seconds')) }}</span>
                    </p>

                    <div class="collapse mt-2" id="bauteilTable{{ $bauteil->id }}">
                      @if ($bauteil->procedures->count())
                        <div class="zt-section-title">Prozeduren</div>
                        @foreach ($bauteil->procedures as $procedure)
                          <div class="zt-subblock">
                            <p class="zt-subblock-header mb-1" data-bs-toggle="collapse" data-bs-target="#bauProcedureTable{{ $procedure->id }}">
                              <strong>Start:</strong> {{ $procedure->start_time ?? '-' }} ·
                              <strong>Ende:</strong> {{ $procedure->end_time ?? '-' }} ·
                              <strong>Prozesse:</strong> {{ $procedure->processes->count() }} ·
                              <span class="zt-chip zt-chip--total">Gesamt: {{ gmdate('H:i:s', $procedure->processes->sum('total_seconds')) }}</span>
                              <span class="zt-chip zt-chip--active">Aktiv: {{ gmdate('H:i:s', $procedure->processes->sum('active_seconds')) }}</span>
                            </p>

                            @if ($procedure->processes->count())
                              <div class="collapse mt-2" id="procedureTable{{ $procedure->id }}">
                                @include('admin.time.partials.process-table', ['processes' => $procedure->processes])
                              </div>
                            @endif
                          </div>
                        @endforeach
                      @endif
                    </div>

                    @if ($bauteil->processes->whereNull('procedure_id')->count())
                      <div class="zt-subblock">
                        @php $directProcesses = $bauteil->processes->whereNull('procedure_id'); @endphp
                        <p class="zt-subblock-header mb-1" data-bs-toggle="collapse" data-bs-target="#bauProcessTable{{ $bauteil->id }}">
                          <span class="zt-chip zt-chip--total">Gesamt: {{ gmdate('H:i:s', $directProcesses->sum('total_seconds')) }}</span>
                          <span class="zt-chip zt-chip--active">Aktiv: {{ gmdate('H:i:s', $directProcesses->sum('active_seconds')) }}</span>
                        </p>

                        <div class="collapse mt-2" id="bauProcessTable{{ $bauteil->id }}">
                          @include('admin.time.partials.process-table', ['processes' => $directProcesses])
                        </div>
                      </div>
                    @endif
                  </div>
                @endforeach
              @endif

              {{-- ===================== CASE 2: No Bauteile but PROCEDURES EXIST ===================== --}}
              @if ($project->procedures->count())
                <div class="zt-section-title">Prozeduren</div>
                @foreach ($project->procedures as $procedure)
                  <div class="zt-subblock">
                    <p class="zt-subblock-header mb-1" data-bs-toggle="collapse" data-bs-target="#procedureTable{{ $procedure->id }}">
                      <strong>Position:</strong> {{ $procedure->processes[0]->position->name ?? 'N/A' }} ·
                      <strong>Start:</strong> {{ $procedure->start_time ?? '-' }} ·
                      <strong>Ende:</strong> {{ $procedure->end_time ?? '-' }} ·
                      <strong>Prozesse:</strong> {{ $procedure->processes->count() }} ·
                      <span class="zt-chip zt-chip--total">Gesamt: {{ gmdate('H:i:s', $procedure->processes->sum('total_seconds')) }}</span>
                      <span class="zt-chip zt-chip--active">Aktiv: {{ gmdate('H:i:s', $procedure->processes->sum('active_seconds')) }}</span>
                    </p>

                    <div class="collapse mt-2" id="procedureTable{{ $procedure->id }}">
                      @if ($procedure->processes->count())
                        @include('admin.time.partials.process-table', ['processes' => $procedure->processes])
                      @else
                        <p class="zt-empty">Keine Prozesse für dieses Prozedur.</p>
                      @endif
                    </div>
                  </div>
                @endforeach
              @endif

              {{-- ===================== CASE 3: DIRECT PROCESSES ===================== --}}
              @php $directProjectProcesses = $project->processes->whereNull('procedure_id')->whereNull('bauteil_id'); @endphp
              @if ($directProjectProcesses->count())
                <div class="zt-section-title">Direkte Prozesse</div>
                <div class="zt-subblock">
                  <p class="zt-subblock-header mb-1" data-bs-toggle="collapse" data-bs-target="#processTable{{ $project->id }}">
                    <span class="zt-chip zt-chip--total">Gesamt: {{ gmdate('H:i:s', $directProjectProcesses->sum('total_seconds')) }}</span>
                    <span class="zt-chip zt-chip--active">Aktiv: {{ gmdate('H:i:s', $directProjectProcesses->sum('active_seconds')) }}</span>
                  </p>

                  <div class="collapse mt-2" id="processTable{{ $project->id }}">
                    @include('admin.time.partials.process-table', ['processes' => $directProjectProcesses, 'showPosition' => true])
                  </div>
                </div>
              @endif

              {{-- ===================== CASE 4: NOTHING EXISTS ===================== --}}
              @if (!$project->processes->count())
                <p class="zt-empty">Keine Daten vorhanden.</p>
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

<style>
    .zt-logs {
        --brand-blue: #002752;
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        --zt-total: #667085;
        --zt-total-bg: #EEF0F2;
        --zt-active: #1E7A46;
        --zt-active-bg: #E4F5EC;
        color: var(--zt-ink);
        font-variant-numeric: tabular-nums;
    }

    .zt-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-card > .card-header { background: var(--brand-blue); color: #fff; border-bottom: none; padding: 1rem 1.25rem; }
    .zt-card > .card-body { background: var(--zt-bg); }

    .zt-export-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: transparent;
        color: #fff; font-size: .8rem; font-weight: 500; padding: .4rem .75rem;
    }
    .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

    .zt-btn {
        display: inline-flex; align-items: center; gap: .35rem; border-radius: 8px; font-size: .82rem;
        font-weight: 500; padding: .38rem .8rem; border: 1px solid var(--zt-line); background: #fff; color: var(--zt-ink);
    }
    .zt-btn--primary { background: var(--brand-blue); border-color: var(--brand-blue); color: #fff; }

    .zt-project-card { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-project-header {
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem;
        background: var(--zt-ink); color: #fff; padding: .9rem 1.1rem; cursor: pointer;
    }
    .zt-project-title { font-size: .9rem; }
    .zt-project-meta { display: flex; gap: .4rem; flex-wrap: wrap; }
    .zt-project-body { padding: 1rem 1.1rem; }

    .zt-section-title {
        font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
        color: var(--zt-muted); margin: .9rem 0 .5rem;
    }

    .zt-subblock { border: 1px solid var(--zt-line); border-radius: 8px; background: #fff; padding: .85rem 1rem; margin-bottom: .75rem; }
    .zt-subblock-header { font-size: .84rem; cursor: pointer; margin: 0; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }

    .zt-chip {
        display: inline-flex; align-items: center; font-size: .74rem; font-weight: 600;
        padding: .22rem .6rem; border-radius: 20px; background: #F2F3F5; color: var(--zt-muted);
    }
    .zt-chip--total { background: var(--zt-total-bg); color: var(--zt-total); }
    .zt-chip--active { background: var(--zt-active-bg); color: var(--zt-active); }

    .zt-empty { color: var(--zt-muted); font-size: .85rem; font-style: italic; }

    .zt-subtable { border: 1px solid var(--zt-line); border-radius: 6px; overflow: hidden; font-size: .8rem; width: 100%; }
    .zt-subtable thead th { background: #FAFBFC; font-weight: 600; color: var(--zt-muted); padding: .5rem; }
    .zt-subtable tbody td { padding: .5rem; border-top: 1px solid var(--zt-line); }
    .zt-pause-list { font-size: .74rem; color: var(--zt-muted); margin: 0; padding-left: 1rem; }
</style>

@endsection