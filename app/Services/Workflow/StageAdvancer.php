<?php

namespace App\Services\Workflow;

use App\Models\User;
use App\Models\Workflow\Project as WorkflowProject;
use App\Models\Workflow\ProjectStep;
use App\Models\Workflow\Stage;
use Illuminate\Support\Facades\DB;
use function App\Helpers\new_notification;

class StageAdvancer
{
    public function __construct(
        protected WorkflowService $service,
    ) {}

    public function canAdvance(WorkflowProject $workflowProject): bool
    {
        $required = $workflowProject->projectSteps()
            ->whereHas('step', function ($query) {
                $query->where('is_required', true);
            })
            ->get();

        if ($required->isEmpty()) {
            return false;
        }

        return $required->every(fn (ProjectStep $step) => $step->isCompleted());
    }

    public function advance(WorkflowProject $workflowProject, User $actor): WorkflowProject
    {
        if (! $this->canAdvance($workflowProject)) {
            abort(422, 'Es sind nicht alle erforderlichen Schritte abgeschlossen.');
        }

        return DB::transaction(function () use ($workflowProject, $actor) {
            $currentStage = $workflowProject->currentStage;

            $nextStage = Stage::where('is_active', true)
                ->where('order_index', '>', $currentStage->order_index)
                ->orderBy('order_index')
                ->first();

            $fromStageId = $currentStage->id;
            $toStageId = $nextStage?->id ?? $currentStage->id;

            $payload = [
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $toStageId,
            ];

            if (! $nextStage) {
                // Final stage reached
                $workflowProject->update([
                    'completed_at' => now(),
                ]);

                $this->service->logActivity(
                    $workflowProject,
                    $actor->id,
                    'project_completed',
                    $payload
                );

                return $workflowProject->fresh();
            }

            $newAssignee = $this->resolveNextAssignee($nextStage, $workflowProject);

            $workflowProject->update([
                'current_stage_id' => $nextStage->id,
                'current_assignee_id' => $newAssignee?->id,
            ]);

            $this->service->syncStepsForStage($workflowProject, $nextStage);

            $this->service->logActivity(
                $workflowProject,
                $actor->id,
                'stage_advanced',
                $payload
            );

            if ($newAssignee) {
                $this->notifyNewAssignee($workflowProject, $nextStage, $newAssignee);
            }

            return $workflowProject->fresh('projectSteps', 'currentStage', 'currentAssignee');
        });
    }

    protected function resolveNextAssignee(Stage $nextStage, WorkflowProject $workflowProject): ?User
    {
        $baseProject = $workflowProject->project;

        if (! $baseProject) {
            return null;
        }

        $departmentMap = [
            'office' => 'office',
            'design' => 'design',
            'workshop' => 'workshop',
            'management' => 'management',
        ];

        $department = $departmentMap[$nextStage->key] ?? null;

        if ($department) {
            $user = User::where('department', $department)->orderBy('id')->first();
            if ($user) {
                return $user;
            }
        }

        if ($nextStage->required_role) {
            $user = User::where('role', $nextStage->required_role)
                ->orderBy('id')
                ->first();
            if ($user) {
                return $user;
            }
        }

        return User::orderBy('id')->first();
    }

    protected function notifyNewAssignee(WorkflowProject $workflowProject, Stage $nextStage, User $assignee): void
    {
        $baseProject = $workflowProject->project;

        $message = sprintf(
            'Projekt %s wurde an die Stufe "%s" übergeben.',
            $baseProject?->project_name ?? ('#' . ($baseProject?->id ?? '?')),
            $nextStage->name
        );

        $url = route('workflow.show', $baseProject?->id ?? 0);

        new_notification('workflow_stage', $message, $url, $assignee->id);
    }
}
