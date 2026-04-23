# Testplan — US-06 Teamlid deactiveren en heractiveren

> **User story:** Als teamleider wil ik medewerkers kunnen deactiveren bij uitdiensttreding zodat ze niet meer kunnen inloggen of cliëntdata kunnen benaderen, maar hun historische gegevens behouden blijven voor audit en bewaartermijnen.
>
> **Branch:** `feature/teamlid-deactiveren`
> **Feature test:** [`tests/Feature/US-06.php`](../../tests/Feature/US-06.php)
> **Algemeen testplan:** [README.md](./README.md)

## 1. Soorten testen uitgevoerd

| Soort | Tool | Locatie | Aantal |
|---|---|---|---|
| Feature test (geautomatiseerd) | Pest v4 | `tests/Feature/US-06.php` | 19 tests · 62 asserts |
| Handmatige browser-test | Chrome / Safari | `http://nexora.test/team/{id}/edit` | 8 TC |

## 2. Test-gebruikers

| Naam | E-mail | Rol | Actief |
|---|---|---|---|
| Fatima El Amrani | `teamleider@nexora.test` | teamleider | ✓ |
| Jeroen Bakker | `zorgbegeleider@nexora.test` | zorgbegeleider | ✓ |
| Mo Yilmaz | `mo@nexora.test` | zorgbegeleider | ✓ |
| Ilse Voskuil | `inactief@nexora.test` | zorgbegeleider | ✗ |

Setup: `php artisan migrate:fresh --seed`

## 3. Handmatige testscenario's

### TC-01 — Deactiveren met confirmatie (AC-1)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als `teamleider@nexora.test` | Op dashboard | ✅ |
| 2 | Ga naar `/team`, klik **Bewerken** bij Jeroen | Edit-form opent | ✅ |
| 3 | Scroll naar **Accountstatus**-card onderaan | Rode **Deactiveren**-knop zichtbaar met Wgbo-uitleg | ✅ |
| 4 | Klik Deactiveren | Browser-confirm: "Weet je zeker dat je Jeroen Bakker wilt deactiveren?" | ✅ native confirm dialog |
| 5 | Bevestigen | Redirect `/team` + flash "Jeroen Bakker is gedeactiveerd." | ✅ |
| 6 | Jeroen in tabel | Rij opacity 0.55, badge **Inactief** (neutraal grijs) | ✅ |
| 7 | Filter status → Inactief | Jeroen zichtbaar in lijst | ✅ |
| 8 | Audit-log via tinker: `User::find(jeroen)->auditLogs` | Rij `field=is_active`, `old_value=1`, `new_value=0` | ✅ |

**Pest-dekking:** `deactivates an active member and writes audit log` + `shows inactive user with grey badge` — **PASS**

### TC-02 — CheckActiveUser middleware (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Open 2 browsers: Chrome ingelogd als Jeroen, Safari ingelogd als teamleider Fatima | Beide op hun dashboard | ✅ |
| 2 | In Safari: deactiveer Jeroen via /team/{id}/edit | Redirect /team met flash | ✅ |
| 3 | In Chrome (Jeroen): navigeer naar elke auth route (refresh /dashboard) | Redirect `/login` met rode melding "Dit account is gedeactiveerd. Neem contact op met je teamleider." | ✅ CheckActiveUser middleware vangt op |
| 4 | Jeroen probeert opnieuw inloggen met `zorgbegeleider@nexora.test` / `password` | Blijft op /login met dezelfde melding | ✅ LoginController detecteert is_active=false (US-01) |

**Pest-dekking:** `logs out a user on next request once they are deactivated` + `shows deactivation error on /login after redirect` — **PASS**

### TC-03 — Heractiveren met bestaand wachtwoord (AC-3)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als teamleider, ga naar `/team`, filter status → Inactief | Jeroen zichtbaar met opacity | ✅ |
| 2 | Klik Bewerken bij Jeroen | Edit-form opent | ✅ |
| 3 | Zie groene **Heractiveren**-knop + uitleg | Ja | ✅ |
| 4 | Klik Heractiveren → confirm | "Jeroen Bakker weer activeren?" | ✅ |
| 5 | Bevestig | Redirect /team + flash "Jeroen Bakker is heractiveerd." | ✅ |
| 6 | Log uit, log in als `zorgbegeleider@nexora.test` / `password` | Lukt direct — geen wachtwoord-reset nodig | ✅ |
| 7 | Audit-log | Nieuwe rij `field=is_active`, `old=0`, `new=1` | ✅ |

**Pest-dekking:** `activates a deactivated member and writes audit log` + `lets a reactivated user log in again with existing password` — **PASS**

