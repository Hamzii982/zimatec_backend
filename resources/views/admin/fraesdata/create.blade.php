@extends('admin.layouts.index')

@section('content')
<div class="container mt-4 mb-5 zt-fraesdata-form">
    <div class="card zt-card shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h5 mb-2">{{ __('fraesdata.upload.title') }}</h1>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="project-name">{{ $project->project_name }}</span>
                    @if($project->auftragsnummer_zt)
                        <span class="zt-tag"><strong>ZT</strong>{{ $project->auftragsnummer_zt }}</span>
                    @endif
                    @if($project->auftragsnummer_zf)
                        <span class="zt-tag"><strong>ZF</strong>{{ $project->auftragsnummer_zf }}</span>
                    @endif
                    <span class="target-tag">
                        <i class="bi {{ $position ? 'bi-list-ol' : 'bi-box-seam' }} me-1"></i>
                        <strong>{{ $position ? __('fraesdata.position') : __('fraesdata.bauteil') }}:</strong>
                        {{ $position?->name ?? $bauteil?->name }}
                    </span>
                </div>
            </div>
            <a href="{{ route('admin.projects.show', $project) }}" class="zt-btn zt-btn--header">
                <i class="bi bi-arrow-left"></i>{{ __('fraesdata.upload.cancel') }}
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.fraesdata.store', $project) }}" method="POST" enctype="multipart/form-data">
                @csrf

                @if($position)
                    <input type="hidden" name="position_id" value="{{ $position->id }}">
                @else
                    <input type="hidden" name="bauteil_id" value="{{ $bauteil->id }}">
                @endif

                <div class="zt-form-section mb-3">
                    <h2 class="zt-section-title">{{ __('fraesdata.upload.document_details') }}</h2>
                    <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="zt-form-label">{{ __('fraesdata.upload.document_name') }}</label>
                        <input type="text" name="name" id="name" class="form-control zt-select" maxlength="255" required value="{{ old('name') }}">
                        @error('name')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="document" class="zt-form-label">{{ __('fraesdata.upload.file') }}</label>
                        <input type="file" name="document" id="document" class="form-control zt-select" accept=".pdf,.jpg,.jpeg,.xml,application/pdf,image/jpeg,application/xml,text/xml" required>
                        <div class="form-text">{{ __('fraesdata.upload.file_help') }}</div>
                        @error('document')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    </div>
                </div>

                @if($bauteil)
                    <div class="zt-form-section mb-3">
                        <label for="related_position_id" class="zt-form-label">{{ __('fraesdata.upload.related_position') }}</label>
                        <select name="related_position_id" id="related_position_id" class="form-select zt-select">
                            <option value="">{{ __('fraesdata.upload.no_related_position') }}</option>
                            @foreach($positions as $projectPosition)
                                <option value="{{ $projectPosition->id }}" @selected(old('related_position_id', $relatedPositionId) == $projectPosition->id)>
                                    {{ $projectPosition->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('related_position_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="zt-form-section mb-3">
                    <label for="material-search" class="zt-form-label">{{ __('fraesdata.upload.material_search') }}</label>
                    <input
                        type="search"
                        id="material-search"
                        class="form-control zt-select"
                        autocomplete="off"
                        placeholder="{{ __('fraesdata.upload.material_search_hint') }}"
                        aria-describedby="material-search-status"
                    >
                    <div id="material-search-status" class="form-text" role="status" aria-live="polite"></div>
                    <div id="material-search-results" class="list-group mt-2" role="list"></div>
                </div>

                <div class="zt-form-section mb-3">
                    <h2 class="zt-section-title">{{ __('fraesdata.upload.selected_materials') }}</h2>
                    <div id="selected-materials" class="d-grid gap-2"></div>
                    @error('materials')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @foreach($errors->get('materials.*.material_id') as $messages)
                        @foreach($messages as $message)
                            <div class="text-danger small">{{ $message }}</div>
                        @endforeach
                    @endforeach
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('admin.projects.show', $project) }}" class="zt-btn zt-btn--ghost">
                        {{ __('fraesdata.upload.cancel') }}
                    </a>
                    <button type="submit" class="zt-btn zt-btn--primary">
                        <i class="bi bi-upload me-1"></i>{{ __('fraesdata.upload.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .zt-fraesdata-form {
        --zt-ink: #1B1F24;
        --zt-line: #DFE3E8;
        --zt-bg: #F5F6F8;
        --zt-muted: #667085;
        color: var(--zt-ink);
    }

    .zt-fraesdata-form .zt-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-fraesdata-form .zt-card > .card-header { background: var(--zt-ink); color: #fff; border-bottom: 0; padding: 1rem 1.25rem; }
    .zt-fraesdata-form .zt-card > .card-body { background: var(--zt-bg); padding: 1.25rem; }
    .zt-fraesdata-form .project-name { font-size: .9rem; font-weight: 600; }
    .zt-fraesdata-form .zt-tag,
    .zt-fraesdata-form .target-tag {
        display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
        padding: .25rem .65rem; font-size: .74rem; font-weight: 500;
    }
    .zt-fraesdata-form .zt-tag { border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.08); color: #fff; }
    .zt-fraesdata-form .zt-tag strong { color: #b8d7ff; font-size: .68rem; }
    .zt-fraesdata-form .target-tag { border: 1px solid rgba(255,255,255,.25); background: #27313b; color: #f3f5f7; }
    .zt-fraesdata-form .target-tag strong { color: #fff; }
    .zt-fraesdata-form .zt-form-section { background: #fff; border: 1px solid var(--zt-line); border-radius: 9px; padding: 1rem; }
    .zt-fraesdata-form .zt-section-title { color: var(--zt-ink); font-size: .9rem; font-weight: 650; margin: 0 0 .85rem; }
    .zt-fraesdata-form .zt-form-label { display: block; color: var(--zt-muted); font-size: .75rem; font-weight: 600; margin-bottom: .35rem; }
    .zt-fraesdata-form .zt-select { border: 1px solid var(--zt-line); border-radius: 8px; color: var(--zt-ink); font-size: .86rem; }
    .zt-fraesdata-form .zt-select:focus { border-color: #667085; box-shadow: 0 0 0 .15rem rgba(102,112,133,.12); }
    .zt-fraesdata-form .zt-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem; border-radius: 8px;
        border: 1px solid var(--zt-line); font-size: .82rem; font-weight: 600; padding: .45rem .85rem;
        text-decoration: none; cursor: pointer;
    }
    .zt-fraesdata-form .zt-btn--primary { background: var(--zt-ink); border-color: var(--zt-ink); color: #fff; }
    .zt-fraesdata-form .zt-btn--primary:hover { background: #000; color: #fff; }
    .zt-fraesdata-form .zt-btn--ghost { background: #fff; color: var(--zt-muted); }
    .zt-fraesdata-form .zt-btn--header { background: transparent; border-color: rgba(255,255,255,.35); color: #fff; }
    .zt-fraesdata-form .zt-btn--header:hover { background: rgba(255,255,255,.1); color: #fff; }
    .zt-fraesdata-form .material-search-result { color: var(--zt-ink); }
    .zt-fraesdata-form .material-search-result:hover { background: #f5f8fc; }
    .zt-fraesdata-form .material-result-code,
    .zt-fraesdata-form .selected-material-code { background: #e7eeff; border: 1px solid #dce6fa; border-radius: 999px; color: #2e5aac; font-size: .7rem; font-weight: 600; padding: .15rem .5rem; }
    .zt-fraesdata-form .zt-material-card { border: 1px solid var(--zt-line); border-radius: 8px; }
</style>
@endpush

@push('scripts')
@php
    $materialSearchMessages = [
        'noMaterials' => __('fraesdata.upload.no_materials'),
        'searchError' => __('fraesdata.upload.search_error'),
        'toolNumber' => __('fraesdata.upload.tool_number'),
        'notes' => __('fraesdata.upload.notes'),
        'articleNumber' => __('fraesdata.upload.article_number'),
        'remove' => __('fraesdata.upload.remove'),
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const endpoint = @json(route('admin.fraesdata.materials.search'));
    const messages = @json($materialSearchMessages);
    const search = document.getElementById('material-search');
    const status = document.getElementById('material-search-status');
    const results = document.getElementById('material-search-results');
    const selected = document.getElementById('selected-materials');
    let rowIndex = 0;
    let debounce;
    let activeRequest;

    function addMaterial(material) {
        const row = document.createElement('div');
        row.className = 'card card-body p-2 zt-material-card';

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = `materials[${rowIndex}][material_id]`;
        hidden.value = material.id;

        const heading = document.createElement('div');
        heading.className = 'd-flex justify-content-between align-items-center gap-2 mb-2';

        const materialLabel = document.createElement('div');
        materialLabel.className = 'd-flex flex-wrap align-items-center gap-2';
        const name = document.createElement('strong');
        name.textContent = material.name;
        if (material.is_werkzeug) {
            const toolBadge = document.createElement('span');
            toolBadge.className = 'badge bg-info text-dark ms-2';
            toolBadge.textContent = 'Werkzeug';
            name.append(toolBadge);
        }
        materialLabel.append(name);

        if (material.code) {
            const code = document.createElement('span');
            code.className = 'selected-material-code';
            code.textContent = `${messages.articleNumber} ${material.code}`;
            materialLabel.append(code);
        }

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-outline-danger btn-sm';
        remove.textContent = messages.remove;
        remove.addEventListener('click', () => row.remove());
        heading.append(materialLabel, remove);

        const fields = document.createElement('div');
        fields.className = 'row g-2';

        const numberWrapper = document.createElement('div');
        numberWrapper.className = 'col-md-4';
        const numberLabel = document.createElement('label');
        numberLabel.className = 'zt-form-label';
        numberLabel.textContent = messages.toolNumber;
        const number = document.createElement('input');
        number.type = 'text';
        number.maxLength = 64;
        number.className = 'form-control form-control-sm zt-select';
        number.name = `materials[${rowIndex}][tool_number]`;
        numberWrapper.append(numberLabel, number);

        const notesWrapper = document.createElement('div');
        notesWrapper.className = 'col-md-8';
        const notesLabel = document.createElement('label');
        notesLabel.className = 'zt-form-label';
        notesLabel.textContent = messages.notes;
        const notes = document.createElement('input');
        notes.type = 'text';
        notes.maxLength = 2000;
        notes.className = 'form-control form-control-sm zt-select';
        notes.name = `materials[${rowIndex}][notes]`;
        notesWrapper.append(notesLabel, notes);

        fields.append(numberWrapper, notesWrapper);
        row.append(hidden, heading, fields);
        selected.append(row);
        rowIndex += 1;
    }

    async function searchMaterials(term) {
        if (activeRequest) {
            activeRequest.abort();
        }

        activeRequest = new AbortController();
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('q', term);

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: activeRequest.signal,
            });

            if (!response.ok) {
                throw new Error(messages.searchError);
            }

            const payload = await response.json();
            results.replaceChildren();

            if (payload.data.length === 0) {
                status.textContent = messages.noMaterials;
                return;
            }

            status.textContent = '';
            payload.data.forEach((material) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action material-search-result';
                const resultRow = document.createElement('span');
                resultRow.className = 'd-flex justify-content-between align-items-center gap-2';
                const materialName = document.createElement('strong');
                materialName.textContent = material.name;
                resultRow.append(materialName);

                if (material.code) {
                    const code = document.createElement('span');
                    code.className = 'material-result-code';
                    code.textContent = `${messages.articleNumber} ${material.code}`;
                    resultRow.append(code);
                }

                button.append(resultRow);
                button.addEventListener('click', () => addMaterial(material));
                results.append(button);
            });
        } catch (error) {
            if (error.name !== 'AbortError') {
                status.textContent = messages.searchError;
            }
        }
    }

    search.addEventListener('input', () => {
        window.clearTimeout(debounce);
        results.replaceChildren();
        const term = search.value.trim();

        if (term.length < 2) {
            status.textContent = '';
            return;
        }

        status.textContent = '';
        debounce = window.setTimeout(() => searchMaterials(term), 250);
    });
});
</script>
@endpush
