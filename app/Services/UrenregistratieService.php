<?php

namespace App\Services;

use App\Enums\UrenStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Urenregistratie;
use App\Models\User;
use App\Notifications\UrenIngediendNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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

    /**
     * US-12 AC-5: centrale state-machine voor uren. Alle status-mutaties
     * lopen hier door, geen verspreide `if`-logica in controllers.
     *
     * Allowed-matrix:
     *  - Concept     → Ingediend
     *  - Ingediend   → Concept / Goedgekeurd / Afgekeurd
     *  - Afgekeurd   → Ingediend
     *  - Goedgekeurd → (terminal)
     */
    public function transition(Urenregistratie $uren, UrenStatus $to, User $actor): void
    {
        $from = $uren->status;

        $allowed = match ($from) {
            UrenStatus::Concept => [UrenStatus::Ingediend],
            UrenStatus::Ingediend => [UrenStatus::Concept, UrenStatus::Goedgekeurd, UrenStatus::Afgekeurd],
            UrenStatus::Afgekeurd => [UrenStatus::Ingediend],
            UrenStatus::Goedgekeurd => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw new InvalidStateTransitionException($from, $to);
        }

        // Extra AC-1: indienen (Concept→Ingediend en Afgekeurd→Ingediend) vereist
        // dat de entry volledig is (cliënt + uren>0 + beide tijden).
        if ($to === UrenStatus::Ingediend && ! $uren->isIndienbaar()) {
            throw new InvalidStateTransitionException(
                $from,
                $to,
                'Entry is niet geldig om in te dienen (cliënt, uren of tijden ontbreken).'
            );
        }

        DB::transaction(function () use ($uren, $to, $actor) {
            // AC-4: bij opnieuw-indienen (Afgekeurd→Ingediend) afkeur_reden wissen.
            if ($uren->status === UrenStatus::Afgekeurd && $to === UrenStatus::Ingediend) {
                $uren->afkeur_reden = null;
            }

            $uren->status = $to;
            $uren->save();

            // AC-2: bij indienen/opnieuw-indienen alle teamleiders van team notificeren.
            if ($to === UrenStatus::Ingediend) {
                $teamleiders = User::query()
                    ->where('role', User::ROLE_TEAMLEIDER)
                    ->where('team_id', $actor->team_id)
                    ->where('is_active', true)
                    ->get();

                if ($teamleiders->isNotEmpty()) {
                    Notification::send(
                        $teamleiders,
                        new UrenIngediendNotification($uren, $actor)
                    );
                }
            }
        });
    }

    /**
     * Convenience: Concept → Ingediend (US-12 AC-1 + AC-2).
     */
    public function submit(Urenregistratie $uren, User $actor): void
    {
        $this->transition($uren, UrenStatus::Ingediend, $actor);
    }

    /**
     * Convenience: Ingediend → Concept (US-12 AC-3).
     */
    public function withdraw(Urenregistratie $uren, User $actor): void
    {
        $this->transition($uren, UrenStatus::Concept, $actor);
    }

    /**
     * Convenience: Afgekeurd → Ingediend (US-12 AC-4).
     * De bijwerking van de velden gebeurt vóór deze call via update().
     */
    public function resubmit(Urenregistratie $uren, User $actor): void
    {
        $this->transition($uren, UrenStatus::Ingediend, $actor);
    }
}
