<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientStatusLog;
use App\Models\User;
use App\Notifications\ClientCaregiverAssignedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Centrale plek voor cliënt-queries en -mutaties.
 *
 * US-02: scopedForUser (scope-regels op één plek voor AVG-auditeerbaarheid).
 * US-07: create (DB-transactie + audit-velden).
 * US-08: computeCaregiverRoles + syncCaregivers (rol-toewijzing + notifications).
 * US-09: getPaginated (zoek + filter + sort + paginate + eager loading).
 */
class ClientService
{
    /**
     * Bouw een query die alleen cliënten retourneert waar $user toegang toe heeft.
     *
     *  - Teamleider: alle cliënten binnen eigen team_id.
     *  - Zorgbegeleider: alleen cliënten waar hij via `client_caregivers` gekoppeld is.
     *  - Inactieve users: lege set (extra defense in depth — middleware weigert al login).
     *  - Onbekende rol: lege set.
     */
    public function scopedForUser(User $user): Builder
    {
        if (!$user->is_active) {
            return Client::query()->whereRaw('1 = 0');
        }

        if ($user->isTeamleider()) {
            return Client::query()->where('team_id', $user->team_id);
        }

        if ($user->isZorgbegeleider()) {
            return Client::query()->whereHas(
                'caregivers',
                fn (Builder $q) => $q->where('users.id', $user->id)
            );
        }

        return Client::query()->whereRaw('1 = 0');
    }

