<?php

namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project as BaseProject;
use App\Models\User;
use App\Models\Workflow\Project as WorkflowProject;
use App\Models\Workflow\Stage;
use App\Services\Workflow\WorkflowService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function attach(Request $request, BaseProject $project, WorkflowService $service)
    {
        $stageKey = $request->input('stage_key');
        $assigneeId = $request->input('assignee_id');

        $stage = $stageKey
            ? Stage::where('key', $stageKey)->firstOrFail()
            : Stage::where('is_active', true)->orderBy('order_index')->firstOrFail();

        $assignee = $assigneeId ? User::find($assigneeId) : null;

        $service->attachProject($project, $stage, $assignee);

        return redirect()
            ->route('admin.workflow.settings')
            ->with('success', 'Projekt dem Workflow zugeordnet.');
    }

    public function attachProjectSelection(Request $request, WorkflowService $service)
    {
        $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'stage_key' => ['nullable', 'string', 'exists:workflow_stages,key'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $project = BaseProject::findOrFail($request->integer('project_id'));
        $stageKey = $request->input('stage_key');
        $assigneeId = $request->input('assignee_id');

        $stage = $stageKey
            ? Stage::where('key', $stageKey)->firstOrFail()
            : Stage::where('is_active', true)->orderBy('order_index')->firstOrFail();

        $assignee = $assigneeId ? User::find($assigneeId) : null;

        $service->attachProject($project, $stage, $assignee);

        return redirect()
            ->route('admin.workflow.settings')
            ->with('success', 'Projekt dem Workflow zugeordnet.');
    }

    public function assign(Request $request, BaseProject $project, WorkflowService $service)
    {
        $request->validate([
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::findOrFail($request->integer('assignee_id'));

        $workflowProject = $project->workflowProject()->firstOrFail();

        $service->reassign($workflowProject, $assignee, $request->user());

        return redirect()
            ->route('workflow.show', $project->id)
            ->with('success', 'Zuständigkeit geändert.');
    }
}
