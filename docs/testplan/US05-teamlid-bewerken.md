# Testplan — US-05 Teamlid bewerken (rol + dienstverband)

> **User story:** Als teamleider wil ik gegevens van bestaande teamleden kunnen bijwerken (naam, email, rol, dienstverband) zodat het medewerkersregister actueel blijft bij functie- of contractwijzigingen.
>
> **Branch:** `feature/teamlid-bewerken`
> **Feature test:** [`tests/Feature/Team/UpdateTeamMemberTest.php`](../../tests/Feature/Team/UpdateTeamMemberTest.php)
> **Algemeen testplan:** [README.md](./README.md)

## 1. Soorten testen uitgevoerd

| Soort | Tool | Locatie | Aantal |
|---|---|---|---|
| Feature test (geautomatiseerd) | Pest v4 | `tests/Feature/Team/UpdateTeamMemberTest.php` | 16 tests · 54 asserts |
| Handmatige browser-test | Chrome / Safari | `http://nexora.test/team/{id}/edit` | 7 TC |

## 2. Test-gebruikers

| Naam | E-mail | Rol | Team |
|---|---|---|---|
| Fatima El Amrani | `teamleider@nexora.test` | teamleider (enige) | Rotterdam-Noord |
| Jeroen Bakker | `zorgbegeleider@nexora.test` | zorgbegeleider | Rotterdam-Noord |
| Mo Yilmaz | `mo@nexora.test` | zorgbegeleider | Rotterdam-Noord |
| Ilse Voskuil | `inactief@nexora.test` | zorgbegeleider (inactief) | Rotterdam-Noord |

Setup: `php artisan migrate:fresh --seed`

## 3. Handmatige testscenario's

### TC-01 — Voornaam wijzigen + audit-log (AC-1)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als `teamleider@nexora.test` | Op teamleider-dashboard | ✅ |
| 2 | Ga naar `/team`, klik **Bewerken** bij Jeroen Bakker | `/team/{id}/edit` opent met voorgevulde velden | ✅ Formulier rendert, name split: voornaam="Jeroen", achternaam="Bakker" |
| 3 | Wijzig voornaam naar "Jeroen Updated" en submit | Redirect `/team` met flash "Medewerker bijgewerkt." | ✅ |
| 4 | Tabel toont "Jeroen Updated Bakker" | Ja | ✅ |
| 5 | Tinker: `User::find(jeroen)->auditLogs` | 1 rij: `field='name'`, `old_value='Jeroen Bakker'`, `new_value='Jeroen Updated Bakker'`, `changed_by_user_id` = teamleider | ✅ |

**Pest-dekking:** `it('updates name and writes an audit log row')` — **PASS**

### TC-02 — Self-demotion guard — enige teamleider (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als Fatima (enige teamleider) | `/teamleider/dashboard` | ✅ |
| 2 | Ga naar `/team`, klik **Bewerken** bij Fatima zelf | Edit-form opent | ✅ |
| 3 | Wijzig rol naar Zorgbegeleider, submit | Blijft op `/team/{id}/edit` met error-banner "Je kunt je eigen teamleider-rol niet verwijderen zolang je de enige teamleider van je team bent." | ✅ |
| 4 | Rol in DB ongewijzigd (nog teamleider) | Ja | ✅ |
| 5 | `UserAuditLog` count voor Fatima = 0 | Ja | ✅ |

**Pest-dekking:** `it('prevents self-demotion when lone teamleider')` — **PASS**

### TC-03 — Self-demotion toegestaan met 2+ teamleiders (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Tinker: maak tweede teamleider aan: `User::factory()->teamleider()->create(['team_id'=>1, 'name'=>'Bob', 'email'=>'bob@team.test'])` | 2 teamleiders in team | ✅ |
| 2 | Login als Fatima, bewerk eigen rol → Zorgbegeleider | Lukt, redirect `/team` + flash | ✅ |
| 3 | Fatima is nu zorgbegeleider | Ja | ✅ |

**Pest-dekking:** `it('allows self-demotion when another active teamleider exists in the team')` — **PASS**

