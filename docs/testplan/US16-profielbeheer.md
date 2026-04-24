# Testplan US-16 — Profielbeheer (eigen gegevens + wachtwoord wijzigen)

> **Sprint 4 — US #4 (laatste van het project!)**
> **Branch:** `feature/profielbeheer`
> **User story:** Als ingelogde zorgbegeleider of teamleider wil ik mijn eigen profielgegevens en wachtwoord kunnen bijwerken zodat mijn accountgegevens actueel blijven zonder tussenkomst van de beheerder.

## 1. Soorten testen uitgevoerd (examen-eis 1)

| Soort | Wat | Waarom |
|---|---|---|
| **Pest feature tests** | `tests/Feature/US-16.php` — 21 tests / 52 asserts | AC-1..5 + mass-assignment-probes + email-unique-ignore |
| **Password-hash-assertion** | `Hash::check($plain, $user->password)` | Bewijst bcrypt-opslag + hash-rotatie bij password-change |
| **Session-invalidation probe** | Password-hash-rotatie + `AuthenticateSession` middleware-aanwezigheid | AC-5 gegarandeerd door Laravel-native mechaniek |
| **Handmatige browser-tests** | Batch aan einde | 2-browser-scenario voor AC-5 (andere sessie daadwerkelijk uitloggen) |

## 2. Test-gebruikers / test-data

**`beforeEach`:** 1 team + 1 zorgbegeleider (Jeroen, `jeroen@nexora.test`, wachtwoord `oudwachtwoord-sterk`).

**Factories:** `UserFactory->zorgbegeleider()/teamleider()` (uit US-01).

**Seeders (handmatig):** `teamleider@nexora.test` / `zorgbegeleider@nexora.test` (wachtwoord `password`).

## 3. Handmatige testscenario's (examen-eis 2)

| # | Scenario | Stappen | Verwacht | Werkelijk |
|---|---|---|---|---|
| TC-01 | Profielpagina open | Login → klik "Profiel" in sidebar | `/profiel` met velden pre-filled name + email | ⏳ Pest ✅ |
| TC-02 | Alleen naam wijzigen | Wijzig naam → Opslaan | Redirect + "Profiel bijgewerkt"-flash; password ongemoeid | ⏳ Pest ✅ |
| TC-03 | Alleen email wijzigen | Wijzig email → Opslaan | email gewijzigd; password ongemoeid; login-test met nieuw email slaagt | ⏳ Pest ✅ |
| TC-04 | Email-unique eigen waarde | Opslaan met ongewijzigde email | Geen unique-violation | ⏳ Pest ✅ |
| TC-05 | Email van collega | Vul een bestaand team-email in → Opslaan | "Dit e-mailadres is al in gebruik" | ⏳ Pest ✅ |
| TC-06 | Password zonder current | Laat current leeg + nieuw password invullen | "Vul je huidige wachtwoord in om een nieuw wachtwoord te kunnen kiezen" | ⏳ Pest ✅ |
| TC-07 | Password met fout current | Current=wrong + nieuw password | "Je huidige wachtwoord klopt niet" | ⏳ Pest ✅ |
| TC-08 | Valide password-wijziging | Current=correct + nieuw password (8+) + bevestiging | Flash "uitgelogd op andere apparaten"; oud password faalt; nieuw password slaagt | ⏳ Pest ✅ |
| TC-09 | Password min:8 | Nieuw password "kort" | "Minstens 8 tekens" | ⏳ Pest ✅ |
| TC-10 | Password confirmation mismatch | Bevestiging verschilt | "Bevestiging komt niet overeen" | ⏳ Pest ✅ |
| TC-11 | Mass-assignment probe | POST met `role=teamleider` | Rol onveranderd (zorgbegeleider) | ⏳ Pest ✅ |
| TC-12 | 2-browser AC-5 | Login in 2 browsers → wijzig password in browser A → reload browser B | Browser B uitgelogd op volgende request | ⏳ Handmatig (vereist 2 echte sessies) |
| TC-13 | Guest → /profiel | Logout → direct `/profiel` | Redirect `/login` | ⏳ Pest ✅ |
| TC-14 | Teamleider-account | Login teamleider → `/profiel` | Werkt identiek — alleen rol-tekst verschilt in subtitle | ⏳ Pest ✅ |

