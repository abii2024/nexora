# Testplan — US-07 Cliënt aanmaken met persoonsgegevens

> **User story:** Als teamleider wil ik nieuwe cliënten registreren met hun persoonsgegevens en zorgtype zodat ik het cliëntenbestand van mijn organisatie centraal kan opbouwen en de juiste zorg kan organiseren.
>
> **Branch:** `feature/client-aanmaken`
> **Feature test:** [`tests/Feature/US-07.php`](../../tests/Feature/US-07.php)
> **Algemeen testplan:** [README.md](./README.md)

## 1. Soorten testen uitgevoerd

| Soort | Tool | Locatie | Aantal |
|---|---|---|---|
| Feature test (geautomatiseerd) | Pest v4 | `tests/Feature/US-07.php` | 21 tests · 75 asserts |
| Handmatige browser-test | Chrome / Safari | `http://nexora.test/clients/create` | 7 TC |

## 2. Test-gebruikers

| Naam | E-mail | Rol |
|---|---|---|
| Fatima El Amrani | `teamleider@nexora.test` | teamleider |
| Jeroen Bakker | `zorgbegeleider@nexora.test` | zorgbegeleider (403-test) |

Setup: `php artisan migrate:fresh --seed`

## 3. Handmatige testscenario's

### TC-01 — Teamleider opent create form (AC-1)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als teamleider | Dashboard | ✅ |
| 2 | Klik "Cliënten" in sidebar → `/clients` | Overzicht (leeg) met "Cliënt toevoegen"-knop | ✅ |
| 3 | Klik "Cliënt toevoegen" → `/clients/create` | Formulier met 3 secties: Persoonlijk / Contact / Zorg | ✅ |
| 4 | Inspecteer velden | voornaam/achternaam/geboortedatum/BSN/email/telefoon/status/zorgtype | ✅ 8 velden zichtbaar |

**Pest-dekking:** `renders the create form with all sections for a teamleider` — **PASS**

### TC-02 — Zorgbegeleider probeert toegang (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als zorgbegeleider | Zorgbeg-dashboard | ✅ |
| 2 | Plak URL `/clients/create` | 403 via ClientPolicy@create | ✅ |
| 3 | cURL POST `/clients` | 403 + geen DB insert | ✅ |

**Pest-dekking:** `denies zorgbegeleider access to /clients/create (403)` + `denies zorgbegeleider POST /clients (403)` — **PASS**

### TC-03 — Lege voornaam + validatie-retentie (AC-3)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Vul alles behalve voornaam | Velden ingevuld | ✅ |
| 2 | Submit | Blijft op `/clients/create` met error "Voornaam is verplicht" | ✅ |
| 3 | Andere velden (achternaam/email/telefoon/BSN/datum) behouden via `old()` | Ja | ✅ |
| 4 | Geen nieuwe client in DB | Count ongewijzigd | ✅ |

**Pest-dekking:** `rejects submission without voornaam and preserves other input via old()` — **PASS**

### TC-04 — BSN duplicate (AC-4)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Maak eerste cliënt aan met BSN `111111111` | Succes | ✅ |
| 2 | Tweede cliënt met zelfde BSN | Fout "Dit BSN is al gekoppeld aan een andere cliënt" | ✅ |
| 3 | Andere BSN-formaten: `"12345"` (te kort) / `"abc123def"` | Fouten over 9-cijferige validatie | ✅ |

**Pest-dekking:** `rejects a BSN that already exists` + `not exactly 9 digits` + `containing non-digits` — **PASS**

### TC-05 — Happy path (AC-5)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Vul: Sanne / de Wit / `sanne@client.test` / `0612345678` / BSN `123456789` / geboortedatum `17-05-1980` / Actief / WMO | Velden ingevuld | ✅ |
| 2 | Submit | Redirect `/clients/{id}` met flash "Cliënt aangemaakt." | ✅ |
| 3 | Show-pagina toont alle gegevens + team + aangemaakt-door | Alle 3 info-cards | ✅ |
| 4 | DB: `created_by_user_id` = teamleider-id, `team_id` = auth-team | Audit correct | ✅ |

**Pest-dekking:** `creates a client with server-side audit fields and redirects to show with flash` — **PASS**

### TC-06 — Mass-assignment protection

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Voeg hidden input `<input type=hidden name=team_id value=999>` toe via DevTools | Ingevoerd | ✅ |
| 2 | Ook `<input type=hidden name=created_by_user_id value=999>` | Ingevoerd | ✅ |
| 3 | Submit | Nieuwe cliënt krijgt team_id van auth-teamleider, created_by=auth-id | ✅ Hidden inputs genegeerd |

**Pest-dekking:** `ignores a hidden team_id input and uses authenticated users team_id` — **PASS**

### TC-07 — Cross-team scope (US-02 regressie)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als teamleider Amsterdam | Dashboard | ✅ |
| 2 | Probeer Rotterdam-cliënt show te openen | 403 via ClientPolicy@view | ✅ |
| 3 | `/clients` overzicht toont alleen eigen team | Geen cross-team leak | ✅ |

