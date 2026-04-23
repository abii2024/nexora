<?php

namespace App\Services;

use App\Enums\UrenStatus;
use App\Models\Urenregistratie;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Centrale plek voor urenregistratie-queries en -mutaties (US-11).
 *
 * AC-4: status + user_id nooit via mass-assignment — altijd server-side.
 * AC-3: duur (uren) wordt server-side berekend uit start/eindtijd.
 */
class UrenregistratieService
{
    /**
     * Alleen eigen uren (zorgbegeleider). Teamleider-scope komt in US-13.
     */
    public function scopedForUser(User $user, ?UrenStatus $status = null): Builder
    {
        $query = Urenregistratie::query()
            ->where('user_id', $user->id)
            ->with(['client'])
            ->orderByDesc('datum')
            ->orderByDesc('starttijd');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query;
    }

    public function getPaginated(User $user, ?UrenStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->scopedForUser($user, $status)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Bereken decimale uren uit HH:MM:SS start + eind.
     *
     * Gebruikt integer-seconds i.p.v. DateTime-diff om float-wobble te vermijden.
     */
    public function computeDuration(string $starttijd, string $eindtijd): float
    {
        $start = strtotime('1970-01-01 '.$starttijd);
        $eind = strtotime('1970-01-01 '.$eindtijd);

        if ($eind <= $start) {
            return 0.0;
        }

        return round(($eind - $start) / 3600, 2);
    }

    /**
     * US-11 AC-4: nieuwe entry altijd status=Concept + user_id=$user->id.
     *
     * @param  array<string, mixed>  $payload  uit StoreUrenregistratieRequest
     */
    public function create(User $user, array $payload): Urenregistratie
    {
        return DB::transaction(function () use ($user, $payload) {
            $uren = new Urenregistratie();
            $uren->user_id = $user->id;
            $uren->client_id = $payload['client_id'];
            $uren->datum = $payload['datum'];
            $uren->starttijd = $payload['starttijd'];
            $uren->eindtijd = $payload['eindtijd'];
            $uren->uren = $this->computeDuration($payload['starttijd'], $payload['eindtijd']);
            $uren->notities = $payload['notities'] ?? null;
            $uren->status = UrenStatus::Concept;
            $uren->save();

            return $uren;
        });
    }

    /**
     * US-11 AC-5: bewerken laat status onaangeroerd — transitie gebeurt in US-12.
     *
     * @param  array<string, mixed>  $payload  uit UpdateUrenregistratieRequest
     */
    public function update(Urenregistratie $uren, array $payload): void
    {
        DB::transaction(function () use ($uren, $payload) {
            $uren->client_id = $payload['client_id'];
            $uren->datum = $payload['datum'];
            $uren->starttijd = $payload['starttijd'];
            $uren->eindtijd = $payload['eindtijd'];
            $uren->uren = $this->computeDuration($payload['starttijd'], $payload['eindtijd']);
            $uren->notities = $payload['notities'] ?? null;
            $uren->save();
        });
    }
}
