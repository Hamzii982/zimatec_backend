@extends('admin.layouts.index')

@section('title', __('tablar.supplier_list.title', ['name' => $supplier->name]))

@section('content')

<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('tablar.supplier_list.title', ['name' => $supplier->name]) }}</h5>
            <a href="{{ route('admin.tablar.index', ['lager_id' => $lager->id]) }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-list me-1"></i> Alle Tablar
            </a>
        </div>

        <div class="card-body">
            @if($supplier->company || $supplier->email || $supplier->phone_number)
                <div class="text-muted small mb-3">
                    @if($supplier->company)<span class="me-3">{{ $supplier->company }}</span>@endif
                    @if($supplier->email)<i class="bi bi-envelope me-1"></i>{{ $supplier->email }}@endif
                    @if($supplier->phone_number)<span class="ms-3"><i class="bi bi-telephone me-1"></i>{{ $supplier->phone_number }}</span>@endif
                </div>
            @endif

            @if($materials->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-truck fs-1 d-block mb-2"></i>
                    {{ __('tablar.supplier_list.empty') }}
                </div>
            @else
                <div class="table-responsive-wrapper">
                    <table class="table table-hover align-middle border-top">
                        <thead class="table-light">
                            <tr class="text-secondary text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.05em;">
                                <th></th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Menge</th>
                                <th>Fach</th>
                                <th>Status</th>
                                <th>{{ __('tablar.supplier_list.col.attached_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($materials as $m)
                                <tr>
                                    <td>
                                        @if($m->image)
                                            <img src="{{ asset('storage/'.$m->image) }}" alt="" width="40" height="40" class="rounded object-fit-cover">
                                        @else
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->code)
                                            <code class="text-muted small">{{ $m->code }}</code>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">
                                        <a href="{{ route('admin.tablar.show', ['lager_id' => $lager->id, 'id' => $m->id]) }}" class="text-decoration-none text-dark">
                                            {{ $m->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border">{{ $m->quantity }} Stk.</span>
                                        @if((int) $m->on_hold_quantity > 0)
                                            <span class="badge rounded-pill bg-info-subtle text-info-emphasis border ms-1" title="Reserviert">
                                                <i class="bi bi-clock-history me-1"></i>{{ (int) $m->on_hold_quantity }}
                                            </span>
                                        @endif
                                        @if((int) $m->order_quantity > 0)
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border ms-1" title="Bestellt">
                                                <i class="bi bi-truck me-1"></i>{{ (int) $m->order_quantity }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $m->tablar ?? '—' }}</td>
                                    <td>
                                        @if($m->status_label)
                                            <span class="badge bg-info-subtle text-info-emphasis">{{ $m->status_label }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">
                                        {{ optional($m->pivot_attached_at)->format('d.m.Y H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
