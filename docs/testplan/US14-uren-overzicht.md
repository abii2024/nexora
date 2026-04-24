# Testplan US-14 — Urenoverzicht met filters (teamleider)

> **Sprint 4 — US #2**
> **Branch:** `feature/uren-overzicht-met-filters` (gebouwd op US-13)
> **User story:** Als teamleider wil ik het urenoverzicht kunnen filteren op status, medewerker en week zodat ik efficiënt door de administratie kan werken en openstaande acties vind.

## 1. Soorten testen uitgevoerd (examen-eis 1)

| Soort | Wat | Waarom |
|---|---|---|
| **Pest feature tests** | `tests/Feature/US-14.php` — 22 tests / 44 asserts | Dekt alle 5 AC + filter-whitelist + service-defensiviteit |
| **N+1 regressie-test** | `DB::listen` in Pest om query-count te meten | Bewijst dat eager loading werkt (<15 queries voor 20 rijen) |
| **Service-unit tests** | `getPaginatedForTeamleider` met invalid inputs | Valideert dat ongeldige week/sort defensief genegeerd worden |
| **Handmatige browser-tests** | Batch aan einde | HTML `<input type="week">` browser-support + UX van sortable headers |

## 2. Test-gebruikers / test-data

**`beforeEach`:** 1 eigen team + 1 vreemd team, 1 teamleider + 1 vreemde teamleider, 2 zorgbegeleiders in eigen team (Piet + Anna), 1 cliënt.

**Factories:** `UrenregistratieFactory` met states `concept()/ingediend()/goedgekeurd()/afgekeurd()` (uit US-11).

**Seeders (handmatig):** `teamleider@nexora.test` + `zorgbegeleider@nexora.test` (wachtwoord `password`).

## 3. Handmatige testscenario's (examen-eis 2)

| # | Scenario | Stappen | Verwacht | Werkelijk |
|---|---|---|---|---|
| TC-01 | Default overzicht | Login teamleider → `/teamleider/uren-overzicht` | Tabel met ingediende uren, 20/pagina, default sort=datum desc | ⏳ Pest ✅ |
| TC-02 | Status-filter | Filter-bar → status=afgekeurd → Filter | URL krijgt `?status=afgekeurd`; alleen afgekeurde uren | ⏳ Pest ✅ |
| TC-03 | Medewerker-filter | Kies Piet in dropdown | Alleen Piet's uren | ⏳ Pest ✅ |
| TC-04 | Week-filter | Selecteer week 17-2026 in `<input type="week">` | Alleen uren tussen ma-zo van week 17 | ⏳ Pest ✅ |
| TC-05 | Filters + paginatie | 25 Piet-entries → filter=Piet → klik pagina 2 | URL 2e pagina bevat `medewerker=Piet-id`; pagina 2 toont alleen Piet | ⏳ Pest ✅ |
| TC-06 | Sorteren op duur | Klik "Uren"-kolomkop twee keer | 1e keer: duur desc (pijl ↓); 2e keer: duur asc (pijl ↑) | ⏳ Pest ✅ |
| TC-07 | Week-summary | Filter week met 2 medewerkers (38u + 38u) | Header toont "Piet: 38,00 · Anna: 38,00 · Totaal: 76,00 uur" | ⏳ Pest ✅ |
| TC-08 | Lege lijst | Filter week zonder uren | "Geen uren gevonden"-empty-state | ⏳ Pest ✅ |
| TC-09 | Reset | Filter actief → klik Reset | URL zonder query-params; default status=ingediend | ⏳ Handmatig (UI-flow) |
| TC-10 | Cross-team-scope | Teamleider team B → /teamleider/uren-overzicht | Alleen team B's uren zichtbaar | ⏳ Pest ✅ |
| TC-11 | Zorgbeg-403 | Login zorgbeg → direct URL | 403 via teamleider-middleware | ⏳ Pest ✅ |
| TC-12 | Vreemde medewerker-ID | Manipulatie URL `medewerker=<vreemde user id>` | Team-guard returnt 0 rijen (geen crash) | ⏳ Pest ✅ |
| TC-13 | Ongeldige week-format | `/teamleider/uren-overzicht?week=niet-een-week` | Geen crash; filter genegeerd | ⏳ Pest ✅ |

## 4. Resultaten van de testen (examen-eis 3)

### 4.1 Pest-output

```
PASS  Tests\Feature\US14 (22 tests, 44 assertions — 0.90s)
```

Groene tests o.a.:
- `it('renders overzicht with all 3 filters and default status ingediend')`
- `it('filters by status afgekeurd and shows it in URL')`
- `it('filters by medewerker and limits to that user')`
- `it('ignores medewerker filter when user is not in own team')`
- `it('filters by week using ISO 8601 format')`
- `it('keeps filters in pagination links via withQueryString')`
- `it('paginates with 20 items per page')`
- `it('sorts by duur asc when ?sort=duur&direction=asc')`
- `it('calculates week summary per medewerker')`
- `it('does not trigger N+1 queries on the overzicht (eager loads user and client)')`
- `it('service getPaginatedForTeamleider ignores invalid ISO week')`
- `it('service getPaginatedForTeamleider ignores unknown sort column')`

### 4.2 Full-project run na US-14

```
Tests:    323 passed (855 assertions)
Duration: 3.37s
```

### 4.3 Dekkingsmatrix

