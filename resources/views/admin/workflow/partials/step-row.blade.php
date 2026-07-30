@php
    /** @var \App\Models\Workflow\Step $step */
@endphp
<li class="list-group-item d-flex align-items-center gap-2 px-2 bg-transparent wf-step-item">
    <i class="bi bi-grip-vertical wf-step-handle" title="{{ __('workflow.settings.step.order') }}"></i>

    <form method="POST" action="{{ route('admin.workflow.steps.update', $step) }}" class="d-flex flex-grow-1 gap-2 align-items-center">
        @csrf @method('PUT')
        <input type="text" name="name" class="form-control form-control-sm flex-grow-1 rounded-pill" value="{{ $step->name }}" required>
        <input type="number" name="order_index" class="form-control form-control-sm rounded-pill wf-order-input" value="{{ $step->order_index }}">
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="is_required" value="1" id="req-{{ $step->id }}" @checked($step->is_required)>
            <label class="form-check-label small text-muted" for="req-{{ $step->id }}">{{ __('workflow.settings.step.required') }}</label>
        </div>
        <button class="btn btn-sm btn-outline-primary rounded-pill wf-step-save" title="{{ __('workflow.settings.stage.update') }}">
            <i class="bi bi-check2"></i>
        </button>
    </form>

    <form method="POST" action="{{ route('admin.workflow.steps.destroy', $step) }}"
          onsubmit="return confirm('Schritt entfernen?')">
        @csrf @method('DELETE')
        <button class="btn btn-sm wf-step-delete" title="Löschen">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</li>

<style>
    .wf-step-item {
        padding-top: .5rem;
        padding-bottom: .5rem;
        border-color: rgba(0, 39, 82, .06) !important;
        transition: background-color .15s ease;
    }

    .wf-step-item:hover { background-color: var(--wf-primary-tint); }

    .wf-step-handle {
        color: #ced4da;
        cursor: grab;
        font-size: 1rem;
    }

    .wf-order-input { width: 64px; }

    .wf-step-save { height: 31px; }

    .wf-step-delete {
        border: none;
        background: transparent;
        color: #adb5bd;
        width: 31px;
        height: 31px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .15s ease, color .15s ease;
    }

    .wf-step-delete:hover {
        background: #fdeaea;
        color: #b3261e;
    }
</style>