# Ontwerpdocument — Nexora

> **Project:** Nexora — zorgbegeleidingssysteem voor beschermd wonen
> **Auteur:** Abdisamad (abii2024)
> **Examen:** PvB Software Developer Niveau 4 (14–25 april 2026)
> **Versie:** 1.0 — 21 april 2026

---

## Onderbouwing van gemaakte keuzes (Privacy · Security · Ethiek)

Een onderbouwing van de gemaakte keuzes wordt toegevoegd aan het ontwerpdocument. Hierbij geef ik aan hoe Nexora omgaat met privacy, security en ethiek bij het verwerken van zorgdata.

---

### 1. Privacy

**Kernprincipe** — *Het platform verwerkt gevoelige persoonsgegevens van cliënten (zoals BSN, geboortedatum en adresgegevens). In lijn met de AVG worden deze gegevens alleen opgeslagen voor zover noodzakelijk en zijn ze uitsluitend toegankelijk voor bevoegde medewerkers. Zorgbegeleiders hebben alleen toegang tot de cliënten die aan hen zijn toegewezen.*

#### 1.1 Juridisch kader

Nexora verwerkt **bijzondere persoonsgegevens** in de zin van de AVG (Algemene Verordening Gegevensbescherming):

| AVG-artikel | Betekenis voor Nexora |
|---|---|
| Art. 5 lid 1 (b) — doelbinding | Elk veld in de database heeft een expliciet zorgdoel (bv. BSN voor identificatie bij zorgfinanciering, adres voor huisbezoeken). "Voor het geval dat"-velden zijn verboden. |
| Art. 5 lid 1 (c) — dataminimalisatie | Geen velden opnemen die niet strikt noodzakelijk zijn. Geen GPS-tracking, geen surveillance. |
| Art. 9 — bijzondere persoonsgegevens | Cliëntdossiers (zorgplannen, rapportages) zijn gezondheidsgegevens. Zware beveiliging vereist. |
| Art. 16 — recht op rectificatie | Gebruikers kunnen eigen profielgegevens zelf bijwerken via `/profiel`. |
| Art. 30 — verwerkingsregister | Audit-tabellen (`client_status_logs`, `user_audit_logs`) leggen wie, wat, wanneer vast. |
| Art. 32 — passende maatregelen | Bcrypt-hashing, HTTPS, CSRF-protectie, rol-scoped queries. |
| Art. 87 (NL) — BSN-gebruik | BSN alleen opslaan waar wettelijke grondslag is (zorgfinanciering WMO/WLZ/JW). |
| Wgbo | Dossierplicht 20 jaar — reden waarom cliënten gearchiveerd (soft delete) worden i.p.v. hard gedeletet. |

#### 1.2 Technische implementatie in Nexora

- **Least-privilege op query-niveau**
  Zorgbegeleiders zien uitsluitend de cliënten waar ze via `client_caregivers` aan gekoppeld zijn. Dit wordt afgedwongen in `ClientService::getPaginated()` op SQL-niveau, **niet** in de Blade-view. Een aanvaller die URL's raadt (`/clients/123`) stuit op een 403 via `ClientPolicy@view` — nooit op een lege pagina met alsnog geleakte ID's in query-responses.

- **Dataminimalisatie per feature**
  | Gegeven | Opslaan? | Waarom |
  |---|---|---|
  | BSN | Alleen als `care_type` WMO/WLZ/JW is | Zorgwet vereist identificatie |
  | Geboortedatum | Ja | Identificatie + leeftijdsindicatie bij medicatie |
  | Thuisadres | Ja | Huisbezoeken door begeleider |
  | GPS-locatie bij uren | **Nee** | Surveillance zonder noodzaak |
  | Werkuren-totalen | Ja | Administratieve verantwoording (CAO) |

- **Audit-trail voor accountability**
  Elke status-wijziging (uren, cliëntstatus) en elke wijziging aan medewerkersaccounts wordt vastgelegd in een aparte `*_audit_logs` tabel. Dit stelt Nexora in staat om bij een AVG-inzageverzoek (art. 15) te tonen wie wanneer welke data heeft geraadpleegd of gewijzigd.

