<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'length_mm'    => 'decimal:2',
        'width_mm'     => 'decimal:2',
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
        do {
            $code = 'SHT-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }
}