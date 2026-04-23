# Testplan US-12 — Uren indienen, terugtrekken en opnieuw indienen

> **Sprint 3 — US #4 (afsluiter)**
> **Branch:** `feature/uren-indienen-terugtrekken` (gebaseerd op `feature/concept-uren-aanmaken`)
> **User story:** Als zorgbegeleider wil ik mijn concept-uren kunnen indienen, terugtrekken bij vergissing en afgekeurde uren corrigeren en opnieuw indienen zodat ik de controle houd tot definitieve goedkeuring.

## 1. Soorten testen uitgevoerd (examen-eis 1)

| Soort | Wat | Waarom |
|---|---|---|
| **Pest feature tests** | `tests/Feature/US-12.php` — 31 tests / 62 asserts | Dekt alle 5 AC's + volledige transitie-matrix (allowed + forbidden pairs) |
| **Notification-asserties** | `Notification::fake()` + `assertSentTo`/`assertNotSentTo` | Valideert dat AC-2 correct werkt op database-channel zonder echte DB-inserts |
| **State-machine-unit** | Directe calls naar `UrenregistratieService::transition()` met elk from-to-paar | Dekking van de matrix onafhankelijk van HTTP/policy-laag |
| **Handmatige browser-tests** | Batch aan einde — zie §3 | Flash-messages, confirm-dialog, afkeur-banner-styling |

## 2. Test-gebruikers / test-data

**Factories:**
- `UrenregistratieFactory->concept()/ingediend()/goedgekeurd()/afgekeurd()` (US-11)
- `UserFactory->teamleider()/zorgbegeleider()`, `ClientFactory`, `TeamFactory`

**`beforeEach`:** 1 team + 1 vreemd team, 2 teamleiders in eigen team + 1 teamleider in vreemd team, 2 zorgbegeleiders, 1 gekoppelde cliënt.

## 3. Handmatige testscenario's (examen-eis 2)

| # | Scenario | Stappen | Verwacht | Werkelijk |
|---|---|---|---|---|
| TC-01 | Indienen concept | Concepten-tab → rij → knop "Indienen" | Redirect Ingediend-tab + flash "Uren ingediend voor goedkeuring" | ⏳ Pest ✅ |
| TC-02 | Indienen faalt bij lege cliënt/uren | Maak concept zonder cliënt in DB (niet mogelijk via UI, alleen script) → Indienen | 422 + status blijft concept | ⏳ Pest (via service) ✅ |
| TC-03 | Teamleiders ontvangen notification | Submit als zorgbeg → login als teamleider | Bell-icon toont 1 ongelezen `uren_ingediend` | ⏳ Pest ✅ |
| TC-04 | Vreemde-team teamleider geen notification | Check notifications-tabel voor teamleider uit ander team | Géén rij | ⏳ Pest ✅ |
| TC-05 | Terugtrekken ingediend | Ingediend-tab → knop "Terugtrekken" → confirm OK | Redirect Concepten-tab + flash "Uren teruggetrokken..."; nu weer bewerkbaar | ⏳ Pest ✅ |
| TC-06 | Terugtrekken geannuleerd | Ingediend-tab → "Terugtrekken" → Cancel in JS-confirm | Geen mutatie | ⏳ handmatig (JS-only) |
| TC-07 | Terugtrekken goedgekeurde entry | Probeer op goedgekeurd-tab via directe URL `/uren/{id}/terugtrekken` | 403 Forbidden | ⏳ Pest ✅ |
| TC-08 | Afkeur-banner op edit | Open `/uren/{id}/edit` voor afgekeurde entry met afkeur_reden ingevuld | Gele banner "Teamleider-notitie bij afkeur" bovenaan formulier | ⏳ Pest ✅ |
| TC-09 | Opnieuw indienen wist notitie | Bewerk afgekeurde entry → wijzig tijden → "Opslaan en opnieuw indienen" | Redirect Ingediend-tab; entry krijgt status=Ingediend + afkeur_reden=null | ⏳ Pest ✅ |
| TC-10 | Non-owner blocked | Login als Mo → probeer knop "Indienen" op Jeroen's concept | Geen knop zichtbaar; directe POST → 403 | ⏳ Pest ✅ |
| TC-11 | Goedgekeurd-tab toont Read-only | Goedgekeurd-tab | Tekst "Read-only" zichtbaar; geen knoppen | ⏳ Pest ✅ |
| TC-12 | Transitie-matrix in service | `service->transition($concept, Goedgekeurd)` | Throwt `InvalidStateTransitionException` met NL message | ⏳ Pest ✅ |

