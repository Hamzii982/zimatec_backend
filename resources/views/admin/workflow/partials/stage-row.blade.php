@php
    /** @var \App\Models\Workflow\Stage $stage */
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-semibold mb-0">
                <span class="badge" style="background-color: {{ $stage->color }};">{{ $stage->name }}</span>
                <span class="text-muted small ms-1">[{{ $stage->key }}]</span>
            </h6>
            <form method="POST" action="{{ route('admin.workflow.stages.destroy', $stage) }}"
                  onsubmit="return confirm('Stufe wirklich entfernen?')">
                @csrf @method('DELETE')
                <button class="btn btn-link btn-sm text-danger p-0">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('admin.workflow.stages.update', $stage) }}" class="row g-2 small">
            @csrf @method('PUT')
            <div class="col-md-4">
                <label class="form-label small mb-1">{{ __('workflow.settings.stage.name') }}</label>
                <input type="text" name="name" class="form-control form-control-sm" value="{{ $stage->name }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">{{ __('workflow.settings.stage.color') }}</label>
                <input type="color" name="color" class="form-control form-control-color form-control-sm" value="{{ $stage->color }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">{{ __('workflow.settings.stage.icon') }}</label>
                <input type="text" name="icon" class="form-control form-control-sm" value="{{ $stage->icon }}">
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">{{ __('workflow.settings.stage.order') }}</label>
                <input type="number" name="order_index" class="form-control form-control-sm" value="{{ $stage->order_index }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">{{ __('workflow.settings.stage.role') }}</label>
                <select name="required_role" class="form-select form-select-sm">
                    <option value="">—</option>
                    <option value="admin" @selected($stage->required_role === 'admin')>admin</option>
                    <option value="user" @selected($stage->required_role === 'user')>user</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active-{{ $stage->id }}" @checked($stage->is_active)>
                    <label class="form-check-label small" for="active-{{ $stage->id }}">{{ __('workflow.settings.stage.active') }}</label>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-sm btn-primary">{{ __('workflow.settings.stage.update') }}</button>
            </div>
        </form>

        <hr class="my-3">

        <h6 class="small fw-semibold mb-2">Prüfschritte</h6>
        <ul class="list-group list-group-flush mb-2">
            @foreach ($stage->steps as $step)
                @include('admin.workflow.partials.step-row', ['step' => $step])
            @endforeach
        </ul>

        <form method="POST" action="{{ route('admin.workflow.stages.steps.store', $stage) }}" class="row g-2 small">
            @csrf
            <div class="col-md-7">
                <input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('workflow.settings.step.name') }}" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="order_index" class="form-control form-control-sm" placeholder="{{ __('workflow.settings.step.order') }}">
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_required" value="1" id="req-new-{{ $stage->id }}" checked>
                    <label class="form-check-label small" for="req-new-{{ $stage->id }}">{{ __('workflow.settings.step.required') }}</label>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <button class="btn btn-sm btn-primary w-100">+</button>
            </div>
        </form>
    </div>
</div>
