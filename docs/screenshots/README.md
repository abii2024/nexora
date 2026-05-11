# Screenshots & code-uitwerking per user story

Per US is er een map met:
- **`README.md`** — checklist van vereiste screenshots (DoD)
- **`USXX-uitwerking.md`** — beschrijving + ingesloten screenshots + code-snippets van de functionaliteit
- **PNG-bestanden** — handmatig gemaakte screenshots van de werkende app

## Sprint 1 — Authenticatie & medewerkersbeheer

| US | Onderwerp | Uitwerking |
|---|---|---|
| 01 | Inloggen | [US01-uitwerking.md](us01-inloggen/US01-uitwerking.md) |
| 02 | Rolgebaseerde toegang | [US02-uitwerking.md](us02-rolgebaseerde-toegang/US02-uitwerking.md) |
| 03 | Nieuwe medewerker aanmaken | [US03-uitwerking.md](us03-medewerker-aanmaken/US03-uitwerking.md) |
| 04 | Medewerkersoverzicht | [US04-uitwerking.md](us04-medewerkers-overzicht/US04-uitwerking.md) |

## Sprint 2 — Teamlid lifecycle & cliënten

| US | Onderwerp | Uitwerking |
|---|---|---|
| 05 | Teamlid bewerken + audit-trail | [US05-uitwerking.md](us05-teamlid-bewerken/US05-uitwerking.md) |
| 06 | Teamlid deactiveren/heractiveren | [US06-uitwerking.md](us06-teamlid-deactiveren/US06-uitwerking.md) |
| 07 | Cliënt aanmaken | [US07-uitwerking.md](us07-client-aanmaken/US07-uitwerking.md) |
| 08 | Cliënten koppelen aan begeleiders | [US08-uitwerking.md](us08-caregivers-koppeling/US08-uitwerking.md) |

## Sprint 3 — Cliëntoverzicht & urenregistratie

| US | Onderwerp | Uitwerking |
|---|---|---|
| 09 | Cliënten overzicht met scope | [US09-uitwerking.md](us09-clienten-overzicht/US09-uitwerking.md) |
| 10 | Cliënt bewerken + archiveren | [US10-uitwerking.md](us10-client-bewerken-archiveren/US10-uitwerking.md) |
| 11 | Concept-uren aanmaken | [US11-uitwerking.md](us11-concept-uren-aanmaken/US11-uitwerking.md) |
| 12 | Uren indienen / terugtrekken | [US12-uitwerking.md](us12-uren-indienen/US12-uitwerking.md) |

## Sprint 4 — Beoordelen, overzicht, auth-extensies & profiel

| US | Onderwerp | Uitwerking |
|---|---|---|
| 13 | Uren goedkeuren/afkeuren | [US13-uitwerking.md](us13-uren-beoordelen/US13-uitwerking.md) |
| 14 | Uren-overzicht teamleider | [US14-uitwerking.md](us14-uren-overzicht/US14-uitwerking.md) |
| 15 | Wachtwoord vergeten/resetten | [US15-uitwerking.md](us15-wachtwoord-reset/US15-uitwerking.md) |
| 16 | Profielbeheer | [US16-uitwerking.md](us16-profielbeheer/US16-uitwerking.md) |

---

## Werkwijze per user story

1. **Setup**: `php artisan migrate:fresh --seed`
2. Open de relevante `USXX-uitwerking.md` — die toont screenshots + code-uitwerking
3. Voer de stappen uit volgens de [testplan-checklist](../testplan/README.md)
4. Plaats nieuwe screenshots in de juiste US-map
5. Vink de checkbox af in `README.md` van die US-map