- **Bewaartermijnen & soft delete**
  Cliënten worden **gearchiveerd** (`deleted_at` gezet via Laravel's `SoftDeletes`-trait), niet hard-gedeletet. Reden:
  - **Wgbo**: zorgdossiers moeten 20 jaar bewaard blijven
  - **Fiscaal**: loonadministratie en facturatie 7 jaar
  - **Herstel bij fout**: een teamleider die per ongeluk archiveert kan via `/clients/archive` > *"Herstellen"* direct terug
  - **Permanente verwijdering** kan alleen via een Artisan-commando met bevestiging — niet vanuit de UI

- **Scope-isolatie per rol**
  | Rol | Toegangsbereik |
  |---|---|
  | Zorgbegeleider | Alleen eigen toegewezen cliënten (via `client_caregivers.user_id`) + eigen uren + eigen profiel |
  | Teamleider | Alleen medewerkers + uren binnen eigen team (via `users.team_id`) |
  | Administratie | Read-only CSV-export van uren (voor loonadministratie) |
  | Directie | Strategisch read-only overzicht — geen mutaties |

#### 1.3 Rechtsgronden per verwerking

| Verwerking | Rechtsgrond (AVG art. 6) |
|---|---|
| Inloggen / sessie | Uitvoering overeenkomst (art. 6 lid 1 b) — zorgcontract |
| Cliëntdossier | Vitaal belang + wettelijke verplichting zorgverlening (art. 6 lid 1 c/d) |
| Urenregistratie medewerker | Uitvoering arbeidsovereenkomst (art. 6 lid 1 b) |
| Audit-logs | Gerechtvaardigd belang (art. 6 lid 1 f) — accountability |

---

### 2. Security

**Kernprincipe** — *De communicatie verloopt via HTTPS. Wachtwoorden worden opgeslagen met bcrypt-hashing. Laravel biedt ingebouwde bescherming tegen CSRF, SQL-injectie en XSS. API-credentials en gevoelige configuratie worden opgeslagen in environment variables (.env) en niet in de repository.*

#### 2.1 Transportlaag

- **HTTPS verplicht** in productie — Laravel's `TrustProxies` middleware + HSTS-header via `config/secure-headers.php` (of webserver-config)
- **Lokaal ontwikkeling** via Laravel Herd met automatisch TLS-certificaat op `https://nexora.test`
- Geen `http://` redirects — forceer protocol-switch op load-balancer niveau

#### 2.2 Authenticatie & sessies

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

#### 2.3 Laravel-ingebouwde bescherming

- **CSRF**: `@csrf` op elke POST/PUT/DELETE form; `VerifyCsrfToken` middleware actief
- **SQL-injectie**: alle queries via Eloquent / query builder met parameter-binding — **geen** `DB::raw()` met user input
- **XSS**: Blade `{{ }}` escapet automatisch; `{!! !!}` alleen met expliciete rationale
- **Mass-assignment**: `$fillable` whitelist op elk Model; velden zoals `role`, `is_active`, `team_id`, `user_id` staan **nooit** in `$fillable`
- **Clickjacking**: `X-Frame-Options: DENY` via Laravel default middleware
- **Content Security Policy**: restrictieve CSP via middleware

#### 2.4 Autorisatie — defense in depth

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

#### 2.5 Secrets-management

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

#### 2.6 Database-integriteit

- **Unique-indices** op security-kritische velden:
  - `users.email` unique
  - `clients.bsn` unique (indien ingevuld)
  - `client_caregivers (client_id, user_id)` unique
  - `client_caregivers` partial unique index voor max 1 primair + 1 secundair
- **Foreign keys** met expliciete cascade-regels:
  - `ON DELETE CASCADE` waar kinderen zinloos zijn zonder ouder
  - `ON DELETE SET NULL` waar historie moet blijven
- **Transacties** rond multi-step writes (`DB::transaction(fn() => ...)`) — geen partial writes

#### 2.7 Logging

- **Laravel logs** in `storage/logs/` (niet in repo)
- **Geen wachtwoorden, tokens of BSN** in logs — custom LogProcessor scrubt deze
- **Failed login attempts** → rate-limiting via Laravel's `throttle` middleware

---

### 3. Ethiek

**Kernprincipe** — *Het platform biedt alleen geautoriseerde gebruikers toegang tot de juiste informatie op basis van hun rol. Zorgbegeleiders zien alleen hun eigen cliënten en teamleiders beheren alleen hun eigen team. Dit waarborgt een eerlijke en verantwoorde verwerking van zorgdata.*

#### 3.1 Rolscheiding als zorgprincipe

In de zorg is het schadelijk en oneerlijk wanneer een medewerker inzage heeft in dossiers van cliënten die niet onder zijn verantwoordelijkheid vallen. Niet alleen juridisch (AVG), maar ook **professioneel**: het beroepsgeheim van een zorgprofessional beperkt zich tot de eigen zorgrelatie.

Nexora vertaalt dit in:
- **Technische onmogelijkheid** i.p.v. beleidsregel — een zorgbegeleider kan andere cliënten niet inzien, óók niet uit nieuwsgierigheid
- **Een centraal beleidsbestand** (`Policies/`) — elke autorisatie-beslissing staat op één plek en is auditeerbaar
- **Expliciete 403-pagina** — bevestigt dat toegang geweigerd is, zonder details te lekken over waarom

#### 3.2 Continuïteit van zorg

Cliënten zijn kwetsbaar; onderbreking van zorg is ethisch onverdedigbaar.

- **Primair / secundair / tertiair begeleider** — elke cliënt heeft minstens één primaire + één vervanger. Bij vakantie of ziekte van de primair blijft er altijd iemand verantwoordelijk.
- **Soft delete i.p.v. hard delete** — als een medewerker uit dienst gaat, blijft de historie traceerbaar voor de cliënt (*"wie was mijn begeleider in 2026?"*).
- **Status-logs bij cliënten** — als een cliënt op de wachtlijst wordt gezet, is zichtbaar waarom en door wie.

#### 3.3 Transparantie & autonomie

- **Verplichte reden bij afkeuring van uren** (min 10 tekens) — een zorgbegeleider heeft recht op uitleg waarom zijn gewerkte uren worden afgewezen. Silent rejects zijn oneerlijk.
- **Eigenaarschap over eigen tijd** — een zorgbegeleider kan ingediende uren **terugtrekken** tot goedkeuring. De teamleider heeft geen absolute macht tijdens de review-fase.
- **Notificaties bij elke koppeling** — een zorgbegeleider wordt **gewaarschuwd** wanneer hij aan een nieuwe cliënt wordt gekoppeld. Geen stille toewijzingen achter zijn rug om.
- **Zelfbeheer van profiel** — gebruikers kunnen eigen naam, e-mail en wachtwoord bijwerken zonder beheerder. Tegelijkertijd kunnen ze hun eigen rol of `is_active`-status **niet** wijzigen: geen privilege escalation, maar ook geen selfsabotage.

#### 3.4 Eerlijke werkverdeling

Het teamleider-overzicht toont per medewerker de totalen per week. Dit maakt workload-verdeling **zichtbaar** — een teamleider die stelselmatig structureel meer aan één begeleider toeschuift kan daarop aangesproken worden.

#### 3.5 Geen surveillance

Bewust **niet** geïmplementeerd in Nexora:

- Geen GPS-tracking bij uren-registratie
- Geen automatische check-in via IP-detectie
- Geen fine-grained activity logging ("wie klikt wanneer op welke knop")
- Geen screenshots of keylogging

De zorgbegeleider registreert **zelf** zijn tijd. Vertrouwen > controle.

#### 3.6 Rechtvaardige beslissingen

- **Self-demotion guard** — een teamleider kan zijn eigen rol niet verlagen naar zorgbegeleider. Dit voorkomt een complete lockout van het teamleider-dashboard en beschermt de organisatie tegen per-ongeluk-verlies van beheerders.
- **Herstelbare archivering** — een gearchiveerde cliënt kan zonder dataverlies hersteld worden. Een fout wordt niet bestraft met datavernietiging.
- **Geen permanente delete vanuit UI** — voorkomt impulsief of boos wissen van dossiers.

#### 3.7 Evenwicht tussen beheer en autonomie

Nexora is een **beheertool**, geen **surveillancesysteem**. Elke feature is getoetst aan de vraag:

> *"Helpt dit de zorgbegeleider zijn werk beter te doen, of controleert het alleen zijn werk?"*

Features die enkel controleren zonder te helpen — zoals GPS-verificatie, automatische aanwezigheidsdetectie, tijd-tracking op minuten-niveau — zijn bewust buiten scope gehouden en **zullen ook niet** als verbetervoorstel worden ingediend.

---

## Definition of Done

Voor de complete Definition of Done die voor alle 16 user stories geldt, zie: **[docs/definition-of-done.md](definition-of-done.md)**.

---

**Einde ontwerpdocument — onderbouwing Privacy · Security · Ethiek.**
