@extends('user.layouts.index')

@section('title', $project->project_name)

@section('content')
@php
    $totalSteps = $workflowProject->projectSteps->count();
    $completedSteps = $workflowProject->projectSteps->where('status', 'completed')->count();
    $overallPct = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
@endphp
<div class="container py-4" x-data="{ goalStepId: null }">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <a href="{{ route('workflow.index') }}" class="small text-decoration-none wf-back-link">
                <i class="bi bi-arrow-left me-1"></i>{{ __('workflow.board.title') }}
            </a>
            <h4 class="mb-1 fw-semibold">{{ $project->project_name }}</h4>
            <div class="small text-muted">
                @if($project->auftragsnummer_zf)
                    <span class="me-2">ZF: {{ $project->auftragsnummer_zf }}</span>
                @endif
                @if($project->auftragsnummer_zt)
                    <span>ZT: {{ $project->auftragsnummer_zt }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <x-workflow.stage-pill :stage="$workflowProject->currentStage" />
            <span class="text-muted small">
                {{ __('workflow.card.completed', ['completed' => $completedSteps, 'total' => $totalSteps]) }}
            </span>
        </div>
    </div>

    {{-- Overall progress --}}
    <div class="wf-overall-progress mb-4">
        <div class="workflow-progress">
            <div class="workflow-progress__bar" style="width: {{ $overallPct }}%; background: {{ $workflowProject->currentStage->color ?? 'var(--wf-primary)' }};"></div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <span class="small text-muted">{{ __('workflow.board.title') }}</span>
            <span class="small fw-semibold" style="color: var(--wf-primary-strong);">{{ $overallPct }}%</span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">
                        <i class="bi bi-list-check me-1"></i>Prüfschritte
                    </h6>

                    <div class="wf-timeline">
                        @php $lastStageId = null; @endphp
                        @foreach ($workflowProject->projectSteps as $index => $projectStep)
                            @php
                                $isCurrent = $workflowProject->current_stage_id === $projectStep->step->stage_id;
                                $previousDone = true;
                                for ($i = 0; $i < $index; $i++) {
                                    $prev = $workflowProject->projectSteps[$i];
                                    if ($prev->step->stage_id === $projectStep->step->stage_id && ! $prev->isCompleted()) {
                                        $previousDone = false;
                                        break;
                                    }
                                }
                                $isProjectAssignee = $workflowProject->currentAssignee?->id === auth()->id();
                                $isStepAssignee = $projectStep->assignees->contains('id', auth()->id());
                                $stageMatchesRole = $workflowProject->currentStage?->required_role
                                    && auth()->user()->role === $workflowProject->currentStage->required_role;
                                $canCompleteStep = $isCurrent
                                    && ! $projectStep->isCompleted()
                                    && $previousDone
                                    && (
                                        auth()->user()->isAdmin()
                                        || $isProjectAssignee
                                        || $isStepAssignee
                                        || $stageMatchesRole
                                    );

                                $canManageAssignees = $isCurrent
                                    && (
                                        auth()->user()->isAdmin()
                                        || $isProjectAssignee
                                        || $stageMatchesRole
                                    );

                                $candidateUsers = \App\Models\User::orderBy('name')
                                    ->whereNotIn('id', $projectStep->assignees->pluck('id'))
                                    ->get(['id', 'name']);

                                $stageChanged = $lastStageId !== $projectStep->step->stage_id;
                                $lastStageId = $projectStep->step->stage_id;
                            @endphp

                            @if($stageChanged)
                                <div class="wf-stage-heading {{ $index === 0 ? '' : 'mt-4' }}">
                                    <span class="wf-stage-heading-dot" style="background: {{ $projectStep->step->stage->color ?? 'var(--wf-primary)' }};"></span>
                                    <span class="wf-stage-heading-label">{{ $projectStep->step->stage->name ?? '' }}</span>
                                </div>
                            @endif

                            <div class="wf-timeline-item">
                                <div class="wf-timeline-marker {{ $projectStep->isCompleted() ? 'wf-marker-done' : ($isCurrent ? 'wf-marker-current' : 'wf-marker-pending') }}">
                                    @if($projectStep->isCompleted())
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </div>

                                <div class="workflow-step {{ $projectStep->isCompleted() ? 'workflow-step--done' : '' }} {{ $isCurrent && ! $projectStep->isCompleted() ? 'wf-step-current' : '' }}"
                                     data-step-id="{{ $projectStep->id }}"
                                     data-workflow-step>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold d-flex align-items-center gap-2">
                                                {{ $projectStep->step->name }}
                                                @if($isCurrent && ! $projectStep->isCompleted())
                                                    <span class="wf-current-badge">Aktuell</span>
                                                @endif
                                            </div>
                                            @if($projectStep->step->description)
                                                <div class="small text-muted">{{ $projectStep->step->description }}</div>
                                            @endif
                                            @if($projectStep->completer)
                                                <div class="small text-muted mt-1">
                                                    <i class="bi bi-check-circle text-success me-1"></i>
                                                    {{ __('workflow.steps.completed_by', ['name' => $projectStep->completer->name]) }}
                                                </div>
                                            @endif
                                        </div>
                                        @if($canCompleteStep)
                                            <form method="POST" action="{{ route('workflow.steps.complete', [$project->id, $projectStep->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success">
                                                    <i class="bi bi-check2 me-1"></i>
                                                    {{ __('workflow.steps.complete') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    {{-- Per-step assignees (parallel work) --}}
                                    <div class="mt-3 workflow-step__assignees">
                                        <div class="small text-muted mb-1">{{ __('workflow.steps.assignees') }}</div>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            @forelse ($projectStep->assignees as $assignee)
                                                <span class="badge rounded-pill bg-light text-dark border d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-person-circle"></i>
                                                    {{ $assignee->name }}
                                                    @if($canManageAssignees)
                                                        <button type="button"
                                                                class="btn btn-link btn-sm p-0 text-danger ms-1"
                                                                data-workflow-unassign
                                                                data-step="{{ $projectStep->id }}"
                                                                data-user="{{ $assignee->id }}"
                                                                title="{{ __('workflow.steps.unassign') }}">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    @endif
                                                </span>
                                            @empty
                                                <span class="small text-muted">{{ __('workflow.steps.assignee_placeholder') }}</span>
                                            @endforelse

                                            @if($canManageAssignees && $candidateUsers->isNotEmpty())
                                                <div class="dropdown">
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-primary rounded-pill"
                                                            data-bs-toggle="dropdown"
                                                            aria-expanded="false"
                                                            data-workflow-assign>
                                                        <i class="bi bi-person-plus me-1"></i>{{ __('workflow.steps.assign') }}
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" data-workflow-assign-menu>
                                                        @foreach ($candidateUsers as $candidate)
                                                            <li>
                                                                <button type="button"
                                                                        class="dropdown-item small"
                                                                        data-workflow-assign-option
                                                                        data-step="{{ $projectStep->id }}"
                                                                        data-user="{{ $candidate->id }}">
                                                                    <i class="bi bi-person-plus me-1"></i>{{ $candidate->name }}
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Goals --}}
                                    <div class="mt-3">
                                        <div class="small text-muted mb-1">Ziele</div>
                                        @forelse ($projectStep->goals as $goal)
                                            <div class="d-flex justify-content-between align-items-center small border-bottom py-1">
                                                <span>{{ $goal->body }}</span>
                                                @if($goal->created_by === auth()->id() || auth()->user()->isAdmin())
                                                    <form method="POST" action="{{ route('workflow.steps.goals.destroy', [$project->id, $projectStep->id, $goal->id]) }}">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-link btn-sm p-0 text-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="small text-muted">{{ __('workflow.steps.no_goals') }}</div>
                                        @endforelse

                                        @if($isCurrent)
                                            <form method="POST"
                                                  action="{{ route('workflow.steps.goals.store', [$project->id, $projectStep->id]) }}"
                                                  class="mt-2 d-flex gap-2"
                                                  x-show="goalStepId === {{ $projectStep->id }}"
                                                  x-cloak>
                                                @csrf
                                                <input type="text" name="body" class="form-control form-control-sm"
                                                       placeholder="{{ __('workflow.steps.goal_placeholder') }}" required>
                                                <button class="btn btn-sm btn-primary">{{ __('workflow.steps.add_goal') }}</button>
                                            </form>
                                            <button class="btn btn-sm btn-link p-0 mt-1"
                                                    x-show="goalStepId !== {{ $projectStep->id }}"
                                                    @click="goalStepId = {{ $projectStep->id }}">
                                                <i class="bi bi-plus-lg me-1"></i>{{ __('workflow.steps.add_goal') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="wf-advance-bar mt-3">
                        <form method="POST" action="{{ route('workflow.advance', $project->id) }}">
                            @csrf
                            <button class="btn btn-primary"
                                    {{ $canAdvance ? '' : 'disabled' }}
                                    data-workflow-advance>
                                <i class="bi bi-arrow-right-circle me-1"></i>
                                {{ __('workflow.card.advance') }}
                            </button>
                        </form>
                        @unless($canAdvance)
                            <span class="small text-muted ms-2">
                                <i class="bi bi-info-circle me-1"></i>Alle Pflichtschritte dieser Stufe müssen zuerst abgeschlossen sein.
                            </span>
                        @endunless
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">
                        <i class="bi bi-clock-history me-1"></i>{{ __('workflow.history.title') }}
                    </h6>

                    <ul class="list-group list-group-flush workflow-history wf-activity-feed">
                        @forelse ($workflowProject->activities as $activity)
                            @php
                                $activityMeta = match($activity->type) {
                                    'step_completed' => ['bi-check-circle-fill', 'text-success'],
                                    'goal_added' => ['bi-flag-fill', 'text-primary'],
                                    'goal_removed' => ['bi-flag', 'text-muted'],
                                    'assignee_added' => ['bi-person-plus-fill', 'text-info'],
                                    'assignee_removed' => ['bi-person-dash-fill', 'text-danger'],
                                    'assignee_changed' => ['bi-arrow-repeat', 'text-warning'],
                                    'project_attached' => ['bi-link-45deg', 'text-muted'],
                                    'project_completed' => ['bi-trophy-fill', 'text-success'],
                                    'stage_advanced' => ['bi-arrow-right-circle-fill', 'text-primary'],
                                    default => ['bi-dot', 'text-muted'],
                                };
                            @endphp
                            <li class="list-group-item px-0 wf-activity-item">
                                <span class="wf-activity-icon {{ $activityMeta[1] }}">
                                    <i class="bi {{ $activityMeta[0] }}"></i>
                                </span>
                                <div class="grow">
                                    <div class="small text-muted">{{ $activity->created_at->diffForHumans() }}</div>
                                    <div>
                                        <strong>{{ $activity->actor?->name ?? 'System' }}</strong>
                                        @switch($activity->type)
                                            @case('step_completed')
                                                {{ __('workflow.history.step_completed', ['step' => $workflowProject->projectSteps->firstWhere('step_id', $activity->payload['step_id'] ?? null)?->step->name ?? '?']) }}
                                                @break
                                            @case('goal_added')
                                                {{ __('workflow.history.goal_added', ['step' => '?']) }}
                                                @break
                                            @case('goal_removed')
                                                {{ __('workflow.history.goal_removed') }}
                                                @break
                                            @case('assignee_added')
                                                {{ __('workflow.history.assignee_added', ['user' => $activity->payload['user_id'] ?? '?']) }}
                                                @break
                                            @case('assignee_removed')
                                                {{ __('workflow.history.assignee_removed', ['user' => $activity->payload['user_id'] ?? '?']) }}
                                                @break
                                            @case('assignee_changed')
                                                {{ __('workflow.history.assignee_changed') }}
                                                @break
                                            @case('project_attached')
                                                {{ __('workflow.history.project_attached') }}
                                                @break
                                            @case('project_completed')
                                                {{ __('workflow.history.project_completed') }}
                                                @break
                                            @case('stage_advanced')
                                                {{ __('workflow.history.stage_advanced') }}
                                                @break
                                        @endswitch
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-muted small">{{ __('workflow.history.empty') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .wf-back-link { color: var(--wf-primary); font-weight: 600; }

    .wf-overall-progress .workflow-progress { height: 8px; }

    /* --- Stage group headings inside the checklist --- */
    .wf-stage-heading {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .75rem;
        text-transform: uppercase;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        color: rgba(0, 39, 82, .55);
    }

    .wf-stage-heading-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    /* --- Timeline layout --- */
    .wf-timeline { position: relative; }

    .wf-timeline-item {
        display: flex;
        gap: .85rem;
        position: relative;
        margin-bottom: .75rem;
    }

    .wf-timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 34px;
        bottom: -.75rem;
        width: 2px;
        background: rgba(0, 39, 82, .1);
    }

    .wf-timeline-marker {
        flex: 0 0 32px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        font-weight: 700;
        margin-top: 2px;
        z-index: 1;
    }

    .wf-marker-done {
        background: var(--wf-primary);
        color: #fff;
    }

    .wf-marker-current {
        background: #fff;
        color: var(--wf-primary);
        border: 2px solid var(--wf-primary);
        box-shadow: 0 0 0 4px rgba(0, 39, 82, .1);
    }

    .wf-marker-pending {
        background: #f1f3f5;
        color: #adb5bd;
        border: 1px solid #e9ecef;
    }

    .wf-timeline-item .workflow-step { flex: 1 1 auto; }

    .wf-step-current {
        border-color: rgba(0, 39, 82, .3) !important;
        box-shadow: var(--wf-shadow-md);
    }

    .wf-current-badge {
        font-size: .62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        background: var(--wf-primary-tint);
        color: var(--wf-primary-strong);
        padding: .15rem .5rem;
        border-radius: 999px;
    }

    .wf-advance-bar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .25rem;
    }

    /* --- Activity feed --- */
    .wf-activity-item {
        display: flex;
        gap: .65rem;
        align-items: flex-start;
    }

    .wf-activity-icon {
        width: 28px;
        height: 28px;
        min-width: 28px;
        border-radius: 50%;
        background: var(--wf-primary-tint);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
    }
</style>

@push('scripts')
    <script src="{{ asset('js/workflow.js') }}"></script>
    <script>
        document.body.dataset.workflowProjectId = "{{ $project->id }}";
        window.workflowRoutes = {
            assigneesStore: "{{ route('workflow.steps.assignees.store', ['__PROJECT__', '__STEP__']) }}",
            assigneesDestroy: "{{ route('workflow.steps.assignees.destroy', ['__PROJECT__', '__STEP__', '__USER__']) }}",
        };
    </script>
@endpush
@stop