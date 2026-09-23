<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'position_id', 'procedure_id', 'bauteil_id', 'machine_id', 'time_record_id', 'name', 'start_time', 'end_time', 'count', 'source_file', 'total_seconds'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function bauteil()
    {
        return $this->belongsTo(Bauteil::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function timeRecord()
    {
        return $this->belongsTo(TimeRecord::class);
    }

    public function getDurationSecondsAttribute()
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        return strtotime($this->end_time) - strtotime($this->start_time);
    }

    public function pauses()
    {
        return $this->hasMany(ProcessPause::class);
    }
}
