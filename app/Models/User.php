<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_LEADER = 'leader';

    public const ROLE_STAFF = 'staff';

    protected $fillable = ['name', 'email', 'password', 'role', 'leader_id', 'is_active'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The leader account this user works on. For a leader it is their own record.
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(Leader::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isLeader(): bool
    {
        return $this->role === self::ROLE_LEADER && $this->leader_id !== null;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF && $this->leader_id !== null;
    }

    public function worksForLeader(): bool
    {
        return $this->isLeader() || $this->isStaff();
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }
}
