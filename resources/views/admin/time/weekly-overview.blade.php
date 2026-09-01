@extends('admin.layouts.index')

@section('content')
@php
    // Guarded with function_exists() — a bare `function` declaration in a Blade
    // file will fatal with "Cannot redeclare function" the moment this view
    // renders twice in one PHP process (e.g. under Octane, queue workers, or
    // simply loading the page twice in one test run). Logic is unchanged.
    if (! function_exists('secondsToIndustryMinutes')) {
        function secondsToIndustryMinutes($seconds) {
          // Real time
          $totalMinutes = $seconds / 60;
          $hours = floor($totalMinutes / 60);
          $minutes = round($totalMinutes % 60);

          $realTime = sprintf("%02d:%02d", $hours, $minutes);

          // Industrial time: 3 real minutes = 5 industrial minutes
          $industryTotalMinutes = ($totalMinutes / 3) * 5;
          $industryHours = floor($industryTotalMinutes / 60);
          $industryMinutes = round($industryTotalMinutes % 60);

          $industryTime = sprintf("%02d:%02d", $industryHours, $industryMinutes);

            return "{$realTime} ({$industryTime})";
        }
    }
@endphp
<div class="zt-compare container">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Wochenübersicht Maschinenlaufstunden</h5>
        </div>

        <div class="card-body">

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

            @forelse($machineTables as $table)
                @php
                    $tableId = 'machine-table-'.$loop->index;
                    $weekLabel = collect($weeks)->firstWhere('value', $selectedWeek)['label'] ?? $selectedWeek;
                    $machineName = $table['machine']->name ?? 'Maschine';
                @endphp
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="zt-machine-title">
                        Maschinenlaufstunden für CNC-Fräse "{{ $machineName }}"
                        <small class="text-muted fw-normal">— {{ $weekLabel }}</small>
                    </h6>

                    <button
                        type="button"
                        class="zt-export-btn"
                        onclick="exportMachineTable('{{ $tableId }}', '{{ $machineName }}', '{{ $weekLabel }}')">
                        <i class="bi bi-file-earmark-excel"></i> Exportieren
                    </button>
                </div>

                <div class="table-responsive mb-5">
                    <table class="table zt-table zt-table--excel align-middle mb-0" id="{{ $tableId }}">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Projekt</th>
                                <th>Auftrags-Nr. ZF</th>
                                <th>Auftrags-Nr. ZIMATEC</th>
                                <th>Pos.</th>
                                <th>Rüstzeit</th>
                                <th>mit Aufsicht</th>
                                <th>ohne Aufsicht</th>
                                <th>Bediener</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($table['rows'] as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->date)->format('d.m.Y') }}</td>
                                    <td>{{ $row->project->project_name ?? '—' }}</td>
                                    <td>{{ $row->project->auftragsnummer_zf ?? '—' }}</td>
                                    <td>{{ $row->project->auftragsnummer_zt ?? '—' }}</td>
                                    <td>{{ $row->position->name ?? '—' }}</td>
                                    <td>{{ secondsToIndustryMinutes($row->ruestzeit_seconds) }}</td>
                                    <td>{{ secondsToIndustryMinutes($row->mit_aufsicht_seconds) }}</td>
                                    <td>{{ secondsToIndustryMinutes($row->ohne_aufsicht_seconds) }}</td>
                                    <td>
                                        @if($row->user_name)
                                            {{ $row->user_name }}
                                            @if($row->is_fallback_attribution)
                                                <i class="bi bi-info-circle text-muted"
                                                    title="Kein Bediener an diesem Tag protokolliert — automatisch dem letzten Bediener dieses Auftrags zugeordnet."></i>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="zt-totals-row">
                                <td colspan="5">Gesamt</td>
                                <td>{{ secondsToIndustryMinutes($table['totals']->ruestzeit_seconds) }}</td>
                                <td>{{ secondsToIndustryMinutes($table['totals']->mit_aufsicht_seconds) }}</td>
                                <td>{{ secondsToIndustryMinutes($table['totals']->ohne_aufsicht_seconds) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @empty
                <p class="zt-empty">Keine Maschinenlaufstunden für diese Woche.</p>
            @endforelse

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

    .zt-machine-title { font-size: .95rem; font-weight: 600; margin-bottom: .75rem; }

    .zt-export-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid var(--zt-line); border-radius: 8px; background: #fff;
        color: var(--zt-ink); font-size: .8rem; font-weight: 500;
        padding: .4rem .75rem; margin-bottom: .75rem;
        transition: border-color .15s, background .15s;
    }
    .zt-export-btn:hover { border-color: var(--zt-ink); background: #FAFBFC; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; white-space: nowrap; }
    .zt-totals-row td { font-weight: 600; background: #FAFBFC; border-top: 2px solid var(--zt-line); }
    .zt-empty { color: var(--zt-muted); font-size: .85rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
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

    function exportMachineTable(tableId, machineName, weekLabel) {
        const table = document.getElementById(tableId);
        if (!table) return;

        // Sheet names are capped at 31 chars by the xlsx spec.
        const sheetName = machineName.substring(0, 31);
        const wb = XLSX.utils.table_to_book(table, { sheet: sheetName });

        const safeName = `Maschinenlaufstunden_${machineName}_${weekLabel}`
            .replace(/[^a-zA-Z0-9_\-]/g, '_');

        XLSX.writeFile(wb, `${safeName}.xlsx`);
    }
</script>
@endsection