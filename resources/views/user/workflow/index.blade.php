@extends('user.layouts.index')

@section('title', __('workflow.board.title'))

@section('content')
<div class="container py-4" x-data="{ assigneeId: '{{ $filters['assignee_id'] ?? '' }}', mine: {{ ! empty($filters['mine']) ? 'true' : 'false' }} }">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-0 fw-semibold">{{ __('workflow.board.title') }}</h4>
            <p class="text-muted small mb-0">{{ __('workflow.board.subtitle') }}</p>
        </div>
        <form method="GET" action="{{ route('workflow.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-1">{{ __('workflow.board.filter_assignee') }}</label>
                <select name="assignee_id" class="form-select form-select-sm" x-model="assigneeId">
                    <option value="">{{ __('workflow.board.all_projects') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-check pb-1">
                <input class="form-check-input" type="checkbox" id="mine" name="mine" value="1"
                       x-model="mine">
                <label class="form-check-label small" for="mine">
                    {{ __('workflow.board.my_projects') }}
                </label>
            </div>
            <button class="btn btn-sm btn-primary">
                <i class="bi bi-funnel"></i>
            </button>
        </form>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 workflow-board">
        @foreach ($stages as $stage)
            @php
                $cards = $grouped[$stage->id] ?? collect();
            @endphp
            <div class="col">
                <div class="workflow-column h-100"
                     data-stage-id="{{ $stage->id }}"
                     style="--stage-color: {{ $stage->color }};">
                    <div class="workflow-column__header d-flex justify-content-between align-items-center p-2 rounded-top">
                        <h6 class="mb-0 fw-semibold">
                            @if($stage->icon)
                                <i class="bi {{ $stage->icon }} me-1"></i>
                            @endif
                            {{ $stage->name }}
                        </h6>
                        <span class="badge bg-light text-dark">{{ $cards->count() }}</span>
                    </div>
                    <div class="workflow-column__body p-2" data-stage-body="{{ $stage->id }}">
                        @forelse ($cards as $workflowProject)
                            @include('user.projects.partials.workflow-card', ['workflowProject' => $workflowProject])
                        @empty
                            <p class="small text-muted text-center py-3 mb-0">
                                {{ __('workflow.board.no_projects') }}
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/workflow.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/workflow.js') }}"></script>
@endpush
@stop
