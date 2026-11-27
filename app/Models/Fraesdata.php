<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fraesdata extends Model
{
    protected $table = 'fraesdata';

    protected $fillable = [
        'project_id',
        'position_id',
        'bauteil_id',
        'related_position_id',
        'name',
        'document_path',
        'uploaded_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function bauteil(): BelongsTo
    {
        return $this->belongsTo(Bauteil::class);
    }

    public function relatedPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'related_position_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function tools(): HasMany
    {
        return $this->hasMany(FraesdataTool::class);
    }
}
