@props(['workflowProject'])

@php
    /** @var \App\Models\Workflow\Project $workflowProject */
    $baseProject = $workflowProject->project;
    $totalSteps = $workflowProject->projectSteps->count();
    $completedSteps = $workflowProject->projectSteps->where('status', 'completed')->count();
    $percent = $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0;
    $stage = $workflowProject->currentStage;
    $assignee = $workflowProject->currentAssignee;
    $assigneeInitials = $assignee ? mb_strtoupper(mb_substr($assignee->name, 0, 1)) : '?';
    $isAssignedToMe = $assignee?->id === auth()->id();
@endphp

<div class="workflow-card"
     data-workflow-card
     data-workflow-project="{{ $workflowProject->id }}"
     data-stage-id="{{ $stage?->id }}">
    <div class="workflow-card__head d-flex justify-content-between align-items-start">
        <div>
            <div class="workflow-card__auftrag">
                {{ __('workflow.card.auftragsnummer') }}
            </div>
            <div class="small text-muted">
                {{ $baseProject?->auftragsnummer_zf ?? $baseProject?->auftragsnummer_zt ?? '—' }}
            </div>
            <a href="{{ route('workflow.show', $baseProject?->id) }}"
               class="workflow-card__name">
                {{ $baseProject?->project_name ?? __('workflow.card.open') }}
            </a>
        </div>
        @if($isAssignedToMe)
            <span class="workflow-card__assigned-badge">
                <i class="bi bi-person-check me-1"></i>{{ __('workflow.steps.parallel_in_progress') }}
            </span>
        @endif
    </div>

    <div class="workflow-card__body">
        <x-workflow.stage-pill :stage="$stage" />
        <div class="d-flex justify-content-between align-items-center small text-muted mt-2">
            <span class="d-inline-flex align-items-center gap-2">
                <span class="workflow-card__avatar">{{ $assigneeInitials }}</span>
                <span>{{ $assignee?->name ?? __('workflow.card.unassigned') }}</span>
            </span>
            <span class="fw-semibold text-dark">{{ $completedSteps }}/{{ $totalSteps }}</span>
        </div>
        <div class="mt-2">
            <x-workflow.progress-bar :percent="$percent" :color="$stage?->color ?? '#002752'" />
        </div>
    </div>

    <div class="workflow-card__foot">
        <a href="{{ route('workflow.show', $baseProject?->id) }}"
           class="btn btn-sm btn-outline-primary flex-grow-1">
            <i class="bi bi-box-arrow-up-right me-1"></i>
            {{ __('workflow.card.open') }}
        </a>
    </div>
</div>
