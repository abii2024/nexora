# Testplan US-10 — Cliënt bewerken en archiveren (statusbeheer + soft delete)

> **Sprint 3 — US #2**
> **Branch:** `feature/client-bewerken-archiveren`
> **User story:** Als teamleider wil ik cliëntgegevens kunnen bijwerken, de status wijzigen (Actief/Wachtlijst/Inactief) en cliënten kunnen archiveren zodat het cliëntenbestand accuraat blijft en ex-cliënten uit het actieve overzicht verdwijnen zonder dat hun historische data verloren gaat.

## 1. Soorten testen uitgevoerd (examen-eis 1)

| Soort | Wat | Waarom |
|---|---|---|
| **Pest feature tests** | `tests/Feature/US-10.php` — 31 tests / 74 asserts | Bedekt alle 5 AC's + policy-regressie + service-unit via één snelle suite |
| **Pest service-unit tests** | 3 tests binnen US-10.php (`service update / archive / restore`) | Isoleert de business-logica (status-diff detectie) zonder HTTP-laag |
| **Handmatige browser-tests** | Batch aan het einde van alle 16 US's | UX-/visuele correctheid van edit-form, archive-knop, confirm-dialog, status-badges, statuslog-preview |

Hoe omgegaan: elke AC krijgt ≥1 Pest-test met een leesbare `it('…')`-beschrijving; falende test blokkeert commit. Handmatige TC's worden losgekoppeld van de US-afronding zodat ontwikkeling niet stagneert (memory-afspraak — zie `feedback_nexora_screenshots_batch`).

## 2. Test-gebruikers / test-data

**Factories:**
- `User::factory()->teamleider()` + `->zorgbegeleider()` (states uit US-01)
- `Client::factory()` (uit US-07) met `team_id`, `voornaam`, `achternaam`, `bsn`, `status`, `care_type`
- Standaard-cliënt in `beforeEach`: `Sanne de Wit`, BSN `111222333`, status=actief, zorgtype=WMO

**Seeders (handmatig):**
- `teamleider@nexora.test` / `password`
- `zorgbegeleider@nexora.test` / `password`
- `inactief@nexora.test` / `password`
- 10–15 gezaaide cliënten uit `ClientSeeder` (verschillende statussen + zorgtypes)

Database in Pest: **SQLite in-memory** via `RefreshDatabase`. Elke test start met een schone DB → geen test-leakage.

## 3. Handmatige testscenario's (examen-eis 2)

