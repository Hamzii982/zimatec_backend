<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MaterialSheet extends Model
{
    protected $fillable = [
        'material_id',
        'code',
        'length_mm',
        'width_mm',
        'thickness_mm',
        'status',
        'parent_sheet_id',
        'sibling_group_id',
    ];

    protected $casts = [
        'length_mm' => 'decimal:2',
        'width_mm' => 'decimal:2',
        'thickness_mm' => 'decimal:2',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function parent()
    {
        return $this->belongsTo(MaterialSheet::class, 'parent_sheet_id');
    }

    public function children()
    {
        return $this->hasMany(MaterialSheet::class, 'parent_sheet_id');
    }

    public function scopeInStock($q)
    {
        return $q->where('status', 'in_stock');
    }

    public function scopeFullSheets($q)
    {
        return $q->whereNull('parent_sheet_id');
    }

    public function scopeCutSheets($q)
    {
        return $q->whereNotNull('parent_sheet_id');
    }

    public function isFullSheet(): bool
    {
        return is_null($this->parent_sheet_id);
    }

    public static function generateCode(): string
    {
        // Globally unique. UUID v4 hex (without dashes) gives 32 chars of randomness,
        // making phantom collisions across concurrent transactions computationally
        // impossible. The schema column is `string(32)`, so we use 28 hex chars of
        // the UUID (still ~10^33 possibilities — no realistic chance of collision)
        // and keep the code exactly 12 chars total.
        return 'SHT-'.strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 8));
    }
}
