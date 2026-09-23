<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shelf extends Model
{
    protected $fillable = ['lager_id', 'name', 'code', 'is_active'];

    public function lager()
    {
        return $this->belongsTo(Lager::class, 'lager_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}
