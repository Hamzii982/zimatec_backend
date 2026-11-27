<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraesdataTool extends Model
{
    protected $table = 'fraesdata_tools';

    protected $fillable = [
        'fraesdata_id',
        'material_id',
        'tool_number',
        'notes',
    ];

    public function fraesdata(): BelongsTo
    {
        return $this->belongsTo(Fraesdata::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
