<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lager extends Model
{
    protected $table = 'lager';

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
        'status',
    ];

    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    public function activeMaterials()
    {
        return $this->hasMany(Material::class)->where('is_active', true);
    }
}
