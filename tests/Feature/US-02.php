<?php

/*
 * US-02 — Rolgebaseerde toegang (autorisatie via Policies + middleware)
 *
 * 3 test-suites gegroepeerd via describe():
 *  1. ClientPolicy (role + pivot-gebaseerde viewrules)
 *  2. ClientService::scopedForUser (query-scope per rol)
 *  3. Route autorisatie (middleware + 403-flow)
 *
 * Totaal 26 tests / 54 asserts.
 */

use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Services\ClientService;

/* ───────────────────────────────────────────────────────────── */
/* 1. ClientPolicy — role + pivot-gebaseerde viewrules          */
/* ───────────────────────────────────────────────────────────── */

describe('ClientPolicy', function () {
    beforeEach(function () {
        $this->policy = new ClientPolicy();
        $this->teamA = Team::factory()->create(['name' => 'Team A']);
        $this->teamB = Team::factory()->create(['name' => 'Team B']);

        $this->teamleiderA = User::factory()->teamleider()->create(['team_id' => $this->teamA->id]);
        $this->teamleiderB = User::factory()->teamleider()->create(['team_id' => $this->teamB->id]);

        $this->zorgA1 = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgA2 = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgB = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamB->id]);

        $this->inactive = User::factory()->zorgbegeleider()->inactive()->create(['team_id' => $this->teamA->id]);

        $this->clientA = Client::factory()->create(['team_id' => $this->teamA->id]);
        $this->clientB = Client::factory()->create(['team_id' => $this->teamB->id]);

        // zorgA1 gekoppeld aan clientA als primair
        $this->clientA->caregivers()->attach($this->zorgA1->id, [
            'role' => Client::ROLE_PRIMAIR,
        ]);
    });

    // AC-4: Teamleider ziet eigen team.
    it('allows teamleider to view clients in their own team', function () {
        expect($this->policy->view($this->teamleiderA, $this->clientA))->toBeTrue();
    });

    it('denies teamleider viewing clients from another team', function () {
        expect($this->policy->view($this->teamleiderA, $this->clientB))->toBeFalse();
        expect($this->policy->view($this->teamleiderB, $this->clientA))->toBeFalse();
    });

    // AC-2 + AC-5: Zorgbegeleider alleen via client_caregivers.
    it('allows zorgbegeleider to view a client they are linked to', function () {
        expect($this->policy->view($this->zorgA1, $this->clientA))->toBeTrue();
    });

    it('denies zorgbegeleider viewing a client they are NOT linked to (US-02 AC-2 kern)', function () {
        expect($this->policy->view($this->zorgA2, $this->clientA))->toBeFalse();
    });

    it('denies zorgbegeleider viewing clients from a different team regardless of pivot', function () {
        expect($this->policy->view($this->zorgB, $this->clientA))->toBeFalse();
        expect($this->policy->view($this->zorgB, $this->clientB))->toBeFalse();
    });

    // Defense in depth: inactieve users nooit toegang.
    it('denies inactive users even when they would otherwise be linked', function () {
        $this->clientA->caregivers()->attach($this->inactive->id, ['role' => Client::ROLE_TERTIAIR]);

        expect($this->policy->view($this->inactive, $this->clientA))->toBeFalse();
        expect($this->policy->viewAny($this->inactive))->toBeFalse();
    });

    it('only allows teamleider to create clients', function () {
        expect($this->policy->create($this->teamleiderA))->toBeTrue();
        expect($this->policy->create($this->zorgA1))->toBeFalse();
        expect($this->policy->create($this->inactive))->toBeFalse();
    });

    it('allows update for users who can view', function () {
        expect($this->policy->update($this->teamleiderA, $this->clientA))->toBeTrue();
        expect($this->policy->update($this->zorgA1, $this->clientA))->toBeTrue();
    });

    it('denies update for users who cannot view', function () {
        expect($this->policy->update($this->teamleiderB, $this->clientA))->toBeFalse();
        expect($this->policy->update($this->zorgA2, $this->clientA))->toBeFalse();
    });

    it('allows only teamleider of own team to delete', function () {
        expect($this->policy->delete($this->teamleiderA, $this->clientA))->toBeTrue();
        expect($this->policy->delete($this->teamleiderB, $this->clientA))->toBeFalse();
        expect($this->policy->delete($this->zorgA1, $this->clientA))->toBeFalse();
    });

    it('never allows forceDelete from UI', function () {
        expect($this->policy->forceDelete($this->teamleiderA, $this->clientA))->toBeFalse();
    });

    it('allows any active user to call viewAny', function () {
        expect($this->policy->viewAny($this->teamleiderA))->toBeTrue();
        expect($this->policy->viewAny($this->zorgA1))->toBeTrue();
        expect($this->policy->viewAny($this->inactive))->toBeFalse();
    });
});

/* ───────────────────────────────────────────────────────────── */
/* 2. ClientService::scopedForUser — query-scope per rol        */
/* ───────────────────────────────────────────────────────────── */

