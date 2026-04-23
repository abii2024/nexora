# Testplan — US-09 Cliëntenoverzicht met rol-gebaseerde weergave, zoek en filter

> **User story:** Als gebruiker van Nexora wil ik een cliëntenoverzicht zien dat past bij mijn rol en waarin ik kan zoeken en filteren zodat ik snel de juiste cliënt vind zonder overzicht te verliezen over mijn caseload.
>
> **Branch:** `feature/clienten-overzicht`
> **Feature test:** [`tests/Feature/US-09.php`](../../tests/Feature/US-09.php)
> **Algemeen testplan:** [README.md](./README.md)

## 1. Soorten testen uitgevoerd

| Soort | Tool | Locatie | Aantal |
|---|---|---|---|
| Feature test (geautomatiseerd) | Pest v4 | `tests/Feature/US-09.php` | 27 tests · 67 asserts |
| Handmatige browser-test | Chrome / Safari | Batch aan einde (afspraak) | 6 TC |

## 2. Test-gebruikers + data

| E-mail | Rol | Team | Cliënten-koppelingen |
|---|---|---|---|
| `teamleider@nexora.test` | teamleider | Rotterdam | ziet alle team-cliënten |
| `zorgbegeleider@nexora.test` | zorgbegeleider | Rotterdam | gekoppeld aan seed-cliënten C1 (primair) + C2 (secundair) |
| `mo@nexora.test` | zorgbegeleider | Rotterdam | gekoppeld aan C3 (primair) |
| `noa@nexora.test` | zorgbegeleider | Amsterdam | 0 koppelingen (empty-state-test) |

## 3. Handmatige testscenario's

### TC-01 — Teamleider tabel-weergave (AC-2)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| Login teamleider → `/clients` | Tabel met 6 kolommen (naam/status/zorgtype/dob/primair/tel) | `teamleider index shows all team clients` |
| Kop "Cliënt toevoegen"-knop rechtsboven zichtbaar | Ja | idem |
| Header toont "X actief · Y wachtlijst · Z inactief · Team naam" | Correct totaal | `shows totaal count + create CTA` |

