<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'quantity',
        'tablar',
        'threshold',
        'type',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'threshold' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusAttribute()
    {
        if (is_null($this->threshold)) {
            return 'ok';
        }

        return $this->quantity <= $this->threshold ? 'low' : 'ok';
    }
}
