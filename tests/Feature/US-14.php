<?php

/*
 * US-14 — Urenoverzicht met filters (teamleider)
 *
 * Dekt alle 5 acceptatiecriteria:
 *  - AC-1: 3 filters (status default Ingediend / medewerker eigen team / week date-picker)
 *  - AC-2: Filters blijven in URL bij paginatie (withQueryString)
 *  - AC-3: Pageable (20/pagina) + sorteerbaar via klikbare kolomkoppen
 *  - AC-4: Week-header met subtotalen per medewerker + weektotaal
 *  - AC-5: Lege-state bij geen resultaten + eager loading (geen N+1)
 */

use App\Enums\UrenStatus;
use App\Models\Client;
use App\Models\Team;
use App\Models\Urenregistratie;
use App\Models\User;
use App\Services\UrenregistratieService;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Team Rotterdam']);
    $this->otherTeam = Team::factory()->create(['name' => 'Team Amsterdam']);

    $this->teamleider = User::factory()->teamleider()->create(['team_id' => $this->team->id]);
    $this->vreemdeTeamleider = User::factory()->teamleider()->create(['team_id' => $this->otherTeam->id]);

    $this->piet = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Piet Bakker',
    ]);
    $this->anna = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Anna de Vries',
    ]);

    $this->client = Client::factory()->create(['team_id' => $this->team->id]);
    $this->client->caregivers()->attach($this->piet->id, [
        'role' => Client::ROLE_PRIMAIR,
        'created_by_user_id' => $this->teamleider->id,
    ]);
    $this->client->caregivers()->attach($this->anna->id, [
        'role' => Client::ROLE_SECUNDAIR,
        'created_by_user_id' => $this->teamleider->id,
    ]);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-1: 3 filters + defaults
 * ────────────────────────────────────────────────────────────────── */

it('renders overzicht with all 3 filters and default status ingediend', function () {
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create();
    Urenregistratie::factory()->concept()->for($this->piet, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.overzicht'));

    $response->assertOk();
    $response->assertSee('Urenoverzicht');
    $response->assertSee('Alle statussen');
    $response->assertSee('Alle medewerkers');
    $response->assertSee('name="week"', false);
    expect($response->viewData('rows')->total())->toBe(1);
});

it('filters by status afgekeurd and shows it in URL', function () {
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create();
    Urenregistratie::factory()->afgekeurd()->for($this->piet, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['status' => 'afgekeurd']));

    $response->assertOk();
    expect($response->viewData('rows')->total())->toBe(1);
    expect($response->viewData('rows')->first()->status)->toBe(UrenStatus::Afgekeurd);
});

it('filters by medewerker and limits to that user', function () {
    Urenregistratie::factory()->ingediend()->count(3)->for($this->piet, 'user')->for($this->client)->create();
    Urenregistratie::factory()->ingediend()->for($this->anna, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['medewerker' => $this->piet->id]));

    $response->assertOk();
    expect($response->viewData('rows')->total())->toBe(3);
});

it('ignores medewerker filter when user is not in own team', function () {
    $foreignZorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->otherTeam->id]);
    $foreignClient = Client::factory()->create(['team_id' => $this->otherTeam->id]);
    Urenregistratie::factory()->ingediend()->for($foreignZorg, 'user')->for($foreignClient)->create();
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['medewerker' => $foreignZorg->id]));

    // Filter op vreemde user_id → 0 resultaten (team-guard in service).
    expect($response->viewData('rows')->total())->toBe(0);
});

it('filters by week using ISO 8601 format', function () {
    $week17Date = now()->setISODate(2026, 17)->startOfWeek()->addDay()->format('Y-m-d');
    $week18Date = now()->setISODate(2026, 18)->startOfWeek()->addDay()->format('Y-m-d');

    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['datum' => $week17Date]);
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['datum' => $week18Date]);

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['week' => '2026-W17']));

    $response->assertOk();
    expect($response->viewData('rows')->total())->toBe(1);
});

it('shows medewerker dropdown with only zorgbegeleiders from own team', function () {
    $foreignZorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->otherTeam->id, 'name' => 'Alien']);

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.overzicht'));

    $response->assertSee('Piet Bakker');
    $response->assertSee('Anna de Vries');
    $response->assertDontSee('Alien');
});

/* ──────────────────────────────────────────────────────────────────
 * AC-2: filters blijven in URL bij paginatie
 * ────────────────────────────────────────────────────────────────── */

it('keeps filters in pagination links via withQueryString', function () {
    Urenregistratie::factory()->ingediend()->count(25)->for($this->piet, 'user')->for($this->client)->create();
    Urenregistratie::factory()->ingediend()->count(25)->for($this->anna, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['medewerker' => $this->piet->id]));

    $response->assertOk();
    expect($response->viewData('rows')->total())->toBe(25);
    // Response bevat medewerker-param in pagination-links:
    $response->assertSee('medewerker='.$this->piet->id, false);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-3: Pageable 20/pagina + sorteerbaar
 * ────────────────────────────────────────────────────────────────── */

it('paginates with 20 items per page', function () {
    Urenregistratie::factory()->ingediend()->count(35)->for($this->piet, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.overzicht'));

    expect($response->viewData('rows')->perPage())->toBe(20);
    expect($response->viewData('rows')->total())->toBe(35);
    expect($response->viewData('rows')->count())->toBe(20);
});

it('sorts by datum desc by default', function () {
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['datum' => now()->subDays(5)->format('Y-m-d')]);
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['datum' => now()->subDays(1)->format('Y-m-d')]);

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.overzicht'));

    $first = $response->viewData('rows')->first();
    expect($first->datum->format('Y-m-d'))->toBe(now()->subDays(1)->format('Y-m-d'));
});

