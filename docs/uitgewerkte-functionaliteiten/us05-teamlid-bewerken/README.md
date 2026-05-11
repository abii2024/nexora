# Screenshots US-05 — Teamlid bewerken (rol + dienstverband)

Plaats hier de screenshots van de handmatige uitvoering van het [testplan](../../testplan/US05-teamlid-bewerken.md).

## Vereiste screenshots (DoD US-05)

- [ ] `01-team-met-bewerken-knop.png` — `/team` met 'Bewerken'-link per rij
- [ ] `02-edit-form-voorgevuld.png` — `/team/{id}/edit` met alle velden ingevuld (geen wachtwoord)
- [ ] `03-flash-bijgewerkt.png` — na submit, redirect `/team` met groene flash "Medewerker bijgewerkt."
- [ ] `04-self-demotion-error.png` — Fatima (enige teamleider) probeert eigen rol naar zorgbeg → error banner in edit-form
- [ ] `05-email-duplicaat-error.png` — email al in gebruik fout
- [ ] `06-audit-log-tinker.png` — `php artisan tinker` met `User::find(x)->auditLogs` tonend rijen
- [ ] `07-zorgbeg-403.png` — zorgbegeleider probeert `/team/{id}/edit` → 403
- [ ] `08-pest-output.png` — terminal: `./vendor/bin/pest tests/Feature/US-05.php` 16/16 groen

## Testgebruikers

| E-mail | Wachtwoord | Rol |
|---|---|---|
| `teamleider@nexora.test` | `password` | teamleider (enige in team) |
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider (voor 403 test) |

Setup: `php artisan migrate:fresh --seed`
URL: [http://nexora.test/team](http://nexora.test/team) (login als teamleider)

## Audit-log controleren via tinker

```bash
php artisan tinker
>>> $jeroen = App\Models\User::where('email', 'zorgbegeleider@nexora.test')->first();
>>> $jeroen->auditLogs()->get(['field', 'old_value', 'new_value', 'changed_by_user_id', 'created_at']);
```

## Test-scenario voor TC-03 (self-demotion met 2 teamleiders)

```bash
php artisan tinker
>>> App\Models\User::factory()->teamleider()->create([
...     'team_id' => App\Models\Team::where('name', 'Team Rotterdam-Noord')->first()->id,
...     'name' => 'Bob Tweede',
...     'email' => 'bob@nexora.test',
... ]);
```

Daarna log in als Fatima en bewerk eigen rol → zorgbegeleider. Moet lukken.
