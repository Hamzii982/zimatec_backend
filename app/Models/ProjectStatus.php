<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    protected $fillable = ['name', 'color'];

    public function supplierProjects()
    {
        return $this->hasMany(SupplierProject::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'project_status_id');
    }
}
