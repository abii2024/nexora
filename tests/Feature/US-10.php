<?php

/*
 * US-10 — Cliënt bewerken en archiveren (statusbeheer + soft delete)
 *
 * Dekt alle 5 acceptatiecriteria + privacy/security-bullets:
 *  - AC-1: /clients/{id}/edit laat velden + caregivers bewerken (BSN-unique ignore)
 *  - AC-2: Statuswissel logt in client_status_logs (alleen bij diff)
 *  - AC-3: Archiveren doet soft delete → verdwijnt uit /clients
 *  - AC-4: /clients/archive toont onlyTrashed → Herstellen restores
 *  - AC-5: forceDelete UI-onbereikbaar (geen route, policy denies)
 */

use App\Models\Client;
use App\Models\ClientStatusLog;
use App\Models\Team;
use App\Models\User;
use App\Services\ClientService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Team Rotterdam']);
    $this->otherTeam = Team::factory()->create(['name' => 'Team Amsterdam']);

    $this->teamleider = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Fatima El Amrani',
    ]);

    $this->zorg = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Jeroen Bakker',
    ]);

    $this->client = Client::factory()->create([
        'team_id' => $this->team->id,
        'voornaam' => 'Sanne',
        'achternaam' => 'de Wit',
        'bsn' => '111222333',
        'status' => Client::STATUS_ACTIEF,
        'care_type' => Client::CARE_WMO,
        'created_by_user_id' => $this->teamleider->id,
    ]);
});

function updatePayload(array $overrides = []): array
{
    return array_merge([
        'voornaam' => 'Sanne',
        'achternaam' => 'de Wit',
        'email' => 'sanne@client.test',
        'telefoon' => '0612345678',
        'bsn' => '111222333',
        'geboortedatum' => '1980-05-17',
        'status' => Client::STATUS_ACTIEF,
        'care_type' => Client::CARE_WMO,
        'caregiver_ids' => [],
        'primary_user_id' => null,
    ], $overrides);
}

/* ──────────────────────────────────────────────────────────────────
 * AC-1: edit-form + update met BSN-unique ignore
 * ────────────────────────────────────────────────────────────────── */

it('renders the edit form with pre-filled values for a teamleider', function () {
    $response = $this->actingAs($this->teamleider)
        ->get(route('clients.edit', $this->client));

    $response->assertOk();
    $response->assertSee('Cliënt bewerken');
    $response->assertSee('value="Sanne"', false);
    $response->assertSee('value="de Wit"', false);
    $response->assertSee('value="111222333"', false);
    $response->assertSee('Archiveren');
});

it('denies edit access for zorgbegeleider', function () {
    $response = $this->actingAs($this->zorg)
        ->get(route('clients.edit', $this->client));

    $response->assertForbidden();
});

it('denies edit access for a teamleider from a different team', function () {
    $other = User::factory()->teamleider()->create(['team_id' => $this->otherTeam->id]);

    $response = $this->actingAs($other)
        ->get(route('clients.edit', $this->client));

    $response->assertForbidden();
});

it('updates all client fields including caregivers', function () {
    $response = $this->actingAs($this->teamleider)
        ->put(route('clients.update', $this->client), updatePayload([
            'voornaam' => 'Sanne',
            'achternaam' => 'Janssen',
            'email' => 'sanne.j@client.test',
            'care_type' => Client::CARE_WLZ,
            'caregiver_ids' => [$this->zorg->id],
            'primary_user_id' => $this->zorg->id,
        ]));

    $response->assertRedirect(route('clients.show', $this->client));
    $this->client->refresh();
    expect($this->client->achternaam)->toBe('Janssen');
    expect($this->client->email)->toBe('sanne.j@client.test');
    expect($this->client->care_type)->toBe(Client::CARE_WLZ);
    expect($this->client->caregivers()->count())->toBe(1);
    expect($this->client->caregivers()->first()->pivot->role)->toBe(Client::ROLE_PRIMAIR);
});

it('accepts own BSN on update without unique conflict', function () {
    $response = $this->actingAs($this->teamleider)
        ->put(route('clients.update', $this->client), updatePayload([
            'bsn' => '111222333',
        ]));

    $response->assertRedirect(route('clients.show', $this->client));
    $response->assertSessionDoesntHaveErrors('bsn');
});

it('rejects an update with a BSN that belongs to another client', function () {
    Client::factory()->create([
        'team_id' => $this->team->id,
        'bsn' => '999888777',
    ]);

    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.edit', $this->client))
        ->put(route('clients.update', $this->client), updatePayload([
            'bsn' => '999888777',
        ]));

    $response->assertSessionHasErrors('bsn');
});

