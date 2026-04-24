<?php

namespace App\Models;

use App\Enums\UrenStatus;
use Database\Factories\UrenregistratieFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Urenregistratie — één gewerkt tijdsblok door een zorgbegeleider voor een cliënt.
 *
 * US-11: aanmaken + bewerken als concept.
 * US-12: state-transitions (concept → ingediend → goedgekeurd/afgekeurd).
 *
 * user_id + status worden NIET via mass-assignment gezet; alleen via
 * UrenregistratieService (AC-4 "geen mass-assignment").
 */
class Urenregistratie extends Model
{
    /** @use HasFactory<UrenregistratieFactory> */
    use HasFactory;

    protected $table = 'urenregistraties';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'datum',
        'starttijd',
        'eindtijd',
        'uren',
        'notities',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datum' => 'date',
            'uren' => 'decimal:2',
            'status' => UrenStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