### TC-04 — Historische data behouden (AC-4 aanpak b)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Setup: koppel Jeroen aan cliënt C1 als primair (US-08 functionaliteit beschikbaar via tinker) | `client_caregivers` rij aanwezig | ✅ |
| 2 | Maak paar audit-rijen voor Jeroen (bewerk hem) | `user_audit_logs` heeft rijen | ✅ |
| 3 | Deactiveer Jeroen | `is_active=false` | ✅ |
| 4 | Check relaties: `Jeroen->clients` en `Jeroen->auditLogs` | Alle historische rijen nog aanwezig — geen cascade-delete | ✅ |
| 5 | **Openstaand voor US-11:** uren-entries van Jeroen blijven ook intact — komt bij test van urenregistratie | — | ⚠️ US-11 |

**Pest-dekking:** `preserves user_audit_logs on deactivation` + `preserves client_caregivers assignments on deactivation (no cascade)` — **PASS**

### TC-05 — Zorgbegeleider probeert deactivate (AC-5)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als `mo@nexora.test` (zorgbeg) | Op dashboard | ✅ |
| 2 | cURL POST naar `/team/{jeroen-id}/deactivate` met sessiecookie | 403 + geen DB-mutatie | ✅ UserPolicy@delete weigert + EnsureTeamleider middleware ervoor |

**Pest-dekking:** `denies zorgbegeleider POST deactivate (403)` — **PASS**

### TC-06 — Self-deactivation geblokkeerd

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als teamleider Fatima | Op dashboard | ✅ |
| 2 | Bewerk jezelf (klik Bewerken bij eigen naam in `/team`) | Edit-form opent | ✅ |
| 3 | Accountstatus-card zichtbaar? | **NEE** — geen deactivate/activate knop voor self (UI-guard) | ✅ |
| 4 | Force via cURL POST /team/{self-id}/deactivate | 403 via UserPolicy@delete | ✅ defense in depth |

**Pest-dekking:** `prevents teamleider from deactivating themselves` + `shows Deactiveren button on edit form for active user (and not for self)` — **PASS**

### TC-07 — Laatste teamleider blokkade

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Rotterdam heeft alleen Fatima als teamleider | Ja (seed) | ✅ |
| 2 | Tinker: `App\Services\UserService->deactivate(fatima, fatima)` | `ValidationException`: "Je kunt de enige actieve teamleider van het team niet deactiveren" | ✅ |
| 3 | Via UI kan dit niet gebeuren (self-deactivation UI-guard) maar guard is defense-in-depth | — | ✅ |

**Pest-dekking:** `blocks deactivating the last active teamleider via UserService guard` — **PASS**

### TC-08 — Idempotentie

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Deactiveer Jeroen (hij wordt inactief) | Flash "gedeactiveerd" | ✅ |
| 2 | Klik nogmaals `/team/{id}/deactivate` (niet mogelijk via UI, maar via direct POST) | Geen fout + geen dubbele audit-rij | ✅ UserService service is idempotent |

**Pest-dekking:** `is idempotent when deactivating an already inactive user` + `is idempotent when activating an already active user` — **PASS**

## 4. Resultaten van de testen

### Geautomatiseerde Pest tests

```text
$ ./vendor/bin/pest tests/Feature/US-06.php

   PASS  Tests\Feature\Team\DeactivateTeamMemberTest
  ✓ it deactivates an active member and writes audit log
  ✓ it shows inactive user with grey badge in team index (US-04 regressie)
  ✓ it logs out a user on next request once they are deactivated
  ✓ it shows deactivation error on /login after redirect
  ✓ it activates a deactivated member and writes audit log
  ✓ it lets a reactivated user log in again with existing password
  ✓ it preserves user_audit_logs on deactivation (Wgbo bewaarplicht)
  ✓ it preserves client_caregivers assignments on deactivation (no cascade)
  ✓ it denies zorgbegeleider POST deactivate (403)
  ✓ it denies zorgbegeleider POST activate (403)
  ✓ it redirects guest from deactivate route to /login
  ✓ it prevents teamleider from deactivating themselves
  ✓ it prevents deactivating the only active teamleider of a team
  ✓ it prevents deactivating a teamleider when they are the last active one in their team
  ✓ it blocks deactivating the last active teamleider via UserService guard
  ✓ it is idempotent when deactivating an already inactive user
  ✓ it is idempotent when activating an already active user
  ✓ it shows Deactiveren button on edit form for active user (and not for self)
  ✓ it shows Heractiveren button on edit form for inactive user

  Tests:    19 passed (62 assertions)
  Duration: 0.63s
```

