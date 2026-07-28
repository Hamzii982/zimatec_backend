<?php

namespace App\Services\Workflow;

use App\Models\Project as BaseProject;
use App\Models\User;
use App\Models\Workflow\Activity;
use App\Models\Workflow\Project as WorkflowProject;
use App\Models\Workflow\ProjectStep;
use App\Models\Workflow\Stage;
use App\Models\Workflow\StepGoal;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    public function attachProject(BaseProject $project, ?Stage $stage = null, ?User $assignee = null): WorkflowProject
    {
        return DB::transaction(function () use ($project, $stage, $assignee) {
            $stage = $stage ?? Stage::where('is_active', true)->orderBy('order_index')->first();

            if (! $stage) {
                throw new \RuntimeException('Keine aktiven Workflow-Stufen vorhanden.');
            }

            $workflowProject = WorkflowProject::firstOrCreate(
                ['project_id' => $project->id],
                [
                    'current_stage_id' => $stage->id,
                    'current_assignee_id' => $assignee?->id,
                    'started_at' => now(),
                ]
            );

            $this->syncStepsForStage($workflowProject, $stage);

            $this->logActivity(
                $workflowProject,
                $assignee?->id,
                'project_attached',
                ['stage_id' => $stage->id]
            );

            return $workflowProject->fresh('projectSteps');
        });
    }

    public function addGoal(WorkflowProject $workflowProject, ProjectStep $projectStep, string $body, User $author): StepGoal
    {
        $this->assertStepBelongsToProject($workflowProject, $projectStep);

        $goal = StepGoal::create([
            'workflow_project_step_id' => $projectStep->id,
            'body' => $body,
            'created_by' => $author->id,
        ]);

        $this->logActivity(
            $workflowProject,
            $author->id,
            'goal_added',
            ['step_id' => $projectStep->step_id, 'goal_id' => $goal->id]
        );

        return $goal;
    }

    public function destroyGoal(WorkflowProject $workflowProject, ProjectStep $projectStep, StepGoal $goal): void
    {
        $this->assertStepBelongsToProject($workflowProject, $projectStep);

        if ($goal->workflow_project_step_id !== $projectStep->id) {
            abort(404, 'Ziel nicht gefunden.');
        }

        $goal->delete();

        $this->logActivity(
            $workflowProject,
            auth()->id(),
            'goal_removed',
            ['step_id' => $projectStep->step_id, 'goal_id' => $goal->id]
        );
    }

    public function completeStep(WorkflowProject $workflowProject, ProjectStep $projectStep, User $actor, ?string $note = null): ProjectStep
    {
        $this->assertStepBelongsToProject($workflowProject, $projectStep);

        $projectStep->update([
            'status' => 'completed',
            'started_at' => $projectStep->started_at ?? now(),
            'completed_at' => now(),
            'completed_by' => $actor->id,
            'note' => $note,
        ]);

        $this->logActivity(
            $workflowProject,
            $actor->id,
            'step_completed',
            ['step_id' => $projectStep->step_id]
        );

        return $projectStep->fresh();
    }

    public function reassign(WorkflowProject $workflowProject, User $newAssignee, User $actor): WorkflowProject
    {
        $workflowProject->update(['current_assignee_id' => $newAssignee->id]);

        $this->logActivity(
            $workflowProject,
            $actor->id,
            'assignee_changed',
            [
                'old_assignee_id' => $workflowProject->getOriginal('current_assignee_id'),
                'new_assignee_id' => $newAssignee->id,
            ]
        );

        return $workflowProject->fresh();
    }

    public function assignStep(WorkflowProject $workflowProject, ProjectStep $projectStep, User $assignee, User $actor): void
    {
        $this->assertStepBelongsToProject($workflowProject, $projectStep);

        $projectStep->assignees()->syncWithoutDetaching([
            $assignee->id => ['assigned_by' => $actor->id],
        ]);

        $this->logActivity(
            $workflowProject,
            $actor->id,
            'assignee_added',
            ['step_id' => $projectStep->step_id, 'user_id' => $assignee->id]
        );
    }

    public function unassignStep(WorkflowProject $workflowProject, ProjectStep $projectStep, User $assignee, User $actor): void
    {
        $this->assertStepBelongsToProject($workflowProject, $projectStep);

        $projectStep->assignees()->detach($assignee->id);

        $this->logActivity(
            $workflowProject,
            $actor->id,
            'assignee_removed',
            ['step_id' => $projectStep->step_id, 'user_id' => $assignee->id]
        );
    }

    public function allowedToComplete(WorkflowProject $workflowProject, ProjectStep $projectStep, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($workflowProject->current_assignee_id === $user->id) {
            return true;
        }

        $stage = $workflowProject->currentStage;
        if ($stage && $stage->required_role && $user->role === $stage->required_role) {
            return true;
        }

        return $projectStep->assignees->contains('id', $user->id);
    }

    public function syncStepsForStage(WorkflowProject $workflowProject, Stage $stage): void
    {
        $existingStepIds = $workflowProject->projectSteps()->pluck('step_id')->all();

        foreach ($stage->steps as $step) {
            if (in_array($step->id, $existingStepIds, true)) {
                continue;
            }

            ProjectStep::create([
                'workflow_project_id' => $workflowProject->id,
                'step_id' => $step->id,
                'order_index' => $step->order_index,
                'status' => 'pending',
            ]);
        }
    }

    protected function assertStepBelongsToProject(WorkflowProject $workflowProject, ProjectStep $projectStep): void
    {
        if ($projectStep->workflow_project_id !== $workflowProject->id) {
            abort(404, 'Schritt gehört nicht zum Projekt.');
        }
    }

    public function logActivity(WorkflowProject $workflowProject, ?int $actorId, string $type, array $payload = []): Activity
    {
        return Activity::create([
            'workflow_project_id' => $workflowProject->id,
            'actor_id' => $actorId,
            'type' => $type,
            'payload' => $payload,
        ]);
    }
}
