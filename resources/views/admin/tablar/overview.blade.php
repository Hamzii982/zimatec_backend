@extends('admin.layouts.index')

@section('content')

<div class="container py-4">

    <!-- HEADER -->
    <div class="mb-4 text-center">
        <h2 class="fw-bold">🏭 Tablar Maschinen Übersicht</h2>
        <p class="text-muted">Analyse von Bestand, Nutzung und Materialfluss</p>
    </div>

    <!-- KPI CARDS -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <h6>Total Materialien</h6>
                <h3>{{ $totalMaterials }}</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm p-3 border-danger">
                <h6 class="text-danger">Low Stock</h6>
                <h3>{{ $lowStockMaterials->count() }}</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <h6>Top Nutzung (10 Tage)</h6>
                <h3>{{ $topUsed10Days->sum('total_used') }}</h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <h6>Top Nutzung (30 Tage)</h6>
                <h3>{{ $topUsed30Days->sum('total_used') }}</h3>
            </div>
        </div>
    </div>

    <!-- LOW STOCK -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            🔴 Kritischer Bestand (Nachbestellen erforderlich)
        </div>
        <div class="card-body">
            @foreach($lowStockMaterials as $m)
                <div class="d-flex justify-content-between border-bottom py-2">
                    <strong>{{ $m->name }}</strong>
                    <span>{{ $m->quantity }} / {{ $m->threshold ?? 20 }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- STOCK INSIGHTS -->
    <div class="row mb-4">

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">📦 Höchster Bestand</div>
                <div class="card-body">
                    @foreach($highestStock as $m)
                        <div>{{ $m->name }} - {{ $m->quantity }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">📉 Niedrigster Bestand</div>
                <div class="card-body">
                    @foreach($lowestStock as $m)
                        <div>{{ $m->name }} - {{ $m->quantity }}</div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <!-- USAGE ANALYTICS -->
    <div class="row mb-4">

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">🔥 Top Nutzung (10 Tage)</div>
                <div class="card-body">
                    @foreach($topUsed10Days as $u)
                        <div>
                            {{ $u->material->name ?? 'N/A' }} -
                            <strong>{{ $u->total_used }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">🔥 Top Nutzung (30 Tage)</div>
                <div class="card-body">
                    @foreach($topUsed30Days as $u)
                        <div>
                            {{ $u->material->name ?? 'N/A' }} -
                            <strong>{{ $u->total_used }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <!-- SHELF ACTIVITY -->
    <div class="card mb-4">
        <div class="card-header">📍 Tablar Aktivität (Nutzung pro Fach)</div>
        <div class="card-body">
            @foreach($shelfActivity as $s)
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>{{ $s->tablar ?? 'Unbekannt' }}</span>
                    <strong>{{ $s->total_used }}</strong>
                </div>
            @endforeach
        </div>
    </div>

    <!-- AUDIT LOG -->
    <div class="card">
        <div class="card-header">🧾 Letzte Aktivitäten (Audit Log)</div>
        <div class="card-body">
            @foreach($recentLogs as $log)
                <div class="border-bottom py-2">
                    <strong>{{ $log->material->name ?? 'N/A' }}</strong>
                    - {{ $log->quantity }} Stk.
                    <span class="text-muted float-end">
                        {{ $log->created_at->diffForHumans() }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

</div>

@endsection