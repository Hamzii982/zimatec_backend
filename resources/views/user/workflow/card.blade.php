@php
    /** @var \App\Models\Workflow\WorkflowProject $workflowProject */
    $project = $workflowProject->project;
    $total = $workflowProject->projectSteps->count();
    $completed = $workflowProject->projectSteps->where('status', 'completed')->count();
    $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
    $assignee = $workflowProject->currentAssignee;
    $initials = $assignee ? collect(explode(' ', $assignee->name))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('') : null;
@endphp
{{-- NOTE: adjust the route name below if your show-page route isn't "workflow.projects.show" --}}
<div class="workflow-card mb-2" data-workflow-project="{{ $project->id }}">
    <a href="{{ route('workflow.show', $project->id) }}" class="text-decoration-none">
        <div class="workflow-card__head">
            <div class="workflow-card__auftrag">
                @if($project->auftragsnummer_zf)
                    ZF {{ $project->auftragsnummer_zf }}
                @elseif($project->auftragsnummer_zt)
                    ZT {{ $project->auftragsnummer_zt }}
                @else
                    #{{ $project->id }}
                @endif
            </div>
            <span class="workflow-card__name">{{ Str::limit($project->project_name, 42) }}</span>
        </div>

        <div class="workflow-card__body">
            <div class="workflow-progress">
                <div class="workflow-progress__bar" style="width: {{ $pct }}%; background: var(--stage-color, var(--wf-primary));"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <span class="wf-card-steps">{{ $completed }}/{{ $total }} Schritte</span>
                <span class="wf-card-pct">{{ $pct }}%</span>
            </div>
        </div>

        <div class="workflow-card__foot">
            @if($assignee)
                <span class="d-flex align-items-center gap-2">
                    <span class="workflow-card__avatar" title="{{ $assignee->name }}">{{ $initials }}</span>
                    <span class="small text-muted">{{ Str::limit($assignee->name, 16) }}</span>
                </span>
            @else
                <span class="small text-muted fst-italic">{{ __('workflow.steps.assignee_placeholder') }}</span>
            @endif

            @if($workflowProject->currentStage?->required_role)
                <span class="workflow-card__assigned-badge">
                    <i class="bi bi-shield-lock me-1"></i>{{ $workflowProject->currentStage->required_role }}
                </span>
            @endif
        </div>
    </a>
</div>

<style>
    .wf-card-steps {
        font-size: .68rem;
        color: rgba(0, 39, 82, .5);
        font-weight: 600;
    }

    .wf-card-pct {
        font-size: .68rem;
        color: rgba(0, 39, 82, .55);
        font-weight: 700;
    }
</style>