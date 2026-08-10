@extends('admin.layouts.index')

@section('title', __('workflow.settings.title'))

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4 wf-fade-in">
        <h4 class="mb-1 fw-semibold" style="color: var(--wf-primary-strong);">
            <i class="bi bi-diagram-3-fill me-2"></i>{{ __('workflow.settings.title') }}
        </h4>
        <p class="text-muted small mb-0">{{ __('workflow.settings.subtitle') }}</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="workflow-settings-card wf-accent-top wf-fade-in wf-delay-1">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="wf-panel-icon"><i class="bi bi-plus-circle-fill"></i></span>
                        <h6 class="fw-semibold mb-0">{{ __('workflow.settings.stage.new') }}</h6>
                    </div>
                    <form method="POST" action="{{ route('admin.workflow.stages.store') }}">
                        @csrf
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="key" id="stage-key" class="form-control" placeholder="office" required>
                                    <label for="stage-key">{{ __('workflow.settings.stage.key') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="name" id="stage-name" class="form-control" placeholder="Büro" required>
                                    <label for="stage-name">{{ __('workflow.settings.stage.name') }}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1 text-muted">{{ __('workflow.settings.stage.color') }}</label>
                                <input type="color" name="color" class="form-control form-control-color w-100" value="#002752">
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" name="icon" id="stage-icon" class="form-control" value="bi-columns-gap" placeholder="bi-columns-gap">
                                    <label for="stage-icon">{{ __('workflow.settings.stage.icon') }}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" name="order_index" id="stage-order" class="form-control" value="0" placeholder="0">
                                    <label for="stage-order">{{ __('workflow.settings.stage.order') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="required_role" id="stage-role" class="form-select">
                                        <option value="">—</option>
                                        <option value="admin">admin</option>
                                        <option value="user">user</option>
                                    </select>
                                    <label for="stage-role">{{ __('workflow.settings.stage.role') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="stage-active" checked>
                                    <label class="form-check-label small" for="stage-active">{{ __('workflow.settings.stage.active') }}</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end mt-3">
                                <button class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i>{{ __('workflow.settings.stage.create') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="workflow-settings-card wf-accent-top wf-fade-in wf-delay-2">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="wf-panel-icon"><i class="bi bi-link-45deg"></i></span>
                        <h6 class="fw-semibold mb-0">{{ __('workflow.settings.project.title') }}</h6>
                    </div>
                    <form method="POST" action="{{ route('admin.workflow.projects.attach') }}">
                        @csrf
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="project_id" id="wf-project-id" class="form-select" required>
                                        <option value="">—</option>
                                        @foreach (\App\Models\Project::orderBy('project_name')->get() as $project)
                                            <option value="{{ $project->id }}">{{ $project->project_name ?: ('#'.$project->id) }}</option>
                                        @endforeach
                                    </select>
                                    <label for="wf-project-id">{{ __('workflow.settings.project.project') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="stage_key" id="wf-stage-key" class="form-select">
                                        <option value="">{{ __('workflow.settings.project.default_stage') }}</option>
                                        @foreach (\App\Models\Workflow\Stage::where('is_active', true)->orderBy('order_index')->get() as $stage)
                                            <option value="{{ $stage->key }}">{{ $stage->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="wf-stage-key">{{ __('workflow.settings.project.stage') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <select name="assignee_id" id="wf-assignee" class="form-select">
                                        <option value="">—</option>
                                        @foreach (\App\Models\User::orderBy('name')->get() as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="wf-assignee">{{ __('workflow.settings.project.assignee') }}</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end mt-3">
                                <button class="btn btn-primary">
                                    <i class="bi bi-link-45deg me-1"></i>{{ __('workflow.settings.project.attach') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="workflow-toolbar d-flex align-items-center justify-content-between gap-3 mb-3 wf-fade-in wf-delay-3">
        <h6 class="fw-semibold text-uppercase small mb-0 text-nowrap" style="color: var(--wf-primary-strong);">
            <i class="bi bi-columns-gap me-1"></i>{{ $stages->count() }} {{ __('workflow.settings.stage.new') }}
        </h6>
        <div class="flex-grow-1" style="max-width: 320px;">
            <input type="search" id="wf-stage-filter" class="form-control form-control-sm" placeholder="Stufe suchen…">
        </div>
    </div>

    <div class="row g-3" id="wf-stage-grid">
        @forelse ($stages as $stage)
            <div class="col-lg-6 wf-stage-col wf-fade-in wf-delay-3" data-stage-name="{{ Str::lower($stage->name) }}">
                @include('admin.workflow.partials.stage-row', ['stage' => $stage])
            </div>
        @empty
            <div class="col-12">
                <div class="wf-empty">
                    <i class="bi bi-inboxes"></i>
                    <p class="mb-0 text-muted">Noch keine Stufen angelegt.</p>
                </div>
            </div>
        @endforelse
        <div class="col-12 d-none" id="wf-no-results">
            <div class="wf-empty">
                <i class="bi bi-search"></i>
                <p class="mb-0 text-muted">Keine Stufe gefunden.</p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Small additive layer — everything else already lives in workflow.css */

    @keyframes wfFadeInUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .wf-fade-in { opacity: 0; animation: wfFadeInUp .45s ease forwards; }
    .wf-delay-1 { animation-delay: .05s; }
    .wf-delay-2 { animation-delay: .12s; }
    .wf-delay-3 { animation-delay: .19s; }

    .wf-accent-top { border-top: 4px solid var(--wf-primary); }

    .wf-panel-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: var(--wf-primary-tint);
        color: var(--wf-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .wf-empty {
        text-align: center;
        padding: 3rem 1rem;
        background: rgba(255, 255, 255, .5);
        border: 2px dashed rgba(0, 39, 82, .12);
        border-radius: var(--wf-radius-sm);
        color: rgba(0, 39, 82, .45);
    }

    .wf-empty i { font-size: 2.2rem; display: block; margin-bottom: .6rem; }
</style>

<script>
    (function () {
        const filterInput = document.getElementById('wf-stage-filter');
        const columns = document.querySelectorAll('.wf-stage-col');
        const noResults = document.getElementById('wf-no-results');
        if (!filterInput) return;

        filterInput.addEventListener('input', () => {
            const term = filterInput.value.trim().toLowerCase();
            let visibleCount = 0;

            columns.forEach((col) => {
                const match = col.dataset.stageName.includes(term);
                col.classList.toggle('d-none', !match);
                if (match) visibleCount++;
            });

            noResults.classList.toggle('d-none', visibleCount !== 0 || term === '');
        });
    })();
</script>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/workflow.css') }}">
@endpush
@stop