@extends('admin.layouts.index')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Zeit Vergleichen</h5>
        </div>

        <div class="card-body">

            {{-- Filters --}}
            <div class="mb-3" style="overflow-x:auto; white-space: nowrap;" id="weekSlider">
                @foreach($weeks as $week)
                    <button 
                        onclick="window.location.href='?week={{ $week['value'] }}'" 
                        style="display:inline-block; width:120px; height:60px; margin-right:4px;"
                        class="week-button {{ $selectedWeek == $week['value'] ? 'bg-secondary bg-opacity-50 text-white shadow' : 'bg-white text-gray text-opacity-80 hover:bg-gray hover:bg-opacity-10' }} border rounded-lg font-medium text-center align-middle"
                        data-week="{{ $week['value'] }}">
                        {{ $week['label'] }}
                    </button>
                @endforeach

                <!-- +1 button -->
                <button id="addWeekBtn" style="display:inline-block; width:120px; height:60px; margin-right:4px;"
                    class="bg-white text-gray text-opacity-80 hover:bg-gray hover:bg-opacity-10 border rounded-lg font-medium text-center align-middle">
                    +1
                </button>
            </div>

            {{-- Comparison Table --}}
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th><i class="bi bi-person-fill"></i></th>
                            <th>Bediener</th>
                            <th>Projekte</th>
                            <th>Position</th>
                            <th>Maschine</th>
                            <th>Bediener Zeit</th>
                            <th><i class="bi bi-clipboard-data-fill"></i></th>
                            <th>Prozesse</th>
                            <th>Maschine Zeit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comparison as $index => $item)
                            <tr>
                                <td><i class="bi bi-person"></i></td>
                                <td>{{ $item['record']->user->name }}</td>
                                <td>{{ $item['record']->project->project_name }} <small class="text-muted">({{ $item['record']->project->auftragsnummer }})</small></td>
                                <td>{{ $item['record']->Position->name }}</td>
                                <td>{{ $item['record']->machine->name }}</td>
                                <td>{{ $item['total_user_time'] }}</td>
                                <td><i class="bi bi-clipboard-data"></i></td>
                                <td>{{ $item['process_count'] }}</td>
                                <td>
                                    <a class="btn btn-outline-dark btn-sm" 
                                        data-bs-toggle="collapse" 
                                        href="#machineDetails{{ $index }}" 
                                        role="button" 
                                        aria-expanded="false" 
                                        aria-controls="machineDetails{{ $index }}">
                                            {{ $item['total_machine_time'] }}
                                    </a>
                                </td>
                            </tr>
                            {{-- Collapsible Row --}}
                            <tr class="collapse bg-light" id="machineDetails{{ $index }}">
                                <td colspan="9">
                                    <div class="p-3 row">
                                        <div class="col-lg-6">
                                            <h6 class="fw-bold mb-2 text-dark">
                                                Bediener Zeiterfassung
                                            </h6>
                                            @if(count($item['logs']) > 0)
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-secondary">
                                                        <tr class="text-center">
                                                            <th>Status</th>
                                                            <th>Start</th>
                                                            <th>Ende</th>
                                                            <th>Gesamt Zeit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item['logs'] as $log)
                                                            <tr class="text-center">
                                                                <td>{{ $log['status'] ?? '-' }}</td>
                                                                <td>{{ $log['start_time'] ?? '-' }}</td>
                                                                <td>{{ $log['end_time'] ?? '-' }}</td>
                                                                <td>{{ gmdate('H:i:s', \Carbon\Carbon::parse($log['start_time'])->diffInSeconds($log['end_time'])) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-muted mb-0">Keine Prozesse Gefunden.</p>
                                            @endif
                                        </div>
                                        <div class="col-lg-6">
                                            <h6 class="fw-bold mb-2 text-dark">
                                                Maschine Zeiterfassung
                                            </h6>
                                            @if(count($item['processes']) > 0)
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-secondary">
                                                        <tr class="text-center">
                                                            <th>Prozesse</th>
                                                            <th>Start</th>
                                                            <th>Ende</th>
                                                            <th>Gesamt Zeit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item['processes'] as $proc)
                                                            <tr class="text-center">
                                                                <td>{{ $proc['process_name'] ?? '-' }}</td>
                                                                <td>{{ $proc['start_time'] ?? '-' }}</td>
                                                                <td>{{ $proc['end_time'] ?? '-' }}</td>
                                                                <td>{{ gmdate('H:i:s', \Carbon\Carbon::parse($proc['start_time'])->diffInSeconds($proc['end_time'])) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-muted mb-0">Keine Prozesse Gefunden.</p>
                                            @endif
                                        </div>
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
<style>
    /* Hide horizontal scrollbar */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
<script>
    window.dailyRecordsCache = window.dailyRecordsCache || {};
    window.dayDetailsCache   = window.dayDetailsCache   || {};

    const slider = document.getElementById('weekSlider');

    function scrollLeft() {
        slider.scrollBy({ left: -200, behavior: 'smooth' });
    }

    function scrollRight() {
        slider.scrollBy({ left: 200, behavior: 'smooth' });
    }

    document.getElementById('addWeekBtn').addEventListener('click', function() {
        const slider = document.getElementById('weekSlider');
        const buttons = slider.querySelectorAll('.week-button');
        const lastButton = buttons[buttons.length - 1];

        // Get last week value, format oW (e.g., 202603)
        let lastWeekValue = lastButton.getAttribute('data-week');
        let year = parseInt(lastWeekValue.slice(0, 4));
        let week = parseInt(lastWeekValue.slice(4, 6));

        // Calculate previous week
        week -= 1;
        if (week < 1) {
            week = 52; // handle previous year
            year -= 1;
        }

        // Pad week to two digits
        let weekStr = week.toString().padStart(2, '0');
        let newWeekValue = year.toString() + weekStr;
        let newWeekLabel = 'KW ' + weekStr + ' / ' + year;

        // Create new button
        const newButton = document.createElement('button');
        newButton.setAttribute('onclick', `window.location.href='?week=${newWeekValue}'`);
        newButton.setAttribute('data-week', newWeekValue);
        newButton.className = "week-button border rounded-lg font-medium bg-white text-gray text-opacity-80 hover:bg-gray hover:bg-opacity-10";
        newButton.style.cssText = "display:inline-block; width:120px; height:60px; margin-right:4px;";
        newButton.innerText = newWeekLabel;

        // Insert before +1 button
        slider.insertBefore(newButton, this);
    });
</script>
@endsection
