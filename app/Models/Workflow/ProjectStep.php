<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStep extends Model
{
    protected $table = 'workflow_project_steps';

    protected $fillable = [
        'workflow_project_id',
        'step_id',
        'status',
        'started_at',
        'completed_at',
        'completed_by',
        'note',
        'order_index',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'order_index' => 'integer',
    ];

    public function workflowProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'workflow_project_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(Step::class, 'step_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(StepGoal::class, 'workflow_project_step_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'workflow_project_step_assignees',
            'workflow_project_step_id',
            'user_id'
        )->withPivot(['assigned_by'])->withTimestamps();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
