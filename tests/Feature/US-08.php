<?php

/*
 * US-08 — Cliënten koppelen aan begeleiders (primair / secundair / tertiair)
 *
 * 3 describe-groepen:
 *  1. ClientService::computeCaregiverRoles — pure functie rol-toewijzing
 *  2. ClientService::syncCaregivers — delete-insert + notifications
 *  3. HTTP integratie — /clients store + /clients/{id}/caregivers flow
 *
 * AC mapping:
 *  AC-1: auto-role-assignment → ClientService describe
 *  AC-2: promote new primary, demote old → ClientService describe
 *  AC-3: remove one caregiver → HTTP describe
 *  AC-4: notification op nieuwe koppeling → ClientService describe (Notification::fake)
 *  AC-5: partial unique blokkeert tweede primair → HTTP describe (raw DB)
 */

use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ClientCaregiverAssignedNotification;
use App\Services\ClientService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/* ───────────────────────────────────────────────────────────── */
/* 1. ClientService::computeCaregiverRoles (pure function)      */
/* ───────────────────────────────────────────────────────────── */

describe('computeCaregiverRoles', function () {
    beforeEach(function () {
        $this->service = new ClientService();
    });

    // AC-1: eerste=primair, tweede=secundair, rest=tertiair.
    it('auto-assigns primair to first, secundair to second, tertiair to rest when no explicit primary', function () {
        $roles = $this->service->computeCaregiverRoles([10, 20, 30, 40], null);

        expect($roles)->toBe([
            10 => Client::ROLE_PRIMAIR,
            20 => Client::ROLE_SECUNDAIR,
            30 => Client::ROLE_TERTIAIR,
            40 => Client::ROLE_TERTIAIR,
        ]);
    });

    it('promotes the explicit primary to first regardless of input order', function () {
        $roles = $this->service->computeCaregiverRoles([10, 20, 30], 20);

        expect($roles)->toBe([
            20 => Client::ROLE_PRIMAIR,
            10 => Client::ROLE_SECUNDAIR,
            30 => Client::ROLE_TERTIAIR,
        ]);
    });

    it('ignores explicit primary that is not in the set', function () {
        $roles = $this->service->computeCaregiverRoles([10, 20], 99);

        expect($roles)->toBe([
            10 => Client::ROLE_PRIMAIR,
            20 => Client::ROLE_SECUNDAIR,
        ]);
    });

    it('deduplicates while preserving order', function () {
        $roles = $this->service->computeCaregiverRoles([10, 20, 10, 30, 20], null);

        expect(array_keys($roles))->toBe([10, 20, 30]);
    });

    it('returns an empty array for an empty input', function () {
        expect($this->service->computeCaregiverRoles([], null))->toBe([]);
    });

    it('assigns primair to single caregiver', function () {
        expect($this->service->computeCaregiverRoles([42], null))
            ->toBe([42 => Client::ROLE_PRIMAIR]);
    });
});

/* ───────────────────────────────────────────────────────────── */
/* 2. ClientService::syncCaregivers (DB + notifications)        */
/* ───────────────────────────────────────────────────────────── */