**Samenvatting:** **19 / 19 US-06 tests groen**, 62 asserts, < 0,7s.

### Totaal projecttests

```text
Tests:    107 passed (323 assertions)
Duration: 1.49s
```

Verdeling per US:

| US | Tests | Asserts |
|---|---|---|
| US-01 Inloggen | 10 | 37 |
| US-02 Rolgebaseerde toegang | 26 | 54 |
| US-03 Medewerker aanmaken | 15 | 53 |
| US-04 Medewerkers overzicht | 19 | 61 |
| US-05 Teamlid bewerken | 16 | 54 |
| US-06 Teamlid deactiveren | 19 | 62 |
| Voorbeelden | 2 | 2 |
| **Totaal** | **107** | **323** |

### Handmatige browser-tests

| TC | Resultaat |
|---|---|
| TC-01 Deactiveren + confirm + audit | ✅ PASS |
| TC-02 CheckActiveUser middleware | ✅ PASS |
| TC-03 Heractiveren + wachtwoord behouden | ✅ PASS |
| TC-04 Historische data behouden | ✅ PASS (uren volgen in US-11) |
| TC-05 Zorgbegeleider 403 | ✅ PASS |
| TC-06 Self-deactivation geblokkeerd | ✅ PASS |
| TC-07 Laatste teamleider blokkade | ✅ PASS |
| TC-08 Idempotentie | ✅ PASS |

### Dekkingsmatrix

| Acceptatiecriterium | Pest | Handmatig | Status |
|---|---|---|---|
| AC-1: Deactiveren + confirm + inactief-badge | ✓ (2 tests) | TC-01 | ✅ |
| AC-2: Gedeactiveerde zorgbeg → volgende request → /login | ✓ (2 tests) | TC-02 | ✅ |
| AC-3: Heractiveren + direct inloggen bestaand wachtwoord | ✓ (2 tests) | TC-03 | ✅ |
| AC-4: Historische data blijft (client_caregivers + audit_logs) | ✓ (2 tests) | TC-04 | ✅ (uren in US-11) |
| AC-5: Zorgbegeleider POST /deactivate → 403 | ✓ (2 tests) | TC-05 | ✅ |
| Privacy: Wgbo 20-jaar (geen hard delete) | ✓ | TC-04 | ✅ |
| Privacy: Directe sessie-invalidatie | ✓ (CheckActiveUser) | TC-02 | ✅ |
| Privacy: Heractiveren behoudt wachtwoord | ✓ | TC-03 | ✅ |

## 5. Conclusies

### Functioneel

1. **Deactiveren/heractiveren werkt via edit-pagina** — accountstatus-card onder het bewerk-formulier toont rol-specifieke knop (Deactiveren voor actief, Heractiveren voor inactief).
2. **Native browser-confirm** voorkomt accidentele klikken (AC-1 "met confirmatiedialog").
3. **Geen hard delete** — user blijft in DB met relaties naar audit-logs, client_caregivers (en later uren-entries van US-11) intact.
4. **Heractiveren zonder wachtwoord-reset** — gebruiker kan direct inloggen met bestaande credentials.
5. **Self-guard** — teamleider ziet geen deactivate-knop op eigen edit-pagina, bovendien weigert `UserPolicy@delete` zelf-deactivatie met 403.
6. **Laatste teamleider guard** — `UserService::deactivate` gooit `ValidationException` als een team zonder actieve teamleider zou komen.
7. **Idempotent** — al-inactief/al-actief is no-op (geen dubbele audit-rijen).

### Privacy & security

8. **AVG art. 30 audit-trail** — elke is_active wijziging (beide richtingen) logt een `user_audit_logs` rij.
9. **Wgbo 20-jaar bewaarplicht** — historische data blijft gekoppeld: Pest bewijst dat `user_audit_logs` en `client_caregivers` intact blijven na deactivatie.
10. **Directe sessie-invalidatie** op 2 lagen:
    - `UserService::invalidateSessionsFor()` verwijdert DB-session-rijen (als driver=database)
    - `CheckActiveUser` middleware blokkeert bij volgende request
    - Resultaat: gedeactiveerde user kan niet eens **tussen requests** cliëntdata benaderen.
11. **Defense in depth** — vier lagen:
    - UI self-guard (geen knop op eigen page)
    - UserPolicy@delete (team-scope + self-block)
    - UserService guards (self + last-teamleider)
    - CheckActiveUser middleware (runtime enforcement)

### Code kwaliteit

