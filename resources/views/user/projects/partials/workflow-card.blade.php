@props(['workflowProject'])

@php
    /** @var \App\Models\Workflow\Project $workflowProject */
    $baseProject = $workflowProject->project;
    $totalSteps = $workflowProject->projectSteps->count();
    $completedSteps = $workflowProject->projectSteps->where('status', 'completed')->count();
    $percent = $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0;
    $stage = $workflowProject->currentStage;
@endphp

<div class="card workflow-card border-0 shadow-sm mb-3"
     data-workflow-card
     data-workflow-project="{{ $workflowProject->id }}"
     data-stage-id="{{ $stage?->id }}">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <div class="small text-muted">{{ __('workflow.card.auftragsnummer') }}</div>
                <div class="fw-semibold">
                    {{ $baseProject?->auftragsnummer_zf ?? $baseProject?->auftragsnummer_zt ?? '—' }}
                </div>
                <a href="{{ route('workflow.show', $baseProject?->id) }}"
                   class="text-decoration-none small d-block mt-1">
                    {{ $baseProject?->project_name ?? __('workflow.card.open') }}
                </a>
            </div>
            <x-workflow.stage-pill :stage="$stage" />
        </div>

        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>{{ __('workflow.card.assignee') }}:
                <strong>{{ $workflowProject->currentAssignee?->name ?? __('workflow.card.unassigned') }}</strong>
            </span>
            <span>{{ $completedSteps }}/{{ $totalSteps }}</span>
        </div>

        <x-workflow.progress-bar :percent="$percent" :color="$stage?->color ?? '#0d6efd'" />

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('workflow.show', $baseProject?->id) }}"
               class="btn btn-sm btn-outline-secondary flex-fill">
                <i class="bi bi-box-arrow-up-right me-1"></i>
                {{ __('workflow.card.open') }}
            </a>
        </div>
    </div>
</div>
