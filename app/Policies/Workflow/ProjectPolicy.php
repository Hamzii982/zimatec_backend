<?php

namespace App\Policies\Workflow;

use App\Models\User;
use App\Models\Workflow\Project as WorkflowProject;

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

    public function completeStep(User $user, WorkflowProject $workflowProject): bool
    {
        return $this->canActOnStage($user, $workflowProject);
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
