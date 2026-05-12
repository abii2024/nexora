# Opdracht 4 — Verbetervoorstellen (B1-K1-W5)

> **Werkproces:** B1-K1-W5 — *Doet verbetervoorstellen voor de software*
> **Datum:** 2026-05-12
> **Sprint-context:** Na Sprint 1-review (US-15 Wachtwoord vergeten & resetten), feedback ontvangen van product owner Badreddine via Trello-comment.

Dit document bundelt de verbetervoorstellen voor Nexora op basis van systematische analyse van **testresultaten**, **PO-feedback** en **retrospective**, en vertaalt elk voorstel naar een concrete nieuwe user story op het Trello-scrumboard met tijdsinschatting en MoSCoW-prioriteit.

---

## 1. Informatiebronnen geanalyseerd

| Bron | Vindplaats | Wat geanalyseerd |
|---|---|---|
| **Testrapport Sprint 1** | [docs/testplan/sprint-1.md](../../testplan/sprint-1.md) | Welke AC's groen, welke handmatig nagelopen, welke flows nog niet end-to-end getest |
| **PO-feedback Trello** | Trello-bord *Nexora-platform*, kaart "Wachtwoord vergeten & resetten via e-maillink" — comment van **Badreddine**, 2 uur na sprint-review | Concreet voorstel om de reset-mail end-to-end te valideren |
| **Retrospective Sprint 1** | [docs/projectverslag.md §11](../../projectverslag.md) | "Wat anders had gekund" + "Afspraken die houden" |
| **Code & .env-config** | [.env:50](../../../.env), [config/mail.php:17](../../../config/mail.php) | Mail-driver staat op `log` — geen echte verzending in dev/staging |

---

## 2. Interpretatie van de bevindingen

### Kernbevinding (uit PO-feedback)

Badreddine schreef letterlijk op de US-15-kaart:

> *"Graag een gmail account aanmaken en deze als account opvoeren in het platform om te testen of het reset-my-password mailtje ook aankomt. Je kunt kijken naar SendGrid of Resend."*

![PO-feedback van Badreddine op US-15 Trello-kaart](01-trello-us15-po-feedback.png)
*Schermafbeelding 1 — De originele PO-comment op de Trello-kaart "Wachtwoord vergeten & resetten via e-maillink" (US-15). Bron van dit verbetervoorstel.*

