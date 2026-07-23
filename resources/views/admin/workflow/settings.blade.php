@extends('admin.layouts.index')

@section('title', __('workflow.settings.title'))

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="mb-1 fw-semibold">{{ __('workflow.settings.title') }}</h4>
        <p class="text-muted small mb-0">{{ __('workflow.settings.subtitle') }}</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">{{ __('workflow.settings.stage.new') }}</h6>
                    <form method="POST" action="{{ route('admin.workflow.stages.store') }}" class="row g-2 small">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('workflow.settings.stage.key') }}</label>
                            <input type="text" name="key" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('workflow.settings.stage.name') }}</label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">{{ __('workflow.settings.stage.color') }}</label>
                            <input type="color" name="color" class="form-control form-control-color form-control-sm" value="#0d6efd">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">{{ __('workflow.settings.stage.icon') }}</label>
                            <input type="text" name="icon" class="form-control form-control-sm" value="bi-columns-gap">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">{{ __('workflow.settings.stage.order') }}</label>
                            <input type="number" name="order_index" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('workflow.settings.stage.role') }}</label>
                            <select name="required_role" class="form-select form-select-sm">
                                <option value="">—</option>
                                <option value="admin">admin</option>
                                <option value="user">user</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label small">{{ __('workflow.settings.stage.active') }}</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-sm btn-primary">{{ __('workflow.settings.stage.create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">{{ __('workflow.settings.project.title') }}</h6>
                    <form method="POST" action="{{ route('admin.workflow.projects.attach') }}" class="row g-2 small">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('workflow.settings.project.project') }}</label>
                            <select name="project_id" class="form-select form-select-sm" required>
                                <option value="">—</option>
                                @foreach (\App\Models\Project::orderBy('project_name')->get() as $project)
                                    <option value="{{ $project->id }}">{{ $project->project_name ?: ('#'.$project->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('workflow.settings.project.stage') }}</label>
                            <select name="stage_key" class="form-select form-select-sm">
                                <option value="">{{ __('workflow.settings.project.default_stage') }}</option>
                                @foreach (\App\Models\Workflow\Stage::where('is_active', true)->orderBy('order_index')->get() as $stage)
                                    <option value="{{ $stage->key }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">{{ __('workflow.settings.project.assignee') }}</label>
                            <select name="assignee_id" class="form-select form-select-sm">
                                <option value="">—</option>
                                @foreach (\App\Models\User::orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-sm btn-primary">{{ __('workflow.settings.project.attach') }}</button>
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
@stop
