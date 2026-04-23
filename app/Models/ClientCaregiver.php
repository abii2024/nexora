<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot-model voor client_caregivers — geeft typed access tot de role-
 * en audit-kolommen bij koppelingen tussen Client en User.
 *
 * Role-constants worden herbruikt uit Client om 1 source-of-truth te houden.
 */
class ClientCaregiver extends Pivot
{
    public $incrementing = true;

    protected $table = 'client_caregivers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'user_id',
        'role',
        'created_by_user_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