**Interpretatie:**
- US-15 is technisch afgerond (alle AC's groen, `WachtwoordResetNotification` correct geïmplementeerd via `Password::sendResetLink()`), maar de feature is **nooit end-to-end gevalideerd in een echte inbox**.
- Oorzaak: `MAIL_MAILER=log` in `.env` — alle mails belanden in `storage/logs/laravel.log` in plaats van bij de gebruiker.
- Risico bij naar productie gaan: een gebruiker die zijn wachtwoord vergeet kan in productie vastlopen omdat we niet weten of de mail überhaupt afgeleverd wordt (SPF, DKIM, bounce-handling, throttling — alles onbekend).

### Andere bevindingen uit retrospective

- Screenshots + handmatige browser-tests worden in één batch aan het einde van alle 16 US's gedaan (zie [projectverslag §11](../../projectverslag.md)). Dat is efficiënt voor UI-checks, maar **mailtransport** vraagt om een aparte verificatie-loop omdat de afhankelijkheid extern is (DNS, mail-provider).
- Definition of Done dekt momenteel automated tests + handmatige UI-test, **niet** "mail-flow gevalideerd in echte inbox".

---

## 3. Verbetervoorstellen

| Nr | Voorstel | Bron | Aanpak |
|---|---|---|---|
| **VV-1** | Reset-mail end-to-end testen via echt e-mailaccount + Resend-integratie | PO-feedback Badreddine | Nieuwe user story **US-17** (zie §4) |
| **VV-2** | Test-gmail-account meenemen in `DatabaseSeeder` | Eigen analyse | Voeg seeder-entry toe zodra US-17 het account heeft aangemaakt — eenmalig werk binnen US-17 |
| **VV-3** | Definition of Done uitbreiden met "mail-/notificatie-flows handmatig gevalideerd in echte inbox" | Retrospective + PO-feedback | Werkafspraak — geen aparte US, vastgelegd in [projectverslag §11](../../projectverslag.md) |

Voor het examen is **VV-1** de hoofd-deliverable: dat is het voorstel dat direct uit een externe bron komt (PO) én vertaald wordt naar een nieuwe user story met planning.

---

## 4. Nieuwe user story op het Trello-scrumboard

### US-17 — Reset-mail end-to-end testen via echt e-mailaccount (Resend)

| Veld | Waarde |
|---|---|
| **Lijst** | Sprint backlog |
| **Labels** | Sprint 1 · Should have · Authenticatie · User Story · Security |
| **Tijd-inschatting** | 4 uur (1u account + Resend/DNS, 1u code & .env, 1u testen, 1u documenteren) |
| **Prioriteit (MoSCoW)** | **Should have** |
| **Trello-link** | <https://trello.com/c/4UEMqcxr> |

**User Story**

> Als **beheerder** wil ik dat de wachtwoord-reset-mail **daadwerkelijk in de inbox** van de gebruiker aankomt, zodat ik weet dat US-15 in productie werkt en gebruikers zonder mijn tussenkomst weer kunnen inloggen.

**Acceptatiecriteria**

1. Test-Gmail-account aangemaakt (bv. `nexora.test@gmail.com`) en als rol-gebruiker opgevoerd in Nexora.
2. Mail-driver omgezet van `log` naar `resend` (of SMTP via Gmail App Password als fallback).
3. Reset-mail komt binnen 60 seconden binnen in de testinbox.
4. Link in de mail leidt naar `/wachtwoord-herstellen/{token}` en de end-to-end flow (nieuw wachtwoord instellen + inloggen) werkt.
5. Foutpad blijft werken: ongeldig/verlopen token toont nette foutmelding.
6. Bewijslast (screenshots van inbox + reset-formulier + succesvolle login) opgenomen onder `docs/uitgewerkte-functionaliteiten/us15-wachtwoord-reset/`.

**Bron**

PO-feedback Badreddine op kaart "Wachtwoord vergeten & resetten via e-maillink" (Trello-bord *Nexora-platform*).

---

## 5. Planning

Tijdsinschatting wordt **op de Trello-kaart zelf** gehanteerd (Trello-veld + dit document). Per taakblok:

| Blok | Tijd | Omschrijving |
|---|---|---|
| 1. Account + Resend setup | 1u | Gmail-account aanmaken, Resend-account + API-key, sender domein/DNS (SPF + DKIM) |
| 2. Code + `.env` | 1u | `MAIL_MAILER=resend`, `RESEND_KEY`, mail-from address; bestaande [`WachtwoordResetNotification`](../../../app/Notifications/WachtwoordResetNotification.php) ongewijzigd |
| 3. Testen | 1u | End-to-end happy-path + verlopen-token foutpad |
| 4. Documenteren | 1u | Screenshots + update [US-15-uitwerking.md](../us15-wachtwoord-reset/US15-uitwerking.md) + bewijs in deze map |

Totaal: **4 uur**, planbaar in Sprint 2 zodra die start.

---

## 6. Screenshot product backlog (bewijslast)

![Trello-kaart US-17 op sprint backlog met alle 5 labels](02-trello-us17-sprint-backlog.png)
*Schermafbeelding 2 — Nieuwe user story **US-17 — Reset-mail end-to-end testen via echt e-mailaccount (Resend)** op de product backlog (lijst **sprint backlog**). Alle 5 vereiste labels zichtbaar: Sprint 1 · Should have · Authenticatie · User Story · Security. Het Trello-activiteitenlog rechts bevestigt dat de kaart is toegevoegd aan sprint backlog ("zojuist").*

![Reset-mail aangekomen in Gmail-inbox via Resend](03-gmail-inbox-reset-mail.png)
*Schermafbeelding 3 — De reset-mail (van `Laravel <onboarding@resend.dev>`) **daadwerkelijk aangekomen** in de Gmail-inbox van `abdisamadvanabdulle@gmail.com`. Onderwerp: "Wachtwoord herstellen" — adressering: "Hoi Fatima El Amrani" — bevat de geldige reset-link (60 min geldig). Dit is het end-to-end bewijs dat het verbetervoorstel uit PO-feedback (mailtje moet aankomen) succesvol is geïmplementeerd.*

---

## 7. Voortgang implementatie (2026-05-12)

Het verbetervoorstel is **gedeeltelijk** doorgevoerd in dezelfde sessie waarin het is opgesteld:

| AC | Status | Bewijs |
|---|---|---|
| Test-Gmail-account aangemaakt + opgevoerd | ✅ | `abdisamadvanabdulle@gmail.com` zit in `DatabaseSeeder.php` als teamleider Fatima El Amrani |
| Mail-driver omgezet `log` → `resend` | ✅ | `MAIL_MAILER=resend` + `resend/resend-laravel` package geïnstalleerd |
| `Password::sendResetLink()` levert succesvol af | ✅ | Status `passwords.sent` ontvangen van Laravel; geen Resend API-error |
| Mail in inbox (handmatige check) | ✅ | Schermafbeelding 3 — mail van `Laravel <onboarding@resend.dev>` in Gmail-inbox |
| Reset-link werkt + nieuw wachtwoord werkt | ✅ | Reset-formulier geopend met token uit mail, nieuw wachtwoord ingesteld, login als Fatima geslaagd |
| Foutpad ongeldig/verlopen token | ✅ | Reeds gedekt door US-15 testsuite (360/360 tests groen) |
| Bewijslast-screenshots | ✅ | 3 screenshots opgenomen onder deze map (§2, §6) + 1 kopie in [us15-wachtwoord-reset/](../us15-wachtwoord-reset/02-gmail-inbox-reset-mail.png) |

**Bekende beperking — Resend free tier:** met `onboarding@resend.dev` als from-address kunnen mails **alleen** worden afgeleverd op het exacte e-mailadres van het Resend-account (`abdisamadvanabdulle@gmail.com`). `+`-aliases en andere recipients worden geweigerd. Voor productie moet een eigen domein worden geverifieerd in Resend (SPF + DKIM + DMARC); dat is buiten scope voor het examen.

---

## 8. Koppeling met examen-rubric

| Rubric-item | Bewijs in dit document |
|---|---|
| Welke informatiebronnen analyseer je? | §1 |
| Hoe vertaal je dat naar concrete acties? | §2 + §3 + §4 |
| Hoe maak je planning? | §5 + Trello-kaart |
| Resultaten in *Examenverslag* in `/docs` | Dit document + verwijzing in [projectverslag §10](../../projectverslag.md) |
| Screenshot product backlog | §6 |
