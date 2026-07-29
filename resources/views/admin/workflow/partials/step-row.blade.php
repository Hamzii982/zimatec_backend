@php
    /** @var \App\Models\Workflow\Step $step */
@endphp
<li class="list-group-item d-flex align-items-center gap-2 px-0 bg-transparent">
    <form method="POST" action="{{ route('admin.workflow.steps.update', $step) }}" class="d-flex flex-grow-1 gap-2 align-items-center">
        @csrf @method('PUT')
        <input type="text" name="name" class="form-control form-control-sm flex-grow-1 rounded-pill" value="{{ $step->name }}" required>
        <input type="number" name="order_index" class="form-control form-control-sm rounded-pill" style="width: 80px;" value="{{ $step->order_index }}">
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="is_required" value="1" id="req-{{ $step->id }}" @checked($step->is_required)>
            <label class="form-check-label small text-muted" for="req-{{ $step->id }}">{{ __('workflow.settings.step.required') }}</label>
        </div>
        <button class="btn btn-sm btn-outline-primary rounded-pill">
            <i class="bi bi-check2"></i>
        </button>
    </form>
    <form method="POST" action="{{ route('admin.workflow.steps.destroy', $step) }}"
          onsubmit="return confirm('Schritt entfernen?')">
        @csrf @method('DELETE')
        <button class="btn btn-link btn-sm text-danger p-0" title="Löschen">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</li>