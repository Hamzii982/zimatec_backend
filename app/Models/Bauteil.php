<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bauteil extends Model
{
    use HasFactory;

    protected $table = 'bauteile'; // table name

    protected $fillable = [
        'name',
        'project_id',
        'parent_id',
        'is_werkzeug',
        'is_baugruppe',
        'image',
        'in_house_production',
    ];

    /**
     * The project this Bauteil belongs to
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Procedures associated with this Bauteil
     */
    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }

    /**
     * Parent Bauteil (if any)
     */
    public function parent()
    {
        return $this->belongsTo(Bauteil::class, 'parent_id');
    }

    /**
     * Child Bauteile
     */
    public function children()
    {
        return $this->hasMany(Bauteil::class, 'parent_id');
    }

    /**
     * Processes associated with this Bauteil
     */
    public function processes()
    {
        return $this->hasMany(Process::class, 'bauteil_id');
    }

    public function fraesdata(): HasMany
    {
        return $this->hasMany(Fraesdata::class);
    }

    public function latestFraesdata(): HasOne
    {
        return $this->hasOne(Fraesdata::class)->latestOfMany('created_at');
    }

    public function measurement()
    {
        return $this->hasOne(BauteilMeasurement::class, 'bauteil_id');
    }

    public function offers()
    {
        return $this->hasMany(SupplierOffer::class);
    }

    public function supplierProjects()
    {
        return $this->hasManyThrough(
            SupplierProject::class,
            SupplierOffer::class,
            'bauteil_id',
            'supplier_offer_id',
            'id',
            'id',
        );
    }
}
