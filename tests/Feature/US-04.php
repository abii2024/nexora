<?php

use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Team Rotterdam']);
    $this->otherTeam = Team::factory()->create(['name' => 'Team Amsterdam']);

    $this->teamleider = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Fatima El Amrani',
        'email' => 'fatima@team.test',
    ]);

    $this->jeroen = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@team.test',
    ]);

    $this->lisa = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Lisa Van Dijk',
        'email' => 'lisa@team.test',
    ]);

    $this->ilse = User::factory()->zorgbegeleider()->inactive()->create([
        'team_id' => $this->team->id,
        'name' => 'Ilse Voskuil',
        'email' => 'ilse@team.test',
    ]);

    // User in ander team — mag nooit zichtbaar zijn.
    $this->noa = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->otherTeam->id,
        'name' => 'Noa De Vries',
        'email' => 'noa@team.test',
    ]);
});

/*
 * US-04 AC-1: Tabel toont team-members van eigen team + eigen teamleider.
 * Cross-team mag nooit lekken (regressie uit US-02).
 */
it('renders only users from the authenticated teamleider team', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index'));

    $response->assertOk();
    $response->assertSee('Fatima El Amrani');
    $response->assertSee('Jeroen Bakker');
    $response->assertSee('Lisa Van Dijk');
    $response->assertSee('Ilse Voskuil');
    $response->assertDontSee('Noa De Vries');
});

/*
 * US-04 AC-5 header-teller.
 */
it('shows active/inactive counts in the header', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index'));

    $response->assertSee('3 actief');
    $response->assertSee('1 inactief');
});

/*
 * US-04 AC-2: Zoekbalk op name/email (LIKE %term%).
 */
it('filters by name search term', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['search' => 'Jeroen']));

    $response->assertOk();
    $response->assertSee('Jeroen Bakker');
    $response->assertDontSee('Lisa Van Dijk');
    $response->assertDontSee('Ilse Voskuil');
});

it('filters by email search term', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['search' => 'lisa@']));

    $response->assertOk();
    $response->assertSee('Lisa Van Dijk');
    $response->assertDontSee('Jeroen Bakker');
});

it('filters by partial match (LIKE %term%)', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['search' => 'voskuil']));

    $response->assertOk();
    $response->assertSee('Ilse Voskuil');
    $response->assertDontSee('Jeroen Bakker');
});

/*
 * US-04 AC-2: Filter op rol.
 */
it('filters by role = teamleider', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['role' => 'teamleider']));

    $response->assertOk();
    $response->assertSee('fatima@team.test');
    $response->assertDontSee('jeroen@team.test');
    $response->assertDontSee('lisa@team.test');
});

it('filters by role = zorgbegeleider', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['role' => 'zorgbegeleider']));

    $response->assertOk();
    $response->assertSee('jeroen@team.test');
    $response->assertSee('lisa@team.test');
    $response->assertSee('ilse@team.test');
    // Fatima mag niet in tabel (check email, niet naam — naam staat ook in sidebar-user-footer)
    $response->assertDontSee('fatima@team.test');
});

it('ignores an invalid role filter value (whitelist)', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['role' => 'admin']));

    $response->assertOk();
    // Geen filter toegepast - alle 4 zichtbaar
    $response->assertSee('Fatima El Amrani');
    $response->assertSee('Jeroen Bakker');
    $response->assertSee('Ilse Voskuil');
});

/*
 * US-04 AC-2: Filter op status.
 */
it('filters by status = actief', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['status' => 'actief']));

    $response->assertOk();
    $response->assertSee('Fatima El Amrani');
    $response->assertSee('Jeroen Bakker');
    $response->assertSee('Lisa Van Dijk');
    $response->assertDontSee('Ilse Voskuil');
});

it('filters by status = inactief', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['status' => 'inactief']));

    $response->assertOk();
    $response->assertSee('Ilse Voskuil');
    $response->assertDontSee('Jeroen Bakker');
});

/*
 * US-04 AC-2: Combinatie van filters.
 */
