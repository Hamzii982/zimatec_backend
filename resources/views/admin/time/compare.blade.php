@extends('admin.layouts.index')

@section('content')
@php
    // Small local helper: "01:23:45" -> 5025 seconds. Only used here for bar-chart
    // proportions — the controller already sends formatted hms strings for display.
    $hmsToSeconds = function (?string $hms): int {
        if (! $hms) return 0;
        [$h, $m, $s] = array_map('intval', explode(':', $hms));
        return $h * 3600 + $m * 60 + $s;
    };
@endphp
<div class="zt-compare container">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Zeit Vergleichen</h5>
            <div class="zt-legend">
                <span class="zt-legend-item"><i class="zt-dot" style="background:var(--zt-ruestzeit)"></i>Rüstzeit</span>
                <span class="zt-legend-item"><i class="zt-dot" style="background:var(--zt-aufsicht)"></i>Mit Aufsicht</span>
                <span class="zt-legend-item"><i class="zt-dot" style="background:var(--zt-ohne-aufsicht)"></i>Ohne Aufsicht</span>
                <span class="zt-legend-item"><i class="zt-dot" style="background:var(--zt-automatik)"></i>Automatisch</span>
            </div>
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

            {{-- Weekly aggregate: one card per Projekt / Position / Maschine --}}
            @if(count($aggregate) > 0)
                <h6 class="zt-section-title">Wochenübersicht</h6>
                <div class="zt-agg-row mb-4">
                    @foreach($aggregate as $agg)
                        @php
                            $ru = $hmsToSeconds($agg['ruestzeit']);
                            $ma = $hmsToSeconds($agg['mit_aufsicht']);
                            $oa = $hmsToSeconds($agg['ohne_aufsicht']);
                            $userTotal = max($ru + $ma + $oa, 1);
                        @endphp
                        <div class="zt-agg-card">
                            <div class="zt-agg-project">{{ $agg['project']->project_name ?? '—' }}</div>
                            <div class="zt-agg-sub">{{ $agg['project']->auftragsnummer ?? '—' }} · {{ $agg['position']->name ?? '—' }} · {{ $agg['machine']->name ?? '—' }}</div>

                            <div class="zt-bar" title="Rüstzeit {{ $agg['ruestzeit'] }} · Mit Aufsicht {{ $agg['mit_aufsicht'] }} · Ohne Aufsicht {{ $agg['ohne_aufsicht'] }}">
                                <span style="width:{{ $ru / $userTotal * 100 }}%; background:var(--zt-ruestzeit)"></span>
                                <span style="width:{{ $ma / $userTotal * 100 }}%; background:var(--zt-aufsicht)"></span>
                                <span style="width:{{ $oa / $userTotal * 100 }}%; background:var(--zt-ohne-aufsicht)"></span>
                            </div>

                            <div class="zt-agg-stats">
                                <div>
                                    <span class="zt-agg-num">{{ $agg['total_user_time'] }}</span>
                                    <span class="zt-agg-label">Bediener</span>
                                </div>
                                <div>
                                    <span class="zt-agg-num">{{ $agg['total_machine_time'] }}</span>
                                    <span class="zt-agg-label">Maschine</span>
                                </div>
                                <div>
                                    <span class="zt-agg-num">{{ $agg['session_count'] }}</span>
                                    <span class="zt-agg-label">{{ $agg['session_count'] == 1 ? 'Bediener-Sitzung' : 'Bediener-Sitzungen' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Session-level comparison --}}
            <h6 class="zt-section-title">Details je Sitzung</h6>
            <div class="table-responsive">
                <table class="table zt-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Bediener</th>
                            <th>Maschine</th>
                            <th>Projekt</th>
                            <th>Position</th>
                            <th>Bediener-Zeit</th>
                            <th>Prozesse</th>
                            <th>Maschine-Zeit</th>
                            <th class="zt-th-toggle"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comparison as $index => $item)
                            @php
                                $ru = $item['status_seconds']['ruestzeit'] ?? 0;
                                $ma = $item['status_seconds']['mit_aufsicht'] ?? 0;
                                $oa = $item['status_seconds']['ohne_aufsicht'] ?? 0;
                                $userTotal = max($ru + $ma + $oa, 1);
                                $isAutomatic = empty($item['record']->user->name);
                                $sourceCounts = collect($item['processes'])->countBy('source');
                            @endphp
                            <tr class="zt-row {{ $isAutomatic ? 'zt-row--automatic' : '' }}" data-bs-toggle="collapse" data-bs-target="#machineDetails{{ $index }}" role="button" aria-expanded="false" aria-controls="machineDetails{{ $index }}">
                                <td>
                                    @if($isAutomatic)
                                        <span class="zt-badge zt-badge--automatik"><i class="bi bi-cpu"></i> Automatisch</span>
                                    @else
                                        {{ $item['record']->user->name }}
                                    @endif
                                </td>
                                <td>{{ $item['record']->machine->name ?? '—' }}</td>
                                <td>
                                    {{ $item['record']->project->project_name ?? '—' }}
                                    <small class="text-muted d-block">{{ $item['record']->project->auftragsnummer ?? '—' }}</small>
                                </td>
                                <td>{{ $item['record']->Position->name ?? '—' }}</td>
                                <td>
                                    <span class="zt-time">{{ $item['total_user_time'] }}</span>
                                    @if(! $isAutomatic)
                                        <div class="zt-bar zt-bar--sm" title="Rüstzeit {{ gmdate('H:i:s', $ru) }} · Mit Aufsicht {{ gmdate('H:i:s', $ma) }} · Ohne Aufsicht {{ gmdate('H:i:s', $oa) }}">
                                            <span style="width:{{ $ru / $userTotal * 100 }}%; background:var(--zt-ruestzeit)"></span>
                                            <span style="width:{{ $ma / $userTotal * 100 }}%; background:var(--zt-aufsicht)"></span>
                                            <span style="width:{{ $oa / $userTotal * 100 }}%; background:var(--zt-ohne-aufsicht)"></span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ $item['process_count'] }}
                                    @if($item['process_count'] > 0)
                                        <small class="text-muted d-block">
                                            {{ collect($sourceCounts)->sum() }} · {{ $sourceCounts->get('manuell', 0) }}m / {{ $sourceCounts->get('überlappend erkannt', 0) }}ü / {{ $sourceCounts->get('unbeaufsichtigt', 0) }}a
                                        </small>
                                    @endif
                                </td>
                                <td><span class="zt-time zt-time--strong">{{ $item['total_machine_time'] }}</span></td>
                                <td class="zt-th-toggle"><i class="bi bi-chevron-down zt-chevron"></i></td>
                            </tr>
                            {{-- Collapsible detail row --}}
                            <tr class="collapse" id="machineDetails{{ $index }}">
                                <td colspan="8" class="zt-detail">
                                    <div class="row g-4">
                                        <div class="col-lg-6">
                                            <h6 class="zt-detail-title">Bediener Zeiterfassung</h6>
                                            @if(count($item['logs']) > 0)
                                                <table class="table table-sm zt-subtable mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Status</th>
                                                            <th>Start</th>
                                                            <th>Ende</th>
                                                            <th>Dauer</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item['logs'] as $log)
                                                            <tr>
                                                                <td>
                                                                    <span class="zt-dot" style="background:{{ match($log['status']) {
                                                                        'Rüstzeit' => 'var(--zt-ruestzeit)',
                                                                        'Mit Aufsicht' => 'var(--zt-aufsicht)',
                                                                        'Ohne Aufsicht' => 'var(--zt-ohne-aufsicht)',
                                                                        default => 'var(--zt-line)',
                                                                    } }}"></span>
                                                                    {{ $log['status'] ?? '-' }}
                                                                </td>
                                                                <td>{{ \Carbon\Carbon::parse($log['start_time'])->format('d.m. H:i') }}</td>
                                                                <td>{{ \Carbon\Carbon::parse($log['end_time'])->format('d.m. H:i') }}</td>
                                                                <td>{{ gmdate('H:i:s', \Carbon\Carbon::parse($log['start_time'])->diffInSeconds($log['end_time'])) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="zt-empty">Keine Zeiterfassung vorhanden.</p>
                                            @endif
                                        </div>
                                        <div class="col-lg-6">
                                            <h6 class="zt-detail-title">Maschine Zeiterfassung</h6>
                                            @if(count($item['processes']) > 0)
                                                <table class="table table-sm zt-subtable mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Prozess</th>
                                                            <th>Start</th>
                                                            <th>Ende</th>
                                                            <th>Dauer</th>
                                                            <th>Quelle</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item['processes'] as $proc)
                                                            <tr>
                                                                <td>{{ $proc['process_name'] ?? '-' }}</td>
                                                                <td>{{ \Carbon\Carbon::parse($proc['start_time'])->format('d.m. H:i') }}</td>
                                                                <td>{{ \Carbon\Carbon::parse($proc['end_time'])->format('d.m. H:i') }}</td>
                                                                <td>{{ gmdate('H:i:s', \Carbon\Carbon::parse($proc['start_time'])->diffInSeconds($proc['end_time'])) }}</td>
                                                                <td>
                                                                    @php
                                                                        $srcClass = match($proc['source']) {
                                                                            'manuell' => 'zt-badge--manuell',
                                                                            'überlappend erkannt' => 'zt-badge--ueberlappend',
                                                                            default => 'zt-badge--automatik',
                                                                        };
                                                                    @endphp
                                                                    <span class="zt-badge {{ $srcClass }}">{{ $proc['source'] }}</span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="zt-empty">Keine Prozesse gefunden.</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center zt-empty py-4">Keine Records vorhanden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
        --zt-ruestzeit: #E8A33D;
        --zt-aufsicht: #3B6FB6;
        --zt-ohne-aufsicht: #7C6FAE;
        --zt-automatik: #4B9C82;
        color: var(--zt-ink);
        font-variant-numeric: tabular-nums;
    }

    .zt-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-card > .card-header { background: var(--zt-ink); color: #fff; border-bottom: none; flex-wrap: wrap; gap: .5rem; }
    .zt-card > .card-body { background: var(--zt-bg); }

    .zt-legend { display: flex; gap: 1rem; font-size: .78rem; color: #C9CDD4; }
    .zt-legend-item { display: inline-flex; align-items: center; gap: .35rem; }
    .zt-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; flex: none; }

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

    .zt-section-title { font-size: .82rem; font-weight: 600; color: var(--zt-muted); margin-bottom: .75rem; }

    /* Aggregate cards */
    .zt-agg-row { display: flex; gap: .75rem; overflow-x: auto; padding-bottom: .25rem; }
    .zt-agg-card {
        flex: none; width: 260px; background: #fff; border: 1px solid var(--zt-line);
        border-radius: 10px; padding: 1rem;
    }
    .zt-agg-project { font-weight: 600; font-size: .95rem; }
    .zt-agg-sub { font-size: .76rem; color: var(--zt-muted); margin-bottom: .75rem; }
    .zt-agg-stats { display: flex; justify-content: space-between; margin-top: .75rem; }
    .zt-agg-stats > div { text-align: left; }
    .zt-agg-num { display: block; font-weight: 600; font-size: .9rem; }
    .zt-agg-label { display: block; font-size: .7rem; color: var(--zt-muted); }

    /* Stacked bars */
    .zt-bar { display: flex; width: 100%; height: 8px; border-radius: 4px; overflow: hidden; background: var(--zt-line); }
    .zt-bar--sm { height: 5px; margin-top: .3rem; max-width: 160px; }
    .zt-bar span { height: 100%; }

    /* Table */
    .zt-table { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table thead th {
        font-size: .72rem; text-transform: none; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .65rem .75rem; background: #FAFBFC;
    }
    .zt-table tbody td { padding: .65rem .75rem; border-bottom: 1px solid var(--zt-line); font-size: .85rem; }
    .zt-row { cursor: pointer; transition: background .1s; }
    .zt-row:hover { background: #F0F2F5; }
    .zt-row--automatic { background: #FAFAF7; }
    .zt-th-toggle { width: 28px; text-align: center; }
    .zt-chevron { color: var(--zt-muted); transition: transform .2s; }
    .zt-row[aria-expanded="true"] .zt-chevron,
    .zt-row.is-open .zt-chevron { transform: rotate(180deg); }

    .zt-time { font-weight: 500; }
    .zt-time--strong { font-weight: 600; }

    .zt-detail { background: #FAFBFC; border-bottom: 1px solid var(--zt-line); }
    .zt-detail-title { font-size: .78rem; font-weight: 600; color: var(--zt-muted); margin-bottom: .5rem; }
    .zt-subtable thead th { font-size: .7rem; color: var(--zt-muted); font-weight: 600; border-bottom: 1px solid var(--zt-line); }
    .zt-subtable td { font-size: .8rem; border-bottom: 1px solid var(--zt-line); vertical-align: middle; }
    .zt-empty { color: var(--zt-muted); font-size: .82rem; margin: 0; }

    .zt-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .55rem; border-radius: 20px; font-size: .72rem; font-weight: 500; }
    .zt-badge--automatik { background: rgba(75, 156, 130, .12); color: var(--zt-automatik); }
    .zt-badge--manuell { background: rgba(59, 111, 182, .12); color: var(--zt-aufsicht); }
    .zt-badge--ueberlappend { background: rgba(232, 163, 61, .15); color: #A66E1E; }

    @media (max-width: 576px) {
        .zt-agg-card { width: 220px; }
    }
</style>

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

    // Rotate the chevron on each row when its detail panel opens/closes.
    document.querySelectorAll('.zt-row').forEach(function (row) {
        const targetId = row.getAttribute('data-bs-target');
        const target = document.querySelector(targetId);
        if (! target) return;

        target.addEventListener('shown.bs.collapse', () => row.classList.add('is-open'));
        target.addEventListener('hidden.bs.collapse', () => row.classList.remove('is-open'));
    });
</script>
@endsection