<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    public const ROLE_ZORGBEGELEIDER = 'zorgbegeleider';

    public const ROLE_TEAMLEIDER = 'teamleider';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'team_id',
        'dienstverband',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Cliënten waar deze gebruiker aan gekoppeld is (als zorgbegeleider).
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_caregivers')
            ->withPivot(['role', 'created_by_user_id'])
            ->withTimestamps();
    }

    /**
     * Audit-log van alle veldwijzigingen op dit user-record (US-05).
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(UserAuditLog::class)->orderByDesc('created_at');
    }

    public function isTeamleider(): bool
    {
        return $this->role === self::ROLE_TEAMLEIDER;
    }

    public function isZorgbegeleider(): bool
    {
        return $this->role === self::ROLE_ZORGBEGELEIDER;
    }
}
