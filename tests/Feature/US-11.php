<?php

/*
 * US-11 — Concept-uren aanmaken en bewerken
 *
 * Dekt alle 5 acceptatiecriteria:
 *  - AC-1: /uren met tabs Concept/Ingediend/Goedgekeurd/Afgekeurd + "Uren toevoegen"
 *  - AC-2: /uren/create met cliënt-dropdown uit eigen caseload
 *  - AC-3: server-side duur-berekening + eindtijd > starttijd validatie
 *  - AC-4: status=concept + user_id=auth->id altijd via service (geen mass-assignment)
 *  - AC-5: bewerken alleen bij concept/afgekeurd; ingediend/goedgekeurd = 403
 */

use App\Enums\UrenStatus;
use App\Models\Client;
use App\Models\Team;
use App\Models\Urenregistratie;
use App\Models\User;
use App\Services\UrenregistratieService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Team Rotterdam']);
    $this->otherTeam = Team::factory()->create(['name' => 'Team Amsterdam']);

    $this->teamleider = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
    ]);

    $this->zorg = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Jeroen Bakker',
    ]);
    $this->otherZorg = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Mo Hassan',
    ]);

    // Cliënt gekoppeld aan $this->zorg
    $this->client = Client::factory()->create([
        'team_id' => $this->team->id,
        'voornaam' => 'Sanne',
        'achternaam' => 'de Wit',
    ]);
    $this->client->caregivers()->attach($this->zorg->id, [
        'role' => Client::ROLE_PRIMAIR,
        'created_by_user_id' => $this->teamleider->id,
    ]);

    // Cliënt NIET aan $this->zorg gekoppeld (collega's caseload)
    $this->collegaClient = Client::factory()->create([
        'team_id' => $this->team->id,
        'voornaam' => 'Peter',
        'achternaam' => 'Jansen',
    ]);
    $this->collegaClient->caregivers()->attach($this->otherZorg->id, [
        'role' => Client::ROLE_PRIMAIR,
        'created_by_user_id' => $this->teamleider->id,
    ]);
});

function urenPayload(array $overrides = []): array
{
    return array_merge([
        'client_id' => null,
        'datum' => now()->format('Y-m-d'),
        'starttijd' => '09:00',
        'eindtijd' => '12:30',
        'notities' => 'Begeleiding bij dagstructuur.',
    ], $overrides);
}

/* ──────────────────────────────────────────────────────────────────
 * AC-1: /uren index + tabs
 * ────────────────────────────────────────────────────────────────── */

it('shows the uren index with 4 tabs and a toevoegen button for zorgbegeleider', function () {
    $response = $this->actingAs($this->zorg)->get(route('uren.index'));

    $response->assertOk();
    $response->assertSee('Urenregistratie');
    $response->assertSee('Concepten');
    $response->assertSee('Ingediend');
    $response->assertSee('Goedgekeurd');
    $response->assertSee('Afgekeurd');
    $response->assertSee('Uren toevoegen');
});

it('defaults to the concept tab when no status query is given', function () {
    Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create();
    Urenregistratie::factory()->ingediend()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.index'));

    $response->assertOk();
    expect($response->viewData('activeStatus'))->toBe(UrenStatus::Concept);
    expect($response->viewData('rows')->count())->toBe(1);
});

it('filters rows by the selected status tab', function () {
    Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create();
    Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)
        ->get(route('uren.index', ['status' => UrenStatus::Goedgekeurd->value]));

    $response->assertOk();
    expect($response->viewData('activeStatus'))->toBe(UrenStatus::Goedgekeurd);
    expect($response->viewData('rows')->count())->toBe(1);
});

it('shows counts for every tab', function () {
    Urenregistratie::factory()->concept()->count(3)->for($this->zorg, 'user')->for($this->client)->create();
    Urenregistratie::factory()->ingediend()->count(2)->for($this->zorg, 'user')->for($this->client)->create();
    Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.index'));

    $counts = $response->viewData('counts');
    expect($counts['concept'])->toBe(3);
    expect($counts['ingediend'])->toBe(2);
    expect($counts['goedgekeurd'])->toBe(1);
    expect($counts['afgekeurd'])->toBe(0);
});