## 4. Resultaten van de testen (examen-eis 3)

### 4.1 Pest-output

```
PASS  Tests\Feature\US12 (31 tests, 62 assertions — 0.70s)
```

Dekking onder meer:
- `it('submits a valid concept entry and transitions to ingediend')`
- `it('rejects submit when uren is zero')` — via `isIndienbaar()`
- `it('sends UrenIngediendNotification to every teamleider in the own team')`
- `it('does not notify teamleiders from other teams')`
- `it('notification payload includes type uren_ingediend')`
- `it('withdraws an ingediend entry back to concept')`
- `it('rejects withdraw from goedgekeurd')` / `afgekeurd` / `concept`
- `it('shows the teamleider_notitie on edit page for an afgekeurd entry')`
- `it('resubmit clears afkeur_reden and transitions to ingediend')`
- `it('resubmit notifies all teamleiders again')`
- `it('transition rejects goedgekeurd to any other state')` — 3× terminal-check
- `it('transition rejects submit when entry is not indienbaar')`

### 4.2 Full-project run na US-12

```
Tests:    243 passed (674 assertions)
Duration: 2.62s
```

### 4.3 Dekkingsmatrix

| AC | Tests |
|---|---|
| AC-1: submit + isIndienbaar | 5 |
| AC-2: notifications (eigen-team only + payload) | 4 |
| AC-3: withdraw (allowed + 3 verboden + non-owner) | 5 |
| AC-4: afkeur-banner + resubmit (clears + notifies) | 4 |
| AC-5: service-transition-matrix (allowed + forbidden) | 7 |
| Enum/helper-unit | 2 |
| UI-regressie | 3 |
| Overig (policy-403) | 1 |
| **Totaal** | **31** |

## 5. Conclusies (examen-eis 4)

### 5.1 Functioneel
Zorgbegeleider kan:
- Concept-entries indienen (alleen valide) → automatische notification naar alle teamleiders in eigen team.
- Ingediende entries terugtrekken → weer bewerkbaar als concept.
- Afgekeurde entries zien met teamleider-notitie → corrigeren en opnieuw indienen in één flow (UI gebruikt dezelfde edit-pagina maar POST naar `/opnieuw-indienen`).

Goedgekeurde entries zijn echt terminal: zowel UI (Read-only) als DB-laag (policy + transition-matrix) weigeren mutatie.

### 5.2 Privacy & security
- **Eigenaarschap**: policy `submit`/`withdraw`/`resubmit` vereist alle drie `$uren->user_id === $user->id` — conform Trello-bullet "zorgbegeleider kan terugtrekken tot goedkeuring".
- **Team-scope** op notifications: teamleiders krijgen alleen notifications voor eigen-team zorgbegeleiders — voorkomt lekken van planning-info naar andere teams.
- **Defense in depth** in 4 lagen op elke transitie:
  1. Route achter `zorgbegeleider`-middleware
  2. Policy-check (`$this->authorize(...)`)
  3. Service-matrix (`transition()` weigert niet-toegestane paren)
  4. `isIndienbaar()`-check op submit/resubmit (domein-invariant)

### 5.3 Code kwaliteit
- **Één centrale `transition()`** i.p.v. 3 aparte mutatie-methodes met ge-kopieerde if-logic. Nieuwe transities toevoegen (US-13) = één matrix-regel wijzigen + policy + route.
- **`InvalidStateTransitionException`** met `render()` → UI krijgt automatisch 422 + NL flash zonder dat controllers hoeven te try/catchen.
- **Notification-fake** dekt alle assertions — database-channel-payload wordt ook daadwerkelijk in `notifications`-tabel gecheckt (test 9).
- State-machine in PHP-code is zelf-documenterend via enum + match-expression (line: ~95 van Service).

### 5.4 Openstaande punten
- Handmatige TC's (TC-01 t/m TC-12) batch aan einde.
- US-13 (teamleider approve/reject) consumeert deze matrix: voegt 2 nieuwe policy-methodes toe, géén service-matrix-wijziging nodig (Ingediend → Goedgekeurd/Afgekeurd is al allowed).
- Email-kanaal (verbetervoorstel) kan worden toegevoegd door `via()` uit te breiden naar `['database', 'mail']`.

