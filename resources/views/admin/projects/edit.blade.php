@extends('admin.layouts.index')

@section('content')
    <div class="container mt-4 zt-compare">
        <div class="card zt-card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h5 class="mb-0">Projekt bearbeiten</h5>
                <a href="{{ route('admin.projects') }}" class="zt-btn zt-btn--ghost btn-sm">
                    <i class="bi bi-arrow-left-circle"></i> Zurück
                </a>
            </div>
            <div class="card-body p-3">

                <form action="{{ route('admin.projects.update', $project) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Auftragsnummer (3+3 nested inside the 6-col half) + Status buttons (other half) --}}
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="zt-form-label">
                                Auftragsnummer <span class="text-muted fw-normal">(nur eine oder beide)</span>
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" name="auftragsnummer_zt" id="auftragsnummer_zt"
                                           class="form-control zt-select form-control-sm at-least-one"
                                           value="{{ old('auftragsnummer_zt', $project->auftragsnummer_zt) }}"
                                           placeholder="ZimaTec">
                                </div>
                                <div class="col-6">
                                    <input type="text" name="auftragsnummer_zf" id="auftragsnummer_zf"
                                           class="form-control zt-select form-control-sm at-least-one"
                                           value="{{ old('auftragsnummer_zf', $project->auftragsnummer_zf) }}"
                                           placeholder="Zimmermann Formtechnik">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="zt-form-label">Status *</label>
                            <div class="zt-status-group">
                                @php
                                    $selectedStatusId = old('project_status_id', $project->project_status_id ?? null);
                                @endphp
                                @foreach($statuses as $status)
                                    <input type="radio" class="btn-check" name="project_status_id"
                                           id="status_{{ $status->id }}" value="{{ $status->id }}"
                                           autocomplete="off"
                                           {{ $selectedStatusId == $status->id ? 'checked' : '' }}
                                           required>
                                    <label class="zt-status-btn" for="status_{{ $status->id }}"
                                           style="--status-color: {{ $status->color ?? '#667085' }}">
                                        {{ ucfirst($status->name) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Toggle + live folder-path preview --}}
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="toggle_path_preview">
                        <label class="form-check-label small text-muted" for="toggle_path_preview">
                            Ordnerstruktur
                        </label>
                    </div>
                    <div id="path_preview" class="zt-subcard zt-path-preview mb-3 is-hidden">
                        <i class="bi bi-folder2 me-2"></i><span id="path_preview_text">storage/app/[Kunde]/[Auftragsnummer]_[Projekt]</span>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label for="project_name" class="zt-form-label">Projektname *</label>
                            <input type="text" name="project_name" id="project_name" class="form-control zt-select form-control-sm"
                                   value="{{ old('project_name', $project->project_name) }}" placeholder="Projektname" required>
                        </div>
                        <div class="col-md-6">
                            <label for="kunde" class="zt-form-label">Kundenname</label>
                            <input type="text" name="kunde" id="kunde" class="form-control zt-select form-control-sm"
                                   value="{{ old('kunde', $project->kunde) }}" placeholder="Kundenname">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_time" class="zt-form-label">Projektstart</label>
                            <input type="datetime-local" name="start_time" id="start_time" class="form-control zt-select form-control-sm"
                                value="{{ old('start_time', isset($project) ? $project->start_time : '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="end_time" class="zt-form-label">Projektende</label>
                            <input type="datetime-local" name="end_time" id="end_time" class="form-control zt-select form-control-sm"
                                value="{{ old('end_time', isset($project) && $project->end_time ? $project->end_time : '') }}">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="zt-btn zt-btn--primary">Aktualisieren</button>
                    </div>
                </form>
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

        .zt-export-btn {
            display: inline-flex; align-items: center; gap: .4rem;
            border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: transparent;
            color: #fff; font-size: .8rem; font-weight: 500;
            padding: .4rem .75rem;
            transition: background .15s;
        }
        .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

        .zt-toggle-btn {
            display: inline-flex; align-items: center; border-radius: 8px; font-size: .8rem; font-weight: 500;
            padding: .35rem .75rem; border: 1px solid var(--zt-line); background: #fff; color: var(--zt-muted); text-decoration: none;
        }
        .zt-toggle-btn:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
        .zt-toggle-btn--warning { color: #92650B; border-color: #F3D9A6; }
        .zt-toggle-btn--warning-on { background: #FFF1D6; border-color: #F3D9A6; color: #92650B; }
        .zt-toggle-btn--on { background: var(--zt-ink); border-color: var(--zt-ink); color: #fff; }

        .zt-filter-form { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; padding: 1rem; }
        .zt-form-label { font-size: .72rem; font-weight: 600; color: var(--zt-muted); margin-bottom: .3rem; display: block; }
        .zt-select, select.zt-select, input.zt-select, textarea.zt-select {
            border: 1px solid var(--zt-line); border-radius: 8px; font-size: .84rem; background: #fff; color: var(--zt-ink);
        }
        .zt-select:focus { border-color: var(--zt-ink); box-shadow: none; }

        .zt-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
            border-radius: 8px; font-size: .82rem; font-weight: 500;
            padding: .4rem .9rem; border: 1px solid var(--zt-line); text-decoration: none; cursor: pointer;
        }
        .zt-btn--primary { background: var(--zt-ink, #1B1F24); color: #fff; border-color: var(--zt-ink, #1B1F24); }
        .zt-btn--primary:hover { background: #000; color: #fff; }
        .zt-btn--ghost { background: #fff; color: var(--zt-muted, #667085); }
        .zt-btn--ghost:hover { border-color: var(--zt-ink, #1B1F24); color: var(--zt-ink, #1B1F24); }
        .zt-btn--success { background: #1E7A46; border-color: #1E7A46; color: #fff; }
        .zt-btn--success:hover { background: #17603a; color: #fff; }

        .zt-empty { color: var(--zt-muted); font-size: .85rem; }

        .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
        .zt-table--excel thead th {
            font-size: .72rem; font-weight: 600; color: var(--zt-muted); text-transform: uppercase; letter-spacing: .03em;
            border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
        }
        .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; }
        .zt-row--danger { background: #FEF5F4; }
        .zt-row--muted { color: var(--zt-muted); }

        .zt-thumb-placeholder {
            width: 40px; height: 40px; border-radius: 6px; background: #EEF0F2;
            display: flex; align-items: center; justify-content: center; color: var(--zt-muted);
        }

        .zt-tag {
            display: inline-block; font-size: .72rem; font-weight: 500; color: var(--zt-ink);
            border: 1px solid var(--zt-line); border-radius: 999px; padding: .15rem .55rem;
            background: #FAFBFC;
        }

        .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
        .zt-badge--pending { background: #EEF0F2; color: var(--zt-muted); }
        .zt-badge--info { background: #E7EEFF; color: #2E5AAC; border: 1px solid #DCE6FA; }
        .zt-badge--warning { background: #FFF1D6; color: #92650B; border: 1px solid #F3D9A6; }
        .zt-badge--success { background: #E4F5EC; color: #1E7A46; }

        .zt-week-link { color: var(--zt-ink); text-decoration: none; }
        .zt-week-link:hover { text-decoration: underline; }

        .zt-icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--zt-line);
            background: #fff; color: var(--zt-muted);
        }
        .zt-icon-btn:hover { border-color: #2E5AAC; color: #2E5AAC; }
        .zt-icon-btn--edit:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
        .zt-icon-btn--danger { color: #B3261E; }
        .zt-icon-btn--danger:hover { border-color: #B3261E; background: #FBEAE9; }

        .zt-subcard { background: #FAFBFC; border: 1px solid var(--zt-line); padding: 1rem; }

        .zt-modal { border-radius: 10px; overflow: hidden; border: 1px solid #DFE3E8; }
        .zt-modal-header {
            background: #1B1F24;
            color: #fff;
            border-bottom: none;
        }
        .zt-modal-header .modal-title,
        .zt-modal-header h5,
        .zt-modal-header h6 {
            color: #fff;
        }

        .zt-pagination .pagination { margin-bottom: 0; }
        .zt-pagination .page-link { border: 1px solid var(--zt-line); color: var(--zt-ink); border-radius: 6px; margin: 0 .15rem; }
        .zt-pagination .page-item.active .page-link { background: var(--zt-ink); border-color: var(--zt-ink); }

        .zt-path-preview {
            font-family: monospace; font-size: .82rem; color: var(--zt-muted);
            transition: opacity .35s ease, filter .35s ease, max-height .35s ease, padding .35s ease, margin .35s ease;
            overflow: hidden;
        }
        .zt-path-preview.is-hidden {
            opacity: 0; filter: blur(4px); max-height: 0; padding-top: 0; padding-bottom: 0; margin-bottom: 0 !important; pointer-events: none;
        }

        .zt-status-group { display: flex; flex-wrap: wrap; gap: .45rem; }
        .zt-status-btn {
            cursor: pointer; user-select: none;
            display: inline-flex; align-items: center;
            padding: .35rem .85rem; border-radius: 999px;
            font-size: .8rem; font-weight: 600;
            border: 1px solid color-mix(in srgb, var(--status-color, var(--zt-muted)) 45%, white);
            background: color-mix(in srgb, var(--status-color, var(--zt-muted)) 14%, white);
            color: color-mix(in srgb, var(--status-color, var(--zt-muted)) 75%, black);
            transition: background .15s, color .15s, border-color .15s;
        }
        .zt-status-btn:hover { border-color: var(--status-color, var(--zt-ink)); }
        .btn-check:checked + .zt-status-btn {
            background: var(--status-color, var(--zt-ink));
            border-color: var(--status-color, var(--zt-ink));
            color: #fff;
        }
        .btn-check:focus-visible + .zt-status-btn { outline: 2px solid var(--status-color, var(--zt-ink)); outline-offset: 2px; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const kundeInput = document.getElementById("kunde");
            const auftragZt = document.getElementById("auftragsnummer_zt");
            const auftragZf = document.getElementById("auftragsnummer_zf");
            const projektInput = document.getElementById("project_name");
            const pathText = document.getElementById("path_preview_text");
            const pathPreview = document.getElementById("path_preview");
            const togglePreview = document.getElementById("toggle_path_preview");

            function updatePath() {
                const kunde = kundeInput.value.trim() || "[Kunde]";
                const auftrag = (auftragZt.value.trim() || auftragZf.value.trim()) || "[Auftragsnummer]";
                const projekt = projektInput.value.trim() || "[Projekt]";
                pathText.textContent = `storage/app/${kunde}/${auftrag}_${projekt}`;
            }

            [kundeInput, auftragZt, auftragZf, projektInput].forEach(el => el.addEventListener('input', updatePath));
            updatePath();

            togglePreview.addEventListener('change', function () {
                pathPreview.classList.toggle('is-hidden', !this.checked);
            });
        });
    </script>
@endsection