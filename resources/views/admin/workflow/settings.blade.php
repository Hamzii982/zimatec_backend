@extends('admin.layouts.index')

@section('title', __('workflow.settings.title'))

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="mb-1 fw-semibold text-dark">{{ __('workflow.settings.title') }}</h4>
        <p class="text-muted small mb-0">{{ __('workflow.settings.subtitle') }}</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="workflow-settings-card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3 text-dark">{{ __('workflow.settings.stage.new') }}</h6>
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
                                <label class="form-label small mb-1">{{ __('workflow.settings.stage.color') }}</label>
                                <input type="color" name="color" class="form-control form-control-color" value="#002752">
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
                                <button class="btn btn-primary">{{ __('workflow.settings.stage.create') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="workflow-settings-card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3 text-dark">{{ __('workflow.settings.project.title') }}</h6>
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
                                <button class="btn btn-primary">{{ __('workflow.settings.project.attach') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($stages as $stage)
            <div class="col-lg-6">
                @include('admin.workflow.partials.stage-row', ['stage' => $stage])
            </div>
        @endforeach
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/workflow.css') }}">
@endpush
@stop