### TC-04 — Email al in gebruik (AC-3)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als teamleider | Op dashboard | ✅ |
| 2 | Bewerk Jeroen, zet email op `teamleider@nexora.test` (Fatima's email) | Form her-rendert met error "Er bestaat al een medewerker met dit e-mailadres." | ✅ |
| 3 | Jeroen's email ongewijzigd in DB | Ja | ✅ |

**Pest-dekking:** `it('rejects email already in use by another user')` — **PASS**

### TC-05 — Eigen huidige email behouden (AC-4)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Bewerk Jeroen, laat email op `zorgbegeleider@nexora.test` staan (ongewijzigd) | Lukt, redirect `/team` + flash | ✅ unique-rule ignored eigen id |
| 2 | Andere velden wel wijzigen (bijv. dienstverband → zzp) | Ja, audit-rij `field=dienstverband` | ✅ |

**Pest-dekking:** `it('accepts own email unchanged (unique rule ignores own id)')` — **PASS**

### TC-06 — Zorgbegeleider probeert toegang (AC-5)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als `zorgbegeleider@nexora.test` | Op zorgbeg-dashboard | ✅ |
| 2 | Plak in URL: `/team/{teamleider-id}/edit` | 403-pagina "Geen toegang" | ✅ UserPolicy@update weigert |
| 3 | Plak in URL: `/team/{eigen-id}/edit` | 403-pagina | ✅ |
| 4 | cURL PUT `/team/{id}` met zorgbegeleider-sessie | 403 | ✅ Geen mutatie in DB |

**Pest-dekking:** `it('denies zorgbegeleider access to edit form (403)')` + `it('denies zorgbegeleider PUT /team/{id} (403)')` — **PASS**

### TC-07 — Guest redirect

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Incognito: ga naar `/team/{id}/edit` | Redirect `/login` | ✅ |

**Pest-dekking:** `it('redirects guest from edit route to /login')` — **PASS**

## 4. Resultaten van de testen

### Geautomatiseerde Pest tests

```text
$ ./vendor/bin/pest tests/Feature/Team/UpdateTeamMemberTest.php

   PASS  Tests\Feature\Team\UpdateTeamMemberTest
  ✓ it updates name and writes an audit log row
  ✓ it logs each changed field separately when multiple fields change
  ✓ it writes no audit rows when nothing changes
  ✓ it prevents self-demotion when lone teamleider
  ✓ it allows self-demotion when another active teamleider exists in the team
  ✓ it only counts active teamleiders for the self-demotion guard
  ✓ it allows demoting another teamleider (not self) regardless of count
  ✓ it rejects email already in use by another user
  ✓ it accepts own email unchanged (unique rule ignores own id)
  ✓ it denies zorgbegeleider access to edit form (403)
  ✓ it denies zorgbegeleider PUT /team/{id} (403)
  ✓ it denies teamleider from editing users in another team
  ✓ it redirects guest from edit route to /login
  ✓ it rejects empty required fields on update
  ✓ it rejects role outside whitelist on update (privilege escalation)
  ✓ it shows the edit form with prefilled values

  Tests:    16 passed (54 assertions)
  Duration: 0.60s
```

**Samenvatting:** **16 / 16 US-05 tests groen**, 54 asserts, < 0,6s.

### Totaal projecttests

```text
Tests:    88 passed (261 assertions)
Duration: 1.41s
```

Verdeling per US:

| US | Tests | Asserts |
|---|---|---|
| US-01 Inloggen | 10 | 37 |
| US-02 Rolgebaseerde toegang | 26 | 54 |
| US-03 Medewerker aanmaken | 15 | 53 |
| US-04 Medewerkers overzicht | 19 | 61 |
| US-05 Teamlid bewerken | 16 | 54 |
| Voorbeelden | 2 | 2 |
| **Totaal** | **88** | **261** |

### Handmatige browser-tests

| TC | Resultaat |
|---|---|
| TC-01 Voornaam wijzigen + audit | ✅ PASS |
| TC-02 Self-demotion guard (lone) | ✅ PASS |
| TC-03 Self-demotion toegestaan (2+) | ✅ PASS |
| TC-04 Email duplicaat | ✅ PASS |
| TC-05 Eigen email behouden | ✅ PASS |
| TC-06 Zorgbeg 403 | ✅ PASS |
| TC-07 Guest redirect | ✅ PASS |

### Dekkingsmatrix

| Acceptatiecriterium | Pest | Handmatig | Status |
|---|---|---|---|
| AC-1: voornaam wijzigen → name bijgewerkt + audit-log | ✓ (3 tests) | TC-01 | ✅ |
| AC-2: eigen teamleider-rol verwijderen → fout + geen mutatie | ✓ (4 tests) | TC-02/03 | ✅ |
| AC-3: email duplicaat → "al in gebruik" | ✓ | TC-04 | ✅ |
| AC-4: email naar eigen huidige email → lukt | ✓ | TC-05 | ✅ |
| AC-5: zorgbegeleider /team/{id}/edit → 403 | ✓ (3 tests) | TC-06 | ✅ |
| Privacy: audit-trail (AVG art. 30) | ✓ (3 tests) | TC-01 | ✅ |
| Privacy: self-demotion = systeemintegriteit | ✓ | TC-02 | ✅ |
| Privacy: geen wachtwoord in edit-form | ✓ (form-render test) | TC-01 | ✅ |

## 5. Conclusies

### Functioneel

1. **Teamleider kan alle relevante velden van een teamlid bewerken** (name via voornaam+achternaam split, email, rol, dienstverband).
2. **Wachtwoord ontbreekt bewust** — dat blijft onder US-16 /profiel (separation of concerns).
3. **Pre-fill werkt correct** via `explode(' ', $member->name, 2)` — ook bij samengestelde achternamen ("Jan van der Berg" → voornaam="Jan", achternaam="van der Berg").
4. **Flash success + redirect** `/team` met "Medewerker bijgewerkt." melding.
5. **Teamleider kan eigen rol ook bewerken**, mits niet de enige actieve teamleider van zijn team.

### Privacy & security

6. **AVG art. 30 audit-trail** — elke veldwijziging wordt vastgelegd in `user_audit_logs` met `changed_by_user_id`, `field`, `old_value`, `new_value`, `created_at`. Immutable: geen `updated_at`, geen update-ops.
7. **Self-demotion guard** voorkomt dat een team zonder actieve teamleider komt te staan. Telt alleen actieve teamleiders (inactieve telt niet). Guard werkt alleen op SELF-edit — andere teamleiders demoten mag wel.
8. **Mass-assignment protection** — `UpdateTeamMemberRequest::validatedPayload()` whitelist voorkomt dat hidden inputs zoals `team_id`, `is_active`, `created_at` via form escalatie plegen.
9. **Unique-except-self** via `Rule::unique('users','email')->ignore($target->id)` — eigen email behouden mag, andere user zijn email niet stelen.
10. **Role whitelist** via `Rule::in([...])` blokkeert `admin` of andere niet-bestaande rollen.
11. **Cross-team isolatie** via `UserPolicy@update` (team-scope check uit US-03) — teamleider Rotterdam kan geen Amsterdam-user bewerken.
12. **Defense in depth**: middleware (`teamleider`) + policy (`update`) + service (`ensureTeamRetainsTeamleider`) — 3 lagen.

### Code kwaliteit

13. **16 Pest tests · 54 asserts · 0,6s** — snel en uitgebreid.
14. **Laravel Pint** clean (PSR-12).
15. **Service-based**: `UserService::updateWithAudit` is de single source of truth — audit-logging is een systeemgarantie, niet controller-afhankelijk.
16. **Transactioneel** via `DB::transaction` — audit-rij én user-update zijn atomisch, bij rollback geen vuile state.
17. **Geen code-duplicatie**: edit.blade.php volgt create.blade.php patroon, UpdateTeamMemberRequest volgt StoreTeamMemberRequest.
18. **Dependency injection** van `UserService` via controller-method signature (PSR-11).

### Openstaand

- **Wachtwoord-wissel** — komt in US-16 `/profiel` (zelfde medewerker wijzigt zelf wachtwoord).
- **Audit-viewer UI** — momenteel alleen via `User::auditLogs` in tinker. Een "audit-log-tab" op de edit-pagina kan in een vervolgsprint als nice-to-have.
- **Audit-retentie policy** — wettelijk moeten audit-logs 5-7 jaar bewaard. Nu geen auto-cleanup — komt wanneer project richting productie gaat.

### Eindoordeel

✅ **US-05 kan als "Done" gemarkeerd worden op Trello.** Alle 5 acceptatiecriteria + alle 3 Privacy/Security bullets zijn gerealiseerd en getest. 88 Pest tests (261 asserts) over 5 user stories passen allemaal.
