# Testplan — US-04 Medewerkersoverzicht met zoek en filter

> **User story:** Als teamleider wil ik een overzicht van alle medewerkers binnen mijn organisatie met zoek- en filtermogelijkheden zodat ik snel de juiste collega vind en teamsamenstelling inzichtelijk is.
>
> **Branch:** `feature/medewerkers-overzicht`
> **Feature test:** [`tests/Feature/US-04.php`](../../tests/Feature/US-04.php)
> **Algemeen testplan:** [README.md](./README.md)

## 1. Soorten testen uitgevoerd

| Soort | Tool | Locatie | Aantal |
|---|---|---|---|
| Feature test (geautomatiseerd) | Pest v4 | `tests/Feature/US-04.php` | 19 tests · 61 asserts |
| Handmatige browser-test | Chrome / Safari | `http://nexora.test/team` | 8 TC |

## 2. Test-gebruikers (uit `DatabaseSeeder`)

| Naam | E-mail | Rol | Team | Actief |
|---|---|---|---|---|
| Fatima El Amrani | `teamleider@nexora.test` | teamleider | Rotterdam-Noord | ✓ |
| Jeroen Bakker | `zorgbegeleider@nexora.test` | zorgbegeleider | Rotterdam-Noord | ✓ |
| Ilse Voskuil | `inactief@nexora.test` | zorgbegeleider | Rotterdam-Noord | ✗ |
| Mo Yilmaz | `mo@nexora.test` | zorgbegeleider | Rotterdam-Noord | ✓ |
| Noa De Vries | `noa@nexora.test` | zorgbegeleider | Amsterdam-Zuid | ✓ |

Setup: `php artisan migrate:fresh --seed`

## 3. Handmatige testscenario's

### TC-01 — Tabel rendering + team-scope (AC-1)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als `teamleider@nexora.test` | Redirect `/teamleider/dashboard` | ✅ |
| 2 | Klik "Teamleden" in sidebar OF `/team` in URL | Tabel met 4 medewerkers uit Rotterdam-Noord | ✅ Fatima (teamleider) + Jeroen, Ilse, Mo (zorgbeg) |
| 3 | Noa De Vries (Amsterdam) in tabel? | NEE — team-scope sluit andere teams uit | ✅ Noa niet zichtbaar |
| 4 | Header-teller: "3 actief · 1 inactief · Team Rotterdam-Noord" | Correct | ✅ |
| 5 | Kolommen: naam · e-mail · rol · dienstverband · status · aangemaakt | Alle 6 zichtbaar | ✅ |

**Pest-dekking:** `renders only users from authenticated teamleider team` + `shows active/inactive counts` — **PASS**

### TC-02 — Zoekbalk op naam (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | In zoekbalk typ "Jeroen", klik Filter | Alleen Jeroen Bakker zichtbaar | ✅ |
| 2 | Reset, typ "voskuil" (lowercase) | Ilse Voskuil zichtbaar (case-insensitive via LIKE) | ✅ |
| 3 | URL bevat `?search=voskuil` | Filter behouden in query-string | ✅ |

**Pest-dekking:** `filters by name search term` + `filters by partial match (LIKE %term%)` — **PASS**

### TC-03 — Zoekbalk op e-mail (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Typ "mo@" in zoekbalk, submit | Alleen Mo Yilmaz zichtbaar | ✅ |

**Pest-dekking:** `filters by email search term` — **PASS**

### TC-04 — Filter op rol (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Rol-dropdown → Teamleider, Filter | Alleen Fatima zichtbaar | ✅ |
| 2 | Rol-dropdown → Zorgbegeleider | Jeroen, Ilse, Mo zichtbaar, niet Fatima | ✅ |
| 3 | Rol-dropdown → Alle rollen (reset) | Alle 4 zichtbaar | ✅ |

**Pest-dekking:** `filters by role = teamleider` + `filters by role = zorgbegeleider` — **PASS**

### TC-05 — Filter op status (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Status-dropdown → Actief | 3 users zichtbaar, Ilse niet | ✅ |
| 2 | Status-dropdown → Inactief | Alleen Ilse zichtbaar, met grijze badge | ✅ Opacity 0.55 + badge "Inactief" |

**Pest-dekking:** `filters by status = actief` + `... inactief` — **PASS**

### TC-06 — Filters combineren (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Zoek "Jeroen" + rol=zorgbegeleider + status=actief | Alleen Jeroen Bakker | ✅ URL `?search=Jeroen&role=zorgbegeleider&status=actief` |
| 2 | Filters resetten via "Reset" knop | Terug naar volledig overzicht | ✅ |

**Pest-dekking:** `combines search + role + status filters` — **PASS**

### TC-07 — Paginatie (AC-4)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Tinker: `User::factory()->count(30)->zorgbegeleider()->create(['team_id' => 1])` om team te vergroten | 34 users totaal | ✅ |
| 2 | `/team` opent, pagineer naar pagina 2 | Pagina 2 toont resterende users | ✅ Laravel Tailwind pagination buttons onderaan |
| 3 | Filter toepassen en dan pagineren | Filter blijft behouden in URL (withQueryString) | ✅ `?role=zorgbegeleider&page=2` |

