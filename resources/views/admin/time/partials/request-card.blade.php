@php
    $payload = json_decode($record->payload, true) ?? [];
    $isLegacy = ! array_key_exists('logs', $payload) && ! array_key_exists('processes', $payload);
    $payloadLogs = $isLegacy ? $payload : ($payload['logs'] ?? []);
    $payloadProcesses = $isLegacy ? [] : ($payload['processes'] ?? []);

    $originalLogs = $record->timeRecord?->logs ?? collect();
    $originalProcesses = $record->timeRecord?->processes ?? collect();

    $origStart = $record->timeRecord?->start_time;
    $origEnd = $record->timeRecord?->end_time;
    $startChanged = $record->record_start_time && (string) $record->record_start_time !== (string) $origStart;
    $endChanged = $record->record_end_time && (string) $record->record_end_time !== (string) $origEnd;
@endphp

<div class="zt-request-card">
    <div class="zt-request-header">
        <div class="zt-request-main">
            <span class="zt-request-title">
                {{ $record->timeRecord->project->project_name ?? 'N/A' }} · {{ $record->timeRecord->machine->name ?? 'N/A' }}
            </span>
            @php
                $auftragsParts = array_filter([
                    $record->timeRecord->project->auftragsnummer_zf ?? null,
                    $record->timeRecord->project->auftragsnummer_zt ?? null,
                ]);
                $auftragsLabel = $auftragsParts ? implode(' / ', $auftragsParts) : 'Kein Auftragsnummer';
            @endphp

            <span class="zt-request-sub">
                {{ $auftragsLabel }} · {{ $record->timeRecord->position->name ?? '' }} · Bediener: {{ $record->requestedBy->name ?? 'N/A' }}
            </span>
        </div>

        <div class="zt-request-meta">
            <span class="zt-chip {{ $startChanged ? 'zt-chip--changed' : '' }}">
                <i class="bi bi-box-arrow-in-right"></i>
                @if($startChanged)
                    {{ $origStart ? \Carbon\Carbon::parse($origStart)->format('d.m H:i') : '-' }} → {{ \Carbon\Carbon::parse($record->record_start_time)->format('d.m H:i') }}
                @else
                    Start: {{ $origStart ? \Carbon\Carbon::parse($origStart)->format('d.m H:i') : '-' }}
                @endif
            </span>
            <span class="zt-chip {{ $endChanged ? 'zt-chip--changed' : '' }}">
                <i class="bi bi-box-arrow-left"></i>
                @if($endChanged)
                    {{ $origEnd ? \Carbon\Carbon::parse($origEnd)->format('d.m H:i') : 'Läuft' }} → {{ \Carbon\Carbon::parse($record->record_end_time)->format('d.m H:i') }}
                @else
                    Ende: {{ $origEnd ? \Carbon\Carbon::parse($origEnd)->format('d.m H:i') : 'Läuft' }}
                @endif
            </span>

            @if($pending)
                <span class="zt-badge zt-badge--pending">Ausstehend</span>
            @elseif($record->status === 'accepted')
                <span class="zt-badge zt-badge--success">Übernommen</span>
            @else
                <span class="zt-badge zt-badge--danger">Abgelehnt</span>
            @endif
        </div>

        <i class="bi bi-chevron-down zt-chevron"></i>
    </div>

    <div class="zt-request-body">
        <div class="zt-reason-line"><strong>Grund:</strong> {{ $record->reason }}</div>

        {{-- Logs diff --}}
        <div class="zt-diff-title">Protokolle</div>
        <div class="row g-3">
            <div class="col-md-6">
                <table class="table zt-subtable zt-subtable--danger mb-0">
                    <thead><tr><th>Status</th><th>Start</th><th>Ende</th></tr></thead>
                    <tbody>
                        @forelse($originalLogs as $log)
                            <tr>
                                <td>{{ $log->status->name ?? 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($log->start_time)->format('d.m H:i') }}</td>
                                <td>{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('d.m H:i') : 'Läuft' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="zt-empty">Keine Originalprotokolle.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table zt-subtable zt-subtable--success mb-0">
                    <thead><tr><th>Status</th><th>Start</th><th>Ende</th></tr></thead>
                    <tbody>
                        @forelse($payloadLogs as $log)
                            @php
                                $machineStatus = ! empty($log['status_id']) ? \App\Models\MachineStatus::find($log['status_id']) : null;
                                $deleted = ! empty($log['delete']) && $log['delete'] === 'true';
                            @endphp
                            <tr @if($deleted) style="text-decoration:line-through; opacity:.6;" @endif>
                                <td>{{ $machineStatus->name ?? '-' }}</td>
                                <td>{{ ! empty($log['start_time']) ? \Carbon\Carbon::parse($log['start_time'])->format('d.m H:i') : '-' }}</td>
                                <td>{{ ! empty($log['end_time']) ? \Carbon\Carbon::parse($log['end_time'])->format('d.m H:i') : 'Läuft' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="zt-empty">Keine Änderungen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Processes diff --}}
        @if($originalProcesses->isNotEmpty() || count($payloadProcesses))
            <div class="zt-diff-title">Manuelle Prozesse</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <table class="table zt-subtable zt-subtable--danger mb-0">
                        <thead><tr><th>Name</th><th>Start</th><th>Ende</th></tr></thead>
                        <tbody>
                            @forelse($originalProcesses as $process)
                                <tr>
                                    <td>{{ $process->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($process->start_time)->format('d.m H:i') }}</td>
                                    <td>{{ $process->end_time ? \Carbon\Carbon::parse($process->end_time)->format('d.m H:i') : 'Läuft' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="zt-empty">Keine Originalprozesse.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table zt-subtable zt-subtable--success mb-0">
                        <thead><tr><th>Name</th><th>Start</th><th>Ende</th></tr></thead>
                        <tbody>
                            @forelse($payloadProcesses as $process)
                                @php $deleted = ! empty($process['delete']) && $process['delete'] === 'true'; @endphp
                                <tr @if($deleted) style="text-decoration:line-through; opacity:.6;" @endif>
                                    <td>{{ $process['name'] ?? '-' }}</td>
                                    <td>{{ ! empty($process['start_time']) ? \Carbon\Carbon::parse($process['start_time'])->format('d.m H:i') : '-' }}</td>
                                    <td>{{ ! empty($process['end_time']) ? \Carbon\Carbon::parse($process['end_time'])->format('d.m H:i') : 'Läuft' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="zt-empty">Keine Änderungen.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="zt-request-footer">
            @if($pending)
                <div class="d-flex gap-2">
                    <form action="{{ route('admin.time.change.accept', $record->id) }}" method="POST">
                        @csrf
                        <button class="zt-btn zt-btn--success" type="submit"><i class="bi bi-check-circle"></i> Übernehmen</button>
                    </form>
                    <form action="{{ route('admin.time.change.reject', $record->id) }}" method="POST">
                        @csrf
                        <button class="zt-btn zt-btn--danger" type="submit"><i class="bi bi-x-circle"></i> Ablehnen</button>
                    </form>
                </div>
            @else
                <span class="zt-review-meta">
                    Bearbeitet von {{ $record->approvedBy->name ?? '-' }}
                    @if($record->approved_at) am {{ \Carbon\Carbon::parse($record->approved_at)->format('d.m.Y H:i') }} @endif
                </span>
            @endif
        </div>
    </div>
</div>