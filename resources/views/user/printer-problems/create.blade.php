@extends('user.layouts.index')

@section('title', 'Neues Druckproblem')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

    {{-- HEADER --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex align-items-center">

            <div class="bg-danger bg-opacity-10 rounded-circle p-3 me-3">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-3"></i>
            </div>

            <div>
                <h4 class="mb-1">Neues Druckproblem erfassen</h4>
                <p class="text-muted mb-0">
                    Erfassen Sie alle relevanten Informationen. Die KI analysiert anschließend das Problem automatisch.
                </p>
            </div>

        </div>
    </div>

    {{-- PROGRESS --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <div class="progress mb-3" style="height: 8px;">
                <div id="progress-bar" class="progress-bar bg-success" style="width: 20%"></div>
            </div>

            <div class="d-flex justify-content-between small text-muted">
                <span id="step-1-ind">Auftrag</span>
                <span id="step-2-ind">Maschine</span>
                <span id="step-3-ind">Fehler</span>
                <span id="step-4-ind">Wartung</span>
                <span id="step-5-ind">Speichern</span>
            </div>

        </div>
    </div>

    @include('user.printer-problems.__form', [
        'problem' => null,
        'action'  => route('printer-problems.store'),
        'method'  => 'POST',
        'mode'    => 'create'
    ])

</div>
@endsection