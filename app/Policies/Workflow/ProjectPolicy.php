<?php

namespace App\Policies\Workflow;

use App\Models\User;
use App\Models\Workflow\Project as WorkflowProject;
use App\Models\Workflow\ProjectStep;

class ProjectPolicy
{
    public function view(User $user, WorkflowProject $workflowProject): bool
    {
        return true;
    }

    public function update(User $user, WorkflowProject $workflowProject): bool
    {
        return $this->canActOnStage($user, $workflowProject);
    }

    public function completeStep(User $user, WorkflowProject $workflowProject, ?ProjectStep $projectStep = null): bool
    {
        if ($this->canActOnStage($user, $workflowProject)) {
            return true;
        }

        if ($projectStep && $projectStep->assignees->contains('id', $user->id)) {
            return true;
        }

        return false;
    }

    public function assignStep(User $user, WorkflowProject $workflowProject): bool
    {
        return $user->isAdmin()
            || $workflowProject->current_assignee_id === $user->id
            || $this->canActOnStage($user, $workflowProject);
    }

    public function unassignStep(User $user, WorkflowProject $workflowProject): bool
    {
        return $this->assignStep($user, $workflowProject);
    }

    public function advance(User $user, WorkflowProject $workflowProject): bool
    {
        return $this->canActOnStage($user, $workflowProject);
    }

    public function reassign(User $user, WorkflowProject $workflowProject): bool
    {
        return $user->isAdmin()
            || $workflowProject->current_assignee_id === $user->id;
    }

    protected function canActOnStage(User $user, WorkflowProject $workflowProject): bool
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

        return false;
    }
}