12. **19 Pest tests · 62 asserts · 0,63s** — uitgebreide dekking inclusief regressie op US-02/US-04/US-05.
13. **Pint clean** (PSR-12).
14. **Service-based** — `UserService::deactivate` en `activate` zijn eenvoudig, transactioneel, idempotent.
15. **Middleware global** in `web`-groep — geen opt-in per route, werkt overal.
16. **Geen code-duplicatie** — audit-logging hergebruikt dezelfde `UserAuditLog::create` patroon uit US-05.

### Openstaand

- **AC-4 uren-entries** — volledig bewijs dat urenregistratie-rijen intact blijven komt bij US-11 wanneer de tabel bestaat. Nu al expliciet getest voor de wél-bestaande gekoppelde tabellen (`client_caregivers`, `user_audit_logs`).
- **UI: gedeactiveerde user ziet eigen inactief-bericht** — als gedeactiveerde user direct opnieuw logt-in komt hij bij de LoginController melding (US-01). Voor sessie-invalidatie-scenario komt de melding via CheckActiveUser op /login. Beide werken, maar een "Je bent zojuist uitgelogd"-specific melding zou user experience verbeteren — optioneel voor vervolgsprint.

### Eindoordeel

✅ **US-06 kan als "Done" gemarkeerd worden op Trello.** Alle 5 acceptatiecriteria + alle 3 Privacy-bullets zijn gerealiseerd en getest. **107 Pest tests (323 asserts)** blijven allemaal groen. Sprint 2 is op de helft — US-07 (cliënt aanmaken) en US-08 (caregiver koppelen) resteren.

## 6. Analyse van gebruikte informatiebronnen

| Bron | Gebruikt? | Bijdrage / bevinding |
|---|---|---|
| **Pest-testoutput** | ✅ 19 tests / 62 asserts | Bewijs voor deactivate/activate + CheckActiveUser middleware + Wgbo retentie. |
| **Eigen bug-meldingen tijdens development** | ✅ 2 punten | (1) AC-4 `uren-entries` verwijst naar US-11-tabel die nog niet bestaat — **beslissing:** retentie bewijzen via wel-bestaande tabellen (`user_audit_logs`, `client_caregivers`). (2) CheckActiveUser middleware moest global in `web`-groep, niet als route-alias, om ook na login direct effect te hebben. |
| **Trello-kaart AC + DoD** | ✅ 5/5 AC + 5/7 DoD | Screenshots + handmatig open. |
| **user-stories.md US-06** | ✅ brondocument | "Geen hard delete — user blijft in tabel" is in test `preserves user_audit_logs on deactivation` geverifieerd. |
| **Ontwerpdocument / verantwoorde-verwerking.md** | ✅ referentie | Wgbo 20-jaar bewaarplicht is hier onderbouwd. |
| **Feedback presentatie** | — | N.v.t. |
| **Retrospective** | — | N.v.t. |

## 7. Interpretatie van bevindingen uit bronnen

1. **4 defense-in-depth-lagen is meer dan spec vereist — bewust.** User-story vraagt 2 maatregelen (is_active-check + geen hard-delete). Implementatie levert 4 lagen (UI-guard + Policy + Service-guard + middleware). Tests bewijzen dat alle 4 lagen **onafhankelijk** weigeren — een aanvaller moet alle 4 omzeilen. Dit is een **proactieve verzwaring** waarvan de kosten (4 extra tests) laag zijn en voordelen (resilient tegen toekomstige bugs in 1 laag) groot.
2. **CheckActiveUser middleware vang-alles is kritisch.** Zonder deze middleware zou een al-ingelogde user die net gedeactiveerd is, pas na logout+login gestopt worden. Met de middleware **blokkeert elke request direct**. Test `logs out a user on next request once they are deactivated` dekt dit exact.
3. **Retentie-test via bestaande tabellen (i.p.v. uren) is een pragmatische maar valide proxy.** De `onDelete('cascade')` foreign keys zijn op relatie-niveau, niet op `is_active`. Test bewijst dat `is_active=false` **geen** cascade triggert. Dezelfde logica geldt dus straks voor `urenregistratie` (US-11) — als relatie-type gelijk is, faalt dit niet plotseling bij nieuwe tabel.
4. **Idempotency expliciet getest.** `is idempotent when deactivating an already inactive user` voorkomt dubbele audit-rijen bij per ongeluk herhaalde clicks. Dit is een UX-probleem dat pas merkbaar wordt in een audit-rapport — Pest vangt het vooraf.
5. **Conclusie per bron:** alle bronnen wijzen op Done. 2 developer-bugs (AC-4 scope + middleware-plaatsing) zijn productief opgelost.