describe('syncCaregivers', function () {
    beforeEach(function () {
        $this->service = new ClientService();

        $this->team = Team::factory()->create();
        $this->teamleider = User::factory()->teamleider()->create(['team_id' => $this->team->id]);

        $this->alice = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id, 'name' => 'Alice']);
        $this->bob = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id, 'name' => 'Bob']);
        $this->charlie = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id, 'name' => 'Charlie']);

        $this->client = Client::factory()->create(['team_id' => $this->team->id]);
    });

    it('creates new pivot rows with correct roles on first sync', function () {
        Notification::fake();

        $this->service->syncCaregivers(
            $this->client,
            [$this->alice->id, $this->bob->id, $this->charlie->id],
            null,
            $this->teamleider
        );

        $this->client->refresh()->load('caregivers');

        expect($this->client->caregivers)->toHaveCount(3);

        $roleFor = fn ($userId) => $this->client->caregivers->firstWhere('id', $userId)->pivot->role;

        expect($roleFor($this->alice->id))->toBe(Client::ROLE_PRIMAIR);
        expect($roleFor($this->bob->id))->toBe(Client::ROLE_SECUNDAIR);
        expect($roleFor($this->charlie->id))->toBe(Client::ROLE_TERTIAIR);
    });

    // AC-4: nieuwe koppeling → database notification.
    it('sends a database notification to every newly assigned caregiver', function () {
        Notification::fake();

        $this->service->syncCaregivers(
            $this->client,
            [$this->alice->id, $this->bob->id],
            null,
            $this->teamleider
        );

        Notification::assertSentTo($this->alice, ClientCaregiverAssignedNotification::class);
        Notification::assertSentTo($this->bob, ClientCaregiverAssignedNotification::class);
        Notification::assertCount(2);
    });

    // AC-2: Maak primair bij bestaande andere → swap rollen.
    it('promotes a new primary and demotes the old one to secundair', function () {
        Notification::fake();

        // Eerst Alice primair, Bob secundair
        $this->service->syncCaregivers(
            $this->client,
            [$this->alice->id, $this->bob->id],
            null,
            $this->teamleider
        );

        // Nu Bob expliciet primair maken
        $this->service->syncCaregivers(
            $this->client,
            [$this->alice->id, $this->bob->id],
            $this->bob->id,
            $this->teamleider
        );

        $this->client->refresh()->load('caregivers');
        $roleFor = fn ($userId) => $this->client->caregivers->firstWhere('id', $userId)->pivot->role;

        expect($roleFor($this->bob->id))->toBe(Client::ROLE_PRIMAIR);
        expect($roleFor($this->alice->id))->toBe(Client::ROLE_SECUNDAIR);
    });

    it('does not send duplicate notifications when only role changes for existing caregivers', function () {
        Notification::fake();

        // First sync — 2 notifications
        $this->service->syncCaregivers(
            $this->client,
            [$this->alice->id, $this->bob->id],
            null,
            $this->teamleider
        );
        Notification::assertCount(2);

        // Second sync — swap primary (no new caregiver); geen nieuwe notifications
        $this->service->syncCaregivers(
            $this->client,
            [$this->alice->id, $this->bob->id],
            $this->bob->id,
            $this->teamleider
        );

        // Total nog steeds 2 (Notification::fake telt alle over de gehele test)
        Notification::assertCount(2);
    });

    // AC-3: Eén verwijderen → één rij minder.
    it('removes exactly one pivot row when a caregiver is unchecked', function () {
        Notification::fake();

        $this->service->syncCaregivers(
            $this->client,
            [$this->alice->id, $this->bob->id, $this->charlie->id],
            null,
            $this->teamleider
        );
        expect($this->client->fresh()->caregivers->count())->toBe(3);

        // Remove Bob
        $this->service->syncCaregivers(
            $this->client,
            [$this->alice->id, $this->charlie->id],
            null,
            $this->teamleider
        );

        $this->client->refresh()->load('caregivers');
        expect($this->client->caregivers->count())->toBe(2);
        expect($this->client->caregivers->pluck('id'))->not->toContain($this->bob->id);
    });

    it('removes all caregivers when given an empty list', function () {
        Notification::fake();

        $this->service->syncCaregivers($this->client, [$this->alice->id, $this->bob->id], null, $this->teamleider);
        expect($this->client->fresh()->caregivers->count())->toBe(2);

        $this->service->syncCaregivers($this->client, [], null, $this->teamleider);

        expect($this->client->fresh()->caregivers->count())->toBe(0);
    });

    it('sends no notifications when syncing the same set twice (idempotent)', function () {
        Notification::fake();

        $this->service->syncCaregivers($this->client, [$this->alice->id], null, $this->teamleider);
        Notification::assertCount(1);

        $this->service->syncCaregivers($this->client, [$this->alice->id], null, $this->teamleider);
        // Nog steeds 1 notification — geen nieuwe voor bestaande koppeling
        Notification::assertCount(1);
    });

    it('stores created_by_user_id on the pivot for audit', function () {
        Notification::fake();

        $this->service->syncCaregivers($this->client, [$this->alice->id], null, $this->teamleider);

        $pivot = DB::table('client_caregivers')
            ->where('client_id', $this->client->id)
            ->where('user_id', $this->alice->id)
            ->first();

        expect($pivot->created_by_user_id)->toBe($this->teamleider->id);
    });

    // AC-5: partial unique index blokkeert raw DB insert van 2x primair.
    it('rejects a second primair for the same client via partial unique index', function () {
        DB::table('client_caregivers')->insert([
            'client_id' => $this->client->id,
            'user_id' => $this->alice->id,
            'role' => Client::ROLE_PRIMAIR,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondInsert = fn () => DB::table('client_caregivers')->insert([
            'client_id' => $this->client->id,
            'user_id' => $this->bob->id,
            'role' => Client::ROLE_PRIMAIR,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        expect($secondInsert)->toThrow(UniqueConstraintViolationException::class);
    });

    it('allows primair on one client and primair on another client (no cross-client conflict)', function () {
        $otherClient = Client::factory()->create(['team_id' => $this->team->id]);

        DB::table('client_caregivers')->insert([
            'client_id' => $this->client->id,
            'user_id' => $this->alice->id,
            'role' => Client::ROLE_PRIMAIR,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('client_caregivers')->insert([
            'client_id' => $otherClient->id,
            'user_id' => $this->alice->id,
            'role' => Client::ROLE_PRIMAIR,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        expect(DB::table('client_caregivers')->where('role', 'primair')->count())->toBe(2);
    });
});

/* ───────────────────────────────────────────────────────────── */
/* 3. HTTP integratie — POST /clients + caregivers-update        */
/* ───────────────────────────────────────────────────────────── */

describe('HTTP integration', function () {
    beforeEach(function () {
        Notification::fake();

        $this->team = Team::factory()->create();
        $this->otherTeam = Team::factory()->create();

        $this->teamleider = User::factory()->teamleider()->create(['team_id' => $this->team->id]);
        $this->zorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);
        $this->alice = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id, 'name' => 'Alice']);
        $this->bob = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id, 'name' => 'Bob']);

        $this->client = Client::factory()->create(['team_id' => $this->team->id]);
    });

    it('shows available caregivers on the create form', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.create'));

        $response->assertOk();
        $response->assertSee('Begeleiders');
        $response->assertSee('Alice');
        $response->assertSee('Bob');
        $response->assertSee('name="caregiver_ids[]"', false);
        $response->assertSee('name="primary_user_id"', false);
    });

    it('stores caregivers when creating a new client with selected begeleiders', function () {
        $response = $this->actingAs($this->teamleider)->post(route('clients.store'), [
            'voornaam' => 'Sanne',
            'achternaam' => 'de Wit',
            'status' => 'actief',
            'care_type' => 'wmo',
            'caregiver_ids' => [$this->alice->id, $this->bob->id],
        ]);

        $client = Client::where('voornaam', 'Sanne')->firstOrFail();
        $response->assertRedirect(route('clients.show', $client));

        $client->load('caregivers');
        expect($client->caregivers)->toHaveCount(2);
        expect($client->caregivers->firstWhere('id', $this->alice->id)->pivot->role)->toBe('primair');
        expect($client->caregivers->firstWhere('id', $this->bob->id)->pivot->role)->toBe('secundair');

        Notification::assertSentTo($this->alice, ClientCaregiverAssignedNotification::class);
        Notification::assertSentTo($this->bob, ClientCaregiverAssignedNotification::class);
    });

    it('stores with explicit primary from the form', function () {
        $this->actingAs($this->teamleider)->post(route('clients.store'), [
            'voornaam' => 'X',
            'achternaam' => 'Y',
            'status' => 'actief',
            'care_type' => 'wmo',
            'caregiver_ids' => [$this->alice->id, $this->bob->id],
            'primary_user_id' => $this->bob->id,
        ]);

        $client = Client::where('voornaam', 'X')->firstOrFail();
        $client->load('caregivers');

        expect($client->caregivers->firstWhere('id', $this->bob->id)->pivot->role)->toBe('primair');
        expect($client->caregivers->firstWhere('id', $this->alice->id)->pivot->role)->toBe('secundair');
    });

    it('renders the caregivers edit page with existing assignments pre-filled', function () {
        $this->client->caregivers()->attach($this->alice->id, ['role' => 'primair']);

        $response = $this->actingAs($this->teamleider)->get(route('clients.caregivers.edit', $this->client));

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Primair'); // bestaande rol-badge
    });

    it('updates caregivers via the dedicated edit page', function () {
        $this->client->caregivers()->attach($this->alice->id, ['role' => 'primair']);

        $response = $this->actingAs($this->teamleider)->put(
            route('clients.caregivers.update', $this->client),
            [
                'caregiver_ids' => [$this->alice->id, $this->bob->id],
                'primary_user_id' => $this->bob->id,
            ]
        );

        $response->assertRedirect(route('clients.show', $this->client));
        $response->assertSessionHas('success', 'Begeleiders bijgewerkt.');

        $this->client->load('caregivers');
        expect($this->client->caregivers->firstWhere('id', $this->bob->id)->pivot->role)->toBe('primair');
        expect($this->client->caregivers->firstWhere('id', $this->alice->id)->pivot->role)->toBe('secundair');
    });

    it('denies zorgbegeleider access to caregivers edit (403)', function () {
        $response = $this->actingAs($this->zorg)->get(route('clients.caregivers.edit', $this->client));

        $response->assertStatus(403);
    });

    it('denies zorgbegeleider PUT on caregivers endpoint (403)', function () {
        $response = $this->actingAs($this->zorg)->put(
            route('clients.caregivers.update', $this->client),
            ['caregiver_ids' => [$this->alice->id]]
        );

        $response->assertStatus(403);
        expect($this->client->fresh()->caregivers->count())->toBe(0);
    });

    it('rejects a caregiver from another team', function () {
        $outsider = User::factory()->zorgbegeleider()->create(['team_id' => $this->otherTeam->id]);

        $response = $this->actingAs($this->teamleider)
            ->from(route('clients.caregivers.edit', $this->client))
            ->put(route('clients.caregivers.update', $this->client), [
                'caregiver_ids' => [$outsider->id],
            ]);

        $response->assertSessionHasErrors('caregiver_ids');
        expect($this->client->fresh()->caregivers->count())->toBe(0);
    });

    it('rejects an inactive caregiver', function () {
        $inactive = User::factory()->zorgbegeleider()->inactive()->create(['team_id' => $this->team->id]);

        $response = $this->actingAs($this->teamleider)
            ->from(route('clients.caregivers.edit', $this->client))
            ->put(route('clients.caregivers.update', $this->client), [
                'caregiver_ids' => [$inactive->id],
            ]);

        $response->assertSessionHasErrors('caregiver_ids');
    });

    it('rejects a teamleider as caregiver (only zorgbegeleider allowed)', function () {
        $response = $this->actingAs($this->teamleider)
            ->from(route('clients.caregivers.edit', $this->client))
            ->put(route('clients.caregivers.update', $this->client), [
                'caregiver_ids' => [$this->teamleider->id],
            ]);

        $response->assertSessionHasErrors('caregiver_ids');
    });

    it('rejects primary_user_id not present in caregiver_ids', function () {
        $response = $this->actingAs($this->teamleider)
            ->from(route('clients.caregivers.edit', $this->client))
            ->put(route('clients.caregivers.update', $this->client), [
                'caregiver_ids' => [$this->alice->id],
                'primary_user_id' => $this->bob->id, // niet aangevinkt
            ]);

        $response->assertSessionHasErrors('primary_user_id');
    });

    it('shows caregiver badges on the show page', function () {
        $this->client->caregivers()->attach($this->alice->id, ['role' => 'primair']);
        $this->client->caregivers()->attach($this->bob->id, ['role' => 'secundair']);

        $response = $this->actingAs($this->teamleider)->get(route('clients.show', $this->client));

        $response->assertOk();
        $response->assertSee('Begeleiders');
        $response->assertSee('Alice');
        $response->assertSee('Primair');
        $response->assertSee('Secundair');
    });

    it('shows empty-state and CTA when no caregivers linked yet', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.show', $this->client));

        $response->assertOk();
        $response->assertSee('Geen begeleiders gekoppeld');
        $response->assertSee('Begeleiders koppelen');
    });
});
