@extends('admin.layouts.index')

@php
    if (! function_exists('secondsToIndustryMinutes')) {
        function secondsToIndustryMinutes($seconds) {
            $totalMinutes = $seconds / 60;
            $hours = floor($totalMinutes / 60);
            $minutes = round($totalMinutes % 60);

            $realTime = sprintf("%02d:%02d", $hours, $minutes);

            $industryTotalMinutes = ($totalMinutes / 3) * 5;
            $industryHours = floor($industryTotalMinutes / 60);
            $industryMinutes = round($industryTotalMinutes % 60);

            $industryTime = sprintf("%02d:%02d", $industryHours, $industryMinutes);

            return "{$realTime} ({$industryTime})";
        }
    }
@endphp

@section('content')
@php
    $isFilterActive = request('user_id') || request('machine_id') || request('project_id') || request('date') || request('status');
@endphp

<div class="zt-compare container">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Zeitaufzeichnungen</h5>
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs zt-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link zt-tab-btn {{ $isFilterActive ? '' : 'active' }}"
                        id="wochenuebersicht-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#wochenuebersicht"
                        type="button"
                        role="tab"
                        aria-controls="wochenuebersicht"
                        aria-selected="true">
                        Wochenübersicht
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link zt-tab-btn {{ $isFilterActive ? 'active' : '' }}"
                        id="benutzerbasierte-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#benutzerbasierte"
                        type="button"
                        role="tab"
                        aria-controls="benutzerbasierte"
                        aria-selected="false">
                        Benutzerbasierte Ansicht
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade {{ $isFilterActive ? '' : 'show active' }}" id="wochenuebersicht" role="tabpanel" aria-labelledby="wochenuebersicht-tab">

                    {{-- Week filter --}}
                    <div class="mb-4 zt-week-slider" id="weekSlider">
                        @foreach($weeks as $week)
                            <button
                                onclick="window.location.href='?week={{ $week['value'] }}'"
                                class="zt-week-btn {{ $selectedWeek == $week['value'] ? 'is-active' : '' }}"
                                data-week="{{ $week['value'] }}">
                                {{ $week['label'] }}
                            </button>
                        @endforeach

                        <button id="addWeekBtn" class="zt-week-btn zt-week-btn--ghost">+1</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table zt-table zt-table--excel align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>KW</th>
                                    <th>Maschine</th>
                                    <th>Auftragsnr.</th>
                                    <th>Position</th>
                                    <th>Rustzeit</th>
                                    <th>Mit Aufsicht</th>
                                    <th>Gesamtzeit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($weeklyRecords as $index => $row)
                                    @php
                                        $totalSeconds = $row->rustzeit_seconds + $row->mit_aufsicht_seconds;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a
                                                onclick="getDailyRecords({{ $index }}, {{ $row->calendar_week }}, {{ $row->auftragsnummer }}, {{ $row->position_id }}, {{ $row->machine_id }})"
                                                data-bs-toggle="collapse"
                                                href="#collapse{{ $index }}"
                                                aria-expanded="false"
                                                aria-controls="collapse{{ $index }}"
                                                class="zt-week-link">
                                                KW {{ substr($row->calendar_week, 4) }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $row->machine_name }}
                                            <span class="zt-badge {{ $row->company === 'ZF' ? 'zt-badge--zf' : 'zt-badge--zt' }}">
                                                {{ $row->company }}
                                            </span>
                                        </td>
                                        <td>{{ $row->auftragsnummer }}</td>
                                        <td>{{ $row->position_name }}</td>
                                        <td>{{ secondsToIndustryMinutes($row->rustzeit_seconds) }}</td>
                                        <td>{{ secondsToIndustryMinutes($row->mit_aufsicht_seconds) }}</td>
                                        <td><strong>{{ secondsToIndustryMinutes($totalSeconds) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="9" class="p-0 border-0">
                                            <div class="collapse" id="collapse{{ $index }}">
                                                <div class="zt-subcard">
                                                    <x-daily-records-table
                                                        :index="$index"
                                                        :week="$row->calendar_week"
                                                        :auftragsnummer="$row->auftragsnummer"
                                                        :positionId="$row->position_id"
                                                        :machineId="$row->machine_id"
                                                        :autoLoad="false" />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="zt-empty text-center py-4">
                                            Keine Daten für diese Kalenderwochen vorhanden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ $isFilterActive ? 'show active' : '' }}" id="benutzerbasierte" role="tabpanel" aria-labelledby="benutzerbasierte-tab">

                    {{-- Filter form --}}
                    <form method="GET" class="zt-filter-form row g-2 mb-4">
                        <div class="col-md-2">
                            <select name="user_id" class="form-select zt-select">
                                <option value="">Alle Benutzer</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="project_id" class="form-select zt-select">
                                <option value="">Alle Projekte</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="machine_id" class="form-select zt-select">
                                <option value="">Alle Maschinen</option>
                                @foreach($machines as $machine)
                                    <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>
                                        {{ $machine->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date" class="form-control zt-select" value="{{ request('date') }}">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="zt-btn zt-btn--primary">Filtern</button>
                            <a href="{{ route('admin.time.records') }}" class="zt-btn zt-btn--ghost">Zurücksetzen</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table zt-table zt-table--excel mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bediener</th>
                                    <th>Projekt</th>
                                    <th>Position</th>
                                    <th>Maschine</th>
                                    <th>Start</th>
                                    <th>Ende</th>
                                    <th>Dauer</th>
                                    <th>Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $record)
                                    <tr>
                                        <td>{{ $records->firstItem() + $index }}</td>
                                        <td>{{ $record->user->name }}</td>
                                        <td>
                                            {{ $record->project->project_name }}
                                            <small class="text-muted d-block project-auftrag">
                                                {{ ($record->project->auftragsnummer_zf ? "ZF: ".$record->project->auftragsnummer_zf : '')
                                                .($record->project->auftragsnummer_zt ? " ZT: " .$record->project->auftragsnummer_zt : '') }}
                                            </small>
                                        </td>
                                        <td>{{ $record->position->name }}</td>
                                        <td>{{ $record->machine->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($record->start_time)->format('d.m.Y H:i') }}</td>
                                        <td>
                                            @if($record->end_time)
                                                {{ \Carbon\Carbon::parse($record->end_time)->format('d.m.Y H:i') }}
                                            @else
                                                <span class="zt-badge zt-badge--running">Läuft</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->end_time)
                                                {{ \Carbon\Carbon::parse($record->start_time)->diff($record->end_time) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('admin.time.show', $record->id) }}" class="zt-icon-btn zt-icon-btn--view">
                                                    <i class="bi bi-eye"></i>
                                                </a>

                                                <a href="{{ route('admin.time.edit', $record->id) }}" class="zt-icon-btn zt-icon-btn--edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>

                                                <form action="{{ route('admin.time.delete', $record->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="zt-icon-btn zt-icon-btn--danger" onclick="return confirm('Diesen Status löschen?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="zt-empty text-center py-4">
                                            Keine Zeitaufzeichnungen gefunden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 zt-pagination">
                        {{ $records->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .zt-compare {
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        color: var(--zt-ink);
        font-variant-numeric: tabular-nums;
    }

    .zt-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-card > .card-header { background: var(--zt-ink); color: #fff; border-bottom: none; }
    .zt-card > .card-body { background: var(--zt-bg); }

    /* Tabs */
    .zt-tabs { border-bottom: none; gap: .5rem; margin-bottom: 1.25rem; }
    .zt-tab-btn.nav-link {
        border: 1px solid var(--zt-line); border-radius: 8px; background: #fff;
        color: var(--zt-muted); font-size: .82rem; font-weight: 500;
        padding: .5rem .9rem; margin-right: 0;
    }
    .zt-tab-btn.nav-link:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
    .zt-tab-btn.nav-link.active {
        background: var(--zt-ink); border-color: var(--zt-ink); color: #fff;
    }

    /* Week slider */
    .zt-week-slider { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .25rem; }
    .zt-week-btn {
        flex: none; min-width: 96px; height: 44px; padding: 0 .75rem;
        border: 1px solid var(--zt-line); border-radius: 8px; background: #fff;
        font-size: .82rem; font-weight: 500; color: var(--zt-muted);
        transition: border-color .15s, color .15s;
    }
    .zt-week-btn:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
    .zt-week-btn.is-active { background: var(--zt-ink); border-color: var(--zt-ink); color: #fff; }
    .zt-week-btn--ghost { border-style: dashed; }
    .zt-week-link { color: var(--zt-ink); font-weight: 500; text-decoration: none; }
    .zt-week-link:hover { text-decoration: underline; }

    /* Badges */
    .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 600; }
    .zt-badge--zf { background: #E7EEFF; color: #2E5AAC; }
    .zt-badge--zt { background: #E4F5EC; color: #1E7A46; }
    .zt-badge--running { background: #FFF1D6; color: #92650B; }

    /* Table */
    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; }
    .zt-empty { color: var(--zt-muted); font-size: .85rem; }

    /* Filter form */
    .zt-select, select.zt-select, input.zt-select {
        border: 1px solid var(--zt-line); border-radius: 8px; font-size: .84rem;
        background: #fff; color: var(--zt-ink);
    }
    .zt-select:focus { border-color: var(--zt-ink); box-shadow: none; }

    .zt-btn {
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; font-size: .84rem; font-weight: 500;
        padding: .4rem .9rem; border: 1px solid var(--zt-line); text-decoration: none;
    }
    .zt-btn--primary { background: var(--zt-ink); color: #fff; border-color: var(--zt-ink); }
    .zt-btn--primary:hover { background: #000; color: #fff; }
    .zt-btn--ghost { background: #fff; color: var(--zt-muted); }
    .zt-btn--ghost:hover { border-color: var(--zt-ink); color: var(--zt-ink); }

    /* Row action icon buttons */
    .zt-icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--zt-line);
        background: #fff; color: var(--zt-muted); text-decoration: none;
    }
    .zt-icon-btn--view:hover { border-color: #2E5AAC; color: #2E5AAC; }
    .zt-icon-btn--edit:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
    .zt-icon-btn--danger { color: #B3261E; }
    .zt-icon-btn--danger:hover { border-color: #B3261E; background: #FBEAE9; }

    /* Sub-card for expanded daily records */
    .zt-subcard { background: #FAFBFC; border-top: 1px solid var(--zt-line); border-bottom: 1px solid var(--zt-line); padding: 1rem; }

    /* Pagination */
    .zt-pagination .pagination { margin-bottom: 0; }
    .zt-pagination .page-link {
        border: 1px solid var(--zt-line); color: var(--zt-ink); border-radius: 6px; margin: 0 .15rem;
    }
    .zt-pagination .page-item.active .page-link { background: var(--zt-ink); border-color: var(--zt-ink); }
</style>

<script>
    window.dailyRecordsCache = window.dailyRecordsCache || {};
    window.dayDetailsCache   = window.dayDetailsCache   || {};

    document.getElementById('addWeekBtn').addEventListener('click', function () {
        const slider = document.getElementById('weekSlider');
        const buttons = slider.querySelectorAll('.zt-week-btn:not(.zt-week-btn--ghost)');
        const lastButton = buttons[buttons.length - 1];

        let lastWeekValue = lastButton.getAttribute('data-week');
        let year = parseInt(lastWeekValue.slice(0, 4));
        let week = parseInt(lastWeekValue.slice(4, 6));

        week -= 1;
        if (week < 1) {
            week = 52;
            year -= 1;
        }

        let weekStr = week.toString().padStart(2, '0');
        let newWeekValue = year.toString() + weekStr;
        let newWeekLabel = 'KW ' + weekStr + ' / ' + year;

        const newButton = document.createElement('button');
        newButton.setAttribute('onclick', `window.location.href='?week=${newWeekValue}'`);
        newButton.setAttribute('data-week', newWeekValue);
        newButton.className = 'zt-week-btn';
        newButton.innerText = newWeekLabel;

        slider.insertBefore(newButton, this);
    });
</script>
@endsection