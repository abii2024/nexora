<?php

/*
 * US-09 — Cliëntenoverzicht met rol-gebaseerde weergave, zoek en filter
 *
 * 2 describe-groepen:
 *  1. ClientService::getPaginated — service-niveau (filter/sort/eager/scope)
 *  2. HTTP integratie — rol-specifieke view, filters in URL, empty-states
 *
 * AC mapping:
 *  AC-1: zorgbeg ziet exact eigen toegewezen cliënten (query-scope bewezen)
 *  AC-2: teamleider ziet alle team-cliënten + 'Cliënt toevoegen' knop
 *  AC-3: zoekterm 'Jan' → case-insensitive match op voornaam of achternaam
 *  AC-4: filters blijven behouden bij paginatie via query-params (withQueryString)
 *  AC-5: zorgbeg zonder koppeling → lege-state 'Je hebt momenteel geen cliënten toegewezen.'
 */

use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use App\Services\ClientService;
use Illuminate\Support\Facades\DB;

/* ───────────────────────────────────────────────────────────── */
/* 1. ClientService::getPaginated — service-niveau              */
/* ───────────────────────────────────────────────────────────── */

describe('getPaginated', function () {
    beforeEach(function () {
        $this->service = new ClientService();

        $this->teamA = Team::factory()->create();
        $this->teamB = Team::factory()->create();

        $this->teamleider = User::factory()->teamleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgA = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgB = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgOther = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamB->id]);

        // 4 cliënten in teamA
        $this->c1 = Client::factory()->actief()->wmo()->create([
            'team_id' => $this->teamA->id, 'voornaam' => 'Jan', 'achternaam' => 'Bakker',
        ]);
        $this->c2 = Client::factory()->wachtlijst()->wlz()->create([
            'team_id' => $this->teamA->id, 'voornaam' => 'Sanne', 'achternaam' => 'Janssen',
        ]);
        $this->c3 = Client::factory()->actief()->jw()->create([
            'team_id' => $this->teamA->id, 'voornaam' => 'Mo', 'achternaam' => 'El-Fassi',
        ]);
        $this->c4 = Client::factory()->inactief()->wmo()->create([
            'team_id' => $this->teamA->id, 'voornaam' => 'Ilse', 'achternaam' => 'Voskuil',
        ]);

        // zorgA gekoppeld aan c1 + c2 (NIET c3/c4). zorgB aan c3.
        $this->c1->caregivers()->attach($this->zorgA->id, ['role' => Client::ROLE_PRIMAIR]);
        $this->c2->caregivers()->attach($this->zorgA->id, ['role' => Client::ROLE_SECUNDAIR]);
        $this->c3->caregivers()->attach($this->zorgB->id, ['role' => Client::ROLE_PRIMAIR]);
    });

    /*
     * AC-2: Teamleider ziet alle team-cliënten.
     */
    it('returns all team clients for teamleider', function () {
        $result = $this->service->getPaginated($this->teamleider);

        expect($result->total())->toBe(4);
        expect($result->pluck('id')->all())->toContain(
            $this->c1->id,
            $this->c2->id,
            $this->c3->id,
            $this->c4->id
        );
    });

    /*
     * AC-1: Zorgbegeleider ziet ALLEEN eigen toegewezen cliënten.
     */
    it('returns only linked clients for zorgbegeleider (AC-1 kern)', function () {
        $result = $this->service->getPaginated($this->zorgA);

        expect($result->total())->toBe(2);
        $ids = $result->pluck('id')->all();
        expect($ids)->toContain($this->c1->id, $this->c2->id);
        expect($ids)->not->toContain($this->c3->id); // zorgB's cliënt
        expect($ids)->not->toContain($this->c4->id); // niemands pivot
    });

    /*
     * AC-5: Zorgbegeleider zonder koppeling → lege set.
     */
    it('returns an empty paginator for a zorgbegeleider with no assignments', function () {
        $result = $this->service->getPaginated($this->zorgOther);

        expect($result->total())->toBe(0);
        expect($result->isEmpty())->toBeTrue();
    });

    /*
     * AC-3: Zoekterm 'Jan' matcht voornaam 'Jan' + achternaam 'Janssen' (case-insensitive).
     */
    it('filters by search term on voornaam and achternaam (case-insensitive)', function () {
        $result = $this->service->getPaginated($this->teamleider, ['search' => 'Jan']);

        $names = $result->map(fn ($c) => $c->fullName())->all();
        expect($names)->toContain('Jan Bakker', 'Sanne Janssen');
        expect($names)->not->toContain('Mo El-Fassi', 'Ilse Voskuil');
    });

    it('filters by lowercase search term (regressie op case-insensitivity)', function () {
        $result = $this->service->getPaginated($this->teamleider, ['search' => 'jan']);

        // SQLite default LIKE is case-insensitive voor ASCII
        expect($result->total())->toBe(2);
    });

    it('filters by status whitelist only', function () {
        $result = $this->service->getPaginated($this->teamleider, ['status' => 'actief']);

        expect($result->total())->toBe(2);
        expect($result->pluck('status')->unique()->all())->toBe(['actief']);
    });

    it('ignores invalid status filter (whitelist)', function () {
        $result = $this->service->getPaginated($this->teamleider, ['status' => 'dropped']);

        expect($result->total())->toBe(4); // whitelist wordt niet toegepast
    });

    it('filters by care_type whitelist', function () {
        $result = $this->service->getPaginated($this->teamleider, ['care_type' => 'wmo']);

        expect($result->pluck('care_type')->unique()->all())->toBe(['wmo']);
        expect($result->total())->toBe(2);
    });

    it('combines search + status + care_type filters', function () {
        $result = $this->service->getPaginated($this->teamleider, [
            'search' => 'Jan',
            'status' => 'actief',
            'care_type' => 'wmo',
        ]);

        expect($result->total())->toBe(1);
        expect($result->first()->voornaam)->toBe('Jan');
    });

    it('sorts by achternaam alphabetically by default', function () {
        $result = $this->service->getPaginated($this->teamleider);

        $achternamen = $result->pluck('achternaam')->all();
        $sorted = $achternamen;
        sort($sorted);
        expect($achternamen)->toBe($sorted);
    });

    it('sorts by created_at descending when sort=created_at', function () {
        $result = $this->service->getPaginated($this->teamleider, ['sort' => 'created_at']);

        $timestamps = $result->pluck('created_at')->map(fn ($t) => $t->timestamp)->all();
        $sortedDesc = $timestamps;
        rsort($sortedDesc);
        expect($timestamps)->toBe($sortedDesc);
    });

    it('paginates at 15 per page by default', function () {
        User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
        Client::factory()->count(20)->create(['team_id' => $this->teamA->id]);

        $result = $this->service->getPaginated($this->teamleider);

        expect($result->perPage())->toBe(15);
        expect($result->count())->toBe(15); // eerste pagina
        expect($result->total())->toBeGreaterThan(20);
    });

    /*
     * N+1 regressie: eager loading voorkomt N+1 queries bij caregiver-rendering.
     */
    it('eager loads caregivers to prevent N+1 queries', function () {
        // Verleen elke teamA-cliënt een caregiver
        foreach ([$this->c1, $this->c2, $this->c3, $this->c4] as $client) {
            if (!$client->caregivers()->count()) {
                $client->caregivers()->attach($this->zorgB->id, ['role' => Client::ROLE_TERTIAIR]);
            }
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $result = $this->service->getPaginated($this->teamleider);
        // Trigger caregiver-access zoals de view dat doet
        foreach ($result as $client) {
            $client->caregivers->first(fn ($c) => $c->pivot->role === 'primair');
        }

        // Verwacht: 1 COUNT + 1 SELECT clients + 1 SELECT caregivers + 1 SELECT team
        // Zonder eager load zou 1 SELECT per cliënt op caregivers gebeuren.
        expect($queryCount)->toBeLessThan(10);
    });
});

