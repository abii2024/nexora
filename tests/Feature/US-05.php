<?php

use App\Models\Team;
use App\Models\User;
use App\Models\UserAuditLog;

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Team Rotterdam']);
    $this->otherTeam = Team::factory()->create(['name' => 'Team Amsterdam']);

    $this->teamleider = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Fatima El Amrani',
        'email' => 'fatima@team.test',
        'dienstverband' => 'intern',
    ]);

    $this->jeroen = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@team.test',
        'dienstverband' => 'intern',
    ]);
});

/*
 * AC-1: Voornaam wijzigen -> users.name bijgewerkt + audit-log rij.
 */
it('updates name and writes an audit log row', function () {
    $this->actingAs($this->teamleider)->put(route('team.update', $this->jeroen), [
        'voornaam' => 'Jeroen Updated',
        'achternaam' => 'Bakker',
        'email' => 'jeroen@team.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertRedirect(route('team.index'));

    $this->jeroen->refresh();
    expect($this->jeroen->name)->toBe('Jeroen Updated Bakker');

    $log = UserAuditLog::where('user_id', $this->jeroen->id)->first();
    expect($log)->not->toBeNull();
    expect($log->field)->toBe('name');
    expect($log->old_value)->toBe('Jeroen Bakker');
    expect($log->new_value)->toBe('Jeroen Updated Bakker');
    expect($log->changed_by_user_id)->toBe($this->teamleider->id);
});

it('logs each changed field separately when multiple fields change', function () {
    $this->actingAs($this->teamleider)->put(route('team.update', $this->jeroen), [
        'voornaam' => 'Jeroen',
        'achternaam' => 'Bakker',
        'email' => 'jeroen.bakker@team.test',
        'role' => User::ROLE_TEAMLEIDER,
        'dienstverband' => 'zzp',
    ]);

    $logs = UserAuditLog::where('user_id', $this->jeroen->id)->pluck('field')->all();

    expect($logs)->toContain('email');
    expect($logs)->toContain('role');
    expect($logs)->toContain('dienstverband');
    expect($logs)->not->toContain('name'); // name is niet gewijzigd
    expect(count($logs))->toBe(3);
});

it('writes no audit rows when nothing changes', function () {
    $this->actingAs($this->teamleider)->put(route('team.update', $this->jeroen), [
        'voornaam' => 'Jeroen',
        'achternaam' => 'Bakker',
        'email' => 'jeroen@team.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertRedirect(route('team.index'));

    expect(UserAuditLog::where('user_id', $this->jeroen->id)->count())->toBe(0);
});

/*
 * AC-2: Self-demotion guard.
 */
it('prevents self-demotion when lone teamleider', function () {
    $this->actingAs($this->teamleider)->from(route('team.edit', $this->teamleider))->put(route('team.update', $this->teamleider), [
        'voornaam' => 'Fatima',
        'achternaam' => 'El Amrani',
        'email' => 'fatima@team.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertSessionHasErrors('role');

    $this->teamleider->refresh();
    expect($this->teamleider->role)->toBe(User::ROLE_TEAMLEIDER);
    expect(UserAuditLog::where('user_id', $this->teamleider->id)->count())->toBe(0);
});

it('allows self-demotion when another active teamleider exists in the team', function () {
    User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Bob Tweede',
        'email' => 'bob@team.test',
    ]);

    $this->actingAs($this->teamleider)->put(route('team.update', $this->teamleider), [
        'voornaam' => 'Fatima',
        'achternaam' => 'El Amrani',
        'email' => 'fatima@team.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertRedirect(route('team.index'));

    $this->teamleider->refresh();
    expect($this->teamleider->role)->toBe(User::ROLE_ZORGBEGELEIDER);
});

it('only counts active teamleiders for the self-demotion guard', function () {
    // Een tweede teamleider die INACTIEF is — telt niet mee.
    User::factory()->teamleider()->inactive()->create([
        'team_id' => $this->team->id,
        'email' => 'inactieve-lead@team.test',
    ]);

    $this->actingAs($this->teamleider)->from(route('team.edit', $this->teamleider))->put(route('team.update', $this->teamleider), [
        'voornaam' => 'Fatima',
        'achternaam' => 'El Amrani',
        'email' => 'fatima@team.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertSessionHasErrors('role');
});

it('allows demoting another teamleider (not self) regardless of count', function () {
    $other = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'email' => 'other-lead@team.test',
    ]);

    $this->actingAs($this->teamleider)->put(route('team.update', $other), [
        'voornaam' => 'Other',
        'achternaam' => 'Lead',
        'email' => 'other-lead@team.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertRedirect(route('team.index'));

    $other->refresh();
    expect($other->role)->toBe(User::ROLE_ZORGBEGELEIDER);
});

/*
 * AC-3 + AC-4: Email uniqueness with self-ignore.
 */
it('rejects email already in use by another user', function () {
    $this->actingAs($this->teamleider)->from(route('team.edit', $this->jeroen))->put(route('team.update', $this->jeroen), [
        'voornaam' => 'Jeroen',
        'achternaam' => 'Bakker',
        'email' => 'fatima@team.test', // in gebruik door teamleider
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertSessionHasErrors('email');

    $this->jeroen->refresh();
    expect($this->jeroen->email)->toBe('jeroen@team.test');
});

it('accepts own email unchanged (unique rule ignores own id)', function () {
    $this->actingAs($this->teamleider)->put(route('team.update', $this->jeroen), [
        'voornaam' => 'Jeroen',
        'achternaam' => 'Bakker',
        'email' => 'jeroen@team.test', // eigen huidige email
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertRedirect(route('team.index'))
        ->assertSessionHasNoErrors();
});

/*
 * AC-5: Autorisatie — zorgbegeleider krijgt 403 op edit/update.
 */
it('denies zorgbegeleider access to edit form (403)', function () {
    $response = $this->actingAs($this->jeroen)->get(route('team.edit', $this->teamleider));

    $response->assertStatus(403);
});

it('denies zorgbegeleider PUT /team/{id} (403)', function () {
    $response = $this->actingAs($this->jeroen)->put(route('team.update', $this->jeroen), [
        'voornaam' => 'Should',
        'achternaam' => 'Fail',
        'email' => 'jeroen@team.test',
        'role' => User::ROLE_TEAMLEIDER,
        'dienstverband' => 'intern',
    ]);

    $response->assertStatus(403);
    $this->jeroen->refresh();
    expect($this->jeroen->name)->toBe('Jeroen Bakker');
    expect($this->jeroen->role)->toBe(User::ROLE_ZORGBEGELEIDER);
});

/*
 * Cross-team protection (US-02 geërfd): teamleider Rotterdam kan niemand uit Amsterdam bewerken.
 */
it('denies teamleider from editing users in another team', function () {
    $outsider = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->otherTeam->id,
    ]);

    $this->actingAs($this->teamleider)->get(route('team.edit', $outsider))->assertStatus(403);
    $this->actingAs($this->teamleider)->put(route('team.update', $outsider), [
        'voornaam' => 'X',
        'achternaam' => 'Y',
        'email' => 'hack@attempt.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
    ])->assertStatus(403);
});

it('redirects guest from edit route to /login', function () {
    $response = $this->get(route('team.edit', $this->jeroen));

    $response->assertRedirect(route('login'));
});

/*
 * Validatie edge cases.
 */
it('rejects empty required fields on update', function () {
    $response = $this->actingAs($this->teamleider)->from(route('team.edit', $this->jeroen))->put(route('team.update', $this->jeroen), []);

    $response->assertSessionHasErrors(['voornaam', 'achternaam', 'email', 'role', 'dienstverband']);
});

it('rejects role outside whitelist on update (privilege escalation)', function () {
    $this->actingAs($this->teamleider)->from(route('team.edit', $this->jeroen))->put(route('team.update', $this->jeroen), [
        'voornaam' => 'Jeroen',
        'achternaam' => 'Bakker',
        'email' => 'jeroen@team.test',
        'role' => 'admin',
        'dienstverband' => 'intern',
    ])->assertSessionHasErrors('role');
});

it('shows the edit form with prefilled values', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.edit', $this->jeroen));

    $response->assertOk();
    $response->assertSee('value="Jeroen"', false);
    $response->assertSee('value="Bakker"', false);
    $response->assertSee('value="jeroen@team.test"', false);
});