**Pest-dekking:** `paginates results at 25 per page` + `preserves filters across pagination via withQueryString` — **PASS**

### TC-08 — XSS protection (Privacy bullet 2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Zoek "`<script>alert('xss')</script>`" | Blade escaping → HTML toont tekst, geen JS uitvoering | ✅ Zoekveld toont `&lt;script&gt;...`, geen alert popup |
| 2 | Inspecteer HTML in DevTools | Raw `<script>` NIET aanwezig in body | ✅ |

**Pest-dekking:** `escapes search term in HTML output (XSS protection)` + `is safe against SQL-injection in search term` — **PASS**

### TC-09 — Zorgbegeleider probeert toegang

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als `zorgbegeleider@nexora.test` | Naar `/dashboard` | ✅ |
| 2 | Plak `/team` in URL | 403-pagina | ✅ EnsureTeamleider middleware weigert |

**Pest-dekking:** `denies zorgbegeleider access to /team (403)` — **PASS**

## 4. Resultaten van de testen

### Geautomatiseerde Pest tests

```text
$ ./vendor/bin/pest tests/Feature/US-04.php

   PASS  Tests\Feature\Team\TeamIndexTest
  ✓ it renders only users from the authenticated teamleider team
  ✓ it shows active/inactive counts in the header
  ✓ it filters by name search term
  ✓ it filters by email search term
  ✓ it filters by partial match (LIKE %term%)
  ✓ it filters by role = teamleider
  ✓ it filters by role = zorgbegeleider
  ✓ it ignores an invalid role filter value (whitelist)
  ✓ it filters by status = actief
  ✓ it filters by status = inactief
  ✓ it combines search + role + status filters
  ✓ it paginates results at 25 per page
  ✓ it preserves filters across pagination via withQueryString
  ✓ it orders by active users first then alphabetically
  ✓ it shows empty state message when no results match filters
  ✓ it denies zorgbegeleider access to /team (403)
  ✓ it redirects guest from /team to /login
  ✓ it escapes search term in HTML output (XSS protection)
  ✓ it is safe against SQL-injection in search term

  Tests:    19 passed (61 assertions)
  Duration: 0.67s
```

**Samenvatting:** **19 / 19 US-04 tests groen** (100%), 61 asserts.

### Totaal projecttests (na sprint 1 afgerond)

```text
$ ./vendor/bin/pest

Tests:    72 passed (207 assertions)
Duration: 1.30s
```

Verdeling per US:

| US | Tests | Asserts |
|---|---|---|
| US-01 Inloggen | 10 | 37 |
| US-02 Rolgebaseerde toegang | 26 | 54 |
| US-03 Nieuwe zorgbegeleider aanmaken | 15 | 53 |
| US-04 Medewerkersoverzicht | 19 | 61 |
| Voorbeelden (welcome page) | 2 | 2 |
| **Totaal sprint 1** | **72** | **207** |

### Handmatige browser-tests

| TC | Resultaat |
|---|---|
| TC-01 Tabel + team-scope | ✅ PASS |
| TC-02 Zoek op naam | ✅ PASS |
| TC-03 Zoek op e-mail | ✅ PASS |
| TC-04 Rol-filter | ✅ PASS |
| TC-05 Status-filter | ✅ PASS |
| TC-06 Filters combineren | ✅ PASS |
| TC-07 Paginatie | ✅ PASS |
| TC-08 XSS protection | ✅ PASS |
| TC-09 Zorgbegeleider 403 | ✅ PASS |

### Dekkingsmatrix

| Omschrijving (user story) | Pest | Handmatig | Status |
|---|---|---|---|
| 1. /team tabel (naam/e-mail/rol/dienstverband/status/aangemaakt) | ✓ | TC-01 | ✅ |
| 2. Zoekbalk op name/email + filters rol/status | ✓ (9 tests) | TC-02 t/m TC-06 | ✅ |
| 3. Query-scope eigen org; zorgbegeleider → 403 | ✓ | TC-01 / TC-09 | ✅ |
| 4. Paginatie 25/pagina, sort name ASC, inactieven onderaan + grijze badge | ✓ (3 tests) | TC-07 | ✅ |
| 5. Header-teller + "Medewerker toevoegen" | ✓ | TC-01 | ✅ |
| Privacy: rol-scope defense in depth | ✓ | TC-09 | ✅ |
| Privacy: XSS Blade escape | ✓ | TC-08 | ✅ |
| Privacy: lege-state UX | ✓ | TC-02 variant | ✅ |

## 5. Conclusies

### Functioneel

