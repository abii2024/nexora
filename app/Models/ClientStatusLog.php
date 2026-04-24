<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable log-rij voor elke statuswijziging op een Client.
 * Geen updated_at — audit-regels worden nooit gewijzigd (AVG art. 30).
 */
class ClientStatusLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'changed_by_user_id',
        'old_status',
        'new_status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
