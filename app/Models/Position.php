<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Position extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'project_service_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectService()
    {
        return $this->belongsTo(ProjectService::class);
    }

    public function fraesdata(): HasMany
    {
        return $this->hasMany(Fraesdata::class);
    }

    public function latestFraesdata(): HasOne
    {
        return $this->hasOne(Fraesdata::class)->latestOfMany('created_at');
    }
}
