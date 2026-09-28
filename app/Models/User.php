<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'company',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isAdmin(): bool
    {
        $role = $this->getRelationValue('role') ?? $this->role()->first();

        if ($role) {
            return (bool) $role->is_admin;
        }

        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        $role = $this->getRelationValue('role') ?? $this->role()->first();

        if ($role) {
            return true;
        }

        return $this->role === 'user';
    }

    public function hasModulePermission(string $module, string $action = 'read'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->getRelationValue('role') ?? $this->role()->first();

        if (! $role) {
            return false;
        }

        if ($action === 'read') {
            return $role->hasModulePermission($module, 'read');
        }

        return $role->hasModulePermission($module, $action);
    }

    public function canAccessModule(string $module, string $action = 'read'): bool
    {
        return $this->hasModulePermission($module, $action);
    }

    public function canWriteModule(string $module): bool
    {
        return $this->canAccessModule($module, 'write');
    }

    public function canDeleteModule(string $module): bool
    {
        return $this->canAccessModule($module, 'delete');
    }

    public function timeRecords()
    {
        return $this->hasMany(TimeRecord::class);
    }

    public function productionSchedules()
    {
        return $this->hasMany(ProductionSchedule::class);
    }

    public function assignedWorkflowProjects()
    {
        return $this->hasMany(\App\Models\Workflow\Project::class, 'current_assignee_id');
    }

    public function workflowActivities()
    {
        return $this->hasMany(\App\Models\Workflow\Activity::class, 'actor_id');
    }

    public function getCompanyName(): ?string
    {
        return match ($this->company) {
            'ZF' => 'Zimmermann Formtechnik',
            'ZT' => 'ZimaTec',
            default => null,
        };
    }

    public function getCompanyKey(): string
    {
        return $this->company === 'ZF'
            ? 'auftragsnummer_zf'
            : 'auftragsnummer_zt';
    }

    public function isZF(): bool
    {
        return $this->company === 'ZF';
    }

    public function isZT(): bool
    {
        return $this->company === 'ZT';
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn ($n) => mb_substr($n, 0, 1))
            ->take(2)
            ->implode('');
    }
}