describe('ClientService scope', function () {
    beforeEach(function () {
        $this->service = new ClientService();

        $this->teamA = Team::factory()->create();
        $this->teamB = Team::factory()->create();

        $this->teamleiderA = User::factory()->teamleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgA1 = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgA2 = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgB = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamB->id]);
        $this->inactive = User::factory()->zorgbegeleider()->inactive()->create(['team_id' => $this->teamA->id]);

        $this->c1 = Client::factory()->create(['team_id' => $this->teamA->id, 'voornaam' => 'C1']);
        $this->c2 = Client::factory()->create(['team_id' => $this->teamA->id, 'voornaam' => 'C2']);
        $this->c3 = Client::factory()->create(['team_id' => $this->teamA->id, 'voornaam' => 'C3']);
        $this->c4 = Client::factory()->create(['team_id' => $this->teamB->id, 'voornaam' => 'C4']);

        // zorgA1 gekoppeld aan C1+C2. zorgA2 aan C3.
        $this->c1->caregivers()->attach($this->zorgA1->id, ['role' => Client::ROLE_PRIMAIR]);
        $this->c2->caregivers()->attach($this->zorgA1->id, ['role' => Client::ROLE_SECUNDAIR]);
        $this->c3->caregivers()->attach($this->zorgA2->id, ['role' => Client::ROLE_PRIMAIR]);
    });

    // AC-4: Teamleider ziet alleen clients van eigen team.
    it('returns all clients in own team for a teamleider', function () {
        $ids = $this->service->scopedForUser($this->teamleiderA)->pluck('id')->toArray();

        expect($ids)->toHaveCount(3);
        expect($ids)->toContain($this->c1->id, $this->c2->id, $this->c3->id);
        expect($ids)->not->toContain($this->c4->id);
    });

    // AC-5: Zorgbegeleider ziet alleen eigen gekoppelde clients.
    it('returns only linked clients for a zorgbegeleider (US-02 AC-5 kern)', function () {
        $ids = $this->service->scopedForUser($this->zorgA1)->pluck('id')->toArray();

        expect($ids)->toHaveCount(2);
        expect($ids)->toContain($this->c1->id, $this->c2->id);
        expect($ids)->not->toContain($this->c3->id);
        expect($ids)->not->toContain($this->c4->id);
    });

    it('does not leak clients across zorgbegeleiders in the same team', function () {
        $a1Ids = $this->service->scopedForUser($this->zorgA1)->pluck('id')->toArray();
        $a2Ids = $this->service->scopedForUser($this->zorgA2)->pluck('id')->toArray();

        expect(array_intersect($a1Ids, $a2Ids))->toBeEmpty();
        expect($a2Ids)->toBe([$this->c3->id]);
    });

    it('returns empty set for zorgbegeleider with no linked clients', function () {
        $ids = $this->service->scopedForUser($this->zorgB)->pluck('id')->toArray();

        expect($ids)->toBe([]);
    });

    it('returns empty set for inactive user as defense in depth', function () {
        $ids = $this->service->scopedForUser($this->inactive)->pluck('id')->toArray();

        expect($ids)->toBe([]);
    });

    it('reflects caregiver changes immediately in the scope', function () {
        expect($this->service->scopedForUser($this->zorgA1)->count())->toBe(2);

        $this->c3->caregivers()->attach($this->zorgA1->id, ['role' => Client::ROLE_TERTIAIR]);
        expect($this->service->scopedForUser($this->zorgA1)->count())->toBe(3);

        $this->c1->caregivers()->detach($this->zorgA1->id);
        expect($this->service->scopedForUser($this->zorgA1)->count())->toBe(2);
    });
});

/* ───────────────────────────────────────────────────────────── */
/* 3. Route autorisatie — middleware + 403-flow                 */
/* ───────────────────────────────────────────────────────────── */

describe('Route authorization', function () {
    beforeEach(function () {
        $this->team = Team::factory()->create();
    });

    // AC-1: Niet-ingelogd + /dashboard of /clients -> redirect /login.
    it('redirects guest from /dashboard to /login', function () {
        $this->get('/dashboard')->assertRedirect('/login');
    });

    it('redirects guest from /teamleider/dashboard to /login', function () {
        $this->get('/teamleider/dashboard')->assertRedirect('/login');
    });

    // AC-3: Zorgbegeleider probeert /teamleider/dashboard -> 403.
    it('returns 403 when zorgbegeleider visits teamleider dashboard', function () {
        $zorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

        $this->actingAs($zorg)->get('/teamleider/dashboard')->assertStatus(403);
    });

    // Middleware symmetrie: teamleider krijgt 403 op zorgbegeleider-only routes.
    it('returns 403 when teamleider visits zorgbegeleider dashboard', function () {
        $teamleider = User::factory()->teamleider()->create(['team_id' => $this->team->id]);

        $this->actingAs($teamleider)->get('/dashboard')->assertStatus(403);
    });

    // Positieve paden (regressie-bescherming).
    it('allows zorgbegeleider on zorgbegeleider dashboard', function () {
        $zorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

        $this->actingAs($zorg)->get('/dashboard')->assertOk();
    });

    it('allows teamleider on teamleider dashboard', function () {
        $teamleider = User::factory()->teamleider()->create(['team_id' => $this->team->id]);

        $this->actingAs($teamleider)->get('/teamleider/dashboard')->assertOk();
    });

    // Privacy bullet 3: 403 pagina toont Nederlandse melding, geen stacktrace.
    it('renders a Dutch 403 page without stacktrace when role check fails', function () {
        $zorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

        $response = $this->actingAs($zorg)->get('/teamleider/dashboard');

        $response->assertStatus(403);
        $response->assertSee('Geen toegang');
        $response->assertDontSee('Stack trace');
        $response->assertDontSee('Symfony\\Component');
        $response->assertDontSee('vendor/laravel');
    });

    // Defense in depth: middleware faalt bij onbekende rol.
    it('returns 403 when user has an unknown role', function () {
        $weirdUser = User::factory()->zorgbegeleider()->create([
            'team_id' => $this->team->id,
            'role' => 'onbekend',
        ]);

        $this->actingAs($weirdUser)->get('/dashboard')->assertStatus(403);
    });
});
