# Testplan — US-08 Cliënten koppelen aan begeleiders (primair / secundair / tertiair)

> **User story:** Als teamleider wil ik één of meer zorgbegeleiders aan een cliënt kunnen koppelen met een rol (primair, secundair, tertiair) zodat duidelijk is wie de hoofdverantwoordelijke zorgbegeleider is en de cliënt altijd vervangend contact heeft bij afwezigheid.
>
> **Branch:** `feature/client-begeleiders-koppelen`
> **Feature test:** [`tests/Feature/US-08.php`](../../tests/Feature/US-08.php)
> **Algemeen testplan:** [README.md](./README.md)

## 1. Soorten testen uitgevoerd

| Soort | Tool | Locatie | Aantal |
|---|---|---|---|
| Feature test (geautomatiseerd) | Pest v4 | `tests/Feature/US-08.php` | 29 tests · 70 asserts |
| Handmatige browser-test | Chrome / Safari | Batch aan einde (afgesproken) | 7 TC |

## 2. Test-gebruikers

| Naam | E-mail | Rol | Team |
|---|---|---|---|
| Fatima El Amrani | `teamleider@nexora.test` | teamleider | Rotterdam |
| Jeroen Bakker | `zorgbegeleider@nexora.test` | zorgbegeleider | Rotterdam |
| Mo Yilmaz | `mo@nexora.test` | zorgbegeleider | Rotterdam |
| Noa De Vries | `noa@nexora.test` | zorgbegeleider | Amsterdam (cross-team test) |

## 3. Handmatige testscenario's

### TC-01 — Auto-rol bij nieuwe cliënt (AC-1)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| 1. `/clients/create`, vul Sanne in, vink 3 begeleiders, geen primair radio | Form accepteert | `it shows available caregivers on the create form` |
| 2. Submit | Redirect `/clients/{id}`, flash "Cliënt aangemaakt." | `it stores caregivers when creating a new client` |
| 3. Show-page toont Alice=Primair, Bob=Secundair, Charlie=Tertiair | Badges kleuren success/warning/neutral | `it shows caregiver badges on the show page` |

**PASS** — 3 Pest tests

### TC-02 — Swap primair (AC-2)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| 1. Cliënt met Alice=Primair, Bob=Secundair | Via factory/sync in setup | — |
| 2. Klik "Begeleiders beheren" → `/clients/{id}/caregivers` | Edit-page met voorgevulde state | `it renders the caregivers edit page with existing assignments pre-filled` |
| 3. Radio op Bob zetten → submit | Redirect + flash "Begeleiders bijgewerkt." | `it updates caregivers via the dedicated edit page` |
| 4. Show-page: Bob=Primair, Alice=Secundair (Charlie=Tertiair ongewijzigd) | Rol-swap werkt (staging fase tertiair voorkomt unique conflict) | `it promotes a new primary and demotes the old one to secundair` |

**PASS** — 3 Pest tests

### TC-03 — Verwijder één begeleider (AC-3)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| 1. Cliënt met 3 begeleiders | — | — |
| 2. Uncheck Bob → submit | 1 rij minder in `client_caregivers` (exact) | `it removes exactly one pivot row when a caregiver is unchecked` |
| 3. Alice=Primair, Charlie=Tertiair blijven | Rollen behouden | — |

**PASS** — 2 Pest tests

### TC-04 — Notification op nieuwe koppeling (AC-4)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| 1. Zorgbeg zonder caregiver-rel. | — | — |
| 2. Teamleider koppelt Jeroen aan cliënt | `notifications` tabel krijgt rij met `type='client_toegewezen'`, `notifiable_id=Jeroen`, data bevat client_id/role/assigned_by | `it sends a database notification to every newly assigned caregiver` |
| 3. Jeroen logt in → bell-icon zou unread count tonen (bell UI komt later) | `$user->unreadNotifications` tellt 1 | — |

**PASS** — 2 Pest tests + database-verificatie

### TC-05 — Partial unique blokkeert tweede primair (AC-5)

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| 1. Pivot-rij `client_id=X, user_id=A, role='primair'` | Insert succes | — |
| 2. Tweede insert `client_id=X, user_id=B, role='primair'` via raw DB | `UniqueConstraintViolationException` — blokkade op DB-niveau | `it rejects a second primair for the same client via partial unique index` |
| 3. Primair op _ander_ client = OK (geen cross-client unique) | Geen conflict | `it allows primair on one client and primair on another client` |
| 4. UI-pad: user klikt nooit 2x primair (radio-button dwingt single) — defense in depth | — | — |

**PASS** — 2 Pest tests

### TC-06 — Cross-team + inactieve + role-guard

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| 1. Probeer Noa (Amsterdam) te koppelen aan Rotterdam-cliënt | Validation error "Alleen actieve zorgbegeleiders uit je eigen team" | `it rejects a caregiver from another team` |
| 2. Probeer inactieve zorgbeg te koppelen | Validation error | `it rejects an inactive caregiver` |
| 3. Probeer Fatima (teamleider) als caregiver | Validation error (alleen role=zorgbegeleider) | `it rejects a teamleider as caregiver` |
| 4. `primary_user_id` = Bob terwijl Bob niet in caregiver_ids | Error "De primaire begeleider moet ook aangevinkt zijn" | `it rejects primary_user_id not present in caregiver_ids` |

