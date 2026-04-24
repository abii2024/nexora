# Testplan US-15 — Wachtwoord vergeten & resetten via e-maillink

> **Sprint 4 — US #3**
> **Branch:** `feature/wachtwoord-vergeten`
> **User story:** Als zorgbegeleider of teamleider wil ik mijn wachtwoord kunnen resetten via een e-maillink zodat ik weer toegang krijg tot Nexora zonder mijn oude wachtwoord te hoeven weten.

## 1. Soorten testen uitgevoerd (examen-eis 1)

| Soort | Wat | Waarom |
|---|---|---|
| **Pest feature tests** | `tests/Feature/US-15.php` — 16 tests / 46 asserts | Dekt AC-1..5 + enumeration-protection + token-lifecycle |
| **Notification-fake** | `Notification::fake()` + `assertSentTo()` | Valideert database/mail dispatch zonder echte SMTP |
| **Hash-asserties** | `Hash::check()` + bcrypt-prefix check | Bewijst geen plaintext-opslag |
| **Handmatige browser-tests** | Batch aan einde | Mail renderen, Gmail-voorvertoning, UX van 2-staps flow |

## 2. Test-gebruikers / test-data

**Factories:** `UserFactory->zorgbegeleider()/teamleider()` (uit US-01).

**Seeders (handmatig):** `teamleider@nexora.test` / `zorgbegeleider@nexora.test` (wachtwoord `password`).

**In development** staat `MAIL_MAILER=log` in `.env.example` → mails komen in `storage/logs/laravel.log` en zijn zichtbaar zonder echte SMTP-server.

## 3. Handmatige testscenario's (examen-eis 2)

| # | Scenario | Stappen | Verwacht | Werkelijk |
|---|---|---|---|---|
| TC-01 | Link op login | Open `/login` | "Wachtwoord vergeten?"-link zichtbaar → klik leidt naar `/wachtwoord-vergeten` | ⏳ Pest ✅ |
| TC-02 | Reset-link versturen (bestaand) | Vul `teamleider@nexora.test` in | Redirect + groene flash; reset-link in `storage/logs/laravel.log` | ⏳ Pest ✅ |
| TC-03 | Reset-link versturen (onbekend) | Vul `niemand@nexora.test` in | **Zelfde** flash als TC-02; log bevat NIETS | ⏳ Pest ✅ |
| TC-04 | Ongeldig e-mailformaat | Vul `geen-email` in | 422 "Dit is geen geldig e-mailadres" | ⏳ Pest ✅ |
| TC-05 | Reset-form openen | Klik link uit de mail-log | Formulier met token + email pre-filled | ⏳ Pest ✅ |
| TC-06 | Wachtwoord te kort | Voer "kort" in (4 chars) | 422 "minstens 8 tekens" | ⏳ Pest ✅ |
| TC-07 | Bevestiging mismatch | Vul twee verschillende wachtwoorden | 422 "bevestiging komt niet overeen" | ⏳ Pest ✅ |
| TC-08 | Valide reset zorgbegeleider | 8+ chars + bevestigd + submit | Redirect `/dashboard`; login-state actief | ⏳ Pest ✅ |
| TC-09 | Valide reset teamleider | Idem met teamleider-account | Redirect `/teamleider/dashboard` | ⏳ Pest ✅ |
| TC-10 | Token-hergebruik | Doe valide reset → terug naar formulier → zelfde token | 422 email-error; wachtwoord niet opnieuw veranderd | ⏳ Pest ✅ |
| TC-11 | Verlopen token | Wacht 60 min + submit | 422 email-error "expired"; oud wachtwoord blijft actief | ⏳ Handmatig (tijdsafhankelijk) |
| TC-12 | BCrypt hash-check | Na reset: `tinker >>> User::first()->password` | String begint met `$2y$` (bcrypt-prefix) | ⏳ Pest ✅ |
| TC-13 | Ingelogde user probeert forgot-form | Login teamleider → `/wachtwoord-vergeten` | Redirect weg (guest-middleware) | ⏳ Pest ✅ |

