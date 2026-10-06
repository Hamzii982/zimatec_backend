@extends('admin.layouts.index')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row mb-4 gx-4 gy-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="h4 m-0">Lager Dashboard</h2>
                <div>
                    <a href="{{ route('admin.lager.index') }}" class="btn btn-outline-secondary btn-sm me-2">Lagerverwaltung</a>
                    {{-- <a href="#" class="btn btn-primary btn-sm">Export CSV</a> --}}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3 p-2 rounded-circle bg-light">
                            <i class="bi bi-box-seam fs-4 text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Lager gesamt</div>
                            <div class="h5 fw-bold mb-0">{{ $lagersCount ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3 p-2 rounded-circle bg-light">
                            <i class="bi bi-boxes fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Materialien</div>
                            <div class="h5 fw-bold mb-0">{{ number_format($totalMaterials ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3 p-2 rounded-circle bg-light">
                            <i class="bi bi-stack fs-4 text-warning"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Gesamt Einheiten</div>
                            <div class="h5 fw-bold mb-0">{{ number_format($totalUnits ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3 p-2 rounded-circle bg-light">
                            <i class="bi bi-tools fs-4 text-info"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Werkzeuge</div>
                            <div class="h5 fw-bold mb-0">{{ $toolsCount ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">Geringer Bestand (Top 10)</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($lowStockMaterials as $m)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5">
                                <span class="text-secondary">{{ $m->name }}</span>
                                <span class="fw-bold">{{ (int) $m->available_total ?? $m->quantity }} Stk.</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-4">Keine kritischen Materialien.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <i class="bi bi-lightning-fill text-warning me-2 fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">Top Nutzung (30 Tage)</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($topUsed30Days as $u)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5">
                                <span class="text-secondary">{{ $u->material->name ?? 'Unbekannt' }}</span>
                                <span class="fw-bold">{{ $u->total_used }} Stk.</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-4">Keine Verbrauchsdaten.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-graph-up me-2 fs-5"></i>
                        <h6 class="mb-0 fw-bold text-dark">Täglicher Verbrauch (letzte 30 Tage)</h6>
                    </div>
                    <div class="small text-muted">Gesamt: <strong>{{ array_sum($consumptionData ?? []) }}</strong></div>
                </div>
                <div class="card-body">
                    <canvas id="consumptionChart" height="120"></canvas>
                </div>
                <div class="card-footer bg-white border-top">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="small text-muted">Durchschnitt / Tag</div>
                            <div class="fw-bold">{{ round(array_sum($consumptionData ?? []) / max(count($consumptionData ?? []),1), 2) }} Stk.</div>
                        </div>
                        <div class="col-sm-6 text-end">
                            <a href="#" class="btn btn-sm btn-outline-primary">Mehr Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-pie-chart-fill me-2 fs-5"></i>
                        <h6 class="mb-0 fw-bold text-dark">Bestand nach Typ</h6>
                    </div>
                    <div class="small text-muted">Top Kategorien</div>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="stockTypeChart" width="250" height="250"></canvas>
                </div>
                <div class="card-footer bg-white border-top">
                    @foreach($stockByType as $type => $val)
                        <div class="d-flex justify-content-between small mb-1">
                            <div>{{ $type }}</div>
                            <div class="fw-semibold">{{ number_format($val) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Footer link removed as requested. Top "Lagerverwaltung" button is sufficient. --}}
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            try {
                const labels = @json($consumptionLabels ?? []);
                const data = @json($consumptionData ?? []);

                const consumptionEl = document.getElementById('consumptionChart');
                if (consumptionEl && consumptionEl.getContext) {
                    const ctx = consumptionEl.getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Verbrauch',
                                data: data,
                                fill: true,
                                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                tension: 0.2,
                            }]
                        },
                        options: {
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                }

                const stockData = @json(array_values($stockByType ?? []));
                const stockLabels = @json(array_keys($stockByType ?? []));
                const stockEl = document.getElementById('stockTypeChart');
                if (stockEl && stockEl.getContext) {
                    const ctx2 = stockEl.getContext('2d');
                    new Chart(ctx2, {
                        type: 'doughnut',
                        data: {
                            labels: stockLabels,
                            datasets: [{
                                data: stockData,
                                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                            }]
                        },
                        options: { plugins: { legend: { position: 'bottom' } } }
                    });
                }
            } catch (err) {
                console.error('Dashboard chart error:', err);
            }
        })();
    </script>
@endpush
