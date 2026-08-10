<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\CompleteStepRequest;
use App\Http\Requests\Workflow\ReassignProjectRequest;
use App\Http\Requests\Workflow\StoreGoalRequest;
use App\Models\Project as BaseProject;
use App\Models\User;
use App\Models\Workflow\ProjectStep;
use App\Models\Workflow\StepGoal;
use App\Services\Workflow\StageAdvancer;
use App\Services\Workflow\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectStepController extends Controller
{
    public function __construct(
        private readonly WorkflowService $service,
        private readonly StageAdvancer $advancer,
    ) {}

    public function addGoal(StoreGoalRequest $request, BaseProject $project, ProjectStep $step)
    {
        $workflowProject = $project->workflowProject()->firstOrFail();

        Gate::authorize('completeStep', $workflowProject);

        $goal = $this->service->addGoal($workflowProject, $step, $request->input('body'), $request->user());

        if ($request->wantsJson()) {
            return response()->json(['goal' => $goal->load('author')]);
        }

        return redirect()
            ->route('workflow.show', $project->id)
            ->with('success', 'Ziel hinzugefügt.');
    }

    public function destroyGoal(Request $request, BaseProject $project, ProjectStep $step, StepGoal $goal)
    {
        $workflowProject = $project->workflowProject()->firstOrFail();

        if ($request->user()->isAdmin() || $goal->created_by === $request->user()->id) {
            $this->service->destroyGoal($workflowProject, $step, $goal);
        } else {
            abort(403, 'Sie dürfen dieses Ziel nicht entfernen.');
        }

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        return redirect()
            ->route('workflow.show', $project->id)
            ->with('success', 'Ziel entfernt.');
    }

    public function complete(CompleteStepRequest $request, BaseProject $project, ProjectStep $step)
    {
        $workflowProject = $project->workflowProject()->firstOrFail();

        Gate::authorize('completeStep', [$workflowProject, $step]);

        $this->service->completeStep(
            $workflowProject,
            $step,
            $request->user(),
            $request->input('note')
        );

        if ($request->wantsJson()) {
            $canAdvance = $this->advancer->canAdvance($workflowProject->fresh('projectSteps'));

            return response()->json([
                'step' => $step->fresh(),
                'can_advance' => $canAdvance,
            ]);
        }

        return redirect()
            ->route('workflow.show', $project->id)
            ->with('success', 'Schritt abgeschlossen.');
    }

    public function assignStep(Request $request, BaseProject $project, ProjectStep $step)
    {
        $workflowProject = $project->workflowProject()->firstOrFail();

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        Gate::authorize('assignStep', $workflowProject);

        $assignee = User::findOrFail($data['user_id']);

        $this->service->assignStep($workflowProject, $step, $assignee, $request->user());

        if ($request->wantsJson()) {
            $step->load('assignees');

            return response()->json([
                'step' => $step,
            ]);
        }

        return redirect()
            ->route('workflow.show', $project->id)
            ->with('success', 'Bearbeiter hinzugefügt.');
    }

    public function unassignStep(Request $request, BaseProject $project, ProjectStep $step, User $user)
    {
        $workflowProject = $project->workflowProject()->firstOrFail();

        Gate::authorize('unassignStep', $workflowProject);

        $this->service->unassignStep($workflowProject, $step, $user, $request->user());

        if ($request->wantsJson()) {
            $step->load('assignees');

            return response()->json([
                'step' => $step,
            ]);
        }

        return redirect()
            ->route('workflow.show', $project->id)
            ->with('success', 'Bearbeiter entfernt.');
    }

    public function advance(Request $request, BaseProject $project)
    {
        $workflowProject = $project->workflowProject()->firstOrFail();

        Gate::authorize('advance', $workflowProject);

        $this->advancer->advance($workflowProject, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'workflow_project' => $workflowProject->fresh('currentStage', 'currentAssignee'),
            ]);
        }

        return redirect()
            ->route('workflow.index')
            ->with('success', 'Projekt an die nächste Stufe übergeben.');
    }

    public function reassign(ReassignProjectRequest $request, BaseProject $project)
    {
        $workflowProject = $project->workflowProject()->firstOrFail();

        Gate::authorize('reassign', $workflowProject);

        $newAssignee = User::findOrFail($request->integer('assignee_id'));

        $this->service->reassign($workflowProject, $newAssignee, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['workflow_project' => $workflowProject->fresh('currentAssignee')]);
        }

        return redirect()
            ->route('workflow.show', $project->id)
            ->with('success', 'Zuständigkeit aktualisiert.');
    }
}
