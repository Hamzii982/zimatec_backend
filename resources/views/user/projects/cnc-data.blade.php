@extends('user.layouts.index')

@section('title', $project->project_name)

@section('content')
<div class="container mt-4 mb-5">
    <div class="card project-cnc-project-card shadow-sm border-0 mb-4">
        <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="project-cnc-project-line">
                <h1 class="h5 mb-0 project-cnc-project-name">
                    <i class="bi bi-folder2-open me-2"></i>{{ $project->project_name }}
                </h1>
                @if($project->auftragsnummer_zf)
                    <span class="zt-tag project-cnc-order-tag"><strong>ZF</strong>{{ $project->auftragsnummer_zf }}</span>
                @endif
                @if($project->auftragsnummer_zt)
                    <span class="zt-tag project-cnc-order-tag"><strong>ZT</strong>{{ $project->auftragsnummer_zt }}</span>
                @endif
                @if($latestProjectUpload)
                    @php($latestUpload = \Carbon\Carbon::parse($latestProjectUpload))
                    <span class="project-cnc-recent-tag">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        <span class="project-cnc-recent-label">{{ __('fraesdata.recent_upload') }}:</span>
                        <time datetime="{{ $latestUpload->toIso8601String() }}">{{ $latestUpload->format('d.m.Y H:i') }}</time>
                    </span>
                @endif
            </div>
            <a href="{{ route('projects') }}" class="btn btn-outline-light btn-sm project-cnc-back">
                <i class="bi bi-arrow-left me-1"></i>{{ __('fraesdata.projects_link') }}
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3 mb-4">
                <section class="col-lg-6" aria-labelledby="positions-heading">
                    <div class="card target-list-card h-100">
                        <div class="card-header target-list-header d-flex justify-content-between align-items-center">
                            <h2 id="positions-heading" class="h6 mb-0">
                                <i class="bi bi-list-ol me-2"></i>{{ __('fraesdata.positions') }}
                            </h2>
                            <span class="badge target-count-badge rounded-pill">{{ $project->positions->count() }}</span>
                        </div>
                        <div class="list-group list-group-flush">
                        @forelse($project->positions as $position)
                            @php($latestDocument = $position->fraesdata->sortByDesc('created_at')->first())
                            <a
                                href="{{ route('projects.cnc-data', ['project' => $project, 'target_type' => 'position', 'target_id' => $position->id]) }}"
                                class="list-group-item list-group-item-action target-list-item {{ $targetType === 'position' && $selectedTarget?->is($position) ? 'active' : '' }}"
                            >
                                <span class="target-name">
                                    <i class="bi bi-chevron-right me-2"></i>{{ $position->name }}
                                </span>
                                @if($latestDocument)
                                    <span class="zt-tag target-update-badge">
                                        <i class="bi bi-clock-history me-1"></i>{{ __('fraesdata.latest_upload') }}
                                        {{ $latestDocument->created_at->format('d.m.Y H:i') }}
                                    </span>
                                @else
                                    <span class="zt-tag target-no-update-badge">
                                        <i class="bi bi-file-earmark me-1"></i>{{ __('fraesdata.no_recent_upload') }}
                                    </span>
                                @endif
                            </a>
                        @empty
                            <div class="list-group-item target-list-empty">{{ __('fraesdata.no_positions') }}</div>
                        @endforelse
                        </div>
                    </div>
                </section>

                <section class="col-lg-6" aria-labelledby="bauteile-heading">
                    <div class="card target-list-card h-100">
                        <div class="card-header target-list-header d-flex justify-content-between align-items-center">
                            <h2 id="bauteile-heading" class="h6 mb-0">
                                <i class="bi bi-box-seam me-2"></i>{{ __('fraesdata.bauteile') }}
                            </h2>
                            <span class="badge target-count-badge rounded-pill">{{ $project->bauteile->count() }}</span>
                        </div>
                        <div class="list-group list-group-flush">
                        @forelse($project->bauteile as $bauteil)
                            @php($latestDocument = $bauteil->fraesdata->sortByDesc('created_at')->first())
                            <a
                                href="{{ route('projects.cnc-data', ['project' => $project, 'target_type' => 'bauteil', 'target_id' => $bauteil->id]) }}"
                                class="list-group-item list-group-item-action target-list-item {{ $targetType === 'bauteil' && $selectedTarget?->is($bauteil) ? 'active' : '' }}"
                            >
                                <span class="target-name">
                                    <i class="bi bi-chevron-right me-2"></i>{{ $bauteil->name }}
                                </span>
                                @if($latestDocument)
                                    <span class="zt-tag target-update-badge">
                                        <i class="bi bi-clock-history me-1"></i>{{ __('fraesdata.latest_upload') }}
                                        {{ $latestDocument->created_at->format('d.m.Y H:i') }}
                                    </span>
                                @else
                                    <span class="zt-tag target-no-update-badge">
                                        <i class="bi bi-file-earmark me-1"></i>{{ __('fraesdata.no_recent_upload') }}
                                    </span>
                                @endif
                            </a>
                        @empty
                            <div class="list-group-item target-list-empty">{{ __('fraesdata.no_bauteile') }}</div>
                        @endforelse
                        </div>
                    </div>
                </section>
            </div>

            @if($selectedTarget)
                <section class="document-section" aria-labelledby="documents-heading">
                    <div class="document-section-heading">
                        <div>
                            <h2 id="documents-heading" class="h5 mb-1">{{ __('fraesdata.documents') }}</h2>
                            <span class="small text-muted">{{ $selectedTarget->name }}</span>
                        </div>
                        <span class="badge document-count-badge rounded-pill">
                            {{ trans_choice('fraesdata.document_count', $selectedDocuments->count(), ['count' => $selectedDocuments->count()]) }}
                        </span>
                    </div>

                    @if($selectedDocuments->isEmpty())
                        <div class="document-empty-state">
                            <i class="bi bi-file-earmark-x"></i>
                            <span>{{ __('fraesdata.no_documents') }}</span>
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach($selectedDocuments as $document)
                                <div class="col-12 col-xl-6">
                                    <article class="card document-card h-100">
                                        <div class="card-header document-card-header d-flex justify-content-between align-items-center gap-2">
                                            <h3 class="h6 mb-0 document-name">
                                                <i class="bi bi-file-earmark-text me-2"></i>{{ $document->name }}
                                            </h3>
                                            <div class="document-actions">
                                                <a
                                                    href="{{ route('projects.cnc-data.download', [$project, $document]) }}"
                                                    class="btn btn-primary btn-sm document-view-button"
                                                >
                                                    <i class="bi bi-eye me-1"></i>{{ __('fraesdata.download') }}
                                                </a>
                                                <a
                                                    href="{{ route('projects.cnc-data.print', [$project, $document]) }}"
                                                    class="btn btn-sm document-print-button"
                                                    target="_blank"
                                                    rel="noopener"
                                                >
                                                    <i class="bi bi-printer me-1"></i>{{ __('fraesdata.print') }}
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="document-meta mb-3">
                                                <span class="zt-tag">
                                                    <i class="bi bi-calendar3 me-1"></i>{{ $document->created_at->format('d.m.Y H:i') }}
                                                </span>
                                                @if($document->relatedPosition)
                                                    <span class="zt-tag">
                                                        <i class="bi bi-link-45deg me-1"></i>{{ $document->relatedPosition->name }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if($document->tools->isNotEmpty())
                                                <div class="table-responsive document-tools-wrap">
                                                    <table class="table table-sm align-middle mb-0 document-tools-table">
                                                        <colgroup>
                                                            <col class="tool-number-column">
                                                            <col class="material-column">
                                                            <col class="notes-column">
                                                        </colgroup>
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('fraesdata.upload.tool_number') }}</th>
                                                                <th>{{ __('fraesdata.upload.material') }}</th>
                                                                <th>{{ __('fraesdata.upload.notes') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($document->tools as $tool)
                                                                <tr>
                                                                    <td>
                                                                        @if($tool->tool_number)
                                                                            <span class="zt-tag tool-number-tag">{{ $tool->tool_number }}</span>
                                                                        @else
                                                                            <span class="text-muted">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <button
                                                                            type="button"
                                                                            class="material-details-trigger"
                                                                            aria-label="{{ __('fraesdata.stock.location_title') }}: {{ $tool->material->name }}"
                                                                            data-material-id="{{ $tool->material->id }}"
                                                                            data-material-name="{{ $tool->material->name }}"
                                                                            data-material-code="{{ $tool->material->code }}"
                                                                            data-lager-id="{{ $tool->material->lager_id }}"
                                                                            data-lager-name="{{ $tool->material->lager?->name ?? __('fraesdata.stock.no_lager') }}"
                                                                            data-shelf-name="{{ $tool->material->shelf?->name ?? $tool->material->tablar ?? __('fraesdata.stock.no_shelf') }}"
                                                                            data-available="{{ $tool->material->quantity }}"
                                                                            data-on-hold="{{ $tool->material->on_hold_quantity ?? 0 }}"
                                                                            data-unit="{{ $tool->material->unit ?: __('fraesdata.stock.unit') }}"
                                                                            data-reserve-url="{{ $tool->material->lager_id ? route('tablar.reserve', $tool->material->lager_id) : '' }}"
                                                                            data-release-url="{{ $tool->material->lager_id ? route('tablar.reserve.release', $tool->material->lager_id) : '' }}"
                                                                            data-damage-url="{{ $tool->material->lager_id ? route('tablar.reserve.damage', $tool->material->lager_id) : '' }}"
                                                                        >
                                                                        <span class="material-name-with-code">
                                                                            <span>{{ $tool->material->name }}</span>
                                                                            @if($tool->material->code)
                                                                                <span class="zt-tag material-code-tag">
                                                                                    {{ __('fraesdata.upload.article_number') }} {{ $tool->material->code }}
                                                                                </span>
                                                                            @endif
                                                                        </span>
                                                                        </button>
                                                                    </td>
                                                                    <td>{{ $tool->notes ?: '—' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="cncMaterialModal" tabindex="-1" aria-labelledby="cncMaterialModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cnc-stock-modal">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 mb-1" id="cncMaterialModalTitle"></h2>
                    <span class="zt-tag d-none" id="cncMaterialModalCode"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cnc-stock-location mb-3">
                    <div>
                        <span class="cnc-stock-label">{{ __('fraesdata.stock.lager') }}</span>
                        <strong id="cncMaterialLager"></strong>
                    </div>
                    <div>
                        <span class="cnc-stock-label">{{ __('fraesdata.stock.shelf') }}</span>
                        <strong id="cncMaterialShelf"></strong>
                    </div>
                </div>

                <div class="cnc-stock-counts">
                    <div>
                        <span class="cnc-stock-label">{{ __('fraesdata.stock.available') }}</span>
                        <strong id="cncMaterialAvailable"></strong>
                    </div>
                    <div>
                        <span class="cnc-stock-label">{{ __('fraesdata.stock.reserved_total') }}</span>
                        <strong id="cncMaterialOnHold"></strong>
                    </div>
                </div>

                <p class="form-text mt-3 mb-3">{{ __('fraesdata.stock.aggregate_note') }}</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="cncMaterialReserve">
                        {{ __('fraesdata.stock.reserve_one') }}
                    </button>
                    <button type="button" class="btn btn-outline-success" id="cncMaterialRelease">
                        {{ __('fraesdata.stock.return_one') }}
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="cncMaterialDamage">
                        {{ __('fraesdata.stock.damage_one') }}
                    </button>
                </div>
                <div class="alert mt-3 mb-0 d-none" id="cncMaterialStatus" role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .zt-tag {
        display: inline-flex;
        align-items: center;
        border: 1px solid #dfe3e8;
        border-radius: 999px;
        background: #fafbfc;
        color: #343a40;
        font-size: 0.72rem;
        font-weight: 500;
        padding: 0.15rem 0.55rem;
    }

    .project-cnc-project-line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem 0.75rem;
    }

    .project-cnc-project-name {
        margin-right: 0.25rem;
        overflow-wrap: anywhere;
    }

    .project-cnc-order-tag {
        gap: 0.35rem;
        padding: 0.3rem 0.65rem;
    }

    .project-cnc-order-tag strong {
        color: #0d5ca8;
        font-size: 0.68rem;
        letter-spacing: 0.04em;
    }

    .project-cnc-recent-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid #cfe0ee;
        border-radius: 999px;
        background: #edf5fb;
        color: #174f7a;
        font-size: 0.76rem;
        padding: 0.35rem 0.75rem;
        white-space: nowrap;
    }

    .project-cnc-recent-label {
        font-weight: 500;
    }

    .project-cnc-recent-tag time {
        color: #002752;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .project-cnc-back {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .target-list-card,
    .document-card {
        overflow: hidden;
        border: 1px solid #e4e7eb;
        border-radius: 0.65rem;
        box-shadow: 0 0.2rem 0.65rem rgba(33, 37, 41, 0.06);
    }

    .target-list-card > .card-header,
    .document-card > .card-header {
        border-bottom: 0;
        padding: 0.8rem 1rem;
    }

    .target-list-header,
    .document-card-header {
        background: linear-gradient(135deg, #f1f5f9, #e8eef5);
        color: #002752;
    }

    .target-list-header h2 {
        font-weight: 700;
    }

    .target-count-badge,
    .document-count-badge {
        background: #002752;
        color: #fff;
    }

    .target-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border-color: #eceff2;
        border-left: 3px solid transparent;
        color: #343a40;
        padding: 0.85rem 1rem;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .target-list-item:hover,
    .target-list-item:focus-visible {
        border-left-color: #0d5ca8;
        background: #f3f6f9;
        color: #002752;
    }

    .target-list-item.active,
    .target-list-item.active:hover,
    .target-list-item.active:focus-visible {
        border-left-color: #0d5ca8;
        background: #002752;
        color: #fff;
    }

    .target-name {
        display: inline-flex;
        align-items: center;
        min-width: 0;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .target-name .bi {
        flex: 0 0 auto;
        color: #6c757d;
        font-size: 0.75rem;
    }

    .target-list-item.active .target-name .bi {
        color: #ced4da;
    }

    .target-update-badge,
    .target-no-update-badge {
        flex: 0 0 auto;
        color: #495057;
        font-size: 0.7rem;
        padding: 0.25rem 0.55rem;
    }

    .target-update-badge {
        border-color: #d7e8f7;
        background: #edf5fb;
        color: #174f7a;
    }

    .target-no-update-badge {
        border-color: #e4e7eb;
        background: #f5f6f7;
        color: #6c757d;
    }

    .target-list-item.active .target-update-badge {
        border-color: rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.12);
        color: #e8f2fa;
    }

    .target-list-item.active .target-no-update-badge {
        border-color: rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.08);
        color: #ced4da;
    }

    .target-list-empty {
        background: #fafbfc;
        color: #6c757d;
        padding: 1rem;
    }

    .document-section {
        border-top: 1px solid #e5e7eb;
        padding-top: 1.25rem;
    }

    .document-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        color: #212529;
    }

    .document-count-badge {
        min-width: 2.2rem;
        padding: 0.45rem 0.7rem;
    }

    .document-empty-state {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border: 1px dashed #cfd4da;
        border-radius: 0.6rem;
        background: #fafbfc;
        color: #6c757d;
        padding: 1rem;
    }

    .document-empty-state .bi {
        color: #495057;
        font-size: 1.25rem;
    }

    .document-card .card-header {
        min-height: 3.5rem;
    }

    .document-name {
        color: #002752;
    }

    .document-name {
        min-width: 0;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .document-view-button {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .document-actions {
        display: flex;
        flex: 0 0 auto;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .document-print-button {
        border: 1px solid #cfe0ee;
        background: #edf5fb;
        color: #174f7a;
        white-space: nowrap;
    }

    .document-print-button:hover,
    .document-print-button:focus-visible {
        border-color: #9fc2dc;
        background: #e1eef8;
        color: #002752;
    }

    .document-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .document-tools-wrap {
        border: 1px solid #e4e7eb;
        border-radius: 0.5rem;
    }

    .document-tools-table thead th {
        border-bottom: 1px solid #dfe3e8;
        background: #f4f6f8;
        color: #495057;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .document-tools-table {
        table-layout: fixed;
        width: 100%;
    }

    .tool-number-column { width: 18%; }
    .material-column { width: 57%; }
    .notes-column { width: 25%; }

    .document-tools-table {
        min-width: 620px;
    }

    .material-name-with-code {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
    }

    .material-code-tag {
        border-color: #d7e8f7;
        background: #edf5fb;
        color: #174f7a;
        font-size: 0.68rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .material-details-trigger {
        width: 100%;
        border: 0;
        background: transparent;
        color: inherit;
        padding: 0;
        text-align: left;
    }

    .material-details-trigger:hover,
    .material-details-trigger:focus-visible {
        color: #174f7a;
        text-decoration: underline;
        text-decoration-thickness: 1px;
        text-underline-offset: 3px;
    }

    .cnc-stock-modal .modal-header {
        border-bottom-color: #e4e7eb;
    }

    .cnc-stock-location,
    .cnc-stock-counts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        padding: 0.85rem;
        border: 1px solid #e4e7eb;
        border-radius: 0.6rem;
        background: #f8fafc;
    }

    .cnc-stock-location > div,
    .cnc-stock-counts > div {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
    }

    .cnc-stock-label {
        color: #6c757d;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .cnc-stock-location strong,
    .cnc-stock-counts strong {
        overflow-wrap: anywhere;
    }

    .document-tools-table tbody td {
        border-color: #edf0f2;
        color: #343a40;
        font-size: 0.82rem;
        vertical-align: middle;
    }

    .tool-number-tag {
        border-radius: 999px;
        background: #f1f3f5;
        color: #212529;
        font-weight: 700;
        white-space: nowrap;
    }

    @media (max-width: 575.98px) {
        .project-cnc-project-line {
            align-items: flex-start;
        }

        .target-list-item {
            align-items: flex-start;
            flex-direction: column;
        }

        .document-card > .card-header {
            align-items: flex-start !important;
            flex-direction: column;
        }

        .document-actions {
            align-self: flex-start;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const modalElement = document.getElementById('cncMaterialModal');
    if (!modalElement) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const status = document.getElementById('cncMaterialStatus');
    const reserveButton = document.getElementById('cncMaterialReserve');
    const releaseButton = document.getElementById('cncMaterialRelease');
    const damageButton = document.getElementById('cncMaterialDamage');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const messages = {
        updated: @json(__('fraesdata.stock.updated')),
        updateFailed: @json(__('fraesdata.stock.update_failed')),
        confirmDamage: @json(__('fraesdata.stock.confirm_damage')),
        articleNumber: @json(__('fraesdata.upload.article_number')),
    };
    let selectedMaterial = null;

    function showStatus(message, isError) {
        status.textContent = message;
        status.classList.remove('d-none', 'alert-success', 'alert-danger');
        status.classList.add(isError ? 'alert-danger' : 'alert-success');
    }

    function refreshMaterialDetails() {
        document.getElementById('cncMaterialAvailable').textContent =
            `${selectedMaterial.quantity} ${selectedMaterial.unit}`;
        document.getElementById('cncMaterialOnHold').textContent =
            `${selectedMaterial.onHold} ${selectedMaterial.unit}`;
        reserveButton.classList.toggle('d-none', !selectedMaterial.reserveUrl || selectedMaterial.quantity < 1);
        releaseButton.classList.toggle('d-none', !selectedMaterial.releaseUrl || selectedMaterial.onHold < 1);
        damageButton.classList.toggle('d-none', !selectedMaterial.damageUrl || selectedMaterial.onHold < 1);
    }

    document.querySelectorAll('.material-details-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            selectedMaterial = {
                id: Number(trigger.dataset.materialId),
                name: trigger.dataset.materialName,
                code: trigger.dataset.materialCode,
                lagerName: trigger.dataset.lagerName,
                shelfName: trigger.dataset.shelfName,
                quantity: Number(trigger.dataset.available),
                onHold: Number(trigger.dataset.onHold),
                unit: trigger.dataset.unit,
                reserveUrl: trigger.dataset.reserveUrl,
                releaseUrl: trigger.dataset.releaseUrl,
                damageUrl: trigger.dataset.damageUrl,
            };

            document.getElementById('cncMaterialModalTitle').textContent = selectedMaterial.name;
            const code = document.getElementById('cncMaterialModalCode');
            code.textContent = selectedMaterial.code
                ? `${messages.articleNumber} ${selectedMaterial.code}`
                : '';
            code.classList.toggle('d-none', !selectedMaterial.code);
            document.getElementById('cncMaterialLager').textContent = selectedMaterial.lagerName;
            document.getElementById('cncMaterialShelf').textContent = selectedMaterial.shelfName;
            status.classList.add('d-none');
            refreshMaterialDetails();
            modal.show();
        });
    });

    async function updateReservation(button, url) {
        if (!selectedMaterial || !url) return;

        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = '...';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
                },
                body: JSON.stringify({ material_id: selectedMaterial.id, quantity: 1 }),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || messages.updateFailed);
            }

            selectedMaterial.quantity = Number(result.new_quantity);
            selectedMaterial.onHold = Number(result.on_hold_quantity);
            document.querySelectorAll('.material-details-trigger').forEach((trigger) => {
                if (Number(trigger.dataset.materialId) === selectedMaterial.id) {
                    trigger.dataset.available = selectedMaterial.quantity;
                    trigger.dataset.onHold = selectedMaterial.onHold;
                }
            });
            refreshMaterialDetails();
            showStatus(messages.updated, false);
        } catch (error) {
            showStatus(error.message || messages.updateFailed, true);
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    reserveButton.addEventListener('click', () => {
        updateReservation(reserveButton, selectedMaterial?.reserveUrl);
    });
    releaseButton.addEventListener('click', () => {
        updateReservation(releaseButton, selectedMaterial?.releaseUrl);
    });
    damageButton.addEventListener('click', () => {
        if (window.confirm(messages.confirmDamage)) {
            updateReservation(damageButton, selectedMaterial?.damageUrl);
        }
    });
})();
</script>
@endpush