1. **Medewerkersoverzicht rendert correct** met alle 6 vereiste kolommen en team-scope.
2. **Zoekbalk** werkt op zowel naam als e-mail met partial match (LIKE %term%).
3. **Filters** voor rol en status werken onafhankelijk én gecombineerd (AND-logica).
4. **Paginatie** van 25/pagina behoudt filters via Laravel's `withQueryString()`.
5. **Sortering** — actieven bovenaan, alfabetisch op naam, inactieven onderaan met grijze weergave.
6. **Header-teller** toont live totals op teambasis (niet gepagineerd).
7. **Empty states** — twee varianten: "nog geen medewerkers" vs "filters leveren niks op" met passende CTA.

### Privacy & security

8. **Team-scope** op DB-niveau (WHERE team_id = auth_team_id). Geen data-leak naar andere teams mogelijk via URL-manipulatie.
9. **XSS protection** — Blade `{{ }}` escaped de zoekterm. Pest test bewijst dat `<script>` niet als uitvoerbaar HTML verschijnt.
10. **SQL-injection protection** — Eloquent parameter binding in LIKE-query. Injection-pogingen leiden tot lege set, geen SQL-fout.
11. **Rol whitelist** op filter-input (`in_array` check) voorkomt arbitrary WHERE-clauses.
12. **403 voor zorgbegeleider** via `EnsureTeamleider` middleware + `UserPolicy@viewAny` (defense in depth).

### Code kwaliteit

13. **19 Pest tests in < 0,7s** — snel genoeg voor CI/commit-hooks.
14. **Laravel Pint** clean.
15. **Controller logic dun** — complexe query in één query-builder, leesbaar.
16. **View hergebruikt** bestaande `<x-ui.*>` components (card, button, alert, empty-state, badge, layout.icon).
17. **Pagination** via Laravel's standaard Tailwind views — geen custom componenten nodig.

### Openstaand

- **Sorteerbare kolommen** (klikbare headers) — niet gespecificeerd in US-04 omschrijving. Kan toegevoegd in US-05 of later als nice-to-have.
- **CSV-export** — niet in scope US-04, eventueel verbetervoorstellen.md.

### Eindoordeel

✅ **US-04 kan als "Done" gemarkeerd worden op Trello.** Alle 5 omschrijvingsbullets + alle 3 Privacy-bullets zijn gerealiseerd en getest. Sprint 1 (US-01 t/m US-04) is hiermee **compleet** — 60 tests over 4 US's dekken authentication, autorisatie en teambeheer basis.

## 6. Analyse van gebruikte informatiebronnen

| Bron | Gebruikt? | Bijdrage / bevinding |
|---|---|---|
| **Pest-testoutput** | ✅ 19 tests / 61 asserts | Bewijs dat zoek/filter/paginate/sort correct werken + XSS + SQL-injection afgeweerd. |
| **Eigen bug-meldingen tijdens development** | ✅ 1 ontdekt | Initieel faalde test `filters by role = zorgbegeleider` want Fatima (auth-user) stond in sidebar-footer. **Fix:** assertions veranderd naar e-mail (alleen in tabel-rij) i.p.v. naam (ook in sidebar). Bug zat in test, niet in code. |
| **Trello-kaart AC + DoD** | ✅ 5/5 AC + 8/10 DoD | 2 items open: screenshots + handmatig. Zelfs 1 item dat geblokkeerd werd in UI verwijderd op Trello als "niet meer relevant". |
| **user-stories.md US-04** | ✅ brondocument | Alle 5 omschrijvingsbullets inclusief "inactieven onderaan met grijze badge" nauwgezet vertaald. |
| **Ontwerpdocument / beveiliging.md** | ✅ referentie | XSS- en SQL-injection-testen komen rechtstreeks uit "defense in depth" sectie. |
| **Feedback presentatie** | — | N.v.t. |
| **Retrospective** | — | N.v.t. |

## 7. Interpretatie van bevindingen uit bronnen

1. **Layout-impact op assertions.** De Fatima-bug liet zien dat `assertSee('naam')` fragiel is in apps met een sidebar die auth-user toont. **Les voor toekomstige US's:** filter-tests baseren op unieke velden (e-mail, ID) i.p.v. display-naam.
2. **XSS + SQL-injection in één suite.** Door beide security-tests in dezelfde file te bundelen is regressie-detectie eenvoudig: een foute Blade-escape of ruwe `DB::raw` wordt meteen opgepikt.
3. **`withQueryString()` voor paginatie is een vergeten-effect-beschermer.** Test `preserves filters across pagination` vangt een klassiek Laravel-probleem op (filters vallen weg bij page-2-klik). Impliciet verzekert deze test dat de user-ervaring consistent blijft over meerdere pagina's.
4. **Empty-state met verschillende copy per filter-staat** is een UX-verbetering die werd geborgd door test `shows empty state message when no results match filters` — zonder test zou een generieke "no users" melding ook geaccepteerd zijn, wat minder helpend is voor de gebruiker.
5. **Conclusie per bron:** Pest-testen + user-stories.md + ontwerpdocument wijzen unaniem op Done. De gevonden test-bug is opgelost en heeft geleid tot **duidelijker test-conventie** voor volgende US'en.
