<?php

namespace App\Services;

use App\Enums\UrenStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Urenregistratie;
use App\Models\User;
use App\Notifications\UrenAfgekeurdNotification;
use App\Notifications\UrenGoedgekeurdNotification;
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
     * Alleen eigen uren (zorgbegeleider). Teamleider-scope: zie scopedForTeamleider.
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

    /**
     * US-13: alle uren van het team van een teamleider, met optionele status-filter.
     * Default op Ingediend — dat is wat de /teamleider/uren beoordelings-pagina toont.
     */
    public function scopedForTeamleider(User $teamleider, ?UrenStatus $status = UrenStatus::Ingediend): Builder
    {
        $query = Urenregistratie::query()
            ->whereHas('user', fn (Builder $q) => $q->where('team_id', $teamleider->team_id))
            ->with(['client', 'user'])
            ->orderBy('user_id')
            ->orderByDesc('datum')
            ->orderByDesc('starttijd');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query;
    }

    /**
     * US-14: gefilterd, gesorteerd en gepagineerd overzicht voor teamleider.
     *
     * Filters (alle via whitelist — geen SQL-injection):
     *  - status: concept/ingediend/goedgekeurd/afgekeurd (default: ingediend)
     *  - medewerker: user_id (int, moet in eigen team zitten)
     *  - week: ISO 8601 week-string `YYYY-Www` (bijv. "2026-W17")
     *
     * Sortering (klikbare kolomkoppen, AC-3):
     *  - datum (default, desc)
     *  - medewerker (oplopend op achternaam — via naam-join is overkill; sort op user_id stabiel)
     *  - duur (uren, desc)
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedForTeamleider(User $teamleider, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Urenregistratie::query()
            ->whereHas('user', fn (Builder $q) => $q->where('team_id', $teamleider->team_id))
            ->with(['client', 'user']);

        // Status-filter (whitelist).
        $status = $filters['status'] ?? UrenStatus::Ingediend->value;
        if ($status !== 'alle') {
            $statusEnum = UrenStatus::tryFrom((string) $status);
            if ($statusEnum !== null) {
                $query->where('status', $statusEnum->value);
            }
        }

        // Medewerker-filter: user_id moet in eigen team zitten.
        $medewerker = $filters['medewerker'] ?? null;
        if (is_numeric($medewerker) && (int) $medewerker > 0) {
            $query->whereHas('user', fn (Builder $q) => $q->where('id', (int) $medewerker)->where('team_id', $teamleider->team_id));
        }

        // Week-filter: ISO 8601 `YYYY-Www` → [start_of_week, end_of_week].
        $week = $filters['week'] ?? null;
        if (is_string($week) && preg_match('/^(\d{4})-W(\d{1,2})$/', $week, $m)) {
            $year = (int) $m[1];
            $weekNr = (int) $m[2];
            if ($weekNr >= 1 && $weekNr <= 53) {
                $start = now()->setISODate($year, $weekNr)->startOfWeek()->toDateString();
                $end = now()->setISODate($year, $weekNr)->endOfWeek()->toDateString();
                $query->whereBetween('datum', [$start, $end]);
            }
        }

        // Sortering (whitelist).
        $sort = $filters['sort'] ?? 'datum';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        match ($sort) {
            'medewerker' => $query->orderBy('user_id', $direction)->orderByDesc('datum'),
            'duur' => $query->orderBy('uren', $direction),
            default => $query->orderBy('datum', $direction)->orderByDesc('starttijd'),
        };

        return $query->paginate($perPage)->withQueryString();
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

        if (!in_array($to, $allowed, true)) {
            throw new InvalidStateTransitionException($from, $to);
        }

        // Extra AC-1: indienen (Concept→Ingediend en Afgekeurd→Ingediend) vereist
        // dat de entry volledig is (cliënt + uren>0 + beide tijden).
        if ($to === UrenStatus::Ingediend && !$uren->isIndienbaar()) {
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

    /**
     * US-13 AC-2: teamleider keurt ingediende uren goed → Ingediend → Goedgekeurd.
     *
     *  - transition() regelt de state-check en weigert invalid transities.
     *  - Metadata: goedgekeurd_door_user_id + beoordeeld_op.
     *  - UrenGoedgekeurdNotification naar de zorgbegeleider (eigenaar).
     */
    public function approve(Urenregistratie $uren, User $teamleider): void
    {
        DB::transaction(function () use ($uren, $teamleider) {
            $this->transition($uren, UrenStatus::Goedgekeurd, $teamleider);

            $uren->forceFill([
                'goedgekeurd_door_user_id' => $teamleider->id,
                'beoordeeld_op' => now(),
            ])->save();

            if ($uren->user) {
                Notification::send(
                    $uren->user,
                    new UrenGoedgekeurdNotification($uren, $teamleider)
                );
            }
        });
    }

    /**
     * US-13 AC-3: teamleider keurt ingediende uren af met verplichte reden → Ingediend → Afgekeurd.
     *
     *  - Reden ≥10 tekens wordt door AfkeurUrenRequest afgedwongen; hier geen extra check.
     *  - afkeur_reden wordt opgeslagen zodat de zorgbegeleider het in de edit-banner ziet.
     */
    public function reject(Urenregistratie $uren, User $teamleider, string $reden): void
    {
        DB::transaction(function () use ($uren, $teamleider, $reden) {
            $this->transition($uren, UrenStatus::Afgekeurd, $teamleider);

            $uren->forceFill([
                'afkeur_reden' => $reden,
                'goedgekeurd_door_user_id' => $teamleider->id,
                'beoordeeld_op' => now(),
            ])->save();

            if ($uren->user) {
                Notification::send(
                    $uren->user,
                    new UrenAfgekeurdNotification($uren, $teamleider, $reden)
                );
            }
        });
    }
}
