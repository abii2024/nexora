<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIEF = 'actief';

    public const STATUS_WACHT = 'wacht';

    public const STATUS_INACTIEF = 'inactief';

    public const CARE_WMO = 'wmo';

    public const CARE_WLZ = 'wlz';

    public const CARE_JW = 'jw';

    public const ROLE_PRIMAIR = 'primair';

    public const ROLE_SECUNDAIR = 'secundair';

    public const ROLE_TERTIAIR = 'tertiair';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'voornaam',
        'achternaam',
        'email',
        'telefoon',
        'bsn',
        'geboortedatum',
        'status',
        'care_type',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'geboortedatum' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Alle zorgbegeleiders die aan deze cliënt zijn gekoppeld (primair/secundair/tertiair).
     */
    public function caregivers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_caregivers')
            ->using(ClientCaregiver::class)
            ->withPivot(['id', 'role', 'created_by_user_id'])
            ->withTimestamps();
    }

    /**
     * Immutable statuswijzigingen — audit trail voor AVG art. 5 (juistheid + traceerbaarheid).
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ClientStatusLog::class)->latest();
    }

    public function fullName(): string
    {
        return trim($this->voornaam.' '.$this->achternaam);
    }
}
