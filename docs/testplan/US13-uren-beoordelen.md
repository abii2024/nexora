# Testplan US-13 — Uren goedkeuren of afkeuren als teamleider

> **Sprint 4 — US #1**
> **Branch:** `feature/uren-goedkeuren-afkeuren`
> **User story:** Als teamleider wil ik ingediende uren kunnen goedkeuren of met reden afkeuren zodat de urenadministratie correct is en fouten tijdig gecorrigeerd worden.

## 1. Soorten testen uitgevoerd (examen-eis 1)

| Soort | Wat | Waarom |
|---|---|---|
| **Pest feature tests** | `tests/Feature/US-13.php` — 27 tests / 63 asserts | AC-1..5 + policy-cross-team + payload-inhoud notifications |
| **Pest service-unit** | `service->approve()` / `reject()` direct | Isolatie van transitie-logica + metadata-correctheid |
| **Handmatige browser-tests** | Batch aan einde project | Modal-open/sluit, confirm-dialog, visuele correctheid afkeur-banner |

## 2. Test-gebruikers / test-data

**Factories (uit US-11):** `UrenregistratieFactory` met states `concept()`/`ingediend()`/`goedgekeurd()`/`afgekeurd()`.

**`beforeEach`-setup:** 1 eigen team + 1 vreemd team; 1 eigen + 1 vreemde teamleider; 2 zorgbegeleiders in eigen team; 1 cliënt met 2 caregiver-koppelingen.

**Seeders (handmatig):** `teamleider@nexora.test` / `zorgbegeleider@nexora.test` (wachtwoord `password`).

## 3. Handmatige testscenario's (examen-eis 2)

| # | Scenario | Stappen | Verwacht | Werkelijk |
|---|---|---|---|---|
| TC-01 | Overzicht als teamleider | Login teamleider → `/teamleider/uren` | Tabel per medewerker, subtotaal rechtsboven, groene Goedkeuren + rode Afkeuren per rij | ⏳ Pest ✅ |
| TC-02 | Lege lijst | Alle ingediende uren afhandelen → refresh | "Niets te beoordelen"-empty-state | ⏳ Pest ✅ |
| TC-03 | Goedkeuren werkt direct | Klik Goedkeuren bij Jeroen's 3,50u | Redirect + "Uren goedgekeurd"-alert; rij verdwijnt uit lijst | ⏳ Pest ✅ |
| TC-04 | Notificatie bij zorgbeg | Login zorgbegeleider na goedkeur | Bell-icon / notifications-tabel bevat `type=uren_goedgekeurd` | ⏳ Pest ✅ |
| TC-05 | Afkeur-modal opent | Klik Afkeuren | Native `<dialog>` opent met textarea + hint | ⏳ Handmatig (JS-only) |
| TC-06 | Lege reden geweigerd | Submit afkeur-form zonder tekst | Browser HTML-required + server-side 422 + "reden verplicht" | ⏳ Pest ✅ |
| TC-07 | <10 tekens geweigerd | Vul "Test" in | Server-side 422 "min 10 tekens" | ⏳ Pest ✅ |
| TC-08 | Whitespace geweigerd | Vul 15 spaties in | 422 (trim in prepareForValidation) | ⏳ Pest ✅ |
| TC-09 | Valide afkeur werkt | "Starttijd klopt niet — check je rooster" (39 tekens) | Redirect + alert; zorgbeg ziet rode banner op edit-form | ⏳ Pest ✅ |
| TC-10 | Resubmit na afkeur | Zorgbeg bewerkt → "Opslaan en opnieuw indienen" | afkeur_reden=null + status=Ingediend (US-12 hergebruik) | ⏳ Pest ✅ |
| TC-11 | Zorgbeg-403 | Login zorgbeg → `/teamleider/uren` | 403 Forbidden | ⏳ Pest ✅ |
| TC-12 | Cross-team-403 | Vreemde-team teamleider → POST goedkeuren | 403 via policy | ⏳ Pest ✅ |
| TC-13 | Idempotentie | Goedkeuren op reeds-goedgekeurde entry (URL-hack) | 403 via policy (status != Ingediend) | ⏳ Pest ✅ |