**Pest-dekking:** `denies teamleider from another team to view a foreign client` + `does not leak clients across teams on the index` — **PASS**

## 4. Resultaten van de testen

### Geautomatiseerde Pest tests

```text
$ ./vendor/bin/pest tests/Feature/US-07.php

   PASS  Tests\Feature\US07
  ✓ it renders the create form with all sections for a teamleider
  ✓ it denies zorgbegeleider access to /clients/create (403)
  ✓ it denies zorgbegeleider POST /clients (403) and stores nothing
  ✓ it redirects guest from /clients/create to /login
  ✓ it rejects submission without voornaam and preserves other input via old()
  ✓ it rejects empty required fields with Dutch error messages
  ✓ it rejects a BSN that already exists in the clients table
  ✓ it rejects a BSN that is not exactly 9 digits
  ✓ it rejects a BSN containing non-digits
  ✓ it accepts a nullable BSN (optioneel veld)
  ✓ it rejects a geboortedatum in the future
  ✓ it creates a client with server-side audit fields and redirects to show with flash
  ✓ it ignores a hidden team_id input and uses authenticated users team_id
  ✓ it rejects an invalid email format
  ✓ it accepts a nullable email
  ✓ it rejects an invalid status value
  ✓ it rejects an invalid care_type value
  ✓ it renders the show page after creation
  ✓ it denies teamleider from another team to view a foreign client (US-02 regressie)
  ✓ it shows the newly created client on the index for the same-team teamleider
  ✓ it does not leak clients across teams on the index

  Tests:    21 passed (75 assertions)
  Duration: ~0.6s
```

**Samenvatting:** **21 / 21 US-07 tests groen**, 75 asserts.

### Totaal projecttests

```text
Tests:    128 passed (398 assertions)
Duration: 1.69s
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
| US-07 Cliënt aanmaken | 21 | 75 |
| Voorbeelden | 2 | 2 |
| **Totaal** | **128** | **398** |

### Handmatige browser-tests (batch aan einde van sprint)

Screenshots + handmatige verificatie worden aan het einde van alle 16 US's in één batch uitgevoerd.

## 5. Conclusies

### Functioneel

1. **Teamleider maakt nieuwe cliënten aan** met minimaal voornaam + achternaam + status + zorgtype.
2. **Optionele velden** BSN, email, telefoon, geboortedatum — ondersteunt AVG-dataminimalisatie (alleen invullen wat strikt nodig is).
3. **3-secties layout** (Persoonlijk / Contact / Zorg) matcht user-stories.md specificatie.
4. **Redirect naar show-page** na succes — basis voor vervolg (US-08 begeleiders koppelen, US-10 bewerken).
5. **Index stub** werkt met scope uit US-02 — teamleiders zien eigen team, zorgbegeleiders zien gekoppelde cliënten (volledig uitgewerkt in US-09).

### Privacy & security

6. **Cliëntgegevens zijn AVG art. 9 bijzondere persoonsgegevens** — dataminimalisatie toegepast:
   - BSN optioneel + hint "alleen invullen indien strikt nodig"
   - BSN niet zichtbaar op index-lijst (alleen op show-pagina)
7. **BSN validatie** — exact 9 cijfers (regex), uniek in tabel. Voorbereid voor encryptie-at-rest in vervolg-story.
8. **Mass-assignment protection** — `StoreClientRequest::validatedPayload()` overschrijft `team_id` + `created_by_user_id` met auth-waarden; hidden form inputs worden genegeerd.
9. **CSRF** via `@csrf` op elk formulier.
10. **Role whitelist + authorization via Policy** (ClientPolicy@create) ipv inline `abort_unless` — auditeerbaar beleid.
11. **Cross-team protectie** via ClientPolicy@view (regressie-test) — teamleider Amsterdam kan geen Rotterdam-cliënt zien.
12. **Geboortedatum** moet in het verleden liggen (`before:today` rule) — voorkomt bizarre invoer.

### Code kwaliteit

13. **21 Pest tests · 75 asserts · ~0,6s** — uitgebreide dekking.
14. **Pint clean**.
15. **Service pattern** — ClientService::create gebruikt DB-transactie, klaar voor US-08 uitbreiding (transactionele caregiver-koppeling).
16. **Constructor DI** voor ClientService in controller (PSR-11).
17. **StoreClientRequest::validatedPayload** is single source of truth voor payload — geen duplicate field-mapping in controller.

### Openstaand

- **Caregiver-koppeling** (primair/secundair/tertiair) → US-08.
- **Zoeken + filter + paginatie op `/clients`** → US-09.
- **Bewerken + status-log + archivering** → US-10.
- **BSN encryptie-at-rest** → vervolg-story (niet in examen-scope).

### Eindoordeel

✅ **US-07 kan als "Done" gemarkeerd worden op Trello.** Alle 5 acceptatiecriteria + Privacy bullets gerealiseerd. Cliëntbeheer-basis staat; US-08/US-09/US-10 bouwen hier voort.
