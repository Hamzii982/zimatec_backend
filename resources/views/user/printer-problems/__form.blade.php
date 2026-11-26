<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif
    
    {{-- hidden defaults --}}
    <input type="hidden" name="design_nozzle_diameter" value="0">
    <input type="hidden" name="offset_x" value="0">
    <input type="hidden" name="offset_y" value="0">
    
    {{-- ================= STEP 1 ================= --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between">
            <strong>1. Auftragsinformationen</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#step1">
                Öffnen
            </button>
        </div>
    
        <div id="step1" class="collapse {{ ($mode ?? '') === 'edit' ? 'show' : 'show' }}">
            <div class="card-body">
    
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Auftragsnummer *</label>
                        <input class="form-control" name="order_number"
                            value="{{ old('order_number', $problem->order_number ?? '') }}" required>
                    </div>
    
                    <div class="col-md-4">
                        <label class="form-label">Kennzeichnung *</label>
                        <input class="form-control" name="designation"
                            value="{{ old('designation', $problem->designation ?? '') }}" required>
                    </div>
    
                    <div class="col-md-4">
                        <label class="form-label">Versionsnummer *</label>
                        <input class="form-control" name="version_number"
                            value="{{ old('version_number', $problem->version_number ?? '') }}" required>
                    </div>
                </div>
    
            </div>
        </div>
    </div>
    
    {{-- ================= STEP 2 ================= --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between">
            <strong>2. Maschineneinstellungen</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#step2">
                Öffnen
            </button>
        </div>
    
        <div id="step2" class="collapse {{ ($mode ?? '') === 'edit' ? 'show' : '' }}">
            <div class="card-body">
    
                <div class="row g-3">
    
                    <div class="col-md-4">
                        <label class="form-label">Düsenwerkzeug</label>
                        <input class="form-control" name="tool_nozzle_diameter"
                            value="{{ old('tool_nozzle_diameter', $problem->tool_nozzle_diameter ?? '') }}">
                    </div>
    
                    <div class="col-md-4">
                        <label class="form-label">Material</label>
                        <input class="form-control" name="material"
                            value="{{ old('material', $problem->material ?? '') }}">
                    </div>
    
                    <div class="col-md-4">
                        <label class="form-label">Düsenhöhe</label>
                        <input class="form-control" name="nozzle_height"
                            value="{{ old('nozzle_height', $problem->nozzle_height ?? '') }}">
                    </div>
    
                    <div class="col-md-4">
                        <label class="form-label">Drucktemperatur</label>
                        <input type="number" step="0.01" class="form-control"
                            name="print_temperature"
                            value="{{ old('print_temperature', $problem->print_temperature ?? '') }}">
                    </div>
    
                    <div class="col-md-4">
                        <label class="form-label">Tischtemperatur</label>
                        <input type="number" step="0.01" class="form-control"
                            name="bed_temperature"
                            value="{{ old('bed_temperature', $problem->bed_temperature ?? '') }}">
                    </div>
    
                    <div class="col-md-4">
                        <label class="form-label">Bahnoffset Z</label>
                        <input type="number" step="0.001" class="form-control"
                            name="offset_z"
                            value="{{ old('offset_z', $problem->offset_z ?? 0) }}">
                    </div>
    
                </div>
    
            </div>
        </div>
    </div>
    
    {{-- ================= STEP 3 ================= --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between">
            <strong>3. Fehlerbeschreibung</strong>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                Öffnen
            </button>
        </div>
    
        <div id="step3" class="collapse {{ ($mode ?? '') === 'edit' ? 'show' : '' }}">
            <div class="card-body">
    
                <div class="mb-3">
                    <label class="form-label">Fehler-ID</label>
                    <input class="form-control" name="machine_error_id"
                        value="{{ old('machine_error_id', $problem->machine_error_id ?? '') }}">
                </div>
    
                <div class="mb-3">
                    <label class="form-label">Kurzbeschreibung *</label>
                    <input class="form-control" name="short_description"
                        value="{{ old('short_description', $problem->short_description ?? '') }}" required>
                </div>
    
                <div>
                    <label class="form-label">Beschreibung</label>
                    <textarea class="form-control" rows="5"
                        name="operator_explanation">{{ old('operator_explanation', $problem->operator_explanation ?? '') }}</textarea>
                </div>
    
            </div>
        </div>
    </div>
    
    {{-- ================= STEP 4 ================= --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between">
            <strong>4. Wartungsprüfung</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#step4">
                Öffnen
            </button>
        </div>
    
        <div id="step4" class="collapse {{ ($mode ?? '') === 'edit' ? 'show' : '' }}">
            <div class="card-body">
    
                <div class="alert alert-info">
                    Vor der Meldung bitte prüfen:
                    <ul class="mb-0">
                        <li>Trockner</li>
                        <li>Waage</li>
                        <li>Vakuumpumpe</li>
                        <li>Sicherheit</li>
                    </ul>
                </div>
    
                <div class="form-check">
                    <input type="hidden" name="maintenance_completed" value="0">
    
                    <input class="form-check-input"
                        type="checkbox"
                        id="maintenance_completed"
                        name="maintenance_completed"
                        value="1"
                        {{ old('maintenance_completed', $problem->maintenance_completed ?? false) ? 'checked' : '' }}>
    
                    <label class="form-check-label">
                        Wartung durchgeführt
                    </label>
                </div>
    
            </div>
        </div>
    </div>
    
    {{-- ================= STEP 5 ================= --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
    
            <a href="{{ route('printer-problems.index') }}" class="btn btn-outline-secondary">
                Zurück
            </a>
    
            <button type="submit" class="btn btn-success px-5">
                Problem speichern & KI Analyse starten
            </button>
    
        </div>
    </div>
    
    </form>
    
    {{-- ================= STYLE ================= --}}
    <style>
    .card{
        border-radius:14px;
    }
    
    .card-header{
        cursor:pointer;
    }
    
    .form-label{
        font-weight:600;
    }
    
    .collapse{
        transition: all .25s ease-in-out;
    }
    </style>
    
    {{-- ================= OPTIONAL JS (progress update hook) ================= --}}
    <script>
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
        btn.addEventListener('click', () => {
            setTimeout(() => {
                const openSteps = document.querySelectorAll('.collapse.show').length;
                const progress = (openSteps / 4) * 100;
                const bar = document.getElementById('progress-bar');
                if(bar) bar.style.width = progress + '%';
            }, 300);
        });
    });
    </script>