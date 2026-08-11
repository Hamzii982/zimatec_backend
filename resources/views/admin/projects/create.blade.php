@extends('admin.layouts.index')

@section('content')
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Neue Projekte Erstellen</h5>
                <a href="{{ route('admin.projects') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left-circle"></i> Zurück
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.projects.store') }}" method="POST">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="project_name" class="form-label fw-bold">Projektname *</label>
                            <input type="text" name="project_name" id="project_name" class="form-control" placeholder="Projektname" required>
                        </div>

                        <div class="col-md-6">
                            <label for="project_status_id" class="form-label fw-bold">Status *</label>
                            <select name="project_status_id" id="project_status_id" class="form-select" required>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}" {{ (old('project_status_id', $project->project_status_id ?? '') == $status->id) ? 'selected' : '' }}>
                                        {{ ucfirst($status->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="auftragsnummer_zt" class="form-label fw-bold">Auftragsnummer <span class="text-muted">(Nur ein oder beide eingeben)</span> *</label>
                        <div class="col-md-6">
                            <input type="text" name="auftragsnummer_zt" id="auftragsnummer_zt" class="form-control at-least-one" placeholder="ZimaTech" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="auftragsnummer_zf" id="auftragsnummer_zf" class="form-control at-least-one" placeholder="Zimmermann Formtechnik" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="kunde" class="form-label fw-bold">Kundenname</label>
                        <input type="text" name="kunde" id="kunde" class="form-control" placeholder="Kundenname">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_time" class="form-label fw-bold">Projektstart</label>
                            <input type="datetime-local" name="start_time" id="start_time" class="form-control"
                                value="{{ old('start_time', isset($project) ? $project->start_time->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    
                        <div class="col-md-6">
                            <label for="end_time" class="form-label fw-bold">Projektende</label>
                            <input type="datetime-local" name="end_time" id="end_time" class="form-control"
                                value="{{ old('end_time', isset($project) && $project->end_time ? $project->end_time->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    </div>

                    {{-- <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="save_to_db" name="save_to_db" value="1">
                        <label class="form-check-label" for="save_to_db">
                            Projekt im Datenbank speichern
                        </label>
                    </div> --}}
                    <input type="hidden" name="save_to_db" value="1">

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="attach_to_workflow" name="attach_to_workflow" value="1" checked>
                        <label class="form-check-label fw-semibold" for="attach_to_workflow">
                            Workflow zuordnen
                        </label>
                        <small class="form-text text-muted d-block">
                            Ordnet das neue Projekt automatisch der ersten aktiven Workflow-Stufe zu.
                            Wenn keine Workflow-Stufen konfiguriert sind, wird diese Option ignoriert.
                        </small>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-wechsel">Projekt Erstellen</button>
                    </div>
                </form>
            </div>
        </div>
        {{-- Folder Structure Preview --}}
        {{-- <div class="card mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Vorschau der Verzeichnisstruktur</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Hier wird gezeigt, wie Ihre Projektordner erstellt werden unter <code>storage/app/</code>.</p>
                
                <div class="bg-light p-3 rounded border" style="font-family: monospace;">
                    <div id="base-path" class="fw-bold text-dark">storage/app/[Kunde]/[Auftragsnummer]_[Projekt]</div>
                    <ul class="list-unstyled ms-4" id="dir-structure">
                        <li>001_Historie</li>
                        <li>01_Eingangsdaten/
                            <ul>
                                <li>Schriftliche_Freigabe</li>
                            </ul>
                        </li>
                        <li>02_Arbeitsverzeichnis_In Arbeit/
                            <ul>
                                <li>Teile_Bezeichnung</li>
                            </ul>
                        </li>
                        <li>03_Ausgangsdaten</li>
                        <li>04_Fraesdaten/
                            <ul id="fraesdaten">
                                <li>Bauteil 1/
                                    <ul>
                                        <li>Bearbeitungsplan</li>
                                        <li>Iges</li>
                                        <li>NC-Prg</li>
                                    </ul>
                                </li>
                                <li>Bauteil 2/ ... (up to 10)</li>
                            </ul>
                        </li>
                        <li>05_CAD-Daten zum Messen</li>
                        <li>06_Messberichte</li>
                        <li>07_Dokumentation</li>
                        <li>08_Temp/
                            <ul>
                                <li>Bauteil 1/
                                    <ul>
                                        <li>Bearbeitungsplan</li>
                                        <li>Iges</li>
                                        <li>NC-Prg</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div> --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.at-least-one');

            inputs.forEach(input => {
                input.addEventListener('input', function () {
                    // Check if at least one input has a value
                    const oneIsFilled = Array.from(inputs).some(el => el.value.trim() !== "");

                    inputs.forEach(el => {
                        // If one is filled, neither are strictly required by the browser
                        el.required = !oneIsFilled;
                    });
                });
            });
        });
    </script>
    
    {{-- Live JavaScript Preview --}}
    {{-- <script>
    document.addEventListener("DOMContentLoaded", function() {
        const kundeInput = document.getElementById("kunde");
        const auftragInput = document.getElementById("auftragsnummer_zt");
        const projektInput = document.getElementById("project_name");
        const basePath = document.getElementById("base-path");
    
        function updatePath() {
            const kunde = kundeInput.value.trim() || "[Kunde]";
            const auftrag = auftragInput.value.trim() || "[Auftragsnummer]";
            const projekt = projektInput.value.trim() || "[Projekt]";
            basePath.textContent = `storage/app/${kunde}/${auftrag}_${projekt}`;
        }
    
        kundeInput.addEventListener("input", updatePath);
        auftragInput.addEventListener("input", updatePath);
        projektInput.addEventListener("input", updatePath);
    });
    </script> --}}
@endsection
