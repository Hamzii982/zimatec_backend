@php $showPosition = $showPosition ?? false; @endphp

<table class="table table-sm zt-subtable mb-0">
    <thead>
        <tr>
            <th>Prozess Name</th>
            @if($showPosition)<th>Position</th>@endif
            <th>Start Zeit</th>
            <th>End Zeit</th>
            <th>Gesamt</th>
            <th>Aktiv</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($processes as $process)
            <tr>
                <td>{{ $process->name }}</td>
                @if($showPosition)<td>{{ $process->position->name ?? 'N/A' }}</td>@endif
                <td>{{ $process->start_time }}</td>
                <td>{{ $process->end_time }}</td>
                <td><span class="zt-chip zt-chip--total">{{ gmdate('H:i:s', $process->total_seconds) }}</span></td>
                <td><span class="zt-chip zt-chip--active">{{ gmdate('H:i:s', $process->active_seconds ?? $process->total_seconds) }}</span></td>
            </tr>
            @if ($process->pauses->count())
                <tr>
                    <td colspan="{{ $showPosition ? 6 : 5 }}">
                        <strong class="text-muted" style="font-size:.74rem;">Pausen:</strong>
                        <ul class="zt-pause-list">
                        @foreach ($process->pauses as $pause)
                            <li>{{ $pause->pause_type }} ({{ $pause->reason }}): {{ $pause->pause_start }} - {{ $pause->pause_end ?? 'läuft' }}</li>
                        @endforeach
                        </ul>
                    </td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>