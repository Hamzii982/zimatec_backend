<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepAssignee extends Model
{
    protected $table = 'workflow_project_step_assignees';

    protected $fillable = [
        'workflow_project_step_id',
        'user_id',
        'assigned_by',
    ];

    public function projectStep(): BelongsTo
    {
        return $this->belongsTo(ProjectStep::class, 'workflow_project_step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
