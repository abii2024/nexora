# Gegevensbescherming — Ontwerpdocument Nexora

> Onderdeel van het [ontwerpdocument](../ontwerpdocument.md) — PvB Software Developer Niveau 4

---

**Kernprincipe** — *Het platform verwerkt gevoelige persoonsgegevens van cliënten (zoals BSN, geboortedatum en adresgegevens). In lijn met de AVG worden deze gegevens alleen opgeslagen voor zover noodzakelijk en zijn ze uitsluitend toegankelijk voor bevoegde medewerkers. Zorgbegeleiders hebben alleen toegang tot de cliënten die aan hen zijn toegewezen.*

---

## 1. Juridisch kader

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

## 2. Technische implementatie in Nexora

### 2.1 Least-privilege op query-niveau

Zorgbegeleiders zien uitsluitend de cliënten waar ze via `client_caregivers` aan gekoppeld zijn. Dit wordt afgedwongen in `ClientService::getPaginated()` op SQL-niveau, **niet** in de Blade-view. Een aanvaller die URL's raadt (`/clients/123`) stuit op een 403 via `ClientPolicy@view` — nooit op een lege pagina met alsnog geleakte ID's in query-responses.

### 2.2 Dataminimalisatie per feature

| Gegeven | Opslaan? | Waarom |
|---|---|---|
| BSN | Alleen als `care_type` WMO/WLZ/JW is | Zorgwet vereist identificatie |
| Geboortedatum | Ja | Identificatie + leeftijdsindicatie bij medicatie |
| Thuisadres | Ja | Huisbezoeken door begeleider |
| GPS-locatie bij uren | **Nee** | Surveillance zonder noodzaak |
| Werkuren-totalen | Ja | Administratieve verantwoording (CAO) |

### 2.3 Audit-trail voor accountability

Elke status-wijziging (uren, cliëntstatus) en elke wijziging aan medewerkersaccounts wordt vastgelegd in een aparte `*_audit_logs` tabel. Dit stelt Nexora in staat om bij een AVG-inzageverzoek (art. 15) te tonen wie wanneer welke data heeft geraadpleegd of gewijzigd.

### 2.4 Bewaartermijnen & soft delete

Cliënten worden **gearchiveerd** (`deleted_at` gezet via Laravel's `SoftDeletes`-trait), niet hard-gedeletet. Reden:

- **Wgbo**: zorgdossiers moeten 20 jaar bewaard blijven
- **Fiscaal**: loonadministratie en facturatie 7 jaar
- **Herstel bij fout**: een teamleider die per ongeluk archiveert kan via `/clients/archive` > *"Herstellen"* direct terug
- **Permanente verwijdering** kan alleen via een Artisan-commando met bevestiging — niet vanuit de UI

### 2.5 Scope-isolatie per rol

| Rol | Toegangsbereik |
|---|---|
| Zorgbegeleider | Alleen eigen toegewezen cliënten (via `client_caregivers.user_id`) + eigen uren + eigen profiel |
| Teamleider | Alleen medewerkers + uren binnen eigen team (via `users.team_id`) |
| Administratie | Read-only CSV-export van uren (voor loonadministratie) |
| Directie | Strategisch read-only overzicht — geen mutaties |

## 3. Rechtsgronden per verwerking

| Verwerking | Rechtsgrond (AVG art. 6) |
|---|---|
| Inloggen / sessie | Uitvoering overeenkomst (art. 6 lid 1 b) — zorgcontract |
| Cliëntdossier | Vitaal belang + wettelijke verplichting zorgverlening (art. 6 lid 1 c/d) |
| Urenregistratie medewerker | Uitvoering arbeidsovereenkomst (art. 6 lid 1 b) |
| Audit-logs | Gerechtvaardigd belang (art. 6 lid 1 f) — accountability |

---

**Zie ook:** [Beveiliging](beveiliging.md) · [Verantwoorde verwerking](verantwoorde-verwerking.md) · [Ontwerpdocument (index)](../ontwerpdocument.md)