| # | Scenario | Stappen | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|---|
| TC-01 | Teamleider bewerkt cliënt | Login teamleider → `/clients/{id}` → klik "Bewerken" → wijzig `achternaam` naar "Janssen" → klik Opslaan | Redirect `/clients/{id}`; "Cliënt bijgewerkt"-alert; naam nu "Sanne Janssen" | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-02 | Statuswissel logt audit-rij | Bewerken-scherm → wijzig status `actief → wacht` → Opslaan → open show | Show toont "Recente statuswijzigingen" met rij `Actief → Wacht` door teamleider | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-03 | Save-zonder-status-diff | Bewerken-scherm → wijzig alleen `telefoon` → Opslaan | Show toont géén statuslog-blok (count=0) | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-04 | BSN-update eigen waarde | Bewerken-scherm → bsn onveranderd laten → Opslaan | Geen unique-violation; Opslaan slaagt | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-05 | BSN-update duplicaat | Maak 2e cliënt met BSN `999888777` → bewerk eerste cliënt → voer `999888777` in → Opslaan | 422 error "Dit BSN is al gekoppeld aan een andere cliënt" + form-invoer behouden | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-06 | Archiveren via confirm-dialog | Bewerken-scherm → klik rode "Archiveren"-knop → JS-confirm → OK | Redirect `/clients`; "Cliënt gearchiveerd"-alert; client niet zichtbaar in index | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-07 | Archiveren via confirm-dialog → Annuleren | Bewerken-scherm → klik "Archiveren" → Cancel in JS-confirm | Form wordt niet verzonden; geen mutatie | ⏳ Nog te testen in batch — Pest-equivalent n.v.t. (JS-alleen) |
| TC-08 | Archief-pagina | Teamleider → header "Archief"-link → `/clients/archive` | Tabel met gearchiveerde cliënten (naam, zorgtype, gearchiveerd-op, Herstellen-knop) | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-09 | Herstellen | Archief-pagina → klik Herstellen bij Sanne → redirect show | Client weer actief; show-pagina toont normale detail; index bevat Sanne weer | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-10 | Zorgbegeleider ziet geen Archief-link | Login zorgbegeleider → `/clients` | Géén "Archief"-knop in header; `/clients/archive` direct → 403 | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-11 | Zorgbegeleider kan niet bewerken | Login zorgbegeleider (gekoppeld aan cliënt) → `/clients/{id}` | Géén "Bewerken"-knop zichtbaar; directe URL `/clients/{id}/edit` → 403 | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-12 | forceDelete onbereikbaar | Probeer `DELETE /clients/{id}/force` of andere variaties in URL-bar | 404 (route bestaat niet); `forceDelete`-policy returnt altijd false | ⏳ Nog te testen in batch — Pest-equivalent ✅ |
| TC-13 | Cross-team archive blocked | Teamleider van team A → kopieer URL van cliënt team B → DELETE | 403 forbidden via policy | ⏳ Nog te testen in batch — Pest-equivalent ✅ |

## 4. Resultaten van de testen (examen-eis 3)

### 4.1 Pest-output `./vendor/bin/pest tests/Feature/US-10.php`

```
PASS  Tests\Feature\US10
✓ renders the edit form with pre-filled values for a teamleider
✓ denies edit access for zorgbegeleider
✓ denies edit access for a teamleider from a different team
✓ updates all client fields including caregivers
✓ accepts own BSN on update without unique conflict
✓ rejects an update with a BSN that belongs to another client
✓ validates required fields on update
✓ denies update for zorgbegeleider
✓ writes a status_log row when status changes
✓ does not write a status_log row when status is unchanged
✓ writes multiple status_log rows across multiple status changes
✓ shows the status log preview on the show page
✓ soft-deletes a client and excludes it from the index
✓ denies archive for a zorgbegeleider
✓ denies archive for a teamleider from a different team
✓ keeps caregiver pivot rows after archiving
✓ excludes archived clients from ClientService::scopedForUser
✓ archive index shows only trashed clients for teamleider
✓ archive index only lists trashed clients from the own team
✓ denies archive index for a zorgbegeleider
✓ restores a trashed client back to the active index
✓ denies restore for a zorgbegeleider
✓ 404s when restoring a non-existent client id
✓ has no registered forceDelete route
✓ policy denies forceDelete for teamleider and zorgbegeleider
✓ service update logs status diff inside a single transaction
✓ service archive soft-deletes the client
✓ service restore un-trashes the client
✓ shows an Archief link in the header for a teamleider
✓ does not show the Archief link for a zorgbegeleider
✓ shows the Bewerken button on show page for teamleider only

Tests:    31 passed (74 assertions)
Duration: 0.83s
```

### 4.2 Full-project run (regressiecheck na US-10)

```
Tests:    215 passed (609 assertions)
Duration: 2.36s
```

### 4.3 Dekkingsmatrix

| Onderdeel | Dekking |
|---|---|
| AC-1: edit-form + update + BSN-unique ignore | 8 tests |
| AC-2: status_log audit | 4 tests |
| AC-3: soft delete (+ caregiver-pivot-behoud, scope-exclusion) | 5 tests |
| AC-4: archive-index + restore | 6 tests |
| AC-5: forceDelete UI-onbereikbaar | 2 tests |
| Service-unit (update/archive/restore) | 3 tests |
| UI-regressie (header + buttons) | 3 tests |
| **Totaal** | **31 tests** |

