@extends('admin.layouts.index')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100 bg-white">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                        <i class="bi bi-box-seam fs-3 d-flex"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Materialien</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalMaterials }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100 bg-white border-start border-danger border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 me-3">
                        <i class="bi bi-exclamation-triangle-fill fs-3 d-flex"></i>
                    </div>
                    <div>
                        <span class="text-danger small fw-bold text-uppercase d-block mb-1">Geringer Bestand</span>
                        <h3 class="fw-bold mb-0 text-danger">{{ $lowStockMaterials->count() }}</h3>
                        <small class="text-muted">Materialien ohne Mindestbestand werden nicht als kritisch gewertet.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100 bg-white">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 me-3">
                        <i class="bi bi-graph-up fs-3 d-flex"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Nutzung (10 Tage)</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $topUsed10Days->sum('total_used') }} <span class="fs-6 text-muted font-normal">Stk.</span></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100 bg-white">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 me-3">
                        <i class="bi bi-activity fs-3 d-flex"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Nutzung (30 Tage)</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $topUsed30Days->sum('total_used') }} <span class="fs-6 text-muted font-normal">Stk.</span></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($lowStockMaterials->isNotEmpty())
    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-header bg-danger text-white py-3 d-flex align-items-center">
            <i class="bi bi-bell-fill me-2 fs-5 animation-pulse"></i>
            <h6 class="mb-0 fw-bold">Kritischer Bestand (Nachbestellen erforderlich)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-secondary">
                        <tr>
                            <th class="ps-4">Materialbezeichnung</th>
                            <th class="text-center">Aktueller Bestand</th>
                            <th class="text-center">Mindestbestand</th>
                            <th class="pe-4 text-end" style="width: 30%;">Statusverlauf</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockMaterials as $m)
                            @php
                                $total = (int) $m->available_total;
                                $ratio = $m->threshold > 0 ? ($total / (int) $m->threshold) * 100 : 0;
                                $progressWidth = max(8, min(100, $ratio));
                            @endphp
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $m->name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-danger-subtle text-danger px-2.5 py-1.5 fw-bold fs-7">
                                        {{ $total }} Stk.
                                    </span>
                                    @if((int) $m->on_hold_quantity > 0)
                                        <small class="text-muted d-block mt-1">davon reserviert: {{ (int) $m->on_hold_quantity }}</small>
                                    @endif
                                    @if((int) $m->order_quantity > 0)
                                        <small class="text-muted d-block">bestellt: {{ (int) $m->order_quantity }}</small>
                                    @endif
                                </td>
                                <td class="text-center text-muted fw-semibold">{{ $m->threshold }} Stk.</td>
                                <td class="pe-4">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <div class="progress w-100" style="height: 6px;">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $progressWidth }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <i class="bi bi-arrow-up-circle-fill text-success me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">📦 Höchster Bestand</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($highestStock as $m)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5 hover-bg border-0 border-bottom">
                                <span class="text-secondary fw-medium small">{{ $m->name }}</span>
                                <span class="badge bg-success-subtle text-success rounded-pill px-3 fw-bold">{{ $m->quantity }} Stk.</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <i class="bi bi-arrow-down-circle-fill text-warning me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">📉 Niedrigster Bestand</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($lowestStock as $m)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5 hover-bg border-0 border-bottom">
                                <span class="text-secondary fw-medium small">{{ $m->name }}</span>
                                <span class="badge bg-warning-subtle text-warning rounded-pill px-3 fw-bold">{{ $m->quantity }} Stk.</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <i class="bi bi-lightning-fill text-warning me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">🔥 Top Nutzung (Letzte 10 Tage)</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($topUsed10Days as $u)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5 hover-bg border-0 border-bottom">
                                <span class="text-secondary fw-medium small">{{ $u->material->name ?? 'Unbekanntes Material' }}</span>
                                <span class="text-dark fw-bold small"><i class="bi bi-dash text-muted me-1"></i>{{ $u->total_used }} Stk.</span>
                            </li>
                        @empty
                        <li class="list-group-item text-center text-muted py-4 small">Kein Verbrauch verzeichnet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <i class="bi bi-calendar3 text-info me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">🔥 Top Nutzung (Letzte 30 Tage)</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($topUsed30Days as $u)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5 hover-bg border-0 border-bottom">
                                <span class="text-secondary fw-medium small">{{ $u->material->name ?? 'Unbekanntes Material' }}</span>
                                <span class="text-dark fw-bold small"><i class="bi bi-dash text-muted me-1"></i>{{ $u->total_used }} Stk.</span>
                            </li>
                        @empty
                        <li class="list-group-item text-center text-muted py-4 small">Kein Verbrauch verzeichnet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-xl-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <i class="bi bi-layers text-secondary me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">📍 Regal Aktivität (Nutzung pro Tablar)</h6>
                </div>
                <div class="card-body p-0 d-flex flex-column justify-content-between" style="min-height: 460px;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-secondary">
                                <tr>
                                    <th class="ps-4">Tablar-Bezeichnung</th>
                                    <th class="pe-4 text-end">Gesamtentnahme</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shelfActivity as $s)
                                <tr class="shelf-js-row">
                                    <td class="ps-4 font-monospace text-secondary fw-semibold small">
                                        <i class="bi bi-hdd-stack text-muted me-2"></i>{{ $s->tablar ?? 'Nicht zugewiesen' }}
                                    </td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5 fw-bold">
                                            {{ $s->total_used }} Einheiten
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4 small">Keine Tablar-Daten vorhanden.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-3 border-top bg-light bg-opacity-50 d-flex justify-content-between align-items-center">
                        <span class="text-muted small" id="jsPageInfo">Lade Informationen...</span>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="jsPaginationNav"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <i class="bi bi-card-list text-primary me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">🧾 Letzte Aktivitäten (Audit Log)</h6>
                </div>
                <div class="card-body p-0 d-flex flex-column justify-content-between" style="min-height: 460px;">
                    <ul class="list-group list-group-flush">
                        @forelse($recentLogs as $log)
                            <li class="list-group-item px-4 py-2.5 border-0 border-bottom d-flex justify-content-between align-items-center hover-bg">
                                <div>
                                    <span class="fw-bold text-dark d-block mb-0.5 small">{{ $log->material->name ?? 'Gelöschtes Material' }}</span>
                                    <small class="text-muted text-xs">
                                        <i class="bi bi-box-arrow-right text-danger me-1"></i> Entnommen: 
                                        <span class="fw-bold text-secondary">{{ $log->quantity }} Stk.</span>
                                    </small>
                                </div>
                                <span class="badge bg-light text-muted border font-monospace px-2.5 py-1.5 small fw-normal">
                                    <i class="bi bi-clock me-1"></i>{{ $log->created_at->diffForHumans() }}
                                </span>
                            </li>
                        @empty
                        <li class="list-group-item text-center text-muted py-5 small">Keine Aktivitäten aufgezeichnet.</li>
                        @endforelse
                    </ul>

                    <div class="p-3 border-top bg-light bg-opacity-50 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Zeige {{ $recentLogs->firstItem() ?? 0 }} bis {{ $recentLogs->lastItem() ?? 0 }} von {{ $recentLogs->total() }} Aktivitäten
                        </div>
                        <div class="dashboard-pagination">
                            {{ $recentLogs->appends(['shelf_page' => request('shelf_page')])->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- EMBEDDED LAYOUT UTILITY CLASSES --}}
