@extends('user.layouts.index')

@section('content')
<style>
    .zt-time {
        --brand-blue: #002752;
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        color: var(--zt-ink);
    }

    .zt-time-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-time-card > .card-header { background: var(--brand-blue); color: #fff; border-bottom: none; padding: 1rem 1.25rem; }
    .zt-time-card > .card-body { background: var(--zt-bg); padding: 1.5rem; }

    .zt-export-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: transparent;
        color: #fff; font-size: .8rem; font-weight: 500;
        padding: .4rem .75rem; text-decoration: none;
        transition: background .15s;
    }
    .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

    .zt-status-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .78rem; font-weight: 600;
        padding: .3rem .7rem; border-radius: 20px;
    }
    .zt-status-pill--muted { background: #EEF0F2; color: var(--zt-muted); }
    .zt-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }

    /* --- Info tiles --- */
    .zt-info-tile {
        display: flex; align-items: flex-start; gap: .65rem;
        background: #fff; border: 1px solid var(--zt-line); border-radius: 8px;
        padding: .75rem .9rem; height: 100%;
    }
    .zt-info-tile i { font-size: 1.1rem; color: var(--brand-blue); margin-top: .15rem; }
    .zt-info-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: var(--zt-muted); display: block; }
    .zt-info-value { font-size: .95rem; font-weight: 600; color: var(--zt-ink); display: block; }

    /* --- Sections --- */
    .zt-section {
        background: #fff; border: 1px solid var(--zt-line); border-radius: 8px;
        padding: 1rem 1.1rem; margin-top: 1rem;
    }
    .zt-section-title {
        font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
        color: var(--zt-muted); margin-bottom: .75rem;
    }

    .btn-wechsel {
        background: var(--brand-blue); color: #fff; border: none; border-radius: 8px;
        padding: .5rem 1.1rem; font-weight: 600; font-size: .85rem;
    }
    .btn-wechsel:hover { background: #001a3d; color: #fff; }

    /* --- Manual processes empty state --- */
    #noProcessRow td { padding: .75rem .25rem; font-size: .85rem; }
</style>

<div class="container mt-4 mb-5 zt-time">
    <div class="card shadow-sm zt-time-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Änderungsantrag für Datensatz</h5>
            <a href="{{ route('time-records.show', $record->id) }}" class="zt-export-btn">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('time-records.store-change-request', $record->id) }}" method="POST">
                @csrf

                {{-- ========== RECORD OVERVIEW ========== --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="zt-info-tile">
                            <i class="bi bi-folder-fill"></i>
                            <div>
                                <span class="zt-info-label">Projekt</span>
                                <span class="zt-info-value">{{ $record->project->project_name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="zt-info-tile">
                            <i class="bi bi-pin-map-fill"></i>
                            <div>
                                <span class="zt-info-label">Position</span>
                                <span class="zt-info-value">{{ $record->position->name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="zt-info-tile">
                            <i class="bi bi-cpu-fill"></i>
                            <div>
                                <span class="zt-info-label">Maschine</span>
                                <span class="zt-info-value">{{ $record->machine->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Anfang</strong></label>
                        <input type="datetime-local" name="record_start_time" step="1"
                            value="{{ \Carbon\Carbon::parse($record->start_time)->format('Y-m-d\TH:i') }}"
                            class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Beendet</strong></label>
                        <input type="datetime-local" name="record_end_time" id="mainEndTime" step="1"
                            value="{{ $record->end_time ? \Carbon\Carbon::parse($record->end_time)->format('Y-m-d\TH:i') : '' }}"
                            class="form-control">
                    </div>
                </div>

                {{-- ========== LOGS ========== --}}
                <div class="zt-section">
                    <div class="zt-section-title">Protokolle bearbeiten</div>

                    <table class="table table-sm align-middle mb-2">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Start</th>
                                <th>Ende</th>
                                <th class="text-center">Läuft noch</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="logTableBody">
                            @foreach ($record->logs as $index => $log)
                                <tr>
                                    <td>
                                        <input type="hidden" name="logs[{{ $index }}][id]" value="{{ $log->id }}">
                                        <input type="hidden" name="logs[{{ $index }}][status_id]" value="{{ $log->status->id }}">
                                        <input type="hidden" name="logs[{{ $index }}][delete]" value="false">
                                        {{ $log->status->name }}
                                    </td>
                                    <td>
                                        <input type="datetime-local" name="logs[{{ $index }}][start_time]" step="1"
                                            value="{{ $log->start_time }}" class="form-control form-control-sm w-auto">
                                    </td>
                                    <td>
                                        <input type="datetime-local" name="logs[{{ $index }}][end_time]"
                                            value="{{ $log->end_time ?: '' }}"
                                            step="1"
                                            class="form-control form-control-sm end-time-field w-auto"
                                            {{ $log->end_time ? '' : 'disabled' }}>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="still-running form-check-input"
                                            {{ is_null($log->end_time) ? 'checked' : '' }}>
                                    </td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm removeRowBtn">×</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <button type="button" id="addRowBtn" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Neues Protokoll hinzufügen
                    </button>
                </div>

                {{-- ========== MANUAL PROCESSES ========== --}}
                <div class="zt-section">
                    <div class="zt-section-title">Manuelle Prozesse bearbeiten</div>

                    <table class="table table-sm align-middle mb-2">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start</th>
                                <th>Ende</th>
                                <th class="text-center">Läuft noch</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="processTableBody">
                            @forelse ($record->processes as $pIndex => $process)
                                <tr>
                                    <td>
                                        <input type="hidden" name="processes[{{ $pIndex }}][id]" value="{{ $process->id }}">
                                        <input type="hidden" name="processes[{{ $pIndex }}][delete]" value="false">
                                        <input type="text" name="processes[{{ $pIndex }}][name]"
                                            value="{{ $process->name }}"
                                            class="form-control form-control-sm" required>
                                    </td>
                                    <td>
                                        <input type="datetime-local" name="processes[{{ $pIndex }}][start_time]" step="1"
                                            value="{{ \Carbon\Carbon::parse($process->start_time)->format('Y-m-d\TH:i:s') }}"
                                            class="form-control form-control-sm w-auto" required>
                                    </td>
                                    <td>
                                        <input type="datetime-local" name="processes[{{ $pIndex }}][end_time]" step="1"
                                            value="{{ $process->end_time ? \Carbon\Carbon::parse($process->end_time)->format('Y-m-d\TH:i:s') : '' }}"
                                            class="form-control form-control-sm process-end-time-field w-auto"
                                            {{ $process->end_time ? '' : 'disabled' }}>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="process-still-running form-check-input"
                                            {{ is_null($process->end_time) ? 'checked' : '' }}>
                                    </td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm removeProcessRowBtn">×</button></td>
                                </tr>
                            @empty
                                <tr id="noProcessRow">
                                    <td colspan="5" class="text-muted">Keine manuellen Prozesse für diesen Datensatz.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <button type="button" id="addProcessRowBtn" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Neuen Prozess hinzufügen
                    </button>
                </div>

                {{-- ========== REASON ========== --}}
                <div class="zt-section">
                    <div class="zt-section-title">Grund für die Änderung</div>

                    <select name="reason" id="reason" class="form-select" required onchange="toggleOtherReason()">
                        <option value="">-- Grund wählen --</option>
                        <option value="Vergessen an und aus zu stechen">Vergessen an und aus zu stechen</option>
                        <option value="Fehler">Fehler</option>
                        <option value="Noch kein Stecher">Noch kein Stecher</option>
                        <option value="Technisches Problem">Technisches Problem</option>
                        <option value="In Besprechung erklären">In Besprechung erklären</option>
                        <option value="Sonstiges">Sonstiges</option>
                    </select>

                    <div class="mt-3 d-none" id="otherReasonDiv">
                        <label for="otherReason" class="form-label">Sonstiger Grund</label>
                        <input type="text" name="other_reason" id="otherReason" class="form-control" placeholder="Bitte Grund angeben...">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-wechsel">
                        <i class="bi bi-send-check me-1"></i>Änderungsantrag einreichen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ========== LOGS ==========
    const mainEndTime = document.getElementById('mainEndTime');
    const logTbody = document.getElementById('logTableBody');
    let logIndex = {{ $record->logs->count() }};

    function getVisibleLogRows() {
        return Array.from(logTbody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');
    }

    function syncMainEndTime() {
        const rows = getVisibleLogRows();
        const lastRow = rows.length ? rows[rows.length - 1] : null;

        if (!lastRow) { mainEndTime.value = ''; mainEndTime.classList.add('border-danger'); return; }

        const endTimeField = lastRow.querySelector('.end-time-field');
        const stillRunning = lastRow.querySelector('.still-running');

        if (stillRunning && stillRunning.checked) {
            mainEndTime.value = ''; mainEndTime.classList.add('border-danger');
        } else if (endTimeField && endTimeField.value) {
            mainEndTime.value = endTimeField.value; mainEndTime.classList.remove('border-danger');
        } else {
            mainEndTime.value = ''; mainEndTime.classList.add('border-danger');
        }
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('end-time-field')) syncMainEndTime();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('still-running')) {
            const row = e.target.closest('tr');
            const endTimeField = row.querySelector('.end-time-field');
            endTimeField.disabled = e.target.checked;
            if (e.target.checked) endTimeField.value = '';
            syncMainEndTime();
        }
    });

    document.getElementById('addRowBtn').addEventListener('click', function () {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select name="logs[${logIndex}][status_id]" class="form-select form-select-sm" required>
                    @foreach (\App\Models\MachineStatus::where('active', true)->get() as $status)
                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="datetime-local" name="logs[${logIndex}][start_time]" class="form-control form-control-sm w-auto" required></td>
            <td><input type="datetime-local" name="logs[${logIndex}][end_time]" class="form-control form-control-sm end-time-field w-auto" disabled></td>
            <td class="text-center"><input type="checkbox" class="still-running form-check-input" checked></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm removeRowBtn">×</button></td>
        `;
        logTbody.appendChild(newRow);
        logIndex++;
        syncMainEndTime();
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('removeRowBtn')) {
            const row = e.target.closest('tr');
            const idField = row.querySelector('input[name*="[id]"]');
            const deleteField = row.querySelector('input[name*="[delete]"]');
            if (idField) {
                if (deleteField) deleteField.value = 'true';
                row.style.display = 'none';
            } else {
                row.remove();
            }
            syncMainEndTime();
        }
    });

    syncMainEndTime();

    // ========== MANUAL PROCESSES ==========
    const processTbody = document.getElementById('processTableBody');
    let processIndex = {{ $record->processes->count() }};

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('process-still-running')) {
            const row = e.target.closest('tr');
            const endTimeField = row.querySelector('.process-end-time-field');
            endTimeField.disabled = e.target.checked;
            if (e.target.checked) endTimeField.value = '';
        }
    });

    document.getElementById('addProcessRowBtn').addEventListener('click', function () {
        const noProcessRow = document.getElementById('noProcessRow');
        if (noProcessRow) noProcessRow.remove();

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><input type="text" name="processes[${processIndex}][name]" class="form-control form-control-sm" placeholder="Prozess Name" required></td>
            <td><input type="datetime-local" name="processes[${processIndex}][start_time]" class="form-control form-control-sm w-auto" required></td>
            <td><input type="datetime-local" name="processes[${processIndex}][end_time]" class="form-control form-control-sm process-end-time-field w-auto" disabled></td>
            <td class="text-center"><input type="checkbox" class="process-still-running form-check-input" checked></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm removeProcessRowBtn">×</button></td>
        `;
        processTbody.appendChild(newRow);
        processIndex++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('removeProcessRowBtn')) {
            const row = e.target.closest('tr');
            const idField = row.querySelector('input[name*="[id]"]');
            const deleteField = row.querySelector('input[name*="[delete]"]');
            if (idField) {
                if (deleteField) deleteField.value = 'true';
                row.style.display = 'none';
            } else {
                row.remove();
            }
        }
    });

    // ========== OTHER REASON ==========
    window.toggleOtherReason = function () {
        const reasonSelect = document.getElementById('reason');
        const otherReasonDiv = document.getElementById('otherReasonDiv');
        const otherReasonInput = document.getElementById('otherReason');
        if (reasonSelect.value === 'Sonstiges') {
            otherReasonDiv.classList.remove('d-none');
            otherReasonInput.required = true;
        } else {
            otherReasonDiv.classList.add('d-none');
            otherReasonInput.required = false;
            otherReasonInput.value = '';
        }
    };
});
</script>
@endsection