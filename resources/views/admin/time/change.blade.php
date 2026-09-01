@extends('admin.layouts.index')

@section('content')
<div class="zt-compare container">

    {{-- ======================
        Pending Requests
    ======================= --}}
    <div class="card shadow-sm zt-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Ausstehende Nachtragsanträge</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table zt-table zt-table--excel align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Projekt</th>
                            <th>Position</th>
                            <th>Maschine</th>
                            <th>Bediener</th>
                            <th>Start</th>
                            <th>Ende</th>
                            <th>Grund</th>
                            <th>Payload</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingRequests as $index => $record)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $record->timeRecord->project->project_name ?? 'N/A' }}
                                    <small class="text-muted d-block project-auftrag">
                                        {{ "ZF: ".$record->timeRecord->project->auftragsnummer_zf ." ZT: " .$record->timeRecord->project->auftragsnummer_zt }}
                                    </small>
                                </td>
                                <td>{{ $record->timeRecord->position->name }}</td>
                                <td>{{ $record->timeRecord->machine->name ?? 'N/A' }}</td>
                                <td>{{ $record->requestedBy->name ?? 'N/A' }}</td>
                                <td>{{ $record->timeRecord->start_time ?? 'N/A' }}</td>
                                <td>{{ $record->timeRecord->end_time ?? 'N/A' }}</td>
                                <td>{{ $record->reason }}</td>
                                <td>
                                    <button class="zt-btn zt-btn--ghost toggle-details" type="button"
                                        data-bs-toggle="collapse" data-target="#details{{ $record->id }}">
                                        <i class="bi bi-eye"></i> Änderungen anzeigen
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('admin.time.change.accept', $record->id) }}" method="POST">
                                            @csrf
                                            <button class="zt-btn zt-btn--success" type="submit">
                                                <i class="bi bi-check-circle"></i> Übernehmen
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.time.change.reject', $record->id) }}" method="POST">
                                            @csrf
                                            <button class="zt-btn zt-btn--danger" type="submit">
                                                <i class="bi bi-x-circle"></i> Ablehnen
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            {{-- Hidden expandable row --}}
                            <tr id="details{{ $record->id }}" class="details-row" style="display:none;">
                                <td colspan="10" class="p-0 border-0">
                                    @php
                                        $payloadLogs = json_decode($record->payload, true);
                                        $originalLogs = $record->timeRecord?->logs ?? collect();
                                    @endphp

                                    <div class="zt-subcard">
                                        <div class="row g-3">
                                            {{-- Original Logs --}}
                                            <div class="col-md-6">
                                                <h6 class="zt-subtitle zt-subtitle--danger">
                                                    <i class="bi bi-clock-history"></i> Originalprotokolle
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table zt-subtable zt-subtable--danger mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Status</th>
                                                                <th>Start</th>
                                                                <th>Ende</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($originalLogs as $index => $log)
                                                                <tr>
                                                                    <td>{{ $log->status->id }}</td>
                                                                    <td>{{ $log->status->name ?? 'N/A' }}</td>
                                                                    <td>{{ \Carbon\Carbon::parse($log->start_time)->format('Y-m-d H:i') }}</td>
                                                                    <td>{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('Y-m-d H:i') : 'Running' }}</td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="4" class="zt-empty text-center">Es wurden keine Originalprotokolle gefunden.</td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            {{-- Requested Changes --}}
                                            <div class="col-md-6">
                                                <h6 class="zt-subtitle zt-subtitle--success">
                                                    <i class="bi bi-arrow-repeat"></i> Gewünschte Änderungen
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table zt-subtable zt-subtable--success mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Status</th>
                                                                <th>Start</th>
                                                                <th>End</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($payloadLogs as $index => $log)
                                                                @php
                                                                    if (!empty($log['status_id'])) {
                                                                        $machine_status = App\Models\MachineStatus::find($log['status_id']);
                                                                    } else {
                                                                        $machine_status = null;
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td>{{ $log['id'] ?? 'New' }}</td>
                                                                    <td>{{ $machine_status ? $machine_status->name : '-' }}</td>
                                                                    <td>{{ $log['start_time'] ? \Carbon\Carbon::parse($log['start_time'])->format('Y-m-d H:i') : '-' }}</td>
                                                                    <td>{{ isset($log['end_time']) && $log['end_time'] ? \Carbon\Carbon::parse($log['end_time'])->format('Y-m-d H:i') : 'Running' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="zt-empty text-center py-4">
                                    Es wurden keine ausstehenden Anfragen gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ======================
        Processed Requests
    ======================= --}}
    <div class="card shadow-sm zt-card">
        <div class="card-header zt-card-header--muted">
            <h5 class="mb-0">Zuvor bearbeitete Anfragen</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table zt-table zt-table--excel align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Projekt</th>
                            <th>Position</th>
                            <th>Maschine</th>
                            <th>Bediener</th>
                            <th>Start</th>
                            <th>Ende</th>
                            <th>Grund</th>
                            <th>Payload</th>
                            <th>Status</th>
                            <th>Rezension von</th>
                            <th>Rezension am</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($processedRequests as $index => $record)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $record->timeRecord->project->project_name ?? 'N/A' }}
                                    <small class="text-muted d-block project-auftrag">
                                        {{ "ZF: ".$record->timeRecord->project->auftragsnummer_zf ." ZT: " .$record->timeRecord->project->auftragsnummer_zt }}
                                    </small>
                                </td>
                                <td>{{ $record->timeRecord->position->name }}</td>
                                <td>{{ $record->timeRecord->machine->name ?? 'N/A' }}</td>
                                <td>{{ $record->requestedBy->name ?? 'N/A' }}</td>
                                <td>{{ $record->timeRecord->start_time ?? 'N/A' }}</td>
                                <td>{{ $record->timeRecord->end_time ?? 'N/A' }}</td>
                                <td>{{ $record->reason }}</td>
                                <td>
                                    <button class="zt-btn zt-btn--ghost toggle-details" type="button"
                                        data-bs-toggle="collapse" data-target="#details{{ $record->id }}">
                                        <i class="bi bi-eye"></i> Änderungen anzeigen
                                    </button>
                                </td>
                                <td>
                                    @if($record->status === 'accepted')
                                        <span class="zt-badge zt-badge--success">Übernimmt</span>
                                    @elseif($record->status === 'rejected')
                                        <span class="zt-badge zt-badge--danger">Abgelehnt</span>
                                    @endif
                                </td>
                                <td>{{ $record->approvedBy->name ?? '-' }}</td>
                                <td>{{ $record->approved_at ? $record->approved_at : '' }}</td>
                            </tr>
                            {{-- Hidden expandable row --}}
                            <tr id="details{{ $record->id }}" class="details-row" style="display:none;">
                                <td colspan="12" class="p-0 border-0">
                                    @php
                                        $payloadLogs = json_decode($record->payload, true);
                                    @endphp

                                    <div class="zt-subcard">
                                        <div class="row g-3">
                                            {{-- Requested Changes --}}
                                            <div class="col-md-6">
                                                <h6 class="zt-subtitle zt-subtitle--success">
                                                    <i class="bi bi-arrow-repeat"></i> Gewünschte Änderungen
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table zt-subtable zt-subtable--success mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Status</th>
                                                                <th>Start</th>
                                                                <th>End</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($payloadLogs as $index => $log)
                                                                @php
                                                                    if (!empty($log['status_id'])) {
                                                                        $machine_status = App\Models\MachineStatus::find($log['status_id']);
                                                                    } else {
                                                                        $machine_status = null;
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td>{{ $log['id'] ?? 'New' }}</td>
                                                                    <td>{{ $machine_status ? $machine_status->name : '-' }}</td>
                                                                    <td>{{ $log['start_time'] ? \Carbon\Carbon::parse($log['start_time'])->format('Y-m-d H:i') : '-' }}</td>
                                                                    <td>{{ isset($log['end_time']) && $log['end_time'] ? \Carbon\Carbon::parse($log['end_time'])->format('Y-m-d H:i') : 'Running' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="zt-empty text-center py-4">
                                    Bisher wurden noch keine Anfragen bearbeitet.
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
    .zt-card-header--muted { background: var(--zt-muted); color: #fff; border-bottom: none; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; }
    .zt-empty { color: var(--zt-muted); font-size: .85rem; }

    .zt-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        border-radius: 8px; font-size: .8rem; font-weight: 500;
        padding: .35rem .75rem; border: 1px solid var(--zt-line); background: #fff;
    }
    .zt-btn--ghost { color: var(--zt-muted); }
    .zt-btn--ghost:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
    .zt-btn--success { background: #1E7A46; border-color: #1E7A46; color: #fff; }
    .zt-btn--success:hover { background: #17603730; }
    .zt-btn--danger { background: #B3261E; border-color: #B3261E; color: #fff; }
    .zt-btn--danger:hover { background: #92201A; }

    .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 600; }
    .zt-badge--success { background: #E4F5EC; color: #1E7A46; }
    .zt-badge--danger { background: #FBEAE9; color: #B3261E; }

    .zt-subcard { background: #FAFBFC; border-top: 1px solid var(--zt-line); border-bottom: 1px solid var(--zt-line); padding: 1rem 1.25rem; }
    .zt-subtitle { font-size: .8rem; font-weight: 600; margin-bottom: .6rem; }
    .zt-subtitle--danger { color: #B3261E; }
    .zt-subtitle--success { color: #1E7A46; }

    .zt-subtable { border: 1px solid var(--zt-line); border-radius: 6px; overflow: hidden; font-size: .78rem; }
    .zt-subtable thead th { text-align: center; font-weight: 600; padding: .4rem; }
    .zt-subtable tbody td { padding: .4rem; text-align: center; border-top: 1px solid var(--zt-line); }
    .zt-subtable--danger thead { background: #FBEAE9; color: #B3261E; }
    .zt-subtable--danger tbody tr { background: #FEF5F4; }
    .zt-subtable--success thead { background: #E4F5EC; color: #1E7A46; }
    .zt-subtable--success tbody tr { background: #F4FBF7; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-details').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const row = document.querySelector(targetId);

                document.querySelectorAll('.details-row').forEach(r => {
                    if (r !== row) r.style.display = 'none';
                });

                row.style.display = (row.style.display === 'none' || row.style.display === '')
                    ? 'table-row'
                    : 'none';
            });
        });
    });
</script>
@endsection