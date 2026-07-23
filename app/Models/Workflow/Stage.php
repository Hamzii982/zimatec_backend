<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    protected $table = 'workflow_stages';

    protected $fillable = [
        'key',
        'name',
        'color',
        'icon',
        'order_index',
        'required_role',
        'is_active',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(Step::class, 'stage_id')->orderBy('order_index');
    }

    public function workflowProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'current_stage_id');
    }
}