## 4. Resultaten van de testen (examen-eis 3)

### 4.1 Pest-output

```
PASS  Tests\Feature\US15 (16 tests, 46 assertions — 1.93s)
```

Groene tests o.a.:
- `it('sends a reset notification when the email exists')`
- `it('returns the same flash for a non-existent email (enumeration protection)')`
- `it('flashes the same UX message whether or not the email exists')`
- `it('resets the password with a valid token and stores a bcrypt hash')`
- `it('rejects a reset with an invalid token and keeps the old password')`
- `it('rejects reusing a token after successful reset')`
- `it('logs in and redirects teamleider to /teamleider/dashboard after successful reset')`
- `it('redirects authenticated users away from the forgot-password form')`

### 4.2 Full-project run na US-15

```
Tests:    290 passed (794 assertions)
Duration: 4.65s
```

(Branche uit sprint-3 basis: 274 + US-15 16 = 290. US-13 + US-14 zitten op parallelle feature-branches en komen in de sprint-4 merge erbij.)

### 4.3 Dekkingsmatrix

| AC | Tests |
|---|---|
| AC-1: forgot-flow + notification | 2 |
| AC-2: enumeration protection (zelfde flash) | 2 |
| AC-3: bcrypt-hash + min:8 + confirmed | 3 |
| AC-4: invalid/reused/non-existent token | 3 |
| AC-5: auto-login + rol-redirect | 2 |
| UI + validatie regressie | 4 |
| **Totaal** | **16** |

## 5. Conclusies (examen-eis 4)

### 5.1 Functioneel
Beide rollen kunnen hun wachtwoord resetten via e-mail. Flow: `/login` → "Wachtwoord vergeten?" → e-mail invoeren → flash-melding → link in mail → nieuwe password invoeren (8+ chars, confirmed) → auto-login + rol-specifiek dashboard. NL mail-template via `resources/views/emails/wachtwoord-reset.blade.php` gebruikt Laravel mail-components.

### 5.2 Privacy & security
- **User-enumeration protection**: identieke flash-melding voor bestaande en onbekende e-mailadressen (test 3 + 4 vergelijken letterlijk de strings). Ook het Notification-fake check bewijst dat niet-bestaande users NOOIT notification triggeren.
- **BCrypt-hash enforcement**: via `User::$casts['password' => 'hashed']` en `Password::reset`'s callback — test 7 checkt expliciet dat `$user->password` start met `$2y$` (Laravel default bcrypt rounds=12).
- **Token-lifecycle**: 60 minuten geldig (Laravel default in `config/auth.php`), eenmalig gebruikt (test 10 bewijst hergebruik faalt), gekoppeld aan e-mail + user-id.
- **Geen plaintext in logs**: `MAIL_MAILER=log` drukt alleen de gerenderde mail met reset-link af; het wachtwoord zelf gaat via POST en wordt nooit gelogd.
- **`forceFill` voor password-update**: `password` + `remember_token` worden expliciet gezet, geen mass-assignment via `$fillable`.

### 5.3 Code kwaliteit
- **Laravel's eigen `Password::sendResetLink` + `Password::reset`** hergebruikt — geen eigen token-management, minder kans op subtle bugs.
- **NL-translate via `sendPasswordResetNotification`-override** op User-model i.p.v. via `ResetPassword::toMailUsing()` global callback — houdt rol-specifieke override duidelijk in het model.
- **Form Requests** splitsen validation van controller-logica; NL messages centraal.
- **Mail-template** gebruikt Laravel's ingebouwde markdown-components (`@component('mail::message')`) → consistent design zonder zelfbouw HTML.

### 5.4 Openstaande punten
- Handmatige TC-11 (verlopen token) is tijdsafhankelijk — kan in productie via `Travel::to(now()->addMinutes(61))` in Pest, maar valt buiten kern-AC.
- Rate-limiting op `/wachtwoord-vergeten` POST (om mail-spam te voorkomen) is verbetervoorstel; Laravel's `RateLimiter` kan hier worden gehangen.
- Mail via echte SMTP (Mailhog of production SES) — voor examen is `MAIL_MAILER=log` voldoende; productie-setup valt buiten scope.

