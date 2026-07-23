<?php

namespace App\Models\Workflow;

use App\Models\Project as BaseProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'workflow_projects';

    protected $fillable = [
        'project_id',
        'current_stage_id',
        'current_assignee_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BaseProject::class, 'project_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'current_stage_id');
    }

    public function currentAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
    }

    public function projectSteps(): HasMany
    {
        return $this->hasMany(ProjectStep::class, 'workflow_project_id')->orderBy('order_index');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'workflow_project_id')->latest('created_at');
    }

    public function getProgressPercentAttribute(): int
    {
        $requiredSteps = $this->projectSteps()
            ->whereHas('step', function ($query) {
                $query->where('is_required', true);
            });

        $total = $requiredSteps->count() ?: $this->projectSteps()->count();

        if ($total === 0) {
            return 0;
        }

        $completed = (clone $requiredSteps)
            ->where('status', 'completed')
            ->count();

        return (int) round(($completed / $total) * 100);
    }
}