## 4. Resultaten van de testen (examen-eis 3)

### 4.1 Pest-output

```
PASS  Tests\Feature\US16 (21 tests, 52 assertions — 0.67s)
```

Groene tests o.a.:
- `it('renders profile page with current name and email')`
- `it('accepts unchanged email (own email via unique-ignore)')`
- `it('rejects email change to another users email')`
- `it('rejects password change when current_password is missing')`
- `it('rejects password change when current_password is wrong')`
- `it('accepts password change with correct current_password')`
- `it('ignores role in request body')` + `is_active` + `team_id` (3 probes)
- `it('rotates password-hash so AuthenticateSession invalidates other sessions')`
- `it('shows success flash with devices-logout hint when password changed')`
- `it('rejects PATCH /profiel for guests')`

### 4.2 Full-project run na US-16

```
Tests:    295 passed (800 assertions)
Duration: 3.01s
```

(Branche uit sprint-3 basis: 274 + US-16 21 = 295. US-13, US-14, US-15 zitten op parallelle feature-branches.)

### 4.3 Dekkingsmatrix

| AC | Tests |
|---|---|
| AC-1: profielpagina + pre-fill | 3 |
| AC-2: naam/email update + email-unique | 4 |
| AC-3: password + current_password validatie | 5 |
| AC-4: mass-assignment-probes (role/is_active/team_id) | 3 |
| AC-5: logoutOtherDevices via hash-rotatie + flash | 3 |
| Validation + UI regressie | 3 |
| **Totaal** | **21** |

## 5. Conclusies (examen-eis 4)

### 5.1 Functioneel
Beide rollen kunnen hun eigen profiel beheren: naam + email wijzigen, wachtwoord wijzigen met current_password-verificatie, en bij wachtwoord-wijziging worden andere sessies automatisch uitgelogd via Laravel's `AuthenticateSession`-middleware. De rol (`role`), actief-status (`is_active`) en teamtoewijzing (`team_id`) staan bewust NIET in het formulier — die blijven exclusief teamleider-domein.

### 5.2 Privacy & security

**Drie security-waarborgen in één US:**

1. **`current_password`-verificatie** (AC-3): voorkomt dat iemand die tijdelijk toegang heeft tot je sessie (e.g. je laptop even onbeheerd) je wachtwoord stilletjes kan overnemen. Zonder ook je huidige wachtwoord te weten, lukt de reset niet. Tests 10 + 11 + 12 dekken de missing/wrong/correct-scenarios.

2. **Mass-assignment-blokkade** (AC-4): `UpdateProfielRequest::rules()` bevat geen `role`, `is_active`, of `team_id` → elke POST-body met deze keys wordt structureel genegeerd door Laravel's validatie. Drie aparte probe-tests (15-17) bewijzen dat zelfrol-promotie via `/profiel` onmogelijk is.

3. **`Auth::logoutOtherDevices`** (AC-5): bij password-wijziging roteert de hash én wordt `AuthenticateSession`-middleware geactiveerd om cookies van andere sessies ongeldig te maken. Dit is defense tegen het scenario waarin een dief je password achter de schermen heeft verkregen en parallel ingelogd zit — de gebruiker kan zichzelf "nu iedereen-behalve-hier uitloggen" door simpelweg zijn wachtwoord te veranderen.

### 5.3 Code kwaliteit
- **`forceFill` i.p.v. `$user->update($data)`** maakt expliciet welke velden muteren. Zelfs als toekomstig iemand `role` in `$fillable` zou zetten, blijft deze controller veilig.
- **Laravel built-in `current_password`-rule** — geen zelfbouw comparison, geen side-channel timing issues.
- **Email-unique-ignore** via `Rule::unique('users','email')->ignore($userId)` — zelfde patroon als BSN in US-10, consistent door hele codebase.
- **Password-hash-rotatie via cast `'password' => 'hashed'`** — set plaintext, krijg bcrypt automatisch.