### 5.5 Eindoordeel
**US-15 voldoet aan alle AC's en DoD-eisen.** Enumeration-protection is expliciet getest en onderbouwd. Bcrypt-opslag is codified via tests. Token-lifecycle via Laravel's ingebouwde mechaniek.

## 6. Analyse van gebruikte informatiebronnen (examen-eis 5)

| Bron | Hoe gebruikt | Invloed |
|---|---|---|
| **Pest-testoutput** | Primair voor AC-dekking + regressie | 16/16 groen |
| **Trello AC + DoD** | 5 AC + 6 DoD-items 1-op-1 naar tests | Volledige dekking |
| **Laravel security docs (Password::sendResetLink)** | Best-practice voor token-flow | Geen eigen token-code nodig |
| **OWASP Auth Cheat Sheet — Account Enumeration** | Onderbouwing voor zelfde-flash-strategie | Test 3 + 4 verankeren het gedrag |
| **AVG art. 32 (passende beveiliging)** | Bcrypt verplicht voor wachtwoorden | Test 7 bewijst hash-opslag |
| **US-01 login-flow** | Login-pagina reeds klaar — alleen link activeren | Geen duplicatie van view-logica |
| **`user-stories.md`** | US-tekst: voor beide rollen | Tests 11 + 12 (zorgbeg + teamleider beide) |

## 7. Interpretatie van bevindingen uit bronnen (examen-eis 6)

1. **OWASP + Pest in combinatie** bewijzen dat enumeration-protection niet alleen documenteerd is maar ook *executable-gevalideerd*: test 4 vergelijkt letterlijk de `status`-strings van twee requests en faalt bij elke divergentie. Een toekomstige refactor die per ongeluk "Er bestaat geen account met dit e-mailadres" terugstuurt voor niet-bestaande users, zou hier direct rood worden.
2. **Laravel-native Password-broker + NL-wrapper** laat zien dat taal-localisatie ideaal in het User-model zelf hoort (`sendPasswordResetNotification`-override) in plaats van via een globale `ResetPassword::toMailUsing()`-callback. Dit houdt het overridable per rol (bijv. later een verkorte teamleider-mail zonder copywriting te veranderen) zonder globale side-effects.
3. **Bcrypt-prefix-assertion** (test 7: `$user->password` start met `$2y$`) is **explicieter** dan alleen `Hash::check($plain, $hash)`: als een toekomstige refactor plaintext-opslag introduceert, zou `Hash::check` ook gewoon true kunnen retourneren (als de "hash" toevallig gelijk is aan plaintext). De prefix-check is een harder contract.
4. **Token-hergebruik-test (test 10)** kwam uit een **bug-scenario**: tijdens development brak ik per ongeluk de auto-login door `Auth::login()` buiten de `Password::reset`-callback te plaatsen. Het resultaat was dat `password_reset_tokens` niet werd opgeruimd. De test codificeert nu "gebruikt token mag niet opnieuw werken" zodat zo'n regressie direct opvalt.
5. **Rol-specifieke redirect na reset** hergebruikt de logica uit `LoginController` (US-01): beide flows sturen zorgbeg → `/dashboard` en teamleider → `/teamleider/dashboard`. Dit is een **consistent mentaal model** voor de gebruiker: "na login ben je op je dashboard" werkt ook na reset. Tests 11 + 12 dekken beide rollen.
6. **Voorkeur voor `MAIL_MAILER=log` in development** haalt infrastructuur-complexiteit weg: geen Mailhog, geen SMTP-config, geen externe service. Voor examen-reviewers is `storage/logs/laravel.log` voldoende bewijs dat de mail daadwerkelijk geproduceerd wordt zonder dat er credentials gedeeld hoeven worden.

---

**Laatst bijgewerkt:** 2026-04-24 — einde US-15 implementatie, lokaal (sprint-4 batch pending na US-16).
