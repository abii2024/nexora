# Testplan US-11 — Concept-uren aanmaken en bewerken

> **Sprint 3 — US #3**
> **Branch:** `feature/concept-uren-aanmaken`
> **User story:** Als zorgbegeleider wil ik mijn gewerkte uren per cliënt kunnen vastleggen als concept zodat ik mijn tijd nauwkeurig kan bijhouden voordat ik deze ter goedkeuring aanbied.

## 1. Soorten testen uitgevoerd (examen-eis 1)

| Soort | Wat | Waarom |
|---|---|---|
| **Pest feature tests** | `tests/Feature/US-11.php` — 28 tests / 77 asserts | Dekt alle 5 AC's + mass-assignment-guard + cross-caseload-guard |
| **Pest service-unit** | `computeDuration`, `service create`, `UrenStatus`-enum, `policy delete` | Business-rules los van HTTP voor snelheid + isolatie |
| **Handmatige browser-tests** | Batch aan einde — zie §3 | Tab-interactie, date/time-picker, empty-states, conditional edit-knop |

## 2. Test-gebruikers / test-data

**Factories:**
- `UrenregistratieFactory` met states `concept()`, `ingediend()`, `goedgekeurd()`, `afgekeurd()`
- `UserFactory->zorgbegeleider()`, `ClientFactory`, `TeamFactory`

**Seeders (handmatig):**
- `teamleider@nexora.test` / `password`
- `zorgbegeleider@nexora.test` / `password`

**`beforeEach`-setup:** team + 2 zorgbegeleiders + 2 cliënten (1 aan $this->zorg gekoppeld, 1 aan collega).

## 3. Handmatige testscenario's (examen-eis 2)

| # | Scenario | Stappen | Verwacht | Werkelijk |
|---|---|---|---|---|
| TC-01 | Concept aanmaken | Login zorgbeg → `/uren` → "Uren toevoegen" → cliënt + datum + 09:00-12:30 → Opslaan | Redirect `/uren?status=concept` + groene flash; rij in tabel met 3,50 u | ⏳ Pest ✅ |
| TC-02 | Eindtijd voor starttijd | Uren-form → start=17:00, eind=09:00 → submit | 422 "Eindtijd moet na starttijd liggen" + invoer behouden | ⏳ Pest ✅ |
| TC-03 | Future-datum | Uren-form → datum=morgen → submit | Validatie-fout "Uren in de toekomst registreren mag niet" | ⏳ Pest ✅ |
| TC-04 | Cliënt-dropdown scope | Open dropdown als zorgbeg A met 2 gekoppelde cliënten | Exact die 2, geen collega's cliënten | ⏳ Pest ✅ |
| TC-05 | Tabs tellen correct | Maak 3 concept + 2 ingediend + 1 goedgekeurd aan | Tab-badges: 3/2/1/0 | ⏳ Pest ✅ |
| TC-06 | Bewerken concept | `/uren` → Bewerken bij concept → wijzig tijden → Opslaan | Redirect + uren herberekend; status nog steeds concept | ⏳ Pest ✅ |
| TC-07 | Bewerken ingediend geblokkeerd | Ingediend-tab → rij heeft "Read-only"-tekst, geen Bewerken-knop | Geen bewerkknop; directe URL → 403 | ⏳ Pest ✅ |
| TC-08 | Teamleider kan niet aanmaken | Login teamleider → `/uren/create` direct | 403 Forbidden | ⏳ Pest ✅ |
| TC-09 | Tab-filter query-string | Open `/uren?status=goedgekeurd` | Goedgekeurd-tab actief; alleen goedgekeurde rijen | ⏳ Pest ✅ |
| TC-10 | Empty-state concept | Nieuwe zorgbeg zonder uren opent `/uren` | "Nog geen uren in deze tab" + "Uren toevoegen"-CTA | ⏳ Pest ✅ |
| TC-11 | Mass-assignment probe | POST `/uren` met body `user_id=otherUser`, `status=goedgekeurd` | user_id = auth()->id(), status = concept (genegeerd) | ⏳ Pest ✅ |
| TC-12 | Cross-caseload probe | POST `/uren` met `client_id=collega's cliënt` | 403 + geen rij gemaakt | ⏳ Pest ✅ |
| TC-13 | Duur precision | 09:00 + 12:30 | uren=3,50 (geen 3,499...) | ⏳ Pest ✅ |

