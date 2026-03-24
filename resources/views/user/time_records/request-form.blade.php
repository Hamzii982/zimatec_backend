@extends('user.layouts.index')

@section('content')
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Änderungsantrag für Datensatz</h5>
                {{-- <a href="{{ route('time-records.list') }}" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Alle Aufzeichnung
                </a> --}}
            </div>
            <div class="card-body">

                <form action="{{ route('time-records.store-change-request', $record->id) }}" method="POST">
                    @csrf
    
                    <div class="d-flex flex-wrap align-items-center gap-4 mb-3 text-secondary">
        
                        <div>
                            <i class="bi bi-person-fill me-1 text-dark"></i>
                            <strong>Bediener:</strong> {{ $record->user->name }}
                        </div>
                    
                        <div>
                            <i class="bi bi-folder-fill me-1 text-primary"></i>
                            <strong>Projekt:</strong> {{ $record->project->project_name }}
                        </div>
                    
                        <div>
                            <i class="bi bi-cpu-fill me-1 text-success"></i>
                            <strong>Maschine:</strong> {{ $record->machine->name }}
                        </div>
                        <!-- New Start & End time inputs -->
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-clock-fill me-1 text-danger"></i>
                            <strong>Anfang:</strong>
                            <input type="datetime-local" name="record_start_time" 
                                value="{{ \Carbon\Carbon::parse($record->start_time)->format('Y-m-d\TH:i') }}" 
                                class="form-control form-control-sm">
                        </div>

                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-clock-fill me-1 text-warning"></i>
                            <strong>Beendet:</strong>
                            <input type="datetime-local" name="record_end_time" id="mainEndTime"
                                value="{{ $record->end_time ? \Carbon\Carbon::parse($record->end_time)->format('Y-m-d\TH:i') : '' }}" 
                                class="form-control form-control-sm">
                        </div>
                    </div>
                    <hr>

                    <h5 class="fw-semibold">Aktuelle Logs Bearbeiten</h5>

                    <table class="table table-striped mt-4">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Läuft noch</th>
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
                                        <input type="datetime-local" name="logs[{{ $index }}][start_time]" 
                                            value="{{ $log->start_time }}" class="form-control w-auto">
                                    </td>
                                    <td>
                                        <input type="datetime-local" name="logs[{{ $index }}][end_time]" 
                                            value="{{ $log->end_time ? $log->end_time : '' }}" 
                                            class="form-control end-time-field w-auto"
                                            {{ $log->end_time ? '' : 'disabled' }}>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="still-running" 
                                            {{ is_null($log->end_time) ? 'checked' : '' }}>
                                    </td>
                                    <td><button type="button" class="btn btn-danger btn-sm removeRowBtn">×</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <button type="button" id="addRowBtn" class="btn btn-outline-primary mb-3">+ Neues Protokoll hinzufügen</button>

                    <div class="mb-3">
                        <label for="reason" class="form-label"><strong>Grund für die Änderung:</strong></label>
                        <select name="reason" id="reason" class="form-select" required onchange="toggleOtherReason()">
                            <option value="">-- Grund wählen --</option>
                            <option value="Vergessen an und aus zu stechen">Vergessen an und aus zu stechen</option>
                            <option value="Fehler">Fehler</option>
                            <option value="Noch kein Stecher">Noch kein Stecher</option>
                            <option value="Technisches Problem">Technisches Problem</option>
                            <option value="In Besprechung erklären">In Besprechung erklären</option>
                            <option value="Sonstiges">Sonstiges</option>
                        </select>
                    </div>

                    <div class="mb-3" id="otherReasonDiv" style="display: none;">
                        <label for="otherReason" class="form-label"><strong>Sonstiger Grund:</strong></label>
                        <input type="text" name="other_reason" id="otherReason" class="form-control" placeholder="Bitte Grund angeben...">
                    </div>

                    <button type="submit" class="btn btn-success">Änderungsantrag einreichen</button>
                </form>
            </div>
        </div>
    </div>

    {{-- JS for adding new rows dynamically --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        const mainEndTime = document.getElementById('mainEndTime');
        const tbody = document.getElementById('logTableBody');

        function getVisibleRows() {
            return Array.from(tbody.querySelectorAll('tr'))
                .filter(row => row.style.display !== 'none');
        }

        function getLastVisibleRow() {
            const rows = getVisibleRows();
            return rows.length ? rows[rows.length - 1] : null;
        }

        function syncMainEndTime() {
            const lastRow = getLastVisibleRow();

            if (!lastRow) {
                mainEndTime.value = '';
                mainEndTime.classList.add('border-danger');
                return;
            }

            const endTimeField = lastRow.querySelector('.end-time-field');
            const stillRunning = lastRow.querySelector('.still-running');

            if (stillRunning && stillRunning.checked) {
                mainEndTime.value = '';
                mainEndTime.classList.add('border-danger');
            } else if (endTimeField && endTimeField.value) {
                mainEndTime.value = endTimeField.value;
                mainEndTime.classList.remove('border-danger');
            } else {
                mainEndTime.value = '';
                mainEndTime.classList.add('border-danger');
            }
        }

        // Listen to any change in log end times
        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('end-time-field')) {
                syncMainEndTime();
            }
        });

        // Listen to still running toggle
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('still-running')) {
                const row = e.target.closest('tr');
                const endTimeField = row.querySelector('.end-time-field');

                endTimeField.disabled = e.target.checked;
                if (e.target.checked) endTimeField.value = '';

                syncMainEndTime();
            }
        });

        // After adding new row
        document.getElementById('addRowBtn').addEventListener('click', function () {
            setTimeout(syncMainEndTime, 50);
        });

        // After removing row
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('removeRowBtn')) {
                setTimeout(syncMainEndTime, 50);
            }
        });

        // Initial sync on load
        syncMainEndTime();
        let logIndex = {{ $record->logs->count() }};

        // Handle "still running" toggle
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('still-running')) {
                const row = e.target.closest('tr');
                const endTimeField = row.querySelector('.end-time-field');
                endTimeField.disabled = e.target.checked;
                if (e.target.checked) endTimeField.value = '';
            }
        });

        // Add new log row
        document.getElementById('addRowBtn').addEventListener('click', function() {
            const tbody = document.getElementById('logTableBody');
            const newRow = document.createElement('tr');

            newRow.innerHTML = `
                <td>
                    <select name="logs[${logIndex}][status_id]" class="form-select" required>
                        @foreach (\App\Models\MachineStatus::where('active', true)->get() as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="datetime-local" name="logs[${logIndex}][start_time]" class="form-control w-auto" required></td>
                <td><input type="datetime-local" name="logs[${logIndex}][end_time]" class="form-control end-time-field w-auto" disabled></td>
                <td class="text-center"><input type="checkbox" class="still-running" checked></td>
                <td><button type="button" class="btn btn-danger btn-sm removeRowBtn">×</button></td>
            `;
            tbody.appendChild(newRow);
            logIndex++;
        });

        // Remove row
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('removeRowBtn')) {
                const row = e.target.closest('tr');
                const idField = row.querySelector('input[name*="[id]"]');
                const deleteField = row.querySelector('input[name*="[delete]"]');

                if(idField) {
                    if (deleteField) {
                        deleteField.value = "true";
                    } else {
                        // Add hidden delete field dynamically if missing
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = idField.name.replace('[id]', '[delete]');
                        hidden.value = 'true';
                        row.appendChild(hidden);
                    }
                    row.style.display = 'none';
                } else {
                    row.remove();
                }
            }
        });

        // Toggle other reason input
        window.toggleOtherReason = function() {
            const reasonSelect = document.getElementById('reason');
            const otherReasonDiv = document.getElementById('otherReasonDiv');
            const otherReasonInput = document.getElementById('otherReason');

            if (reasonSelect.value === 'Sonstiges') {
                otherReasonDiv.style.display = 'block';
                otherReasonInput.required = true;
            } else {
                otherReasonDiv.style.display = 'none';
                otherReasonInput.required = false;
                otherReasonInput.value = '';
            }
        };
    });
    </script>
@endsection