<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project as BaseProject;
use App\Models\Workflow\Project as WorkflowProject;
use App\Models\Workflow\Stage;
use App\Models\Workflow\ProjectStep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $stages = Stage::where('is_active', true)
            ->orderBy('order_index')
            ->with(['steps' => fn ($q) => $q->orderBy('order_index')])
            ->get();

        $query = WorkflowProject::with(['project', 'currentStage', 'currentAssignee']);

        if ($request->filled('assignee_id')) {
            $query->where('current_assignee_id', (int) $request->input('assignee_id'));
        }

        if ($request->boolean('mine')) {
            $query->where('current_assignee_id', Auth::id());
        }

        $workflowProjects = $query->get();

        $grouped = $stages
            ->filter(function (Stage $stage) use ($workflowProjects) {
                return $workflowProjects->contains('current_stage_id', $stage->id);
            })
            ->mapWithKeys(function (Stage $stage) use ($workflowProjects) {
                return [
                    $stage->id => $workflowProjects->where('current_stage_id', $stage->id)->values(),
                ];
            });

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('user.workflow.index', [
            'stages' => $stages,
            'grouped' => $grouped,
            'users' => $users,
            'filters' => $request->only(['assignee_id', 'mine']),
        ]);
    }

    public function show(BaseProject $project)
    {
        $workflowProject = $project->workflowProject()->with([
            'projectSteps.step',
            'projectSteps.goals',
            'activities.actor',
            'currentStage',
            'currentAssignee',
        ])->firstOrFail();

        $canAdvance = app(\App\Services\Workflow\StageAdvancer::class)
            ->canAdvance($workflowProject);

        return view('user.workflow.show', [
            'project' => $project,
            'workflowProject' => $workflowProject,
            'canAdvance' => $canAdvance,
        ]);
    }

    public function history(BaseProject $project)
    {
        $workflowProject = $project->workflowProject()->firstOrFail();

        $activities = $workflowProject->activities()->with('actor')->get();

        return response()->json([
            'project_id' => $project->id,
            'activities' => $activities,
        ]);
    }
}