it('only shows the own uren on the index (not colleague rows)', function () {
    Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create();
    Urenregistratie::factory()->concept()->for($this->otherZorg, 'user')->for($this->collegaClient)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.index'));

    $response->assertOk();
    expect($response->viewData('rows')->count())->toBe(1);
    $response->assertDontSee('Peter Jansen');
});

it('redirects guests from the uren index to login', function () {
    $response = $this->get(route('uren.index'));
    $response->assertRedirect(route('login'));
});

/* ──────────────────────────────────────────────────────────────────
 * AC-2: /uren/create formulier
 * ────────────────────────────────────────────────────────────────── */

it('renders the create form with own caseload clients only', function () {
    $response = $this->actingAs($this->zorg)->get(route('uren.create'));

    $response->assertOk();
    $response->assertSee('Uren toevoegen');
    $response->assertSee('Sanne de Wit');
    $response->assertDontSee('Peter Jansen');
});

it('denies create for teamleider', function () {
    $response = $this->actingAs($this->teamleider)->get(route('uren.create'));

    $response->assertForbidden();
});

it('shows an empty-state when the zorgbegeleider has no assigned clients', function () {
    $loneZorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

    $response = $this->actingAs($loneZorg)->get(route('uren.create'));

    $response->assertOk();
    $response->assertSee('Geen cliënten toegewezen');
});

/* ──────────────────────────────────────────────────────────────────
 * AC-3: validatie + server-side duur
 * ────────────────────────────────────────────────────────────────── */

it('rejects when eindtijd is before or equal to starttijd', function () {
    $response = $this->actingAs($this->zorg)
        ->from(route('uren.create'))
        ->post(route('uren.store'), urenPayload([
            'client_id' => $this->client->id,
            'starttijd' => '17:00',
            'eindtijd' => '09:00',
        ]));

    $response->assertSessionHasErrors(['eindtijd']);
    $response->assertSessionHasInput('starttijd', '17:00');
    expect(Urenregistratie::count())->toBe(0);
});

it('rejects when required fields are missing', function () {
    $response = $this->actingAs($this->zorg)
        ->from(route('uren.create'))
        ->post(route('uren.store'), [
            'client_id' => '',
            'datum' => '',
            'starttijd' => '',
            'eindtijd' => '',
        ]);

    $response->assertSessionHasErrors(['client_id', 'datum', 'starttijd', 'eindtijd']);
});

it('rejects a future datum', function () {
    $response = $this->actingAs($this->zorg)
        ->from(route('uren.create'))
        ->post(route('uren.store'), urenPayload([
            'client_id' => $this->client->id,
            'datum' => now()->addDays(1)->format('Y-m-d'),
        ]));

    $response->assertSessionHasErrors(['datum']);
});

it('computes the duration server-side in decimal hours', function () {
    $this->actingAs($this->zorg)
        ->post(route('uren.store'), urenPayload([
            'client_id' => $this->client->id,
            'starttijd' => '09:00',
            'eindtijd' => '12:30',
        ]))
        ->assertRedirect();

    $row = Urenregistratie::first();
    expect((float) $row->uren)->toBe(3.5);
});

