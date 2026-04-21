# Beveiliging — Ontwerpdocument Nexora

> Onderdeel van het [ontwerpdocument](../ontwerpdocument.md) — PvB Software Developer Niveau 4

---

**Kernprincipe** — *De communicatie verloopt via HTTPS. Wachtwoorden worden opgeslagen met bcrypt-hashing. Laravel biedt ingebouwde bescherming tegen CSRF, SQL-injectie en XSS. API-credentials en gevoelige configuratie worden opgeslagen in environment variables (.env) en niet in de repository.*

---

## 1. Transportlaag

- **HTTPS verplicht** in productie — Laravel's `TrustProxies` middleware + HSTS-header via `config/secure-headers.php` (of webserver-config)
- **Lokaal ontwikkeling** via Laravel Herd met automatisch TLS-certificaat op `https://nexora.test`
- Geen `http://` redirects — forceer protocol-switch op load-balancer niveau

## 2. Authenticatie & sessies

| Maatregel | Implementatie |
|---|---|
| Wachtwoord-hashing | `Hash::make($plain)` — Laravel default = **bcrypt** met automatische salt |
| Wachtwoord-verificatie | `Hash::check($plain, $hash)` — constant-time vergelijking |
| Minimale lengte | 8 tekens (`StoreTeamMemberRequest` + `ResetPasswordController`) |
| Sessie-fixatie-protectie | `session()->regenerate()` na succesvolle login |
| Sessie-invalidatie | `session()->invalidate()` bij logout + bij deactivatie (User Story 12) |
| User enumeration | Identieke foutmelding voor onbekende e-mail / fout wachtwoord |
| Account-deactivatie | `is_active = false` — `CheckActiveUser` middleware checkt dit op elke request |
| Wachtwoord-reset tokens | Eenmalig + tijd-beperkt (60 min) via Laravel's `Password::sendResetLink()` |

## 3. Laravel-ingebouwde bescherming

- **CSRF**: `@csrf` op elke POST/PUT/DELETE form; `VerifyCsrfToken` middleware actief
- **SQL-injectie**: alle queries via Eloquent / query builder met parameter-binding — **geen** `DB::raw()` met user input
- **XSS**: Blade `{{ }}` escapet automatisch; `{!! !!}` alleen met expliciete rationale
- **Mass-assignment**: `$fillable` whitelist op elk Model; velden zoals `role`, `is_active`, `team_id`, `user_id` staan **nooit** in `$fillable`
- **Clickjacking**: `X-Frame-Options: DENY` via Laravel default middleware
- **Content Security Policy**: restrictieve CSP via middleware

## 4. Autorisatie — defense in depth

Elke autorisatie-beslissing in Nexora loopt door **drie** lagen:

```
Request → auth-middleware → rol-middleware → Policy → Controller → Service
           (ingelogd?)       (juiste rol?)   (resource?)  (uitvoeren)
```

Voorbeeld voor `DELETE /clients/{id}`:

1. `auth` middleware → 302 redirect naar `/login` als niet ingelogd
2. `zorgbegeleider` / `teamleider` middleware → 403 als verkeerde rol
3. `ClientPolicy@delete` → 403 als cliënt niet van deze teamleider
4. `ClientService::archive()` → alleen dan uitvoeren

Een aanvaller moet **alle** lagen omzeilen om data te bereiken.

## 5. Secrets-management

- **`.env`** staat in `.gitignore` — nooit in de repo
- **`.env.example`** documenteert vereiste keys zonder waarden:
  ```
  APP_KEY=
  DB_CONNECTION=sqlite
  MAIL_MAILER=log
  MAIL_FROM_ADDRESS=no-reply@nexora.test
  ```
- **`config()`** in app-code, `env()` uitsluitend in `config/*.php`-files (zodat config caching werkt)
- **`APP_KEY`** is cryptografisch random en wordt bij deployment gegenereerd

## 6. Database-integriteit

- **Unique-indices** op security-kritische velden:
  - `users.email` unique
  - `clients.bsn` unique (indien ingevuld)
  - `client_caregivers (client_id, user_id)` unique
  - `client_caregivers` partial unique index voor max 1 primair + 1 secundair
- **Foreign keys** met expliciete cascade-regels:
  - `ON DELETE CASCADE` waar kinderen zinloos zijn zonder ouder
  - `ON DELETE SET NULL` waar historie moet blijven
- **Transacties** rond multi-step writes (`DB::transaction(fn() => ...)`) — geen partial writes

## 7. Logging

- **Laravel logs** in `storage/logs/` (niet in repo)
- **Geen wachtwoorden, tokens of BSN** in logs — custom LogProcessor scrubt deze
- **Failed login attempts** → rate-limiting via Laravel's `throttle` middleware

---

**Zie ook:** [Gegevensbescherming](gegevensbescherming.md) · [Verantwoorde verwerking](verantwoorde-verwerking.md) · [Ontwerpdocument (index)](../ontwerpdocument.md)
