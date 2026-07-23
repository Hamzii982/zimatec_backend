@extends('user.layouts.index')

@section('title', $project->project_name)

@section('content')
<div class="container py-4" x-data="{ goalStepId: null }">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-2">
        <div>
            <a href="{{ route('workflow.index') }}" class="small text-decoration-none">
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
                {{ __('workflow.card.completed', ['completed' => $workflowProject->projectSteps->where('status', 'completed')->count(), 'total' => $workflowProject->projectSteps->count()]) }}
            </span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Prüfschritte</h6>

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
                            $canCompleteStep = $isCurrent
                                && $workflowProject->currentAssignee?->id === auth()->id()
                                && $previousDone
                                && ! $projectStep->isCompleted();
                        @endphp

                        <div class="workflow-step border rounded p-3 mb-2 {{ $projectStep->is_completed ? 'workflow-step--done' : '' }}"
                             data-step-id="{{ $projectStep->id }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">
                                        <i class="bi {{ $projectStep->isCompleted() ? 'bi-check-circle-fill text-success' : 'bi-circle' }} me-1"></i>
                                        {{ $projectStep->step->name }}
                                    </div>
                                    @if($projectStep->step->description)
                                        <div class="small text-muted">{{ $projectStep->step->description }}</div>
                                    @endif
                                    @if($projectStep->completer)
                                        <div class="small text-muted mt-1">
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
                    @endforeach

                    <form method="POST" action="{{ route('workflow.advance', $project->id) }}" class="mt-3">
                        @csrf
                        <button class="btn btn-primary"
                                {{ $canAdvance ? '' : 'disabled' }}
                                data-workflow-advance>
                            <i class="bi bi-arrow-right-circle me-1"></i>
                            {{ __('workflow.card.advance') }}
                        </button>
                    </form>                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">{{ __('workflow.history.title') }}</h6>

                    <ul class="list-group list-group-flush workflow-history">
                        @forelse ($workflowProject->activities as $activity)
                            <li class="list-group-item px-0">
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
@stop
