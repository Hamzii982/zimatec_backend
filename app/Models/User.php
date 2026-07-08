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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function timeRecords()
    {
        return $this->hasMany(TimeRecord::class);
    }

    public function productionSchedules()
    {
        return $this->hasMany(ProductionSchedule::class);
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
}
