@extends('user.layouts.index')

@section('title', __('workflow.board.title'))

@section('content')
<div class="container-xxl py-4" x-data="{ assigneeId: '{{ $filters['assignee_id'] ?? '' }}', mine: {{ ! empty($filters['mine']) ? 'true' : 'false' }}, q: '{{ request('q') }}' }">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="mb-1 fw-semibold text-dark">
                <i class="bi bi-kanban-fill me-2" style="color: var(--wf-primary);"></i>{{ __('workflow.board.title') }}
            </h4>
            <p class="text-muted small mb-0">{{ __('workflow.board.subtitle') }}</p>
        </div>

        <form method="GET" action="{{ route('workflow.index') }}"
              class="workflow-toolbar d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group input-group-sm" style="min-width: 220px;">
                <span class="input-group-text bg-transparent border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="q" class="form-control border-start-0"
                       placeholder="{{ __('workflow.card.auftragsnummer') }}"
                       x-model="q">
            </div>

            <select name="assignee_id" class="form-select form-select-sm" x-model="assigneeId" style="max-width: 200px;">
                <option value="">{{ __('workflow.board.all_projects') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>

            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="mine" name="mine" value="1"
                       x-model="mine">
                <label class="form-check-label small" for="mine">
                    {{ __('workflow.board.my_projects') }}
                </label>
            </div>

            <button class="btn btn-sm btn-primary">
                <i class="bi bi-funnel me-1"></i>{{ __('workflow.board.filter_assignee') }}
            </button>
        </form>
    </div>

    <div class="wf-board-scroll">
        <div class="d-flex gap-3 workflow-board wf-board-track">
            @foreach ($stages as $stage)
                @php
                    $cards = $grouped[$stage->id] ?? collect();
                @endphp
                <div class="wf-board-col wf-fade-in" style="animation-delay: {{ $loop->index * .06 }}s;">
                    <div class="workflow-column h-100"
                         data-stage-id="{{ $stage->id }}"
                         style="--stage-color: {{ $stage->color }};">
                        <div class="workflow-column__header d-flex justify-content-between align-items-center p-3">
                            <h6 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                                <span class="wf-col-dot" style="background: {{ $stage->color }};"></span>
                                @if($stage->icon)
                                    <i class="bi {{ $stage->icon }}"></i>
                                @endif
                                {{ $stage->name }}
                            </h6>
                            <span class="badge rounded-pill bg-light text-dark border">{{ $cards->count() }}</span>
                        </div>
                        <div class="workflow-column__body" data-stage-body="{{ $stage->id }}">
                            @forelse ($cards as $workflowProject)
                                @include('user.workflow.card', ['workflowProject' => $workflowProject])
                            @empty
                                <div class="workflow-empty">
                                    <i class="bi bi-inboxes me-1"></i>{{ __('workflow.board.no_projects') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    @keyframes wfColFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .wf-fade-in { opacity: 0; animation: wfColFadeIn .4s ease forwards; }

    /* Horizontal, Jira-style board scroll instead of wrapping columns */
    .wf-board-scroll {
        overflow-x: auto;
        padding-bottom: .5rem;
        scrollbar-width: thin;
    }

    .wf-board-track { min-width: min-content; }

    .wf-board-col {
        flex: 0 0 300px;
        max-width: 300px;
    }

    .wf-col-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        display: inline-block;
    }

    /* Navy-tinted switch to match the rest of the module */
    .workflow-toolbar .form-check-input:checked {
        background-color: var(--wf-primary);
        border-color: var(--wf-primary);
    }

    .workflow-toolbar .form-check-input:focus {
        box-shadow: 0 0 0 .2rem rgba(0, 39, 82, .15);
    }

    @media (max-width: 767.98px) {
        .wf-board-col { flex-basis: 260px; max-width: 260px; }
    }
</style>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/workflow.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/workflow.js') }}"></script>
@endpush
@stop