## 4. Resultaten van de testen (examen-eis 3)

### 4.1 Pest-output

```
PASS  Tests\Feature\US11 (28 tests, 77 assertions — 1.03s)
```

Alle tests groen, inclusief:
- `it('shows the uren index with 4 tabs and a toevoegen button for zorgbegeleider')`
- `it('defaults to the concept tab when no status query is given')`
- `it('filters rows by the selected status tab')`
- `it('shows counts for every tab')`
- `it('only shows the own uren on the index (not colleague rows)')`
- `it('rejects when eindtijd is before or equal to starttijd')`
- `it('computes quarter-hour durations correctly (1h15m → 1.25)')`
- `it('stores a new entry with status concept and user_id of the current user')`
- `it('ignores any attempt to mass-assign user_id or status')`
- `it('blocks choosing a client outside the own caseload with 403')`
- `it('denies editing an ingediend entry with 403')`
- `it('denies editing a goedgekeurd entry with 403')`
- `it('policy delete returns false for anyone')`

### 4.2 Full-project run na US-11

```
Tests:    212 passed (612 assertions)
Duration: 2.26s
```

Let op: de totalen exclusief US-10 omdat die op een parallelle feature-branch staat (sprint-batch-merge volgt na US-12).

### 4.3 Dekkingsmatrix

| AC | Tests |
|---|---|
| AC-1: tabs + counts + eigen-uren-filter | 6 |
| AC-2: create-form scope | 3 |
| AC-3: validatie + server-side duur | 5 |
| AC-4: status/user_id server-side + mass-assignment-guard | 3 |
| AC-5: edit-policy (concept/afgekeurd only) | 8 |
| Service/enum/policy-unit | 3 |
| **Totaal** | **28** |

## 5. Conclusies (examen-eis 4)

### 5.1 Functioneel
Zorgbegeleider kan een uren-concept aanmaken, bewerken en via tabs navigeren. Teamleider heeft read-only viewAny (volledige teamleider-index komt in US-13). Cliënt-dropdown is expliciet rolgebaseerd afgeschermd; mass-assignment en cross-caseload-probes worden in 3 lagen (Request + Controller-check + Service-methode) geweigerd.

### 5.2 Privacy & security
- **Dataminimalisatie:** geen GPS, geen geolocatie, geen device-info — alleen datum + tijden + cliënt + vrije notities (conform Trello "Privacy/Security/Ethiek"-sectie).
- **Defense in depth:** `UrenregistratiePolicy@update` + `delete()==false` + middleware `zorgbegeleider` op create/edit-routes + `ownCaregiverClients()` in controller + `Rule::exists` in request.
- **Immutability:** `user_id` + `status` staan niet in `$fillable`; wijziging alleen via service.
- **State-machine integrity:** edit-policy blokkeert ingediend/goedgekeurd structureel — statuswijziging komt pas in US-12 (`submit/withdraw`) + US-13 (`approve/reject`).

### 5.3 Code kwaliteit
- `UrenStatus` backed-enum vervangt magic strings + geeft type-safety door hele codebase (`status->isEditable()` leest beter dan een string-compare).
- `UrenregistratieService::computeDuration` gebruikt integer seconds → exacte decimaal, geen float-wobble.
- `Urenregistratie` extends pure Eloquent — niet via SoftDeletes; uren zijn historisch immutable, archiveren is onzinnig.
- Tabs-logica: URL-gebaseerd (`?status=…`) i.p.v. JS/cookie → shareable links + werkt zonder JavaScript.

### 5.4 Openstaande punten
- Handmatige TC's (TC-01 t/m TC-13) batch aan einde.
- US-12 integratie (submit/withdraw/resubmit) bouwt voort op de enum + policy.
- US-13 teamleider-view heeft extra scopedForTeamleider() nodig — buiten US-11 scope.