### TC-02 — Zorgbegeleider kaart-weergave + eigen caseload (AC-1, AC-5)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| Login Jeroen → `/clients` | Kaart-grid met **alleen eigen 2 cliënten** (C1 primair, C2 secundair) | `zorgbegeleider sees only own linked clients` |
| Info-banner "Eigen caseload" zichtbaar | Ja | `sees info banner about eigen caseload` |
| "Cliënt toevoegen"-knop NIET zichtbaar | Correct | `does NOT see the Client toevoegen button` |
| C3 (Mo's cliënt) NIET zichtbaar | Geen cross-caregiver leak | idem |

### TC-03 — Zoekterm 'Jan' (AC-3)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| Teamleider typt "Jan" in zoekbalk | "Jan Bakker" (voornaam) + "Sanne Janssen" (achternaam) zichtbaar | `filters index by search term Jan` |
| "Mo" en "Ilse" NIET zichtbaar | Correct | idem |
| Case-insensitive ("jan" lowercase werkt ook) | Ja | `filters by lowercase search term` |

### TC-04 — Filter-combinatie + retentie bij paginatie (AC omschrijving bullet 3 + AC-4)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| Combinatie search + status + care_type | Alleen cliënten die aan alle 3 voldoen | `combines search + status + care_type filters` |
| Navigeer naar pagina 2 van gefilterd resultaat | URL bevat nog steeds `status=actief&care_type=wmo&page=2` | `preserves filters across pagination links` |

### TC-05 — Lege-state varianten (AC-5)

| Scenario | Verwachte melding | Pest-dekking |
|---|---|---|
| Zorgbegeleider Noa (Amsterdam, 0 koppelingen) | "Je hebt momenteel geen cliënten toegewezen." | `zorgbegeleider zonder koppeling shows empty state with exact Trello AC text` |
| Teamleider zonder cliënten in team | "Nog geen cliënten" + create-CTA | (regressie US-07) |
| Filters zonder resultaten | "Geen cliënten gevonden" + reset-knop | `shows filter-empty-state when search returns no results` |

### TC-06 — N+1-preventie & query-performance

| Controle | Verwacht | Pest-dekking |
|---|---|---|
| DB::listen count bij 4 cliënten met caregivers | < 10 queries (geen N+1) | `eager loads caregivers to prevent N+1 queries` |
| Eager loading `with(['caregivers', 'team'])` actief | Ja | idem |

## 4. Resultaten van de testen

### Geautomatiseerde Pest tests

```text
$ ./vendor/bin/pest tests/Feature/US-09.php

   PASS  Tests\Feature\US09
  ✓ getPaginated (12 tests)
  ✓ HTTP integration (15 tests)

  Tests:    27 passed (67 assertions)
  Duration: 0.75s
```

### Totaal projecttests

```text
Tests:    184 passed (535 assertions)
Duration: 2.68s
```

| US | Tests | Asserts |
|---|---|---|
| US-01 t/m US-08 | 155 | 466 |
| US-09 Cliëntenoverzicht | 27 | 67 |
| Voorbeelden | 2 | 2 |
| **Totaal** | **184** | **535** |

### Dekkingsmatrix

| AC | Pest-test | Status |
|---|---|---|
| AC-1 zorgbeg ziet alleen toegewezen cliënten | `returns only linked clients for zorgbegeleider (AC-1 kern)` + `zorgbegeleider sees only own linked clients (AC-1 kern)` | ✅ |
| AC-2 teamleider ziet alle team-cliënten + CTA | `teamleider index shows all team clients with Client toevoegen button` | ✅ |
| AC-3 zoekterm case-insensitive | 2 tests (Jan + jan) | ✅ |
| AC-4 filter-retentie bij paginatie | `preserves filters across pagination links` | ✅ |
| AC-5 empty-state exact Trello-tekst | `zorgbegeleider zonder koppeling shows empty state with exact Trello AC text` | ✅ |
| Privacy: rol-scope | 3 scoping tests + cross-team regressie | ✅ |
| Privacy: N+1-preventie (performance) | `eager loads caregivers to prevent N+1 queries` | ✅ |

## 5. Conclusies

### Functioneel

1. **Rol-specifieke weergave** — teamleider ziet tabel met 6 kolommen inclusief "Primaire begeleider", zorgbegeleider ziet kaart-grid met eigen-rol-badge.
2. **Filters werken onafhankelijk en gecombineerd** — search / status / care_type / sort zijn whitelist-based en combineerbaar (AND-logica).
3. **Paginatie 15/pagina** — `LengthAwarePaginator` via `withQueryString()` behoudt filters.
4. **Totaal-banner per rol** — teamleider ziet actief/wacht/inactief split, zorgbegeleider ziet gekoppeld-totaal.
5. **3 empty-state varianten** geven correcte UX-feedback (met-filters / teamleider-leeg / zorgbeg-leeg).

### Privacy & security

6. **Query-level scoping** — `ClientService::getPaginated` bouwt op `scopedForUser` (US-02) dus rol-regels zijn gedeeld. Cross-team leak getest via regressie-test.
7. **SQL-injection protection** — alle filters via whitelist (`in_array` check) of Eloquent binding. Geen `DB::raw` met user-input.
8. **XSS protection** — Blade `{{ }}` escaped de zoekterm in de `<input value="...">`.
9. **AVG-minimum** — index toont alleen naam/status/care_type/dob/tel, geen BSN of adres (dataminimalisatie regressie uit US-07).

### Code kwaliteit

10. **27 Pest tests · 67 asserts · 0,75s** — uitgebreide dekking inclusief N+1 regressie.
11. **Pint clean**.
12. **Eager loading bewezen** via `DB::listen` — geen regressie mogelijk zonder test-falen.
13. **Service-patroon consistent** — `getPaginated` hergebruikt `scopedForUser`, geen dubbele scope-logica.
14. **Blade-component `<x-clients.filter-bar>`** is herbruikbaar voor US-14 (uren-filter) en US-04 (medewerker-filter) template.

### Openstaand

- **Sorteerbare tabel-kolommen** (klik op kolomkop) — niet in scope US-09. Dropdown-sort is voldoende.
- **CSV/Excel-export** — verbetervoorstel, niet in examen-scope.
- **Geavanceerde filters** (leeftijdsrange, geboortedatum-range) — niet genoemd in Trello, niet uitgewerkt.

### Eindoordeel

✅ **US-09 kan als "Done" gemarkeerd worden op Trello.** Alle 5 acceptatiecriteria + Privacy + performance-eisen gerealiseerd en getest. 184 tests totaal blijven groen.

## 6. Analyse van gebruikte informatiebronnen

| Bron | Gebruikt? | Bijdrage / bevinding |
|---|---|---|
| **Pest-testoutput** | ✅ 27 tests / 67 asserts over 2 suites | Service-laag + HTTP-laag beide gedekt, inclusief N+1 via DB::listen. |
| **Eigen bug-meldingen tijdens development** | ✅ 1 syntax-fout | Initiële attempt met `$client->caregivers->firstWhere('pivot.role', 'primair')` werkte niet — Eloquent-collection `firstWhere` kijkt niet automatisch in pivot-attributen. **Fix:** closure via `->first(fn ($c) => $c->pivot->role === 'primair')`. Eenvoudige syntactische bug, maar herinnert me dat pivot-attributen niet standaard via dot-notation doorzoekbaar zijn. |
| **Trello-kaart AC + DoD** | ✅ 5/5 AC (1 AC was geblokkeerd in Trello UI) + 6/8 DoD | Screenshots + handmatig open (batch). |
| **user-stories.md US-09** | ✅ brondocument | Bullet "paginated (15 per pagina) + sorteerbaar" + "eager loading met('caregivers.user','team') voorkomt N+1" letterlijk vertaald. |
| **Ontwerpdocument / verantwoorde-verwerking.md** | ✅ referentie | "Continuïteit van zorg" verklaart waarom kaart-weergave voor zorgbeg rolspecifiek is (snel eigen caseload scannen). |
| **US-02 scopedForUser-implementatie** | ✅ hergebruikt | `getPaginated` bouwt voort op `scopedForUser` (US-02) — geen copy-paste van scope-regels. |
| **Feedback presentatie** | — | N.v.t. — presentatie na sprint 4. |
| **Retrospective** | — | N.v.t. |

## 7. Interpretatie van bevindingen uit bronnen

1. **Pivot-dot-notation-bug was leerzaam.** `firstWhere('pivot.role', 'primair')` werkt in Eloquent-collections op relationele attributen, niet op pivot. De fix (closure) is **expliciet** en maakt de zoek-intent duidelijker. **Les:** voor pivot-filtering altijd closure of `wherePivot()` op Builder-niveau gebruiken.
2. **`getPaginated` op `scopedForUser` bouwen bespaart duplicatie.** Zou ik scope-regels in `getPaginated` opnieuw schrijven, zou elke US-02-regressie gemist kunnen worden. Nu: als scope-test faalt, falen ook `getPaginated` tests — **single point of truth** bewijst zijn waarde.
3. **N+1 preventie met `DB::listen` is goud waard.** Zonder deze test zou een toekomstige ontwikkelaar per ongeluk `->with('caregivers')` kunnen weghalen. De test faalt dan direct met > 10 queries — regressie-anker.
4. **Rol-specifieke UI (tabel vs kaart) was ontwerp-keuze die testen ondersteunde.** Teamleider wil overzicht → scanbare tabel met veel info per rij. Zorgbegeleider wil focus op eigen caseload → kaarten met rol-badge zichtbaar. Tests controleren dat beide paden dezelfde data laten zien, maar in verschillende representatie.
5. **Exacte Trello-tekst voor empty-state ("Je hebt momenteel geen cliënten toegewezen.") is getest literal.** Dit is meer dan symbolic — als iemand de tekst per ongeluk zou wijzigen naar bijvoorbeeld "Geen cliënten gevonden", faalt de test. Impact: copy-consistency over UI + tests.
6. **Conclusie per bron:** alle bronnen wijzen unaniem op Done. De 1 developer-bug was snel opgelost en levert een **code-review-les** op voor pivot-collectie-filtering. Geen feedback of retrospective data beschikbaar — die komen na sprint 4 en zullen dan worden teruggevoegd.
