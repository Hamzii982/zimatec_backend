<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialConsumption extends Model
{
    protected $table = 'material_consumption';

    protected $fillable = [
        'material_id',
        'quantity',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