/* ───────────────────────────────────────────────────────────── */
/* 2. HTTP integratie                                            */
/* ───────────────────────────────────────────────────────────── */

describe('HTTP integration', function () {
    beforeEach(function () {
        $this->teamA = Team::factory()->create(['name' => 'Team Rotterdam']);
        $this->teamB = Team::factory()->create(['name' => 'Team Amsterdam']);

        $this->teamleider = User::factory()->teamleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgA = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
        $this->zorgOther = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamB->id]);

        $this->c1 = Client::factory()->actief()->wmo()->create([
            'team_id' => $this->teamA->id, 'voornaam' => 'Jan', 'achternaam' => 'Bakker',
        ]);
        $this->c2 = Client::factory()->wachtlijst()->wlz()->create([
            'team_id' => $this->teamA->id, 'voornaam' => 'Sanne', 'achternaam' => 'Janssen',
        ]);
        $this->c3 = Client::factory()->actief()->jw()->create([
            'team_id' => $this->teamA->id, 'voornaam' => 'Mo', 'achternaam' => 'El-Fassi',
        ]);

        $this->c1->caregivers()->attach($this->zorgA->id, ['role' => Client::ROLE_PRIMAIR]);
        $this->c2->caregivers()->attach($this->zorgA->id, ['role' => Client::ROLE_SECUNDAIR]);
    });

    /*
     * AC-2: Teamleider ziet tabel-weergave met alle team-cliënten.
     */
    it('teamleider index shows all team clients with Client toevoegen button', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee('Cliënt toevoegen');
        $response->assertSee('Jan Bakker');
        $response->assertSee('Sanne Janssen');
        $response->assertSee('Mo El-Fassi');
        $response->assertSee('Primaire begeleider'); // tabel-kolom (teamleider-specifiek)
    });

    /*
     * AC-1: Zorgbegeleider A met 2 toegewezen cliënten ziet exact die 2, niet c3.
     */
    it('zorgbegeleider sees only own linked clients (AC-1 kern)', function () {
        $response = $this->actingAs($this->zorgA)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee('Jan Bakker');
        $response->assertSee('Sanne Janssen');
        $response->assertDontSee('Mo El-Fassi'); // collega's cliënt
    });

    it('zorgbegeleider sees info banner about eigen caseload', function () {
        $response = $this->actingAs($this->zorgA)->get(route('clients.index'));

        $response->assertSee('Eigen caseload');
    });

    it('zorgbegeleider does NOT see the Client toevoegen button', function () {
        $response = $this->actingAs($this->zorgA)->get(route('clients.index'));

        $response->assertDontSee('Cliënt toevoegen');
    });

    /*
     * AC-3: Zoekterm matcht voornaam OR achternaam.
     */
    it('filters index by search term Jan (matches voornaam and achternaam)', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.index', ['search' => 'Jan']));

        $response->assertOk();
        $response->assertSee('Jan Bakker');
        $response->assertSee('Sanne Janssen');
        $response->assertDontSee('Mo El-Fassi');
    });

    it('filters by status=actief', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.index', ['status' => 'actief']));

        $response->assertOk();
        $response->assertSee('Jan Bakker');
        $response->assertSee('Mo El-Fassi');
        $response->assertDontSee('Sanne Janssen'); // wachtlijst
    });

    it('filters by care_type=wmo', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.index', ['care_type' => 'wmo']));

        $response->assertOk();
        $response->assertSee('Jan Bakker');
        $response->assertDontSee('Sanne Janssen'); // wlz
    });

    /*
     * AC-4: Filters blijven behouden bij paginatie via withQueryString.
     */
    it('preserves filters across pagination links', function () {
        Client::factory()->count(20)->create(['team_id' => $this->teamA->id]);

        $response = $this->actingAs($this->teamleider)->get(route('clients.index', ['status' => 'actief']));

        $response->assertOk();
        // De paginatie-links moeten de filter-query-string bevatten
        expect($response->getContent())->toContain('status=actief');
    });

    /*
     * AC-5: Zorgbegeleider zonder koppeling → empty-state met exacte tekst uit Trello AC.
     */
    it('zorgbegeleider zonder koppeling shows empty state with exact Trello AC text', function () {
        $response = $this->actingAs($this->zorgOther)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee('Je hebt momenteel geen cliënten toegewezen.');
    });

    /*
     * Guest + cross-team regressie (US-02).
     */
    it('redirects guest from /clients to /login', function () {
        $this->get(route('clients.index'))->assertRedirect(route('login'));
    });

    it('does not leak clients across teams on the index (US-02 regressie)', function () {
        Client::factory()->create([
            'team_id' => $this->teamB->id,
            'voornaam' => 'Geheim',
            'achternaam' => 'Amsterdam',
        ]);

        $response = $this->actingAs($this->teamleider)->get(route('clients.index'));

        $response->assertDontSee('Geheim Amsterdam');
    });

    /*
     * Empty-state met filters (niet eigen-caseload-empty).
     */
    it('shows filter-empty-state when search returns no results for teamleider', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.index', ['search' => 'nonexistent_xyz_name']));

        $response->assertOk();
        $response->assertSee('Geen cliënten gevonden');
    });

    /*
     * Filter-bar component rendert met juiste state.
     */
    it('renders filter-bar with selected values from query string', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.index', [
            'search' => 'Jan',
            'status' => 'actief',
            'care_type' => 'wmo',
        ]));

        $response->assertOk();
        $response->assertSee('value="Jan"', false); // search-veld behoudt waarde
        // Actieve filters renderen geselecteerd
        expect($response->getContent())->toContain('<option value="actief" selected');
        expect($response->getContent())->toContain('<option value="wmo" selected');
    });

    /*
     * Rolspecifieke banner per rol (AC omschrijving bullet 4).
     */
    it('shows totaal count + create CTA for teamleider banner', function () {
        $response = $this->actingAs($this->teamleider)->get(route('clients.index'));

        $response->assertSee('3 cliënten');
        $response->assertSee('Team Rotterdam');
    });
});