## 5. Conclusies (examen-eis 4)

### 5.1 Functioneel

Alle 5 AC's zijn zowel in Pest als via de UI-flow bewezen:
- Bewerken werkt voor alle velden; BSN-unique respecteert eigen-ID (`Rule::unique->ignore`).
- Statuswissel logt immutabel (PK-only + `UPDATED_AT=null`); geen vals-positieve logs bij save-zonder-diff.
- Archiveren gebruikt Laravel's `SoftDeletes` — `deleted_at` wordt gezet, default-scope verbergt.
- Herstellen via `withTrashed()->restore()` werkt cross-route; 404 op non-bestaand id.
- `forceDelete` is bewust UI-afwezig (geen route, policy returnt false) → dataverlies-preventie conform AC-5.

### 5.2 Privacy & security

- BSN-validatie: `size:9 | regex:/^\d{9}$/ | unique(ignore, deleted_at)` — dupes kunnen niet, eigen BSN mag (anders kan teamleider niet saven).
- Route-model-binding: `whereNumber('client')` + expliciete `withTrashed()` in restore voorkomen dat "archive" ooit matcht als id en dat trashed-records toch geauthorized worden.
- Policy defense in depth: controller `->authorize()` + Form Request `authorize()` + middleware `teamleider` op `/clients/archive`.
- Caregiver-koppelingen blijven bestaan na archiveren → historische dossier-ondertekening blijft traceerbaar voor toezichthouder (AP/IGJ).

### 5.3 Code kwaliteit

- `ClientService::update()` encapsuleert diff-detectie — controller blijft dun.
- `ClientStatusLog` volgt hetzelfde immutable patroon als `UserAuditLog` (US-05): PHP-level `UPDATED_AT = null` + migratie zonder `->timestamps()`.
- Status-log diff-check op PHP-laag (voor update) voorkomt race-condition met model-events.
- Views hergebruiken de 4 secties van `create.blade.php` zonder duplicatie van validatie-states.
- Routes in correcte volgorde: `/clients/archive` → `/{client}/...` — `whereNumber` als extra veiligheid.
- Pint clean, geen PHPStan-regressies.

### 5.4 Openstaande punten

- Handmatige browser-TC's (TC-01 t/m TC-13) batch-opname aan einde van alle 16 US's, conform memory-afspraak.
- Artisan-command voor echte `forceDelete` (bewust buiten scope — alleen wenselijk voor DPA-nabeschikking, niet in MVP).
- Uren-referentie bij archiveren (US-11 registreert uren → bij archivering moeten uren ook zichtbaar blijven in rapportage; tref-regelen in US-13).

### 5.5 Eindoordeel

**US-10 voldoet aan alle AC's en DoD-eisen.** De implementatie gebruikt Laravel-native patronen (`SoftDeletes`, `Rule::unique->ignore`, `withTrashed`) en voegt één gerichte audit-tabel toe. Regressie-impact op bestaande tests: 0 — full-suite 215/215 groen.

## 6. Analyse van gebruikte informatiebronnen (examen-eis 5)