    /**
     * US-09: Levert een gefilterd, gesorteerd en gepagineerd cliënten-overzicht
     * met eager-loaded caregivers + team om N+1 te voorkomen.
     *
     * Bouwt bovenop scopedForUser() — dezelfde rol-regels, nu met toegevoegde
     * user-filters uit de query-string. Alle filter-waarden worden server-side
     * gevalideerd via whitelist (geen SQL-injection mogelijk).
     *
     * @param  array<string, mixed>  $filters  search/status/care_type/sort
     */
    public function getPaginated(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->scopedForUser($user)
            ->with(['caregivers', 'team']);

        // Zoekterm op voornaam OF achternaam (LIKE %term%, case-insensitive via SQLite NOCASE).
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('voornaam', 'like', "%{$search}%")
                    ->orWhere('achternaam', 'like', "%{$search}%");
            });
        }

        // Status-filter via whitelist.
        $status = $filters['status'] ?? null;
        if (in_array($status, [Client::STATUS_ACTIEF, Client::STATUS_WACHT, Client::STATUS_INACTIEF], true)) {
            $query->where('status', $status);
        }

        // Care-type filter via whitelist (WMO/WLZ/JW).
        $careType = $filters['care_type'] ?? null;
        if (in_array($careType, [Client::CARE_WMO, Client::CARE_WLZ, Client::CARE_JW], true)) {
            $query->where('care_type', $careType);
        }

        // Sortering via whitelist (geen arbitrary column-name via user-input).
        $sort = $filters['sort'] ?? 'name';
        match ($sort) {
            'status' => $query->orderBy('status')->orderBy('achternaam')->orderBy('voornaam'),
            'created_at' => $query->orderByDesc('created_at'),
            default => $query->orderBy('achternaam')->orderBy('voornaam'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Maakt een nieuwe cliënt binnen een DB-transactie (US-07).
     *
     * @param  array<string, mixed>  $payload  uit StoreClientRequest
     */
    public function create(array $payload): Client
    {
        return DB::transaction(fn () => Client::create($payload));
    }

    /**
     * US-10: werk cliëntgegevens bij en log statuswijziging (indien gewijzigd).
     *
     * Diff-check voorkomt een lege audit-rij bij save-zonder-status-change.
     *
     * @param  array<string, mixed>  $payload  uit UpdateClientRequest
     */
    public function update(Client $client, array $payload, User $changedBy): void
    {
        DB::transaction(function () use ($client, $payload, $changedBy) {
            $oldStatus = $client->status;
            $client->update($payload);

            if ($oldStatus !== $payload['status']) {
                ClientStatusLog::create([
                    'client_id' => $client->id,
                    'changed_by_user_id' => $changedBy->id,
                    'old_status' => $oldStatus,
                    'new_status' => $payload['status'],
                ]);
            }
        });
    }

    /**
     * US-10 AC-3: archiveer een cliënt via soft delete.
     *
     * Pivot-koppelingen in `client_caregivers` blijven bestaan — caregiver-relatie
     * verdwijnt pas bij echte `forceDelete` (bewust UI-onbereikbaar).
     */
    public function archive(Client $client, User $by): void
    {
        DB::transaction(fn () => $client->delete());
    }

    /**
     * US-10 AC-4: herstel een gearchiveerde cliënt naar het actieve overzicht.
     */
    public function restore(Client $client, User $by): void
    {
        DB::transaction(fn () => $client->restore());
    }

    /**
     * US-08 AC-1 + AC-2: bepaalt rol per userId.
     *
     * Regels:
     *  - Primair is de $explicitPrimaryId (indien opgegeven en aanwezig in $userIds),
     *    anders het eerste element van $userIds.
     *  - Tweede element in resterende volgorde = secundair.
     *  - Alle overige = tertiair.
     *  - Duplicates in $userIds worden gededupliceerd, volgorde behouden.
     *
     * @param  list<int>  $userIds
     * @return array<int, string>  userId => role
     */
    public function computeCaregiverRoles(array $userIds, ?int $explicitPrimaryId = null): array
    {
        $unique = array_values(array_unique(array_map('intval', $userIds)));

        if ($unique === []) {
            return [];
        }

        // Promote explicit primary to first position if present.
        if ($explicitPrimaryId !== null && in_array($explicitPrimaryId, $unique, true)) {
            $unique = array_values(array_filter($unique, fn ($id) => $id !== $explicitPrimaryId));
            array_unshift($unique, $explicitPrimaryId);
        }

        $roles = [];
        foreach ($unique as $i => $id) {
            $roles[$id] = match ($i) {
                0 => Client::ROLE_PRIMAIR,
                1 => Client::ROLE_SECUNDAIR,
                default => Client::ROLE_TERTIAIR,
            };
        }

        return $roles;
    }

    /**
     * US-08: synchroniseert caregivers voor een client via delete-insert patroon.
     *
     *  - Transactioneel (AVG + Multi-caregiver integriteit).
     *  - Verwijdert pivots die niet meer in de nieuwe set zitten.
     *  - Update bestaande pivots met nieuwe rol (zonder detach+attach, zodat
     *    gekoppelde data zoals created_by_user_id behouden blijft).
     *  - Voegt nieuwe koppelingen toe met created_by_user_id = $changedBy.
     *  - Notification::send alleen naar NIEUWE koppelingen (AC-4).
     *
     * @param  list<int>  $userIds
     */
    public function syncCaregivers(Client $client, array $userIds, ?int $primaryId, User $changedBy): void
    {
        DB::transaction(function () use ($client, $userIds, $primaryId, $changedBy) {
            $roles = $this->computeCaregiverRoles($userIds, $primaryId);

            $currentIds = $client->caregivers()->pluck('users.id')->all();
            $newIds = array_keys($roles);

            // 1. Remove: bestaande die niet meer aangevinkt zijn.
            $toRemove = array_diff($currentIds, $newIds);
            if ($toRemove !== []) {
                $client->caregivers()->detach($toRemove);
            }

            // 2. Stagefase: alle blijvende koppelingen tijdelijk naar 'tertiair'
            //    zodat partial unique indexes (primair+secundair) niet botsen
            //    bij role-swaps (bijv. A→primair→secundair + B→secundair→primair).
            //    Tertiair heeft geen unique constraint — veilig tussenstation.
            $commonIds = array_intersect($currentIds, $newIds);
            if ($commonIds !== []) {
                DB::table('client_caregivers')
                    ->where('client_id', $client->id)
                    ->whereIn('user_id', $commonIds)
                    ->update([
                        'role' => Client::ROLE_TERTIAIR,
                        'updated_at' => now(),
                    ]);
            }

            // 3. Upsert: assign de uiteindelijke rollen.
            $nieuwGekoppeld = [];
            foreach ($newIds as $userId) {
                $role = $roles[$userId];

                if (in_array($userId, $currentIds, true)) {
                    $client->caregivers()->updateExistingPivot($userId, [
                        'role' => $role,
                        'updated_at' => now(),
                    ]);
                } else {
                    $client->caregivers()->attach($userId, [
                        'role' => $role,
                        'created_by_user_id' => $changedBy->id,
                    ]);
                    $nieuwGekoppeld[] = ['user_id' => $userId, 'role' => $role];
                }
            }

            // 4. Notifications voor NIEUWE koppelingen (AC-4).
            foreach ($nieuwGekoppeld as $entry) {
                $user = User::find($entry['user_id']);
                if ($user) {
                    Notification::send(
                        $user,
                        new ClientCaregiverAssignedNotification($client, $entry['role'], $changedBy)
                    );
                }
            }
        });
    }
}