<style>
    .animation-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    .fs-7 { font-size: 0.785rem; }
    .text-xs { font-size: 0.75rem; }
    .hover-bg:hover { background-color: rgba(0, 0, 0, 0.018); transition: background 0.15s ease-in-out; }
    
    /* Cleaning up pagination component dimensions inside cards */
    .dashboard-pagination .pagination { margin-bottom: 0 !important; font-size: 0.8rem; }
    .dashboard-pagination .page-link { padding: 0.25rem 0.5rem; }
    #jsPaginationNav .page-link { cursor: pointer; }
</style>

{{-- FRONTEND PAGINATION MANAGEMENT FOR REGAL ACTIVITY (TABLAR) --}}
<script>
document.addEventListener("DOMContentLoaded", function() {
    const recordsPerPage = 8; 
    const targetRows = document.querySelectorAll(".shelf-js-row");
    const totalRecords = targetRows.length;
    const computedPages = Math.ceil(totalRecords / recordsPerPage);
    
    const uiNavWrapper = document.getElementById("jsPaginationNav");
    const uiTextWrapper = document.getElementById("jsPageInfo");
    let activePage = 1;

    function renderPage(targetPage) {
        activePage = targetPage;
        let startIndex = (targetPage - 1) * recordsPerPage;
        let endIndex = startIndex + recordsPerPage;

        targetRows.forEach((row, idx) => {
            if (idx >= startIndex && idx < endIndex) {
                row.style.display = ""; 
            } else {
                row.style.display = "none"; 
            }
        });

        let currentLimitDisplay = endIndex > totalRecords ? totalRecords : endIndex;
        uiTextWrapper.innerText = totalRecords > 0 
            ? `Zeige ${startIndex + 1} bis ${currentLimitDisplay} von ${totalRecords} Tablaren`
            : "Keine Tablare erfasst";

        generateNavControls();
    }

    function generateNavControls() {
        uiNavWrapper.innerHTML = "";
        if (computedPages <= 1) return;

        // Backward step button element
        appendControlItem("«", activePage > 1, () => renderPage(activePage - 1));

        // Indexed page selection triggers
        for (let pageIdx = 1; pageIdx <= computedPages; pageIdx++) {
            appendControlItem(pageIdx, true, () => renderPage(pageIdx), pageIdx === activePage);
        }

        // Forward step button element
        appendControlItem("»", activePage < computedPages, () => renderPage(activePage + 1));
    }

    function appendControlItem(label, clickable, targetAction, isActiveState = false) {
        const itemLi = document.createElement("li");
        itemLi.className = `page-item ${isActiveState ? 'active' : ''} ${!clickable ? 'disabled' : ''}`;
        
        const anchorBtn = document.createElement("a");
        anchorBtn.className = "page-link";
        anchorBtn.innerText = label;
        
        if (clickable) {
            anchorBtn.addEventListener("click", function(event) {
                event.preventDefault();
                targetAction();
            });
        }
        
        itemLi.appendChild(anchorBtn);
        uiNavWrapper.appendChild(itemLi);
    }

    // Execute first run configuration
    renderPage(1);
});
</script>
@endsection