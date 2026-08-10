<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Step extends Model
{
    protected $table = 'workflow_steps';

    protected $fillable = [
        'stage_id',
        'name',
        'description',
        'order_index',
        'is_required',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'is_required' => 'boolean',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    public function projectSteps(): HasMany
    {
        return $this->hasMany(ProjectStep::class, 'step_id');
    }
}