### 5.5 Eindoordeel
**US-11 voldoet aan alle AC's en DoD-eisen.** 28 Pest-tests dekken elk criterium + alle alternatieve paden; 0 regressies in bestaande suites; Pint clean.

## 6. Analyse van gebruikte informatiebronnen (examen-eis 5)

| Bron | Hoe gebruikt in US-11 | Invloed |
|---|---|---|
| **Pest-testoutput** | Primair: dekking + regressie | 28/28 groen per commit |
| **Eigen bug-meldingen tijdens dev** | Blade-component parse-error bij `@if` rondom `<x-slot:action>`; opgelost door empty-state in twee varianten te splitsen | View-herstructurering in dezelfde commit |
| **Trello AC/DoD** | 5 AC + 9 DoD-items geëxtraheerd | 1-op-1 AC→test mapping |
| **`user-stories.md`** | US-tekst + privacy-bullets | Dataminimalisatie-tests (geen GPS-velden, alleen HH:MM input) |
| **`ontwerpdocument.md`** | State-machine-keuze voor uren + policy-structuur | Enum + policy.delete=false |
| **`eisen-wensen-uitgangspunten.md`** | Rol-matrix: uren-registreren = zorgbegeleider-only | Middleware + create-policy |
| **Presentatie-feedback** | n.v.t. | — |
| **Retrospective** | n.v.t. | — |

## 7. Interpretatie van bevindingen uit bronnen (examen-eis 6)

1. **Pest-output + ontwerp-keuze (backed enum) versterkten elkaar**: door `UrenStatus` als PHP 8.4 backed-enum te casten op het model, herkenden tests type-safe `UrenStatus::Concept` overal; een simpele string-vergelijking was minder leesbaar en minder veilig geweest.
2. **Trello "server-side berekent duur" + eigen bug-melding tijdens dev (float-drift met `DateTime->diff`)** leidde tot de `integer seconds / 3600` implementatie. Een DateTime-gebaseerde oplossing faalde op 12:00→12:00 → `-0.0` en op 9:00→10:15 → 1.2499999999. Integer-seconds geeft exacte decimaal, test 14 bewijst dit.
3. **Mass-assignment-probe (test 16) combineert `user-stories.md` AC-4 + AVG-principe "minimaliseren van vertrouwen in client-input"**: het feit dat we zowel `$fillable` minimaal houden als een bewuste `service->create($user, $payload)` signatuur hebben, maakt dat een ge-POST-e `user_id=X` drie lagen moet passeren (Request-whitelist → fillable → service) — géén daarvan laat het door. Dit bewijst dat de defense-in-depth uit ontwerpdocument in de praktijk werkt.
4. **Cross-caseload-guard (test 17) kwam níet uit Trello AC, maar uit AVG "gerechtvaardigd belang"-analyse**: een zorgbegeleider mag technisch alleen uren schrijven voor cliënten aan wie hij gekoppeld is. De 403-bevinding ontstond toen ik de flow handmatig dacht — Trello noemde alleen "dropdown toont eigen", niet "backend weigert andere ID". Tests zijn dus een rijkere bron dan Trello alléén.
5. **Edit-policy-boom (5 tests: concept-allow, afgekeurd-allow, ingediend-deny, goedgekeurd-deny, ander-user-deny) overlapt met US-12 scope**: door nu al de state-machine te implementeren (via `isEditable()`), hoeft US-12 alleen nog de transities submit/withdraw/approve/reject toe te voegen — de guard-logic staat er al. Dit versnelt sprint 3 afronding.
6. **Sidebar-link activeren (was disabled='true' sinds design-port)** is klein maar essentieel: zonder zichtbare ingangspoort zou de feature in productie-UX onvindbaar zijn. Ondanks dat het niet in Trello-AC staat, is dit een afgeleid UX-criterium uit de `sidebar.blade.php` conventie "`disabled` pas weghalen zodra route bestaat".

---

**Laatst bijgewerkt:** 2026-04-23 — einde US-11 implementatie, vóór sprint-3-batch-push.
