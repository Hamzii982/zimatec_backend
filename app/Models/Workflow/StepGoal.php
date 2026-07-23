<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepGoal extends Model
{
    protected $table = 'workflow_step_goals';

    protected $fillable = [
        'workflow_project_step_id',
        'body',
        'created_by',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function projectStep(): BelongsTo
    {
        return $this->belongsTo(ProjectStep::class, 'workflow_project_step_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
