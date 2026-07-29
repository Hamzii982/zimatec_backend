@php
    /** @var \App\Models\Workflow\Stage $stage */
@endphp
<div class="workflow-settings-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0 text-dark d-flex align-items-center gap-2">
                <span class="workflow-stage-pill badge rounded-pill text-white"
                      style="background-color: {{ $stage->color }};">
                    @if($stage->icon)<i class="bi {{ $stage->icon }} me-1"></i>@endif
                    {{ $stage->name }}
                </span>
                <code class="small text-muted">[{{ $stage->key }}]</code>
            </h6>

            <div class="btn-group btn-group-icon" role="group">
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
                    <label class="form-label small mb-1">{{ __('workflow.settings.stage.color') }}</label>
                    <input type="color" name="color" class="form-control form-control-color" value="{{ $stage->color }}">
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
                    <button class="btn btn-sm btn-primary">{{ __('workflow.settings.stage.update') }}</button>
                </div>
            </div>
        </form>

        <hr class="my-3 opacity-50">

        <h6 class="small fw-semibold mb-2 text-uppercase text-muted">Prüfschritte</h6>
        <ul class="list-group list-group-flush mb-3">
            @foreach ($stage->steps as $step)
                @include('admin.workflow.partials.step-row', ['step' => $step])
            @endforeach
        </ul>

        <form method="POST" action="{{ route('admin.workflow.stages.steps.store', $stage) }}">
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
                    <button class="btn btn-primary rounded-circle" style="height: 38px; width: 38px;" type="submit">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>