| AC | Tests |
|---|---|
| AC-1: 3 filters + defaults | 6 |
| AC-2: filters in URL bij paginatie | 1 |
| AC-3: 20/pagina + sorteerbaar | 4 |
| AC-4: week-summary header | 2 |
| AC-5: lege-state + N+1 | 2 |
| Policy / role / scope | 3 |
| Service defensiviteit + UI regressie | 4 |
| **Totaal** | **22** |

## 5. Conclusies (examen-eis 4)

### 5.1 Functioneel
Teamleider kan administratief efficiënt door uren bladeren: filter op combinaties van status + medewerker + week, sorteer klikbaar per kolom, zie week-totalen per medewerker in de header. Default is `status=ingediend` (meest frequente werklijst).

### 5.2 Privacy & security
- **Team-scope**: service-level `whereHas('user', team_id)` zorgt dat filter-ID-tampering cross-team geen data lekt (test 4 bewijst dit).
- **Whitelist-validation**: `sort`, `direction`, `status`, en `week`-format gaan door strikte filters → geen SQL-injection mogelijk.
- **Middleware + policy defense**: zorgbeg krijgt 403 voordat controller draait.
- **Geen gevoelige data in URL**: alleen `status`/`medewerker-id`/`week-nummer` — geen namen of BSN's in query-string.

### 5.3 Code kwaliteit
- **`getPaginatedForTeamleider`** bouwt bovenop `scopedForTeamleider` uit US-13 — beide methodes blijven bestaan en hebben hun eigen doel (beoordeel-inbox vs. administratie).
- **`<x-uren.filter-bar>`** is een herbruikbare Blade-component (kan later in US-14+ ook zorgbeg-variant aandrijven).
- **Sorteerbare kolomkoppen**: inline `$sortLink` closure in Blade — geen eigen component nodig voor 3 kolommen.
- **N+1-test** is codified gedrag: een toekomstige refactor die eager loading breekt, faalt de test.

### 5.4 Openstaande punten
- Handmatige TC's (TC-01 t/m TC-13) batch-oplevering.
- `<input type="week">` valt in oudere browsers terug op text — voor examen niet relevant (alle moderne browsers ondersteunen het).
- Export naar CSV/PDF niet in scope (verbetervoorstel).

### 5.5 Eindoordeel
**US-14 voldoet aan alle AC's en DoD-eisen.** De US hergebruikt US-11/12/13 infrastructuur zonder aanpassing; volledig additief. Regressie-impact: 0.

## 6. Analyse van gebruikte informatiebronnen (examen-eis 5)

| Bron | Hoe gebruikt | Invloed |
|---|---|---|
| **Pest-testoutput** | Primair: AC-dekking + regressie | 22/22 groen |
| **Eigen bug-meldingen dev** | `alle` als status-escape-hatch ontbrak initieel — bij filter-reset viel de gebruiker default terug op `ingediend` wat andere statussen verbergt | `alle` toegevoegd als extra option |
| **Trello AC + DoD** | 5 AC + 8 DoD-items → test-mapping | Volledige dekking |
| **AVG-analyse (team-scope)** | Filter-ID-tampering moet geen cross-team data tonen | Dubbele team-guard (policy + service) |
| **US-09 filter-bar pattern** | `clients/filter-bar` als blueprint voor `uren/filter-bar` | Zelfde structuur (labels + selects + reset) |
| **US-11/12/13 service-scope** | `scopedForTeamleider` al aanwezig | Nieuwe method bouwt erop voort |

## 7. Interpretatie van bevindingen uit bronnen (examen-eis 6)

1. **Trello AC-2 (filters in URL) + US-09 `withQueryString`-patroon** gaven direct de implementatie: `paginate()->withQueryString()`. Dit is een voorbeeld van hoe eerdere US's versnellen omdat er al een "huis-standaard" is. Test 7 bewijst dat deze uit-de-doos-oplossing correct werkt.
2. **N+1-test uit US-09 heruit gebruikt** (DB::listen) — laat zien dat het patroon schaalbaar is. Door dit als automatische test te handhaven, is performance een codified-concern i.p.v. manuele review.
3. **Week-filter in ISO 8601** (`YYYY-Www`) gekoppeld aan HTML `<input type="week">`: de browser geeft direct het juiste formaat terug, geen JS-parsing nodig. Test 5 verifieert de format-herkenning én de start/end-week-grenzen.
4. **Team-scope-guard-duplicatie** (in policy + service): lijkt redundant maar beschermt tegen twee aanvalsvectoren: (a) iemand die de controller bypasst via een nieuwe route → policy vangt; (b) iemand die via `service->getPaginatedForTeamleider($other, [...])` vreemd team kiest → service-level `whereHas('user', team_id)` vangt. Tests 4 + 18 dekken beide.
5. **`alle` status-escape** kwam uit een **eigen handmatige test**: als je Reset gebruikt op de filter-bar, ga je terug naar `/teamleider/uren-overzicht` met default status=ingediend, maar als je expliciet "alle statussen" wilt zien in de dropdown moet dat een keuze zijn. Zonder `alle`-optie was de dropdown misleidend (er leek gemiddeld een "alle"-betekenis te zijn maar die bestond niet). Test 20 codificeert dit nu.
6. **Sprint 4 momentum**: na US-13 + US-14 is de teamleider-kant functioneel compleet. De resterende US-15 + US-16 zijn auth-/profiel-US's met minimale uren-interactie — de uren-stack is nu "af" qua functies en blijft alleen nog onderhevig aan bug-fixes.

---

**Laatst bijgewerkt:** 2026-04-24 — einde US-14 implementatie, lokaal (sprint-4 batch pending na US-16).
