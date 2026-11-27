<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialConsumption extends Model
{
    protected $table = 'material_consumption';

    protected $fillable = [
        'material_id',
        'quantity',
        'consumption_type',
        'consumption_time',
    ];

    protected $casts = [
        'consumption_time' => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function scopeUsed($query)
    {
        return $query->where('consumption_type', 'use');
    }

    public function scopeReturned($query)
    {
        return $query->where('consumption_type', 'return');
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('consumption_time', [$startDate, $endDate]);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->consumption_type) {
            'use' => 'Entnommen',
            'return' => 'Zurückgelegt',
            'reserve' => 'Reserviert',
            'damaged' => 'Beschädigt ausgebucht',
            'restock' => 'Aufgefüllt',
            'audit_adjust' => 'Korrektur',
            'delivery' => 'Geliefert',
            default => ucfirst($this->consumption_type),
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->consumption_type) {
            'use' => 'bg-primary-subtle text-primary-emphasis',
            'return', 'restock' => 'bg-success-subtle text-success-emphasis',
            'damaged' => 'bg-danger-subtle text-danger-emphasis',
            'reserve' => 'bg-info-subtle text-info-emphasis',
            'audit_adjust' => 'bg-warning-subtle text-warning-emphasis',
            'delivery' => 'bg-primary-subtle text-primary-emphasis',
            default => 'bg-secondary-subtle text-secondary-emphasis',
        };
    }
}
