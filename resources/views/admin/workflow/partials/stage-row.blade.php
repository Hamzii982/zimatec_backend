@php
    /** @var \App\Models\Workflow\Stage $stage */
    $totalSteps = $stage->steps->count();
    $requiredSteps = $stage->steps->where('is_required', true)->count();
    $requiredPct = $totalSteps > 0 ? round(($requiredSteps / $totalSteps) * 100) : 0;
@endphp
<div class="workflow-settings-card wf-stage-card {{ $stage->is_active ? '' : 'wf-stage-inactive' }}"
     style="--stage-color: {{ $stage->color }};">

    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="wf-stage-icon" style="background: {{ $stage->color }};">
                    <i class="bi {{ $stage->icon ?: 'bi-columns-gap' }}"></i>
                </span>
                <div>
                    <h6 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                        {{ $stage->name }}
                        <code class="small text-muted">[{{ $stage->key }}]</code>
                    </h6>
                    <span class="wf-order-badge">Position {{ $stage->order_index }}</span>
                    @unless($stage->is_active)
                        <span class="badge bg-light text-dark border ms-1">
                            <i class="bi bi-pause-circle me-1"></i>Inaktiv
                        </span>
                    @endunless
                </div>
            </div>

            <div class="btn-group-icon" role="group">
                <form method="POST" action="{{ route('admin.workflow.stages.destroy', $stage) }}"
                      onsubmit="return confirm('Stufe wirklich entfernen?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger" title="Löschen">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.workflow.stages.update', $stage) }}">
            @csrf @method('PUT')
            <div class="row g-2 small">
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" name="name" id="stage-name-{{ $stage->id }}" class="form-control" value="{{ $stage->name }}" required>
                        <label for="stage-name-{{ $stage->id }}">{{ __('workflow.settings.stage.name') }}</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1 text-muted">{{ __('workflow.settings.stage.color') }}</label>
                    <input type="color" name="color" class="form-control form-control-color w-100" value="{{ $stage->color }}">
                </div>
                <div class="col-md-2">
                    <div class="form-floating">
                        <input type="text" name="icon" id="stage-icon-{{ $stage->id }}" class="form-control" value="{{ $stage->icon }}">
                        <label for="stage-icon-{{ $stage->id }}">{{ __('workflow.settings.stage.icon') }}</label>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-floating">
                        <input type="number" name="order_index" id="stage-order-{{ $stage->id }}" class="form-control" value="{{ $stage->order_index }}">
                        <label for="stage-order-{{ $stage->id }}">{{ __('workflow.settings.stage.order') }}</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating">
                        <select name="required_role" id="stage-role-{{ $stage->id }}" class="form-select">
                            <option value="">—</option>
                            <option value="admin" @selected($stage->required_role === 'admin')>admin</option>
                            <option value="user" @selected($stage->required_role === 'user')>user</option>
                        </select>
                        <label for="stage-role-{{ $stage->id }}">{{ __('workflow.settings.stage.role') }}</label>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active-{{ $stage->id }}" @checked($stage->is_active)>
                        <label class="form-check-label visually-hidden" for="active-{{ $stage->id }}">{{ __('workflow.settings.stage.active') }}</label>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end mt-2">
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-check2 me-1"></i>{{ __('workflow.settings.stage.update') }}
                    </button>
                </div>
            </div>
        </form>

        <hr class="my-3 opacity-50">

        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="small fw-semibold mb-0 text-uppercase text-muted d-flex align-items-center gap-2">
                <i class="bi bi-list-check"></i>Prüfschritte
                <span class="badge rounded-pill wf-step-count">{{ $totalSteps }}</span>
            </h6>
            @if($totalSteps > 0)
                <span class="wf-required-label">{{ $requiredSteps }}/{{ $totalSteps }} verpflichtend</span>
            @endif
        </div>

        @if($totalSteps > 0)
            <div class="workflow-progress mb-3">
                <div class="workflow-progress__bar" style="width: {{ $requiredPct }}%; background: {{ $stage->color }};"></div>
            </div>
        @endif

        @if($stage->steps->isEmpty())
            <p class="text-muted small mb-3 fst-italic">Noch keine Prüfschritte für diese Stufe.</p>
        @else
            <ul class="list-group list-group-flush mb-3">
                @foreach ($stage->steps as $step)
                    @include('admin.workflow.partials.step-row', ['step' => $step])
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('admin.workflow.stages.steps.store', $stage) }}" class="wf-add-step-form">
            @csrf
            <div class="row g-2 small align-items-center">
                <div class="col-md-7">
                    <div class="form-floating">
                        <input type="text" name="name" id="step-name-new-{{ $stage->id }}" class="form-control" placeholder="{{ __('workflow.settings.step.name') }}" required>
                        <label for="step-name-new-{{ $stage->id }}">{{ __('workflow.settings.step.name') }}</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating">
                        <input type="number" name="order_index" id="step-order-new-{{ $stage->id }}" class="form-control" placeholder="0">
                        <label for="step-order-new-{{ $stage->id }}">{{ __('workflow.settings.step.order') }}</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="req-new-{{ $stage->id }}" checked>
                        <label class="form-check-label small" for="req-new-{{ $stage->id }}">{{ __('workflow.settings.step.required') }}</label>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <button class="btn btn-primary rounded-circle wf-add-step-btn" type="submit" title="Schritt hinzufügen">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .wf-stage-card {
        border-top: 4px solid var(--stage-color, var(--wf-primary));
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .wf-stage-card:hover {
        box-shadow: var(--wf-shadow-md);
        transform: translateY(-2px);
    }

    .wf-stage-inactive { opacity: .6; }

    .wf-stage-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.05rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
    }

    .wf-order-badge {
        font-size: .72rem;
        color: rgba(0, 39, 82, .55);
        font-weight: 600;
    }

    .wf-step-count {
        background: var(--wf-primary-tint);
        color: var(--wf-primary-strong);
        font-size: .7rem;
    }

    .wf-required-label {
        font-size: .72rem;
        color: rgba(0, 39, 82, .5);
        font-weight: 600;
    }

    .wf-add-step-form {
        background: var(--wf-primary-tint);
        border-radius: var(--wf-radius-sm);
        padding: .75rem .75rem .25rem;
    }

    .wf-add-step-btn {
        height: 38px;
        width: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
</style>