it('sorts by duur asc when ?sort=duur&direction=asc', function () {
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['uren' => 2.00, 'starttijd' => '09:00:00', 'eindtijd' => '11:00:00']);
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['uren' => 6.50, 'starttijd' => '09:00:00', 'eindtijd' => '15:30:00']);
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['uren' => 3.00, 'starttijd' => '09:00:00', 'eindtijd' => '12:00:00']);

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['sort' => 'duur', 'direction' => 'asc']));

    $urenValues = $response->viewData('rows')->pluck('uren')->map(fn ($u) => (float) $u)->all();
    expect($urenValues)->toBe([2.0, 3.0, 6.5]);
});

it('sorts by medewerker asc', function () {
    Urenregistratie::factory()->ingediend()->for($this->anna, 'user')->for($this->client)->create();
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['sort' => 'medewerker', 'direction' => 'asc']));

    $firstUserId = $response->viewData('rows')->first()->user_id;
    expect($firstUserId)->toBe(min($this->piet->id, $this->anna->id));
});

/* ──────────────────────────────────────────────────────────────────
 * AC-4: Week-summary
 * ────────────────────────────────────────────────────────────────── */

it('calculates week summary per medewerker', function () {
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['uren' => 8.00]);
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['uren' => 7.50]);
    Urenregistratie::factory()->ingediend()->for($this->anna, 'user')->for($this->client)->create(['uren' => 6.00]);

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.overzicht'));

    $summary = $response->viewData('weekSummary');
    expect((float) $summary['Piet Bakker'])->toBe(15.5);
    expect((float) $summary['Anna de Vries'])->toBe(6.0);
    expect((float) $response->viewData('weekTotal'))->toBe(21.5);
});

it('shows medewerker-subtotaal in the rendered header', function () {
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create(['uren' => 8.00]);

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.overzicht'));

    $response->assertSee('Piet Bakker:');
    $response->assertSee('8,00');
});

/* ──────────────────────────────────────────────────────────────────
 * AC-5: Lege-state + eager loading (geen N+1)
 * ────────────────────────────────────────────────────────────────── */

it('shows empty-state when no uren match filters', function () {
    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['week' => '2025-W01']));

    $response->assertOk();
    $response->assertSee('Geen uren gevonden');
});

it('does not trigger N+1 queries on the overzicht (eager loads user and client)', function () {
    Urenregistratie::factory()->ingediend()->count(10)->for($this->piet, 'user')->for($this->client)->create();
    Urenregistratie::factory()->ingediend()->count(10)->for($this->anna, 'user')->for($this->client)->create();

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $this->actingAs($this->teamleider)->get(route('teamleider.uren.overzicht'))->assertOk();

    // Harde bovengrens: 20 rijen zou naïef ~60 queries doen (20×user + 20×client + N).
    // Met eager loading blijft dit onder 15.
    expect($queries)->toBeLessThan(15);
});

/* ──────────────────────────────────────────────────────────────────
 * Policy / role
 * ────────────────────────────────────────────────────────────────── */

it('denies overzicht for zorgbegeleider via middleware', function () {
    $response = $this->actingAs($this->piet)->get(route('teamleider.uren.overzicht'));

    $response->assertForbidden();
});

it('redirects guests to login', function () {
    $response = $this->get(route('teamleider.uren.overzicht'));

    $response->assertRedirect(route('login'));
});

it('limits results to own team only (cross-team data hidden)', function () {
    $foreignZorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->otherTeam->id]);
    $foreignClient = Client::factory()->create(['team_id' => $this->otherTeam->id]);
    Urenregistratie::factory()->ingediend()->count(5)->for($foreignZorg, 'user')->for($foreignClient)->create();
    Urenregistratie::factory()->ingediend()->count(2)->for($this->piet, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['status' => 'alle']));

    expect($response->viewData('rows')->total())->toBe(2);
});

/* ──────────────────────────────────────────────────────────────────
 * UI regressie
 * ────────────────────────────────────────────────────────────────── */

it('sidebar shows Urenoverzicht link for teamleider only', function () {
    $responseTL = $this->actingAs($this->teamleider)->get(route('teamleider.uren.overzicht'));
    $responseTL->assertSee('Urenoverzicht');

    $responseZorg = $this->actingAs($this->piet)->get(route('dashboard'));
    $responseZorg->assertDontSee('Urenoverzicht');
});

it('status alle returns results from every status', function () {
    Urenregistratie::factory()->concept()->for($this->piet, 'user')->for($this->client)->create();
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create();
    Urenregistratie::factory()->goedgekeurd()->for($this->piet, 'user')->for($this->client)->create();
    Urenregistratie::factory()->afgekeurd()->for($this->piet, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)
        ->get(route('teamleider.uren.overzicht', ['status' => 'alle']));

    expect($response->viewData('rows')->total())->toBe(4);
});

it('service getPaginatedForTeamleider ignores invalid ISO week', function () {
    Urenregistratie::factory()->ingediend()->for($this->piet, 'user')->for($this->client)->create();

    $rows = app(UrenregistratieService::class)->getPaginatedForTeamleider($this->teamleider, [
        'week' => 'niet-een-week',
    ]);

    expect($rows->total())->toBe(1);
});

it('service getPaginatedForTeamleider ignores unknown sort column', function () {
    Urenregistratie::factory()->ingediend()->count(3)->for($this->piet, 'user')->for($this->client)->create();

    $rows = app(UrenregistratieService::class)->getPaginatedForTeamleider($this->teamleider, [
        'sort' => 'drop_table',
        'direction' => 'desc',
    ]);

    expect($rows->total())->toBe(3);
});