**PASS** — 4 Pest tests

### TC-07 — Autorisatie zorgbeg

| Stap | Verwacht | Pest-dekking |
|---|---|---|
| 1. Zorgbeg GET `/clients/{id}/caregivers` | 403 | `it denies zorgbegeleider access to caregivers edit (403)` |
| 2. Zorgbeg PUT `/clients/{id}/caregivers` | 403 + geen DB-mutatie | `it denies zorgbegeleider PUT on caregivers endpoint (403)` |

**PASS** — 2 Pest tests

## 4. Resultaten van de testen

### Geautomatiseerde Pest tests

```text
$ ./vendor/bin/pest tests/Feature/US-08.php

   PASS  Tests\Feature\US08
  ✓ computeCaregiverRoles → 6 tests
  ✓ syncCaregivers → 10 tests
  ✓ HTTP integration → 13 tests

  Tests:    29 passed (70 assertions)
  Duration: 3.55s
```

**Samenvatting:** **29 / 29 US-08 tests groen**, 70 asserts.

### Totaal projecttests — Sprint 2 afgerond

```text
Tests:    157 passed (468 assertions)
Duration: ~3.7s
```

| US | Tests | Asserts |
|---|---|---|
| US-01 Inloggen | 10 | 37 |
| US-02 Rolgebaseerde toegang | 26 | 54 |
| US-03 Medewerker aanmaken | 15 | 53 |
| US-04 Medewerkers overzicht | 19 | 61 |
| US-05 Teamlid bewerken | 16 | 54 |
| US-06 Teamlid deactiveren | 19 | 62 |
| US-07 Cliënt aanmaken | 21 | 75 |
| US-08 Caregivers koppeling | 29 | 70 |
| Voorbeelden | 2 | 2 |
| **Totaal** | **157** | **468** |

### Dekkingsmatrix

| Acceptatiecriterium | Pest | Status |
|---|---|---|
| AC-1 3 begeleiders → auto primary/secondary/tertiary | 2+ tests | ✅ |
| AC-2 Maak primair bij B → B primary, A secondary | 2 tests | ✅ |
| AC-3 Eén verwijderen → exact één rij minder | 1 test | ✅ |
| AC-4 Nieuwe koppeling → DatabaseNotification | 2 tests | ✅ |
| AC-5 Twee primair → partial unique blokkeert | 2 tests | ✅ |
| Privacy: continuïteit van zorg (primair/secundair) | AC-1/AC-2 | ✅ |
| Privacy: notifications = transparantie | AC-4 | ✅ |
| Privacy: DB-constraints tegen race | AC-5 | ✅ |
| Privacy: alleen teamleider koppelt | zorgbeg-403 tests | ✅ |

## 5. Conclusies

### Functioneel

1. **Auto-rol-assignment** werkt correct: eerste aangevinkte = primair, tweede = secundair, rest = tertiair.
2. **Expliciet primair** via radio-button verplaatst de gekozen begeleider naar positie 0 ongeacht check-volgorde.
3. **Swap-flow** (AC-2) werkt via staging-fase waarbij blijvende koppelingen tijdelijk op tertiair worden gezet — voorkomt partial unique conflict.
4. **Verwijder via uncheck** — exact één rij verwijdering, andere pivot-metadata (created_by) behouden voor andere rijen.
5. **Lege sync** — alle koppelingen verwijderd zonder fouten (idempotent).
6. **Dedicated caregivers-page** gescheiden van client-edit (die komt in US-10) — schone UI-verantwoordelijkheid per route.
7. **Store-flow** combineert client-create en caregiver-sync in één submit (minder clicks voor user).

### Privacy & security

8. **Partial unique indexes** op DB-niveau (max 1 primair + 1 secundair per `client_id`) — defense in depth tegen race conditions bij concurrent edits.
9. **DatabaseNotification** per nieuwe koppeling = transparantie naar zorgbegeleider over eigen caseload (AVG beginsel).
10. **Email-notificatie out of scope** — expliciet genoteerd in user-story en verbetervoorstellen.
11. **Cross-team isolatie** — zorgbegeleider uit ander team kan niet gekoppeld worden (validation + policy, 2 lagen).
12. **Role-guard** — alleen `role=zorgbegeleider` + `is_active=true` kunnen gekoppeld worden (teamleider-als-caregiver verhinderd).
13. **`primary_user_id` in `caregiver_ids`-check** — voorkomt orphan primary zonder checkbox.
14. **Mass-assignment** — `created_by_user_id` altijd auth-id; geen hidden form override mogelijk.
15. **Autorisatie**: 4 lagen — EnsureAuth middleware, ClientPolicy@create/@update, Request authorize(), Request withValidator cross-check.