## 4. Resultaten van de testen (examen-eis 3)

### 4.1 Pest-output

```
PASS  Tests\Feature\US13 (27 tests, 63 assertions — 0.80s)
```

Groene tests o.a.:
- `it('shows teamleider uren index with all ingediende entries grouped by user')`
- `it('approves an ingediende entry and notifies the zorgbegeleider')`
- `it('does not notify other zorgbegeleiders on approve')`
- `it('denies approve for zorgbegeleider with 403')`
- `it('rejects afkeur when teamleider_notitie is under 10 chars')`
- `it('rejects afkeur when teamleider_notitie is only whitespace')`
- `it('accepts afkeur with valid notitie and stores afkeur_reden')`
- `it('shows the afkeur-reason banner on the zorgbegeleider edit page')`
- `it('allows the zorgbegeleider to resubmit an afgekeurde entry')`
- `it('afgekeurd notification payload contains afkeur_reden')`

### 4.2 Full-project run na US-13

```
Tests:    301 passed (811 assertions)
Duration: 3.06s
```

### 4.3 Dekkingsmatrix

| AC | Tests |
|---|---|
| AC-1: /teamleider/uren index + groepering + scope | 5 |
| AC-2: approve flow + notificatie + cross-team/role-denials | 6 |
| AC-3: reject validatie + happy path + 403-paden | 6 |
| AC-4: zorgbeg edit-banner + resubmit | 2 |
| AC-5: middleware + guest-redirect + defense | 2 |
| Service + payload-assertions | 5 |
| UI regressie (sidebar) | 1 |
| **Totaal** | **27** |

## 5. Conclusies (examen-eis 4)

### 5.1 Functioneel
Alle 5 AC's werken. Teamleider ziet gestructureerd overzicht per medewerker + subtotaal. Goedkeuren is 1 klik (geen modal); afkeuren dwingt een kwalitatieve reden af via `<dialog>` + server-side `min:10`.

### 5.2 Privacy & security
- **Team-scope**: `scopedForTeamleider` én `goedkeuren/afkeuren`-policy checken beide `team_id`-gelijkheid; cross-team-acties returnen 403 (tests 10 + 17).
- **Defense in depth** op elke transitie: `teamleider`-middleware + policy + `transition()`-matrix (uit US-12). Geen enkele laag hoeft exclusief te werken.
- **Geen bijzondere persoonsgegevens** in notification-payload — alleen metadata (uren, client_name, teamleider-naam) + optionele afkeur_reden. Geen BSN, geen medische data.
- **`forceFill` gebruikt voor `goedgekeurd_door_user_id` + `beoordeeld_op`**: deze velden staan níet in `$fillable`, dus mass-assignment blijft onmogelijk.

### 5.3 Code kwaliteit
- **Eén centrale state-machine** (uit US-12): US-13 voegt geen nieuwe transitie-paden toe, alleen de *actoren* (teamleider) + metadata.
- **Native `<dialog>`** in plaats van JS-library: 0 dependencies, toegankelijk out-of-the-box (Escape sluit, focus-trap native).
- **`AfkeurUrenRequest::prepareForValidation`** trimt whitespace vóór de `min:10`-rule zodat `"          "` ongeldig is — test 15 dekt dit.
- **`TeamleiderUrenController`** als eigen controller (niet uitbreiding van `UrenregistratieController`) houdt rol-scoping schoon: teamleider-routes + zorgbeg-routes blijven gescheiden.

### 5.4 Openstaande punten
- Handmatige TC's (TC-01 t/m TC-13) batch aan einde.
- US-14 bouwt urenoverzicht met filters — gebruikt dezelfde `scopedForTeamleider` maar zonder status-filter.
- Bell-icon-teller voor unread notifications is verbetervoorstel.