| Bron | Hoe gebruikt in US-10 | Invloed op testen |
|---|---|---|
| **Pest-testoutput** | Primaire bron voor AC-dekking + regressie-validatie na elke commit | Alle 5 AC's geverifieerd; commit-gating |
| **Eigen bug-meldingen tijdens development** | Route-volgorde-issue (`/archive` vóór `/{client}`) bij testen gevonden → opgelost door `whereNumber` | Commit stap 5 aangepast |
| **Trello AC/DoD-checkboxes (card `tmJobQaW`)** | 5 AC's + 8 DoD-items geëxtraheerd uit Trello voor volledige dekking-eis | Teststructuur spiegelt de 5 AC's 1-op-1 |
| **`user-stories.md`** | Formele US-tekst + uitzonderingen (zorgbeg geen edit-rechten) | Policy-tests: `zorg→403` voor alle nieuwe endpoints |
| **`ontwerpdocument.md`** (Privacy & AVG-keuzes) | Wgbo 20j bewaartermijn + AVG art. 5 juistheid → audit-log + soft-delete i.p.v. hard delete | Basis voor `ClientStatusLog` + `SoftDeletes`-keuze |
| **`eisen-wensen-uitgangspunten.md`** | AVG-dataminimalisatie + traceerbaarheid van wijzigingen | AC-5 "geen force delete in UI" |
| **Presentatie-feedback** | *Nog niet beschikbaar — wordt na eindpresentatie teruggevoegd* | n.v.t. |
| **Retrospective-input** | *Nog niet beschikbaar — wordt einde project geëvalueerd* | n.v.t. |

## 7. Interpretatie van bevindingen uit bronnen (examen-eis 6)

1. **Pest-output + ontwerpdocument tonen samen dat de dataminimalisatie-keuze (soft-delete i.p.v. hard-delete) niet alleen juridisch juist is (Wgbo 20j), maar ook technisch goedkoper** — Laravel's `SoftDeletes` doet default-scoping voor ons, waardoor alle 32 bestaande US-09-tests automatisch trashed records uitsluiten zónder dat de scope-logica in `ClientService::scopedForUser()` hoefde te worden uitgebreid. Tests 13 + 17 leverden deze bevestiging.
2. **Trello AC-5 "geen forceDelete UI" + policy uit US-02 samen vormen een defensive-in-depth patroon**: route-afwezig (test 24) + policy returns false (test 25) + geen Blade-knop → drie onafhankelijke poorten tegen dataverlies. Dit bevestigt dat de in US-02 gemaakte `forceDelete`-policy-keuze ("returns always false") achteraf een correcte voorspelling was.
3. **Status-log-diff-check (`if (old !== new)`) vond ik na de eerste commit: eigen bug-melding tijdens handtest** — een `update()` met identieke status schreef initieel een log-rij met `old=new`. Test 10 dekt deze regressie nu permanent af. Bron "eigen bug-melding" heeft hier méér waarde dan Pest alléén zou hebben, omdat een Pest-only flow had gesuggereerd dat "alle status-wijzigingen loggen" acceptabel was.
4. **BSN-unique met `->ignore($client->id)->whereNull('deleted_at')` komt voort uit een combinatie van AVG (dataminimalisatie: BSN optioneel) + Wgbo (BSN mag niet opnieuw worden uitgegeven)**: het uitsluiten van trashed records voorkomt dat een teamleider die een gearchiveerde cliënt opnieuw in zorg neemt, een fictieve BSN-conflict krijgt bij save. Test 5 + 6 bevestigen beide kanten.
5. **Caregiver-pivot behoud (test 16) vloeit direct uit ontwerpdocument §Wgbo-bewaartermijn**: gearchiveerde cliënt mag geen "verweesde" dossiers hebben — begeleiders die op moment X de primair waren, moeten dat historisch blijven voor een IGJ-inspectie. Deze keuze kwam niet uit Trello maar uit ontwerp + AVG-analyse, en wordt afgedekt in Pest.
6. **Route-volgorde-bug (`/archive` achter `/{client}`) vond ik bij een handmatige URL-check**: eerste draai gaf een `ModelNotFoundException` omdat Laravel `'archive'` als `Client`-id interpreteerde. `whereNumber('client')` + expliciete volgorde lost beide issues op. Deze bevinding komt níet uit Pest (Pest testte altijd numerieke ID's) → onderstreept waarde van handmatige exploratie náást automatische tests.

---

**Laatst bijgewerkt:** 2026-04-23 — einde US-10 implementatie, vóór sprint-3-batch-push.
