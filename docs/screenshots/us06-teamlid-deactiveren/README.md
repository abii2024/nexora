# Screenshots US-06 — Teamlid deactiveren en heractiveren

Plaats hier de screenshots van de handmatige uitvoering van het [testplan](../../testplan/US06-teamlid-deactiveren.md).

## Vereiste screenshots (DoD US-06)

- [ ] `01-edit-accountstatus-actief.png` — Edit-form met "Deactiveren"-knop in Accountstatus-card (rood, voor actieve user)
- [ ] `02-confirm-dialog.png` — native browser-confirm dialog "Weet je zeker dat je ... wilt deactiveren?"
- [ ] `03-flash-gedeactiveerd.png` — /team na deactivatie met groene flash + inactief-badge + opacity op rij
- [ ] `04-login-blocked-melding.png` — gedeactiveerde user probeert in te loggen → rode melding "Dit account is gedeactiveerd"
- [ ] `05-middleware-redirect.png` — Chrome DevTools Netwerk-tab: /dashboard request returns 302 → /login voor gedeactiveerde user
- [ ] `06-edit-accountstatus-inactief.png` — Edit-form met "Heractiveren"-knop (groen) voor inactieve user
- [ ] `07-reactivated-login.png` — heractiveerde user logt in met bestaand wachtwoord → /dashboard
- [ ] `08-audit-log-tinker.png` — tinker output van `User::find(x)->auditLogs` met is_active rijen
- [ ] `09-zorgbeg-403.png` — zorgbegeleider probeert POST /team/{id}/deactivate → 403
- [ ] `10-pest-output.png` — terminal: `./vendor/bin/pest tests/Feature/Team/DeactivateTeamMemberTest.php` 19/19 groen

## Testgebruikers

| E-mail | Wachtwoord | Rol |
|---|---|---|
| `teamleider@nexora.test` | `password` | teamleider |
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider (test-target) |
| `mo@nexora.test` | `password` | zorgbegeleider (voor 403-test) |

Setup: `php artisan migrate:fresh --seed`

URL-flow:
1. [http://nexora.test/team](http://nexora.test/team) (login als teamleider)
2. Klik Bewerken bij Jeroen → `/team/{id}/edit`
3. Scroll naar Accountstatus-card → Deactiveren

## Audit-log inspectie

```bash
php artisan tinker
>>> App\Models\User::where('email', 'zorgbegeleider@nexora.test')
...     ->first()->auditLogs()->get(['field', 'old_value', 'new_value']);
```

## 2-browser test voor CheckActiveUser middleware (TC-02)

1. Chrome: login als `zorgbegeleider@nexora.test` → bij /dashboard
2. Safari (of Chrome incognito): login als `teamleider@nexora.test`
3. Safari: Bewerken → Deactiveren Jeroen
4. Chrome: refresh pagina → redirect /login met melding