it('validates required fields on update', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.edit', $this->client))
        ->put(route('clients.update', $this->client), updatePayload([
            'voornaam' => '',
            'achternaam' => '',
            'status' => '',
            'care_type' => '',
        ]));

    $response->assertSessionHasErrors(['voornaam', 'achternaam', 'status', 'care_type']);
});

it('denies update for zorgbegeleider', function () {
    $response = $this->actingAs($this->zorg)
        ->put(route('clients.update', $this->client), updatePayload());

    $response->assertForbidden();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-2: statuswissel → client_status_logs
 * ────────────────────────────────────────────────────────────────── */

it('writes a status_log row when status changes', function () {
    $this->actingAs($this->teamleider)
        ->put(route('clients.update', $this->client), updatePayload([
            'status' => Client::STATUS_WACHT,
        ]))
        ->assertRedirect();

    expect(ClientStatusLog::count())->toBe(1);
    $log = ClientStatusLog::first();
    expect($log->client_id)->toBe($this->client->id);
    expect($log->changed_by_user_id)->toBe($this->teamleider->id);
    expect($log->old_status)->toBe(Client::STATUS_ACTIEF);
    expect($log->new_status)->toBe(Client::STATUS_WACHT);
});

it('does not write a status_log row when status is unchanged', function () {
    $this->actingAs($this->teamleider)
        ->put(route('clients.update', $this->client), updatePayload([
            'email' => 'nieuw@client.test',
            'status' => Client::STATUS_ACTIEF,
        ]))
        ->assertRedirect();

    expect(ClientStatusLog::count())->toBe(0);
    $this->client->refresh();
    expect($this->client->email)->toBe('nieuw@client.test');
});

it('writes multiple status_log rows across multiple status changes', function () {
    $this->actingAs($this->teamleider);

    $this->put(route('clients.update', $this->client), updatePayload(['status' => Client::STATUS_WACHT]));
    $this->put(route('clients.update', $this->client), updatePayload(['status' => Client::STATUS_INACTIEF]));
    $this->put(route('clients.update', $this->client), updatePayload(['status' => Client::STATUS_INACTIEF])); // geen diff

    expect(ClientStatusLog::count())->toBe(2);
});

it('shows the status log preview on the show page', function () {
    $this->actingAs($this->teamleider)
        ->put(route('clients.update', $this->client), updatePayload([
            'status' => Client::STATUS_WACHT,
        ]));

    $response = $this->actingAs($this->teamleider)
        ->get(route('clients.show', $this->client));

    $response->assertOk();
    $response->assertSee('Recente statuswijzigingen');
    $response->assertSee('Wacht');
});

/* ──────────────────────────────────────────────────────────────────
 * AC-3: archiveren via soft delete
 * ────────────────────────────────────────────────────────────────── */

it('soft-deletes a client and excludes it from the index', function () {
    $response = $this->actingAs($this->teamleider)
        ->delete(route('clients.archive', $this->client));

    $response->assertRedirect(route('clients.index'));
    $this->client->refresh();
    expect($this->client->trashed())->toBeTrue();

    $index = $this->actingAs($this->teamleider)->get(route('clients.index'));
    $index->assertOk();
    $index->assertDontSee('Sanne de Wit');
});

it('denies archive for a zorgbegeleider', function () {
    $response = $this->actingAs($this->zorg)
        ->delete(route('clients.archive', $this->client));

    $response->assertForbidden();
});

it('denies archive for a teamleider from a different team', function () {
    $other = User::factory()->teamleider()->create(['team_id' => $this->otherTeam->id]);

    $response = $this->actingAs($other)
        ->delete(route('clients.archive', $this->client));

    $response->assertForbidden();
});

it('keeps caregiver pivot rows after archiving', function () {
    $service = app(ClientService::class);
    $service->syncCaregivers($this->client, [$this->zorg->id], $this->zorg->id, $this->teamleider);

    $this->actingAs($this->teamleider)
        ->delete(route('clients.archive', $this->client))
        ->assertRedirect();

    expect(\DB::table('client_caregivers')->where('client_id', $this->client->id)->count())->toBe(1);
});

it('excludes archived clients from ClientService::scopedForUser', function () {
    $this->client->delete();

    $service = app(ClientService::class);
    $ids = $service->scopedForUser($this->teamleider)->pluck('id')->all();

    expect($ids)->not->toContain($this->client->id);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-4: /clients/archive + restore
 * ────────────────────────────────────────────────────────────────── */

it('archive index shows only trashed clients for teamleider', function () {
    $active = $this->client;
    $archived = Client::factory()->create([
        'team_id' => $this->team->id,
        'voornaam' => 'Peter',
        'achternaam' => 'Janssen',
    ]);
    $archived->delete();

    $response = $this->actingAs($this->teamleider)
        ->get(route('clients.archive.index'));

    $response->assertOk();
    $response->assertSee('Peter Janssen');
    $response->assertDontSee($active->fullName());
});

it('archive index only lists trashed clients from the own team', function () {
    $foreign = Client::factory()->create([
        'team_id' => $this->otherTeam->id,
        'voornaam' => 'Alien',
        'achternaam' => 'Test',
    ]);
    $foreign->delete();

    $response = $this->actingAs($this->teamleider)
        ->get(route('clients.archive.index'));

    $response->assertOk();
    $response->assertDontSee('Alien Test');
});

it('denies archive index for a zorgbegeleider', function () {
    $response = $this->actingAs($this->zorg)
        ->get(route('clients.archive.index'));

    $response->assertForbidden();
});

it('restores a trashed client back to the active index', function () {
    $this->client->delete();

    $response = $this->actingAs($this->teamleider)
        ->post(route('clients.restore', $this->client->id));

    $response->assertRedirect(route('clients.show', $this->client));
    $this->client->refresh();
    expect($this->client->trashed())->toBeFalse();

    $index = $this->actingAs($this->teamleider)->get(route('clients.index'));
    $index->assertSee('Sanne de Wit');
});

it('denies restore for a zorgbegeleider', function () {
    $this->client->delete();

    $response = $this->actingAs($this->zorg)
        ->post(route('clients.restore', $this->client->id));

    $response->assertForbidden();
});

it('404s when restoring a non-existent client id', function () {
    $response = $this->actingAs($this->teamleider)
        ->post(route('clients.restore', 99999));

    $response->assertNotFound();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-5: forceDelete UI-onbereikbaar
 * ────────────────────────────────────────────────────────────────── */

it('has no registered forceDelete route', function () {
    $routes = collect(app('router')->getRoutes())->map(fn ($r) => $r->getActionName())->all();
    $forceDeleteActions = array_filter($routes, fn ($a) => str_contains(strtolower($a), 'forcedelete'));
    expect($forceDeleteActions)->toBeEmpty();
});

it('policy denies forceDelete for teamleider and zorgbegeleider', function () {
    $policy = new \App\Policies\ClientPolicy();
    expect($policy->forceDelete($this->teamleider, $this->client))->toBeFalse();
    expect($policy->forceDelete($this->zorg, $this->client))->toBeFalse();
});

/* ──────────────────────────────────────────────────────────────────
 * Service-level unit tests
 * ────────────────────────────────────────────────────────────────── */

it('service update logs status diff inside a single transaction', function () {
    $service = app(ClientService::class);
    $service->update($this->client, [
        'voornaam' => 'Sanne',
        'achternaam' => 'de Wit',
        'email' => null,
        'telefoon' => null,
        'bsn' => '111222333',
        'geboortedatum' => null,
        'status' => Client::STATUS_INACTIEF,
        'care_type' => Client::CARE_WMO,
    ], $this->teamleider);

    $this->client->refresh();
    expect($this->client->status)->toBe(Client::STATUS_INACTIEF);
    expect(ClientStatusLog::count())->toBe(1);
});

it('service archive soft-deletes the client', function () {
    $service = app(ClientService::class);
    $service->archive($this->client, $this->teamleider);

    expect($this->client->refresh()->trashed())->toBeTrue();
});

it('service restore un-trashes the client', function () {
    $this->client->delete();
    $service = app(ClientService::class);
    $service->restore($this->client, $this->teamleider);

    expect($this->client->refresh()->trashed())->toBeFalse();
});

/* ──────────────────────────────────────────────────────────────────
 * UI regressie
 * ────────────────────────────────────────────────────────────────── */

it('shows an Archief link in the header for a teamleider', function () {
    $response = $this->actingAs($this->teamleider)->get(route('clients.index'));

    $response->assertOk();
    $response->assertSee('Archief');
});

it('does not show the Archief link for a zorgbegeleider', function () {
    $this->client->caregivers()->attach($this->zorg->id, [
        'role' => Client::ROLE_PRIMAIR,
        'created_by_user_id' => $this->teamleider->id,
    ]);

    $response = $this->actingAs($this->zorg)->get(route('clients.index'));

    $response->assertOk();
    $response->assertDontSee('route(\'clients.archive.index\')');
});

it('shows the Bewerken button on show page for teamleider only', function () {
    $this->actingAs($this->teamleider)
        ->get(route('clients.show', $this->client))
        ->assertSee('Bewerken');

    $this->client->caregivers()->attach($this->zorg->id, [
        'role' => Client::ROLE_PRIMAIR,
        'created_by_user_id' => $this->teamleider->id,
    ]);

    $this->actingAs($this->zorg)
        ->get(route('clients.show', $this->client))
        ->assertDontSee('Bewerken</a>', false);
});