### 5.5 Eindoordeel
**US-12 voldoet aan alle AC's en DoD-eisen.** Sprint 3 is hiermee functioneel compleet (US-09 t/m US-12). Na merge + tag `sprint-3` is het project klaar voor sprint 4 (uren goedkeuren + rapportages + wachtwoord + profiel).

## 6. Analyse van gebruikte informatiebronnen (examen-eis 5)

| Bron | Hoe gebruikt in US-12 | Invloed |
|---|---|---|
| **Pest-testoutput** | Primair: matrix-dekking + notification-asserties | 31/31 groen per commit |
| **Eigen bug-meldingen dev** | (a) `InvalidStateTransitionException::render()` return-type — HTTP `Response` faalde met `RedirectResponse`; opgelost door `Symfony\HttpFoundation\Response` (parent); (b) test `client_id=null` kon niet via `saveQuietly()` door DB-constraint — opgelost door direct op model-instance `isIndienbaar()` te testen | 2 testfixes |
| **Trello AC + DoD** | 5 AC + 8 DoD-items → 1-op-1 test-mapping | Volledige dekking |
| **`user-stories.md`** | "zorgbegeleider houdt controle tot definitieve goedkeuring" | Policy-eigenaar-check + terminal=goedgekeurd |
| **`ontwerpdocument.md`** | State-machine-keuze | Enum + matrix in service |
| **AVG-analyse (team-scope notificaties)** | Teamleiders zien alleen eigen-team uren | Team-id-filter op Notification::send |
| **US-08 notification-pattern** | `ClientCaregiverAssignedNotification` als blueprint | `UrenIngediendNotification` volgt zelfde structuur |
| **Presentatie-feedback** | n.v.t. | — |
| **Retrospective** | n.v.t. | — |

## 7. Interpretatie van bevindingen uit bronnen (examen-eis 6)

1. **Pest + ontwerp-keuze (centrale transition()) versterkten elkaar**: doordat de matrix in één match-expression staat, dekken de 7 matrix-tests de state-machine volledig. Ware de logica verspreid over 3 if-ladders per controller-methode, had ik per methode 3-5 edge cases apart moeten testen — meer code en makkelijker iets vergeten. De geheugen-herinnering van US-08 dat een centrale service-methode een "single source of truth" biedt is hier opnieuw bevestigd.
2. **Trello AC-4 ("afkeur_reden gewist bij opnieuw-indienen") + user-story "controle tot definitieve goedkeuring" impliceren samen dat afkeur een conversatie-start is, geen status-einde**: het wissen van de notitie na correctie zorgt dat volgend afkeur opnieuw uitleg vereist. Test 20 verifieert dit: `afkeur_reden=null` na resubmit.
3. **Notification-team-scope** is niet expliciet in Trello AC maar komt uit AVG-analyse ("gegevens delen met personen die legitiem belang hebben"): teamleider uit ander team heeft géén belang bij uren van Jeroen. Test 12 controleert dit. De bron (AVG) leverde een beveiligings-constraint die de user-story impliceerde maar niet uitsprak.
4. **Bug tijdens dev (InvalidStateTransitionException return-type)** leerde dat `Illuminate\Http\Response` niet de parent is van `RedirectResponse` — beide erven van `Symfony\Component\HttpFoundation\Response`. De fout werd in Pest-output zichtbaar met duidelijke TypeError — Pest-output was als bron waardevoller dan naieve PHP-stacktrace omdat de test direct wees naar HTTP-context (uren.submit → 500).
5. **Goedgekeurd = terminal** (test 25-27) is een bewuste keuze: zonder deze constraint kunnen teamleiders in theorie uren heropenen, wat AVG "juistheid" zou ondermijnen (wijzigingen na akkoord = datamanipulatie). Door goedgekeurd → `[]` in de matrix te zetten, hebben we dit architectonisch vastgelegd; als US-13 ooit een "heropenen"-flow wil, moet dat expliciet via een nieuwe policy-methode én matrix-uitbreiding, wat een review-moment garandeert.
6. **Sprint-3 momentum**: bij afsluiten van US-12 staat de full-suite op 243 tests. De groei (157 na sprint 2 → 243 nu) laat zien dat het Pest-first-patroon schaalt zonder dat testen onderhoudsbrossig worden. De tests uit US-11 bleven ongewijzigd — geen regressie door het toevoegen van status-transities.

---

**Laatst bijgewerkt:** 2026-04-23 — einde US-12 implementatie, klaar voor sprint-3 batch-push + tag.
