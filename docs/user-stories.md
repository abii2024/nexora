# User stories — Nexora

> **Project:** Nexora — zorgbegeleidingssysteem voor beschermd wonen  
> **Auteur:** Abdisamad (abii2024)  
> **Bron:** [Trello — Nexora-platform](https://trello.com/b/ILocXzfF/nexora-platform), lijst *sprint backlog* (op prioriteit)  
> **Totaal:** 16 user stories — volgorde = prioriteit (hoog → laag)

De volgorde van deze user stories is gelijk aan de volgorde in de Trello-lijst *sprint backlog*. Hoogste prioriteit (authenticatie + rolbeheer) staat bovenaan, laagste prioriteit (profielbeheer en wachtwoord-reset) onderaan. Screenshots van de sprint backlog staan in [`sprint-backlog-screenshots/`](sprint-backlog-screenshots/).

---

## US-01 — Inloggen op Nexora (zorgbegeleider + teamleider)

**User Story**
Als zorgbegeleider of teamleider wil ik met mijn e-mailadres en wachtwoord kunnen inloggen op Nexora zodat ik veilig toegang krijg tot de cliëntgegevens en functionaliteiten die bij mijn rol horen.

**Omschrijving**
1. `/login` toont een formulier met e-mail, wachtwoord, "wachtwoord vergeten"-link en inlog-knop.
2. Sessie wordt aangemaakt via `Auth::attempt()` + `session()->regenerate()` (session fixation protection).
3. Teamleider → `teamleider.dashboard`; zorgbegeleider → `dashboard` (op basis van `$user->role`).
4. Foute credentials: blijft op `/login` met foutmelding, wachtwoordveld leeg, e-mail onthouden.
5. Gedeactiveerde accounts (`is_active = false`) kunnen niet inloggen; eigen melding, geen DB-check (timing attack protection).

**Privacy / Security / Ethiek**
- Wachtwoorden bcrypt-gehasht via `Hash::make()`.
- CSRF-token op elk loginformulier.
- Sessie-ID geregenereerd na login en ongeldig gemaakt bij logout.
- Identieke foutmelding voor onbekende e-mail en fout wachtwoord (user enumeration protection).

---

## US-02 — Rolgebaseerde toegang (autorisatie via Policies + middleware)

**User Story**
Als organisatie wil ik dat zorgbegeleiders alleen hun eigen cliënten en taken zien en dat teamleiders alleen hun eigen team beheren zodat gevoelige cliëntinformatie niet lekt naar onbevoegde medewerkers.

**Omschrijving**
1. Routes krijgen middleware `auth` + `zorgbegeleider` of `teamleider` waar nodig.
2. Resource-autorisatie (wie mag welke cliënt/rapport) via Laravel Policies (`ReportPolicy`, `TaskPolicy`, nieuwe `ClientPolicy`).
3. Zorgbegeleiders zien alleen cliënten waar ze via `client_caregivers.user_id = auth()->id()` aan gekoppeld zijn — afgedwongen in `ClientService` (query-scope), niet in de UI.
4. Teamleiders zien alleen hun eigen team-data (`users.team_id = auth()->user()->team_id`).
5. 403-situaties leiden naar `errors/403.blade.php` met Nederlandse melding, geen stacktrace.

**Privacy / Security / Ethiek**
- AVG-dataminimalisatie: alleen strikt noodzakelijke toegang tot cliëntdossiers.
- Least privilege principe via Policies (auditeerbaar beleid per resource).
- Geen technische details in 403-pagina's (geen info leak).

---

## US-03 — Nieuwe zorgbegeleider aanmaken

**User Story**
Als teamleider wil ik nieuwe zorgbegeleiders kunnen toevoegen met een initieel wachtwoord zodat ik het team kan uitbreiden zonder systeembeheerder.

**Omschrijving**
1. /team/create formulier: voornaam+achternaam, email (uniek), rol (default zorgbegeleider), dienstverband (intern/extern/zzp), wachtwoord (min 8) + bevestiging.
2. Validatie via StoreTeamMemberRequest: email unique, password confirmed, role in ['zorgbegeleider','teamleider'] (geen willekeurige rollen).
3. Hash::make() voor wachtwoord (bcrypt); is_active=true; team_id = auth()->user()->team_id (nieuwe user bij eigen team).
4. Redirect /team met flash succes; validatiefouten → oude invoer behouden, wachtwoordvelden leeg.
5. Initieel wachtwoord wordt veilig gecommuniceerd buiten de app; e-mailverzending out-of-scope → /docs/verbetervoorstellen.md (Should have).

**Privacy / Security / Ethiek**
- bcrypt (never plaintext), wachtwoord niet in response body of logs.
- Rol whitelist voorkomt privilege escalation (geen admin via form).
- Unique email voorkomt duplicaten.

---

## US-04 — Medewerkersoverzicht met zoek en filter

**User Story**
Als teamleider wil ik een overzicht van alle medewerkers binnen mijn organisatie met zoek- en filtermogelijkheden zodat ik snel de juiste collega vind en teamsamenstelling inzichtelijk is.

**Omschrijving**
1. /team toont tabel: naam · e-mail · rol · dienstverband · status · aangemaakt op. Alleen teamleider (EnsureTeamleider middleware).
2. Zoekbalk op name/email (LIKE %term%) + filters: rol (zorgbegeleider/teamleider) en status (Actief/Inactief).
3. Query-scope: teamleider ziet alle medewerkers van eigen org. Zorgbegeleider → 403.
4. Pageable (25/pagina), default sortering name ASC, inactieve users onderaan met grijze badge.
5. Header: teller ("X actief · Y inactief") + knop "Medewerker toevoegen" → /team/create.

**Privacy / Security / Ethiek**
- Rol-scope via middleware + UserPolicy (defense in depth).
- XSS protection: Blade {{ }} escapes zoekterm.
- Lege-state = goede UX.

---

## US-05 — Teamlid bewerken (rol + dienstverband)

**User Story**
Als teamleider wil ik gegevens van bestaande teamleden kunnen bijwerken (naam, email, rol, dienstverband) zodat het medewerkersregister actueel blijft bij functie- of contractwijzigingen.

**Omschrijving**
1. /team/{id}/edit toont formulier met alle velden voorgevuld, EXCLUSIEF wachtwoord (dat doet medewerker via /profiel).
2. Rol dropdown: zorgbegeleider ↔ teamleider. Dienstverband: intern/extern/zzp.
3. Validatie via UpdateTeamMemberRequest met unique:users,email,{id} — eigen email behouden mag.
4. Self-demotion guard: teamleider kan zijn eigen rol niet naar zorgbegeleider zetten (voorkomt lockout).
5. Redirect /team met flash "Medewerker bijgewerkt"; elke veldwijziging gelogd in user_audit_logs (changed_by, field, old, new).

**Privacy / Security / Ethiek**
- Audit-trail = AVG art. 30 (accountability).
- Self-demotion protection = systeemintegriteit.
- Geen wachtwoord in edit-form = separation of concerns.

---

## US-06 — Teamlid deactiveren en heractiveren

**User Story**
Als teamleider wil ik medewerkers kunnen deactiveren bij uitdiensttreding zodat ze niet meer kunnen inloggen of cliëntdata kunnen benaderen, maar hun historische gegevens behouden blijven voor audit en bewaartermijnen.

**Omschrijving**
1. /team/{id} show-pagina heeft Deactiveren-knop (of Heractiveren bij inactief) met confirmatiedialog.
2. POST /team/{id}/deactivate → is_active=false. Geen soft delete — user blijft in tabel met relaties intact (Wgbo 20-jaar dossierplicht).
3. Bij deactivatie wordt alle actieve sessies van target user direct geïnvalideerd (logoutOtherDevices of session-tabel legen).
4. LoginController checkt is_active=true bij login-poging; inactief → "Dit account is gedeactiveerd." (sluit aan bij User Story 1).
5. Heractiveren zet is_active=true terug; medewerker kan weer inloggen met bestaande wachtwoord.

**Privacy / Security / Ethiek**
- Wgbo-bewaarplicht: geen hard delete.
- Directe sessie-invalidatie = geen window voor gedeactiveerde user.
- Soft-reactivatie zonder wachtwoord-reset = gemak bij tijdelijke deactivatie.

---

## US-07 — Cliënt aanmaken met persoonsgegevens

**User Story**
Als teamleider wil ik nieuwe cliënten registreren met hun persoonsgegevens en zorgtype zodat ik het cliëntenbestand van mijn organisatie centraal kan opbouwen en de juiste zorg kan organiseren.

**Omschrijving**
1. `/clients` knop "Cliënt toevoegen" is enkel zichtbaar voor teamleiders (ClientPolicy@create).
2. `/clients/create` toont Blade-formulier met secties Persoonlijk, Contact en Zorg (status + care_type WMO/WLZ/JW).
3. Validatie via `StoreClientRequest`: voornaam + achternaam verplicht, BSN uniek (9 cijfers), geboortedatum in verleden, geldig e-mailformaat.
4. Opslaan via `ClientService::create()` binnen DB-transactie; `created_by_user_id = auth()->id()` voor audit.
5. Redirect naar `/clients/{id}` met flash "Cliënt aangemaakt."; validatiefouten behouden oude invoer via `old()`.

**Privacy / Security / Ethiek**
- Cliëntgegevens zijn bijzondere persoonsgegevens (AVG art. 9) — dataminimalisatie toegepast.
- BSN-validatie op uniek + 9 cijfers; voorbereid voor encryptie-at-rest in vervolg-story.
- Mass-assignment protection via `$fillable` whitelist.
- CSRF op form; autorisatie via Policy in plaats van inline `abort_unless`.

---

## US-08 — Cliënten koppelen aan begeleiders (primair / secundair / tertiair)

**User Story**
Als teamleider wil ik één of meer zorgbegeleiders aan een cliënt kunnen koppelen met een rol (primair, secundair, tertiair) zodat duidelijk is wie de hoofdverantwoordelijke zorgbegeleider is en de cliënt altijd vervangend contact heeft bij afwezigheid.

**Omschrijving**
1. Sectie "Begeleiders" op create/edit met checkboxes per zorgbegeleider, gegroepeerd per team, plus "Maak primair"-knop per aangevinkte begeleider.
2. Pivot-tabel `client_caregivers` (client_id, user_id, role, created_by_user_id) met unique(client_id, user_id) + partial unique voor max 1 primair en 1 secundair.
3. `ClientService::computeCaregiverRoles()`: eerste/gekozen = primary, tweede = secondary, rest = tertiary.
4. `syncCaregivers()` gebruikt delete-insert patroon binnen DB::transaction() — geen partial writes.
5. Elke nieuwe gekoppelde begeleider krijgt `ClientCaregiverAssignedNotification` (**alleen** database-channel — externe mail-verzending valt buiten exam-scope en staat in verbetervoorstellen.md).

**Privacy / Security / Ethiek**
- Continuïteit van zorg: primair/secundair garandeert vervanging bij afwezigheid.
- Notificaties = transparantie naar begeleider over eigen caseload.
- DB-niveau constraints voorkomen race conditions bij concurrent edits.
- Alleen teamleiders kunnen koppelen (ClientPolicy + middleware).

---

## US-09 — Cliëntenoverzicht met rol-gebaseerde weergave, zoek en filter

**User Story**
Als gebruiker van Nexora wil ik een cliëntenoverzicht zien dat past bij mijn rol en waarin ik kan zoeken en filteren zodat ik snel de juiste cliënt vind zonder overzicht te verliezen over mijn caseload.

**Omschrijving**
1. `GET /clients` → teamleider ziet tabel (naam, status, care_type, dob, primaire begeleider, tel); zorgbegeleider ziet alleen eigen cliënten in kaartweergave.
2. Scoping server-side in `ClientService::getPaginated()`: zorgbegeleider join op `client_caregivers.user_id = auth()->id()`; teamleider filter op eigen team_id.
3. Zoekbalk op name/last_name (LIKE %term%) + statusfilter (Alle/Actief/Wachtlijst/Inactief) + zorgtype-filter (WMO/WLZ/JW); filters blijven bij paginatie via query-params.
4. Rolspecifieke banner: zorgbegeleider ziet info-tekst, teamleider ziet totaal + "Cliënt toevoegen"-knop.
5. Paginated (15 per pagina) + sorteerbaar (naam default, status, created_at); eager loading `with('caregivers.user', 'team')` voorkomt N+1.

**Privacy / Security / Ethiek**
- AVG-dataminimalisatie: query-scope op DB-niveau, niet in UI-filter — geen data-leak via URL-manipulatie.
- SQL-injection protection: alle queries via Eloquent parameter-binding.
- XSS-protection: Blade {{ }} escape ook zoekterm.
- N+1 voorkomen: performance + minder DB-load.

---

## US-10 — Cliënt bewerken en archiveren (statusbeheer + soft delete)

**User Story**
Als teamleider wil ik cliëntgegevens kunnen bijwerken, de status wijzigen (Actief/Wachtlijst/Inactief) en cliënten kunnen archiveren zodat het cliëntenbestand accuraat blijft en ex-cliënten uit het actieve overzicht verdwijnen zonder dat hun historische data verloren gaat.

**Omschrijving**
1. `/clients/{id}/edit` laat alle velden + caregiver-koppelingen bewerken; validatie via UpdateClientRequest met unique:clients,bsn,{id} (eigen BSN behouden mag).
2. Statuswissel dropdown (actief ↔ wacht ↔ inactief); wijziging gelogd in `client_status_logs` (client_id, old_status, new_status, changed_by_user_id, changed_at).
3. Knop "Archiveren" doet soft delete via SoftDeletes trait; `deleted_at` wordt gezet, cliënt verdwijnt uit /clients overzicht.
4. `/clients/archive` toont alleen gearchiveerde cliënten (`Client::onlyTrashed()`) met "Herstellen"-knop die `restore()` aanroept.
5. Permanente verwijdering is NIET mogelijk vanuit UI — dataverlies-preventie. Alleen via Artisan met confirmatie (buiten scope).

**Privacy / Security / Ethiek**
- Audit-trail via client_status_logs = AVG art. 30 (verwerkingsregister).
- Soft delete = Wgbo 20-jaar dossierplicht geborgd.
- Confirmatie-dialog bij archiveren = ethische drempel tegen ongelukken.
- Herstelbare archivering = recht op rectificatie (AVG art. 16).

---

## US-11 — Concept-uren aanmaken en bewerken

**User Story**
Als zorgbegeleider wil ik mijn gewerkte uren per cliënt kunnen vastleggen als concept zodat ik mijn tijd nauwkeurig kan bijhouden voordat ik deze ter goedkeuring aanbied.

**Omschrijving**
1. `/uren` overzicht met tabs Concepten/Ingediend/Goedgekeurd/Afgekeurd + knop "Uren toevoegen".
2. `/uren/create` formulier: cliënt (eigen toegewezen), datum, start/eindtijd, notities. Validatie via StoreUrenregistratieRequest.
3. Server-side berekent duur (eind - start) in decimaal; eind < start → fout "Eindtijd moet na starttijd liggen".
4. Nieuwe entries krijgen altijd status=Concept + user_id=auth()->id() (service, geen mass-assignment).
5. Bewerken alleen bij status ∈ {Concept, Afgekeurd}; ingediend/goedgekeurd = read-only (UrenregistratiePolicy@update).

**Privacy / Security / Ethiek**
- Dataminimalisatie: geen GPS, geen locatietracking.
- Rol-scope: cliënt-dropdown filtert op eigen toegewezen cliënten.
- Mass-assignment protection: status/user_id whitelisted uit Form Request.

---

## US-12 — Uren indienen, terugtrekken en opnieuw indienen

**User Story**
Als zorgbegeleider wil ik mijn concept-uren kunnen indienen, terugtrekken bij vergissing en afgekeurde uren corrigeren en opnieuw indienen zodat ik de controle houd tot definitieve goedkeuring.

**Omschrijving**
1. POST /uren/{id}/indienen: concept → ingediend, alleen als cliënt + uren>0 + beide tijden ingevuld (service-check isIndienbaar()).
2. UrenIngediendNotification naar alle teamleiders van org (database-channel, Notification::send()).
3. POST /uren/{id}/terugtrekken: ingediend → concept, alleen vanuit status Ingediend (niet na goedkeur/afkeur).
4. Afgekeurde entries tonen teamleider_notitie boven formulier; opnieuw indienen zet notitie op null.
5. Eén service-methode UrenregistratieService::transition() valideert hele state-transitie matrix centraal.

**Privacy / Security / Ethiek**
- Eigenaarschap: zorgbegeleider kan terugtrekken tot goedkeuring (professionele autonomie).
- State-transitie-guard voorkomt onmogelijke overgangen.
- Notificatie naar teamleiders = transparante workflow.

---

## US-13 — Uren goedkeuren of afkeuren als teamleider

**User Story**
Als teamleider wil ik ingediende uren kunnen goedkeuren of met reden afkeuren zodat de urenadministratie correct is en fouten tijdig gecorrigeerd worden.

**Omschrijving**
1. /teamleider/uren toont tabel met alle ingediende uren, gegroepeerd per medewerker + week.
2. Goedkeuren-knop (groen) → POST /teamleider/uren/{id}/goedkeuren → status=Goedgekeurd (direct, geen modal).
3. Afkeuren opent modal met verplichte teamleider_notitie (min 10 tekens) via AfkeurUrenRequest.
4. Zorgbegeleider krijgt UrenGoedgekeurdNotification of UrenAfgekeurdNotification (met reden) bij beoordeling.
5. Autorisatie: teamleider-middleware + UrenregistratiePolicy@goedkeuren/afkeuren; scope op eigen team_id (query-niveau).

**Privacy / Security / Ethiek**
- Verplichte afkeurreden (min 10 tekens) = transparantie, geen silent rejects.
- Dubbele bescherming: middleware + policy.
- Scope op team_id: geen cross-team inzage.

---

## US-14 — Urenoverzicht met filters (teamleider)

**User Story**
Als teamleider wil ik het urenoverzicht kunnen filteren op status, medewerker en week zodat ik efficiënt door de administratie kan werken en openstaande acties vind.

**Omschrijving**
1. 3 filters boven tabel: status (default Ingediend), medewerker (eigen team), week (date-picker).
2. Filters blijven in URL bij paginatie: ?status=ingediend&medewerker=5&week=2026-W17.
3. Pageable (20/pagina), sorteerbaar op datum/medewerker/duur via klikbare kolomkoppen.
4. Rijen tonen: medewerker · cliënt · datum · tijd · duur (2 dec) · status-badge + teamleider_notitie on-hover bij afgekeurd.
5. Samenvattende header: totaal ingediend, totaal goedgekeurd, uren per medewerker per week.

**Privacy / Security / Ethiek**
- Scope op eigen team (geen cross-team leakage).
- Geen N+1: eager loading user + client.
- SQL-injection protection via Eloquent parameter-binding.

---

## US-15 — Wachtwoord vergeten & resetten via e-maillink

**User Story**
Als zorgbegeleider of teamleider wil ik mijn wachtwoord kunnen resetten via een e-maillink zodat ik weer toegang krijg tot Nexora zonder mijn oude wachtwoord te hoeven weten.

**Omschrijving**
1. Link "Wachtwoord vergeten?" op `/login` → `/wachtwoord-vergeten` formulier.
2. `Password::sendResetLink()` stuurt eenmalig token (60 min geldig); bevestigingsmelding identiek ongeacht of e-mail bestaat (user enumeration protection).
3. `/wachtwoord-reset/{token}?email=...` laat nieuw wachtwoord kiezen (min 8 chars, `confirmed` rule).
4. Nieuw wachtwoord bcrypt-gehasht via `Hash::make()`; token wordt na gebruik ongeldig (geen replay).
5. Na succes: automatisch ingelogd → rol-specifiek dashboard. Ongeldig/verlopen token → duidelijke foutmelding + link om opnieuw te proberen.

**Privacy / Security / Ethiek**
- Eenmalig, tijd-beperkt token (anti-replay).
- Identieke bevestigingsmelding voor bestaande en niet-bestaande e-mails.
- Wachtwoord altijd bcrypt-gehasht in DB, nooit plaintext in logs.
- Zelfbeschikking: medewerker kan zelf wachtwoord resetten zonder beheerder.

---

## US-16 — Profielbeheer (eigen gegevens + wachtwoord wijzigen)

**User Story**
Als ingelogde zorgbegeleider of teamleider wil ik mijn eigen profielgegevens en wachtwoord kunnen bijwerken zodat mijn accountgegevens actueel blijven zonder tussenkomst van de beheerder.

**Omschrijving**
1. `/profiel` bereikbaar vanuit layout; toont huidige `name` + `email` in formulier.
2. E-mail valideren op `unique:users,email,{id}` (eigen e-mail mag behouden blijven).
3. Wachtwoord wijzigen is optioneel: bij invullen verplicht `current_password` validation rule.
4. Rol, `is_active` en `team_id` kunnen NOOIT door gebruiker zelf gewijzigd worden — `UpdateProfielRequest` whitelist alleen `name`, `email`, `current_password`, `password`, `password_confirmation`.
5. Na update: flash "Profiel bijgewerkt."; bij wachtwoordwijziging `Auth::logoutOtherDevices()` om andere sessies uit te loggen.

**Privacy / Security / Ethiek**
- AVG art. 16 (recht op rectificatie): gebruiker beheert eigen gegevens.
- Geen privilege escalation mogelijk (rol/status whitelisted uit Form Request).
- Current password check voor wachtwoordwijziging (geen session hijack kan nieuw wachtwoord zetten).
- Andere sessies geïnvalideerd bij wachtwoordwissel.

---