it('combines search + role + status filters', function () {
    // Extra zorgbegeleider matches 'Jeroen' maar inactief -> niet zichtbaar bij status=actief
    User::factory()->zorgbegeleider()->inactive()->create([
        'team_id' => $this->team->id,
        'name' => 'Jeroen Oud',
    ]);

    $response = $this->actingAs($this->teamleider)->get(route('team.index', [
        'search' => 'Jeroen',
        'role' => 'zorgbegeleider',
        'status' => 'actief',
    ]));

    $response->assertOk();
    $response->assertSee('Jeroen Bakker');
    $response->assertDontSee('Jeroen Oud');
});

/*
 * US-04 AC-4: Paginatie 25/pagina.
 */
it('paginates results at 25 per page', function () {
    User::factory()->zorgbegeleider()->count(30)->create([
        'team_id' => $this->team->id,
    ]);

    $response = $this->actingAs($this->teamleider)->get(route('team.index'));

    $response->assertOk();
    // 4 originele + 30 extra = 34 members, max 25 per pagina
    // Pagination links moeten zichtbaar zijn
    $response->assertSee('page=2', false);
});

it('preserves filters across pagination via withQueryString', function () {
    User::factory()->zorgbegeleider()->count(30)->create([
        'team_id' => $this->team->id,
    ]);

    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['role' => 'zorgbegeleider']));

    $response->assertOk();
    $response->assertSee('role=zorgbegeleider', false);
});

/*
 * US-04 AC-4: Inactieve users onderaan (sort by is_active desc, name asc).
 */
it('orders by active users first then alphabetically', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index'));

    $html = $response->getContent();
    $fatimaPos = strpos($html, 'Fatima El Amrani');
    $jeroenPos = strpos($html, 'Jeroen Bakker');
    $lisaPos = strpos($html, 'Lisa Van Dijk');
    $ilsePos = strpos($html, 'Ilse Voskuil');

    // Alle actieven (F/J/L) vóór de inactieve (Ilse)
    expect($fatimaPos)->toBeLessThan($ilsePos);
    expect($jeroenPos)->toBeLessThan($ilsePos);
    expect($lisaPos)->toBeLessThan($ilsePos);

    // Binnen actieven: alfabetisch (F < J < L)
    expect($fatimaPos)->toBeLessThan($jeroenPos);
    expect($jeroenPos)->toBeLessThan($lisaPos);
});

/*
 * Empty state wanneer filters geen match geven.
 */
it('shows empty state message when no results match filters', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.index', [
        'search' => 'bestaatniet',
    ]));

    $response->assertOk();
    $response->assertSee('Geen medewerkers gevonden');
});

/*
 * US-04 AC-3 + Privacy bullet 1: autorisatie.
 */
it('denies zorgbegeleider access to /team (403)', function () {
    $response = $this->actingAs($this->jeroen)->get(route('team.index'));

    $response->assertStatus(403);
});

it('redirects guest from /team to /login', function () {
    $response = $this->get(route('team.index'));

    $response->assertRedirect(route('login'));
});

/*
 * US-04 Privacy bullet 2: XSS protection — zoekterm wordt Blade-escaped in view.
 */
it('escapes search term in HTML output (XSS protection)', function () {
    $xss = '<script>alert("xss")</script>';

    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['search' => $xss]));

    $response->assertOk();
    // Raw script tag mag NIET in de body verschijnen — Blade moet escapen.
    expect($response->getContent())->not->toContain('<script>alert("xss")</script>');
    // Wel ge-escaped aanwezig in het input-veld value
    expect($response->getContent())->toContain('&lt;script&gt;');
});

/*
 * Query-binding protection: SQL-injection pogingen hebben geen effect.
 */
it('is safe against SQL-injection in search term', function () {
    $injection = "' OR 1=1 --";

    $response = $this->actingAs($this->teamleider)->get(route('team.index', ['search' => $injection]));

    $response->assertOk();
    // Mag geen users retourneren omdat LIKE %' OR 1=1 --% op niemand matcht
    $response->assertSee('Geen medewerkers gevonden');
});