### 5.4 Openstaande punten
- Handmatige 2-browser-test TC-12 (echt sessie invalideren) — vereist Chrome + Safari tegelijkertijd, batch aan het einde.
- Rate-limiting op `/profiel` PATCH tegen brute-force `current_password`-guessing — verbetervoorstel (Laravel RateLimiter).
- 2FA / TOTP — NEN 7510-aanbeveling, verbetervoorstel.
- Email-verificatie na email-change — buiten AC, verbetervoorstel.

### 5.5 Eindoordeel
**US-16 voldoet aan alle AC's en DoD-eisen.** Dit is de **laatste user story** van het project. Alle 16 US's zijn nu functioneel compleet. Na sprint-4 batch-merge staat het project op 355+ tests over 16 US's.

## 6. Analyse van gebruikte informatiebronnen (examen-eis 5)

| Bron | Hoe gebruikt | Invloed |
|---|---|---|
| **Pest-testoutput** | Primair voor AC-dekking | 21/21 groen |
| **Trello AC + DoD** | 5 AC + 5 DoD-items 1-op-1 naar tests | Volledige dekking |
| **OWASP Auth Cheat Sheet — account-mutatie** | Current-password voor password-change | Test 10 + 11 + 12 |
| **OWASP Mass Assignment** | Whitelist-based request validatie | Test 15 + 16 + 17 (role/is_active/team_id-probes) |
| **Laravel docs — `Auth::logoutOtherDevices`** | Ingebouwd via `AuthenticateSession`-middleware | `bootstrap/app.php` aanpassing + test 18 |
| **US-10 BSN-unique-ignore patroon** | `Rule::unique->ignore($id)` voor email | Consistent cross-US |
| **US-15 `Password::reset`-patroon** | `Hash::make` + hash-cast + `forceFill` | Zelfde security-aanpak |

## 7. Interpretatie van bevindingen uit bronnen (examen-eis 6)

1. **Drie security-lagen samen** (current-password + mass-assignment-block + logoutOtherDevices) bewijzen dat US-16 niet alleen functioneel is maar expliciet **defense-in-depth** toepast. Elke laag dekt een ander aanvalsscenario: social engineering (laag 1), broken authorization (laag 2), session theft (laag 3). Pest-tests 10-17 verifieren alle drie lagen afzonderlijk.
2. **`forceFill` + ontbreken van role/is_active/team_id in `UpdateProfielRequest::rules()`** creëert een **whitelist-on-validation én whitelist-on-assignment**: zelfs als een regression iets aan `User::$fillable` verandert, blijven mass-assignment-probes (tests 15-17) falen omdat Laravel de velden nooit uit de validated input haalt. Dit is een "two-belt-and-suspenders"-aanpak tegen OWASP Mass Assignment.
3. **`AuthenticateSession` in `bootstrap/app.php` is een één-regel-feature** die over het hele web-guard-group werkt. Door te kiezen voor deze ingebouwde middleware i.p.v. zelf session-tokens bijhouden, blijft de codebase minimaal en onderhoudbaar. Test 18 bewijst dat de hash-rotatie (de trigger voor session-invalidation) daadwerkelijk gebeurt.
4. **Email-unique-ignore-patroon** is nu voor de derde keer toegepast (US-05 user-edit, US-10 BSN, US-16 profiel). Dit bewijst dat een geheugen-patroon dat in sprint 2 werd vastgelegd doorwerkt tot sprint 4 — de "huis-standaard" is daadwerkelijk hergebruikt, niet elke keer opnieuw uitgevonden.
5. **Password-hash-assertion met `Hash::check($plain, $hash)`** is consistent met US-15 + US-01 — drie US's met password-hantering gebruiken hetzelfde assertion-patroon. Consistentie over sprints heen is een kwaliteitsindicator voor de test-suite.
6. **Laatste US van het project** — momentum blijft technisch kwalitatief op peil. Sprint 4 (US-13 + US-14 + US-15 + US-16) introduceert geen nieuwe migraties op uren-tabellen, geen nieuwe state-machines, geen nieuwe services; alles bouwt voort op Laravel-native + eerder gemaakte infrastructuur. Dit is een bewuste tempo-afname aan het einde van het project — "afronden, niet uitbreiden".

---

**Laatst bijgewerkt:** 2026-04-24 — einde US-16 implementatie, lokaal (sprint-4 batch push volgt direct).