### 5.5 Eindoordeel
**US-13 voldoet aan alle AC's en DoD-eisen.** De US-12 state-machine heeft zich hier bewezen: slechts 2 policy-methodes + 2 service-methodes nodig om de teamleider-kant te bouwen. Full-suite 301 groen; geen regressies.

## 6. Analyse van gebruikte informatiebronnen (examen-eis 5)

| Bron | Hoe gebruikt in US-13 | Invloed |
|---|---|---|
| **Pest-testoutput** | Primaire bron voor AC-dekking en regressievrijwaring | 27/27 groen per commit |
| **Eigen bug-meldingen tijdens dev** | `prepareForValidation`-trim bij notitie — zonder trim zou `"          "` (15 spaties) de `min:10`-rule halen; bug gezien bij whitespace-only test | Request uitgebreid met trim |
| **Trello AC + DoD (card `qmcxUGo0`)** | 5 AC + 8 DoD-items 1-op-1 naar tests | Dekkings-volledigheid |
| **`user-stories.md`** | US-tekst: "met reden afkeuren" → min 10 tekens als kwaliteits-minimum | NL-message benadrukt kwaliteit ("vage redenen helpen niet") |
| **`ontwerpdocument.md`** | State-machine-keuze uit US-12 | Geen nieuwe matrix-wijziging |
| **US-08 + US-12 notification-pattern** | `ClientCaregiverAssignedNotification` + `UrenIngediendNotification` als blueprint | UrenGoedgekeurd/Afgekeurd volgen zelfde shape |
| **Privacy-analyse ("geen BSN in payload")** | Notifications bevatten alleen metadata + naam + reden | Payload-review in test 24 + 25 |

## 7. Interpretatie van bevindingen uit bronnen (examen-eis 6)

1. **Pest-resultaten + US-12-ontwerp bewijzen de waarde van centrale state-machines**: US-13 voegt geen regel toe aan de `transition()`-matrix, alleen 2 policy-methodes en 2 service-methodes. Dit is het "OCP-moment" — de matrix uit US-12 is **open for extension** (teamleider-actor) maar **closed for modification** (allowed pairs onveranderd). Een klassieke if-ladder had hier 2-3 extra branches nodig gehad.
2. **Trello AC-3 "min 10 tekens" gecombineerd met prakt-observatie** dat validators whitespace-only vaak missen, leidde tot de `prepareForValidation`-trim. Zonder die trim zou test 15 ("whitespace only") de `min:10`-rule passeren — dat is een klassieke security/UX-faal die alleen met actief testen boven water komt.
3. **Notification-scoping** (teamleider → alleen eigen zorgbeg) komt uit AVG-principe "gerechtvaardigd belang" én maakt notification-spam onmogelijk. In test 8 checken we expliciet dat `anderZorg` geen kopie krijgt — dat bewijst dat we niet naïef via `User::where('role', 'zorgbegeleider')->get()` sturen.
4. **`forceFill` voor de audit-velden** (`goedgekeurd_door_user_id`, `beoordeeld_op`) voorkomt dat een kwaadaardige user via POST-body deze velden kan spoofen. De velden staan bewust niet in `$fillable`, consistent met het US-11 patroon voor `user_id` + `status`.
5. **Afkeur-reden-wissing bij resubmit** (US-12) + **herbruik van de edit-banner** (US-12) maken US-13 compleet zonder view-duplicatie: de "rode kader met reden"-UI stond er al. Door de banner-logica in US-12 te bouwen was US-13's AC-4 alleen maar een test-regressie-check — zowel snel als robuust.
6. **Sprint 4 startstrategie**: US-13 bewijst dat het splitsen van "schrijven" (US-11/12) en "beoordelen" (US-13) over sprints de tests overzichtelijk houdt. De US-13-tests testen *alleen* teamleider-flow; US-12-tests blijven dekken voor zorgbeg-flow. Geen enkele test werd in beide US's herhaald.

---

**Laatst bijgewerkt:** 2026-04-24 — einde US-13 implementatie, lokaal (sprint-4 batch pending).
