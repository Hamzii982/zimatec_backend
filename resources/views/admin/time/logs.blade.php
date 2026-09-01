@extends('admin.layouts.index')

@php
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

@section('content')

<div class="zt-compare container">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Maschinenprotokolle</h5>
            <a href="{{ route('admin.time.logs_old') }}" class="zt-export-btn">
                <i class="bi bi-clock-history"></i> Alte Seite
            </a>
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

            <div class="table-responsive">
                <table class="table zt-table zt-table--excel align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>KW</th>
                            <th>Firma</th>
                            <th>Auftragsnr.</th>
                            <th>Position</th>
                            <th>Maschine</th>
                            <th>Gesamtzeit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($weeklyRecords as $index => $row)
                            @php
                                $totalSeconds = $row->process_seconds - $row->pause_seconds;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>KW {{ substr($row->calendar_week, 4) }}</td>
                                <td>
                                    <span class="zt-badge {{ $row->company === 'ZF' ? 'zt-badge--zf' : 'zt-badge--zt' }}">
                                        {{ $row->company }}
                                    </span>
                                </td>
                                <td>{{ $row->auftragsnummer }}</td>
                                <td>{{ $row->position_name }}</td>
                                <td>{{ $row->machine_name }}</td>
                                <td><strong>{{ secondsToIndustryMinutes($totalSeconds) }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="zt-empty text-center py-4">
                                    Keine Daten für diese Kalenderwochen vorhanden.
                                </td>
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

    .zt-export-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: transparent;
        color: #fff; font-size: .8rem; font-weight: 500;
        padding: .4rem .75rem; text-decoration: none;
        transition: background .15s;
    }
    .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

    .zt-badge {
        display: inline-block; padding: .2rem .55rem; border-radius: 6px;
        font-size: .72rem; font-weight: 600;
    }
    .zt-badge--zf { background: #E7EEFF; color: #2E5AAC; }
    .zt-badge--zt { background: #E4F5EC; color: #1E7A46; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; white-space: nowrap; }
    .zt-empty { color: var(--zt-muted); font-size: .85rem; }
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
</script>
@endsection