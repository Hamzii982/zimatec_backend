@extends('user.layouts.index')

@section('title', $problem->problem_uid)

@section('content')
<div class="container py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('printer-problems.index') }}" class="text-muted text-decoration-none small">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
                <span class="text-muted small">/</span>
                <h5 class="mb-0 fw-semibold">{{ $problem->problem_uid }}</h5>
                <span class="{{ $problem->status == 'open' ? 'badge bg-danger' : 'badge bg-success' }}">
                    {{ $problem->status == 'open' ? 'Offen' : 'Geschlossen' }}
                </span>
            </div>
            <p class="text-muted small mb-0">
                Erstellt am {{ $problem->created_at->format('d.m.Y H:i') }}
                von {{ $problem->creator->name ?? '—' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('printer-problems.edit', $problem->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Bearbeiten
            </a>
            <form method="POST" action="{{ route('printer-problems.destroy', $problem->id) }}"
                    onsubmit="return confirm('Problem wirklich löschen?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash me-1"></i> Löschen
                </button>
            </form>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- ── Left column ──────────────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Auftragsinformationen --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-semibold">Auftragsinformationen</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted fw-normal">Auftragsnummer</dt>
                        <dd class="col-sm-8">{{ $problem->order_number ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Kennzeichnung</dt>
                        <dd class="col-sm-8">{{ $problem->designation ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Versionsnummer</dt>
                        <dd class="col-sm-8 mb-0">{{ $problem->version_number ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Maschineneinstellungen --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-semibold">Maschineneinstellungen</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted fw-normal">Düsendesign</dt>
                        <dd class="col-sm-8">{{ $problem->design_nozzle_diameter ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Düsenwerkzeug</dt>
                        <dd class="col-sm-8">{{ $problem->tool_nozzle_diameter ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Material</dt>
                        <dd class="col-sm-8">{{ $problem->material ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Drucktemperatur</dt>
                        <dd class="col-sm-8">
                            {{ $problem->print_temperature ? $problem->print_temperature . ' °C' : '—' }}
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Tischtemperatur</dt>
                        <dd class="col-sm-8">
                            {{ $problem->bed_temperature ? $problem->bed_temperature . ' °C' : '—' }}
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Höhe Düse</dt>
                        <dd class="col-sm-8">
                            {{ $problem->nozzle_height ? $problem->nozzle_height . ' mm' : '—' }}
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Bahnoffset X / Y / Z</dt>
                        <dd class="col-sm-8">
                            {{ $problem->offset_x ?? '—' }} /
                            {{ $problem->offset_y ?? '—' }} /
                            {{ $problem->offset_z ?? '—' }} mm
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Wartung gemacht</dt>
                        <dd class="col-sm-8 mb-0">
                            @if ($problem->maintenance_completed)
                                <span class="badge bg-success-subtle text-success">Ja</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Nein</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Fehlerdetails --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-semibold">Fehlerdetails</h6>
                </div>
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">Fehler-ID</dt>
                        <dd class="col-sm-8">{{ $problem->error_id ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Kurzbeschreibung</dt>
                        <dd class="col-sm-8">{{ $problem->short_description ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Bedienererklärung</dt>
                        <dd class="col-sm-8 mb-0" style="white-space: pre-wrap;">
                            {{ $problem->operator_explanation ?? '—' }}
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Email history (populated in Phase 4) --}}
            @if ($problem->emails->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-2">
                        <h6 class="mb-0 fw-semibold">Kommunikationsverlauf</h6>
                    </div>
                    <div class="card-body p-0">
                        @foreach ($problem->emails as $email)
                            <div class="px-3 py-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <span class="badge bg-light text-dark border me-1">
                                            {{ $email->email_type == 'incoming' ? 'Eingehend' : 'Ausgehend' }}
                                        </span>
                                        <span class="small fw-medium">
                                            {{ $email->subject ?? '(kein Betreff)' }}
                                        </span>
                                    </div>
                                    <span class="text-muted small">
                                        {{ $email->created_at->format('d.m.Y H:i') }}
                                    </span>
                                </div>
                                <p class="small text-muted mb-0" style="white-space: pre-wrap;">
                                    {{ Str::limit($email->body, 300) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        {{-- ── Right column ─────────────────────────────────────────────── --}}
        <div class="col-lg-4">

            {{-- AI suggestions (populated in Phase 3) --}}
            @if ($problem->issue_type || $problem->ai_troubleshooting || $problem->ai_next_steps)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-2">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-stars me-1 text-warning"></i>KI-Analyse
                        </h6>
                    </div>
                    <div class="card-body small">
                        @if ($problem->issue_type)
                            <p class="mb-1 fw-medium text-muted">Problemtyp</p>
                            <p class="mb-3">{{ $problem->issue_type }}</p>
                        @endif
                        @if ($problem->ai_troubleshooting)
                            <p class="mb-1 fw-medium text-muted">Fehlerbehebung</p>
                            <p class="mb-3" style="white-space: pre-wrap;">{{ $problem->ai_troubleshooting }}</p>
                        @endif
                        @if ($problem->ai_next_steps)
                            <p class="mb-1 fw-medium text-muted">Nächste Schritte</p>
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $problem->ai_next_steps }}</p>
                        @endif

                        <hr class="my-3">
                        @include('user.printer-problems.partials._emails_modal')
                    </div>
                </div>
            @endif

            {{-- ── Attachments ──────────────────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-paperclip me-1"></i>Anhänge
                        @if ($problem->attachments->isNotEmpty())
                            <span class="badge bg-secondary-subtle text-secondary ms-1">
                                {{ $problem->attachments->count() }}
                            </span>
                        @endif
                    </h6>
                </div>

                {{-- Upload form --}}
                <div class="card-body border-bottom pb-3">
                    <form method="POST"
                          action="{{ route('printer-problems.attachments.store', $problem->id) }}"
                          enctype="multipart/form-data"
                          id="attachment-upload-form">
                        @csrf

                        <div class="mb-2">
                            <label for="files" class="form-label small fw-medium mb-1">
                                Dateien hochladen
                                <span class="text-muted fw-normal">(JPEG, PNG, WEBP, PDF — max. 20 MB)</span>
                            </label>

                            {{-- Drop zone --}}
                            <div id="drop-zone"
                                 class="border border-dashed rounded p-3 text-center text-muted small"
                                 style="cursor:pointer; border-style: dashed !important;">
                                <i class="bi bi-cloud-upload fs-4 d-block mb-1"></i>
                                Dateien hier ablegen oder
                                <span class="text-primary" style="cursor:pointer;"
                                      onclick="document.getElementById('files').click()">
                                    auswählen
                                </span>
                            </div>

                            <input type="file"
                                   name="files[]"
                                   id="files"
                                   multiple
                                   accept=".jpg,.jpeg,.png,.webp,.pdf"
                                   class="d-none"
                                   onchange="handleFileSelect(this)">

                            {{-- Preview list --}}
                            <ul id="file-preview" class="list-unstyled mb-0 mt-2 small"></ul>
                        </div>

                        <button type="submit"
                                id="upload-btn"
                                class="btn btn-sm btn-primary w-100 d-none">
                            <i class="bi bi-upload me-1"></i>
                            Hochladen
                        </button>

                    </form>
                </div>

                {{-- Existing attachments --}}
                @if ($problem->attachments->isNotEmpty())
                    <ul class="list-group list-group-flush">
                        @foreach ($problem->attachments as $attachment)
                            <li class="list-group-item py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between gap-2">

                                    {{-- Icon + name --}}
                                    <div class="d-flex align-items-center gap-2 text-truncate">
                                        @if ($attachment->type === 'image')
                                            <i class="bi bi-image text-primary shrink-0"></i>
                                        @else
                                            <i class="bi bi-file-earmark-pdf text-danger shrink-0"></i>
                                        @endif
                                        <span class="small text-truncate" title="{{ $attachment->file_name }}">
                                            {{ $attachment->file_name }}
                                        </span>
                                    </div>

                                    {{-- Size + actions --}}
                                    <div class="d-flex align-items-center gap-1 shrink-0">
                                        <span class="text-muted" style="font-size: 0.7rem;">
                                            {{ number_format($attachment->file_size / 1024, 0) }} KB
                                        </span>
                                        <a href="{{ route('printer-problems.attachments.download', [$problem->id, $attachment->id]) }}"
                                           class="btn btn-sm btn-outline-secondary py-0 px-1"
                                           title="Herunterladen">
                                            <i class="bi bi-download" style="font-size: 0.75rem;"></i>
                                        </a>
                                        <form method="POST"
                                              action="{{ route('printer-problems.attachments.destroy', [$problem->id, $attachment->id]) }}"
                                              onsubmit="return confirm('Anhang löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger py-0 px-1"
                                                    title="Löschen">
                                                <i class="bi bi-trash" style="font-size: 0.75rem;"></i>
                                            </button>
                                        </form>
                                    </div>

                                </div>

                                {{-- Image thumbnail --}}
                                @if ($attachment->type === 'image')
                                    <div class="mt-2">
                                        <img src="{{ route('printer-problems.attachments.download', [$problem->id, $attachment->id]) }}"
                                             alt="{{ $attachment->file_name }}"
                                             class="rounded"
                                             style="max-width: 100%; max-height: 140px; object-fit: cover;">
                                    </div>
                                @endif

                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="card-body text-center text-muted small py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Noch keine Anhänge vorhanden.
                    </div>
                @endif

            </div>{{-- /attachments card --}}

        </div>{{-- /right column --}}
    </div>{{-- /row --}}
</div>

@push('scripts')
<script>
    // ── Drag & drop + file picker preview ──────────────────────────────────
    const dropZone  = document.getElementById('drop-zone');
    const fileInput = document.getElementById('files');
    const preview   = document.getElementById('file-preview');
    const uploadBtn = document.getElementById('upload-btn');

    ['dragenter', 'dragover'].forEach(e => {
        dropZone.addEventListener(e, ev => {
            ev.preventDefault();
            dropZone.classList.add('border-primary', 'bg-light');
        });
    });

    ['dragleave', 'drop'].forEach(e => {
        dropZone.addEventListener(e, ev => {
            ev.preventDefault();
            dropZone.classList.remove('border-primary', 'bg-light');
        });
    });

    dropZone.addEventListener('drop', ev => {
        ev.preventDefault();
        // Assign dropped files to the input using DataTransfer
        fileInput.files = ev.dataTransfer.files;
        renderPreview(fileInput.files);
    });

    function handleFileSelect(input) {
        renderPreview(input.files);
    }

    function renderPreview(files) {
        preview.innerHTML = '';
        if (!files.length) {
            uploadBtn.classList.add('d-none');
            return;
        }

        Array.from(files).forEach(file => {
            const li = document.createElement('li');
            li.className = 'd-flex align-items-center gap-2 py-1 border-bottom';

            const icon = file.type.startsWith('image/')
                ? '<i class="bi bi-image text-primary"></i>'
                : '<i class="bi bi-file-earmark-pdf text-danger"></i>';

            const size = (file.size / 1024).toFixed(0) + ' KB';
            li.innerHTML = `${icon} <span class="text-truncate flex-grow-1">${file.name}</span>
                            <span class="text-muted">${size}</span>`;
            preview.appendChild(li);
        });

        uploadBtn.classList.remove('d-none');
    }
</script>
@endpush

@endsection