it('computes quarter-hour durations correctly (1h15m → 1.25)', function () {
    $service = app(UrenregistratieService::class);
    expect($service->computeDuration('09:00:00', '10:15:00'))->toBe(1.25);
    expect($service->computeDuration('08:30:00', '17:00:00'))->toBe(8.5);
    expect($service->computeDuration('12:00:00', '12:00:00'))->toBe(0.0);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-4: status=concept + user_id altijd server-side
 * ────────────────────────────────────────────────────────────────── */

it('stores a new entry with status concept and user_id of the current user', function () {
    $this->actingAs($this->zorg)
        ->post(route('uren.store'), urenPayload([
            'client_id' => $this->client->id,
        ]))
        ->assertRedirect();

    $row = Urenregistratie::first();
    expect($row->status)->toBe(UrenStatus::Concept);
    expect($row->user_id)->toBe($this->zorg->id);
});

it('ignores any attempt to mass-assign user_id or status', function () {
    $this->actingAs($this->zorg)
        ->post(route('uren.store'), urenPayload([
            'client_id' => $this->client->id,
            'user_id' => $this->otherZorg->id,
            'status' => UrenStatus::Goedgekeurd->value,
        ]))
        ->assertRedirect();

    $row = Urenregistratie::first();
    expect($row->user_id)->toBe($this->zorg->id);
    expect($row->status)->toBe(UrenStatus::Concept);
});

it('blocks choosing a client outside the own caseload with 403', function () {
    $response = $this->actingAs($this->zorg)
        ->post(route('uren.store'), urenPayload([
            'client_id' => $this->collegaClient->id,
        ]));

    $response->assertForbidden();
    expect(Urenregistratie::count())->toBe(0);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-5: bewerken alleen bij concept/afgekeurd
 * ────────────────────────────────────────────────────────────────── */

it('allows editing a concept entry', function () {
    $uren = Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create([
        'starttijd' => '09:00:00',
        'eindtijd' => '12:00:00',
        'uren' => 3.0,
    ]);

    $response = $this->actingAs($this->zorg)->get(route('uren.edit', $uren));

    $response->assertOk();
    $response->assertSee('Uren bewerken');
});

it('allows editing an afgekeurd entry', function () {
    $uren = Urenregistratie::factory()->afgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.edit', $uren));

    $response->assertOk();
});

it('denies editing an ingediend entry with 403', function () {
    $uren = Urenregistratie::factory()->ingediend()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.edit', $uren));

    $response->assertForbidden();
});

it('denies editing a goedgekeurd entry with 403', function () {
    $uren = Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.edit', $uren));

    $response->assertForbidden();
});

it('denies editing another users entry with 403', function () {
    $uren = Urenregistratie::factory()->concept()->for($this->otherZorg, 'user')->for($this->collegaClient)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.edit', $uren));

    $response->assertForbidden();
});

it('updates a concept entry and keeps status unchanged', function () {
    $uren = Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create([
        'starttijd' => '09:00:00',
        'eindtijd' => '12:00:00',
        'uren' => 3.0,
        'notities' => 'Oud.',
    ]);

    $response = $this->actingAs($this->zorg)
        ->put(route('uren.update', $uren), urenPayload([
            'client_id' => $this->client->id,
            'starttijd' => '10:00',
            'eindtijd' => '13:30',
            'notities' => 'Nieuw.',
        ]));

    $response->assertRedirect(route('uren.index', ['status' => 'concept']));
    $uren->refresh();
    expect((float) $uren->uren)->toBe(3.5);
    expect($uren->notities)->toBe('Nieuw.');
    expect($uren->status)->toBe(UrenStatus::Concept);
});

it('rejects an update that points to a client outside the own caseload', function () {
    $uren = Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)
        ->put(route('uren.update', $uren), urenPayload([
            'client_id' => $this->collegaClient->id,
        ]));

    $response->assertForbidden();
});

it('denies update via POST request by a teamleider', function () {
    $uren = Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->teamleider)
        ->put(route('uren.update', $uren), urenPayload([
            'client_id' => $this->client->id,
        ]));

    $response->assertForbidden();
});

/* ──────────────────────────────────────────────────────────────────
 * Service / policy unit-checks
 * ────────────────────────────────────────────────────────────────── */

it('service create sets status and user_id correctly', function () {
    $service = app(UrenregistratieService::class);

    $uren = $service->create($this->zorg, [
        'client_id' => $this->client->id,
        'datum' => now()->format('Y-m-d'),
        'starttijd' => '09:00:00',
        'eindtijd' => '11:00:00',
        'notities' => null,
    ]);

    expect($uren->user_id)->toBe($this->zorg->id);
    expect($uren->status)->toBe(UrenStatus::Concept);
    expect((float) $uren->uren)->toBe(2.0);
});

it('policy delete returns false for anyone', function () {
    $policy = new \App\Policies\UrenregistratiePolicy();
    $uren = Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create();

    expect($policy->delete($this->zorg, $uren))->toBeFalse();
    expect($policy->delete($this->teamleider, $uren))->toBeFalse();
});

it('UrenStatus helpers return consistent values', function () {
    expect(UrenStatus::Concept->isEditable())->toBeTrue();
    expect(UrenStatus::Afgekeurd->isEditable())->toBeTrue();
    expect(UrenStatus::Ingediend->isEditable())->toBeFalse();
    expect(UrenStatus::Goedgekeurd->isEditable())->toBeFalse();
    expect(UrenStatus::Goedgekeurd->label())->toBe('Goedgekeurd');
    expect(UrenStatus::Afgekeurd->badgeTone())->toBe('danger');
});