### Code kwaliteit

16. **29 Pest tests / 70 asserts / 3,5s** — uitgebreide dekking van pure functie, service, en HTTP-integratie.
17. **Staging-fase voor role-swaps** — pragmatische oplossing voor partial unique zonder complex rewrite-beleid.
18. **Pint clean**.
19. **ClientService groeit incrementeel** — scopedForUser (US-02) + create (US-07) + computeCaregiverRoles + syncCaregivers (US-08) blijven testbaar in isolatie.
20. **Notification pattern** via `Notification::send()` maakt e-mail-toevoeging in vervolgsprint triviaal (extra channel toevoegen aan `via()`).

### Openstaand

- **Email-notificatie** (buiten scope, zie verbetervoorstellen).
- **Bell-icon-UI** in topbar voor unread count — komt in latere sprint (niet in US-08 scope).
- **Client bewerken** (US-10) — andere velden dan caregivers.
- **Audit-trail op caregivers-wijzigingen** — nu nog geen log zoals bij User. Kan in vervolg-story.

### Eindoordeel

✅ **US-08 kan als "Done" gemarkeerd worden op Trello.** Alle 5 acceptatiecriteria + Privacy-bullets gerealiseerd en getest.

**Sprint 2 is hiermee afgerond** (US-05, US-06, US-07, US-08). Klaar voor batch-push naar GitHub en `sprint-2` tag. Volgende: Sprint 3 (US-09 t/m US-12).

## 6. Analyse van gebruikte informatiebronnen

| Bron | Gebruikt? | Bijdrage / bevinding |
|---|---|---|
| **Pest-testoutput** | ✅ 29 tests / 70 asserts over 3 describe-groepen | Bewijs voor pure function (auto-roles) + service (DB + notifications) + HTTP integratie. |
| **Eigen bug-meldingen tijdens development** | ✅ 1 kritisch opgelost | Eerste implementatie `syncCaregivers` deed direct `updateExistingPivot` — dit **zou crashen op partial-unique-index** bij role-swap (A primair→secundair, B secundair→primair gelijktijdig). **Fix:** staging-fase waarbij blijvende pivots tijdelijk op `tertiair` gezet worden. Tertiair heeft geen unique-constraint → veilig tussenstation. |
| **Trello-kaart AC + DoD** | ✅ 5/5 AC + 7/9 DoD | Screenshots + handmatig open. |
| **user-stories.md US-08** | ✅ brondocument | "Elke nieuwe gekoppelde begeleider krijgt ClientCaregiverAssignedNotification" vertaald in test met `Notification::fake()` + `assertSentTo`. |
| **Ontwerpdocument / verantwoorde-verwerking.md** | ✅ referentie | "Continuïteit van zorg" via primair/secundair is hier onderbouwd. |
| **Examenopdracht** | ✅ referentie | Pivot-constraints (unique + partial) komen uit de user-story expliciet. |
| **Feedback presentatie** | — | N.v.t. |
| **Retrospective** | — | N.v.t. |

## 7. Interpretatie van bevindingen uit bronnen

1. **Staging-fase voor role-swap is pure-engineering-inzicht.** Zonder de bug-ontdekking tijdens ontwikkeling had de feature in productie kunnen crashen bij een concurrent swap. De test `promotes a new primary and demotes the old one to secundair` **reproduceert** exact dit scenario — het is nu een regressie-anker.
2. **Partial unique op DB-niveau is belangrijker dan tests suggereren.** Applicatie-code zou 2 primair tegelijk kunnen proberen te schrijven in race-conditie tussen twee teamleiders. De DB-level constraint vangt dit zonder app-hulp. Test `rejects a second primair for the same client via partial unique index` bewijst dit via **raw DB insert** die app-logic omzeilt.
3. **`Notification::fake()` voor database-channel is even waardevol als voor e-mail.** Zelfs zonder echte SMTP-server bewijzen we dat de notification-send-call correct getriggerd wordt. E-mail-uitbreiding in vervolg-story = 1 regel `via()` aanpassen.
4. **3 describe-groepen = 3 isolatie-niveaus.** Pure functie (zonder DB) test `computeCaregiverRoles` in < 1ms. Service-laag test real-DB maar zonder HTTP. HTTP-integratie test volledige pipeline. Deze piramide = **snelle feedback + volledig vertrouwen**.
5. **Cross-team + role + active-check in CaregiverAssignmentRequest.** Deze 3 guards in 1 Form Request voorkomen dat een teamleider foute user-IDs in het form injecteert. Alle 3 zijn afzonderlijk getest (`rejects caregiver from another team`, `rejects inactive caregiver`, `rejects teamleider as caregiver`).
6. **Conclusie per bron:** alle bronnen bevestigen Done. De **belangrijkste bug-vondst** (swap-race via partial-unique) is een **lesson learned** dat we meenemen naar US-10 (client bewerken): staging-patroon bij multi-constraint-swaps.
