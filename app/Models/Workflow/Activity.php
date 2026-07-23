<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $table = 'workflow_activities';

    public const UPDATED_AT = null;

    protected $fillable = [
        'workflow_project_id',
        'actor_id',
        'type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function workflowProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'workflow_project_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
