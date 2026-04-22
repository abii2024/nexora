<?php

use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create();
});

/*
 * US-02 AC-1: Niet-ingelogd + /dashboard of /clients -> redirect naar /login.
 */
it('redirects guest from /dashboard to /login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

it('redirects guest from /teamleider/dashboard to /login', function () {
    $response = $this->get('/teamleider/dashboard');

    $response->assertRedirect('/login');
});

/*
 * US-02 AC-3: Zorgbegeleider probeert /teamleider/dashboard -> 403.
 */
it('returns 403 when zorgbegeleider visits teamleider dashboard', function () {
    $zorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

    $response = $this->actingAs($zorg)->get('/teamleider/dashboard');

    $response->assertStatus(403);
});

/*
 * US-02 middleware symmetrie: teamleider krijgt 403 op zorgbegeleider-only routes.
 */
it('returns 403 when teamleider visits zorgbegeleider dashboard', function () {
    $teamleider = User::factory()->teamleider()->create(['team_id' => $this->team->id]);

    $response = $this->actingAs($teamleider)->get('/dashboard');

    $response->assertStatus(403);
});

/*
 * Positieve paden (regressie-bescherming na policy-toevoegingen).
 */
it('allows zorgbegeleider on zorgbegeleider dashboard', function () {
    $zorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

    $response = $this->actingAs($zorg)->get('/dashboard');

    $response->assertOk();
});

it('allows teamleider on teamleider dashboard', function () {
    $teamleider = User::factory()->teamleider()->create(['team_id' => $this->team->id]);

    $response = $this->actingAs($teamleider)->get('/teamleider/dashboard');

    $response->assertOk();
});

/*
 * US-02 Privacy bullet 3: 403 pagina toont Nederlandse melding, geen stacktrace.
 */
it('renders a Dutch 403 page without stacktrace when role check fails', function () {
    $zorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

    $response = $this->actingAs($zorg)->get('/teamleider/dashboard');

    $response->assertStatus(403);
    $response->assertSee('Geen toegang');
    $response->assertDontSee('Stack trace');
    $response->assertDontSee('Symfony\\Component');
    $response->assertDontSee('vendor/laravel');
});

/*
 * Defense in depth: middleware faalt bij ontbrekende rol / null user.
 * (Dit kan gebeuren als role-kolom corrupt is of nieuw toegevoegde rol nog geen
 * dedicated middleware heeft. Gedrag moet 403 zijn, geen stacktrace.)
 */
it('returns 403 when user has an unknown role', function () {
    $weirdUser = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'role' => 'onbekend',
    ]);

    $response = $this->actingAs($weirdUser)->get('/dashboard');

    $response->assertStatus(403);
});
