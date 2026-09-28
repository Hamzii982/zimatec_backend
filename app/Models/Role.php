<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }

    public function hasModulePermission(string $module, string $action = 'read'): bool
    {
        if ($action === 'read') {
            return $this->permissions()
                ->where('module', $module)
                ->whereIn('action', ['read', 'write'])
                ->exists();
        }

        return $this->permissions()
            ->where('module', $module)
            ->where('action', $action)
            ->exists();
    }
}
