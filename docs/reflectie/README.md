# Opdracht 7 — Reflecteren (B1-K2-W3)

> **Werkproces:** B1-K2-W3 — *Reflecteert op het werk*
> **Datum:** 2026-05-12
> **Scope:** Sprint 1 t/m 4 (alle 16 user stories) + US-17 (verbetervoorstel uit Opdracht 4)

Dit document bundelt de retrospectives voor werkproces **B1-K2-W3**. Per sprint reflecteer ik op drie dimensies — **het proces**, **de samenwerking met de product owner** en **mijn eigen prestaties** — telkens met de structuur *wat ging goed (+)*, *wat kan beter (−)* en *wat ga ik anders doen (→)*. Het document sluit af met een overkoepelende eindreflectie en de koppeling met het examen-rubric.

---

## 1. Wanneer & hulpmiddelen

| Aspect | Invulling |
|---|---|
| **Moment** | Aan het einde van elke sprint, direct na de sprint-review met PO Badreddine |
| **Wie** | Individueel — ik werk als enige ontwikkelaar aan Nexora |
| **Structuur** | *Wat ging goed* / *wat kan beter* / *wat ga ik anders doen* |
| **Dimensies** | (1) Proces · (2) Samenwerking met PO · (3) Eigen prestaties |
| **Hulpmiddelen** | Trello-scrumboard (activiteitenlog), [`projectverslag.md`](../projectverslag.md), commit-historie + PR-discussies op GitHub, testrapporten in [`docs/testplan/`](../testplan/), PO-feedback op Trello-kaarten |
| **Vastlegging** | Dit document onder `/docs` in de GitHub-repo, plus verwijzing in [`projectverslag.md`](../projectverslag.md) §10.3 en §11 |

---

## 2. Retrospective Sprint 1 — Auth + team basis (US-01..04)

**Sprint-feiten:** 4 PRs (#3..#6), 70 Pest-tests, 205 asserts. Tag: [`sprint-1`](https://github.com/abii2024/nexora/tree/sprint-1).

### Proces

- **+** Pest-first aanpak meteen vanaf US-01: 10 tests voor login vóór de controller compleet was → geen post-merge bugfixes.
- **+** Design-system eerst geport in `chore/design-system-curava` (PR #2) vóór de eerste US — daardoor bouwen US-02 t/m US-04 visueel consistent op `<x-ui.*>` componenten zonder duplicatie.
- **+** Commit-hygiëne: scoped prefixes (`feat(auth):`, `test(team):`) met Nederlandstalige body — historie is doorzoekbaar.
- **−** Testbestandnamen volgden Laravel-default (`tests/Feature/Auth/LoginTest.php`) i.p.v. US-nummer. Pas in sprint 2 omgenoemd naar `US-01.php` — 4 renames + bijbehorende route-aanpassingen die ik had kunnen voorkomen.
- **−** `client_caregivers` migratie al meegenomen in US-02 om autorisatie-tests te kunnen schrijven, terwijl de echte feature pas in US-08 zou komen. Functioneel werkt het, maar conceptueel had ik een aparte "auth-infrastructure"-story kunnen opzetten.
- **→** Anders doen in sprint 2: testbestandnamen vanaf US-05 direct als `US-XX.php`, en bij infrastructurele beslissingen die meerdere US's raken explicieter benoemen waarom ik dat in deze US oppak.

### Samenwerking met PO

- **+** PO had Trello-kaarten met duidelijke acceptatiecriteria klaar staan vóór sprint-start — geen vertraging door onduidelijke scope.
- **−** Bij US-01 had ik twijfel over AC-2 ("rol-gebaseerde redirect na login"): teamleider → `/team`, zorgbegeleider → `/clients`. Ik heb dat niet kort op de Trello-kaart geverifieerd, gewoon mijn invulling gebouwd, en pas bij de review hoorde ik dat het klopte. Twijfel had ik in 2 minuten met een comment kunnen wegnemen.
- **→** Anders doen: bij elke "ja-tenzij"-twijfel een korte vraag op de Trello-kaart plaatsen vóórdat ik bouw, niet pas bij de review.

### Eigen prestaties

- **+** Login-controller had vanaf de eerste commit throttling (`RateLimiter`) + user-enumeration-protection — security niet als afterthought.
- **+** 100% van de geplande US's (4) gehaald in de sprint.
- **−** Onderschatting van scope: ik begon met de aanname "3 US's haalbaar", werd 4. Was goed nieuws maar ik had de eerste planning realistischer moeten maken.
- **→** Anders doen: aan begin sprint per US een tijdsinschatting in uren op de Trello-kaart zetten (start in sprint 2), zodat ik over- of onderscatting kan meten i.p.v. inschatten op gevoel.

---

## 3. Retrospective Sprint 2 — Team compleet + cliënt basis (US-05..08)

**Sprint-feiten:** 4 PRs (#7..#10), 85 Pest-tests, 261 asserts. Tag: [`sprint-2`](https://github.com/abii2024/nexora/tree/sprint-2).

### Proces

- **+** Test-rename uit Sprint 1-retro doorgevoerd: alle nieuwe tests heten direct `US-XX.php` — examinator kan testbewijs per US opzoeken.
- **+** Audit-logs (`user_audit_logs`) ingevoerd als eerste-klasse-burger via `UserService::updateWithAudit` — AVG art. 30 vanaf US-05 ingebakken i.p.v. nageschroefd.
- **+** Partial unique indexes (max 1 primair + 1 secundair caregiver per cliënt) op DB-niveau geforceerd via raw SQL in de migration — niet alleen application-level validatie.
- **−** `client_caregivers` had nu een dubbele waarheid: stub uit US-02 (autorisatie-tests) + productieklare versie in US-07. Ik moest een refactor-commit doen om beide te verzoenen.
- **−** `CheckActiveUser`-middleware kwam pas in sprint 2 terwijl het functioneel ook al in sprint 1 had gekund — runtime sessie-invalidatie zat niet in mijn mentale model.
- **→** Anders doen: bij grote infrastructurele wijzigingen apart kort overleg met PO inplannen ("we maken X nu écht klaar, niet langer stubben") in plaats van impliciet uitbreiden.

### Samenwerking met PO

- **+** PO accepteerde US-08 (cliënten koppelen aan begeleiders) in één review-ronde — duidelijk teken dat de AC's en demo strak waren.
- **+** Trello-activiteitenlog laat zien dat PO actief kaarten zelf verplaatste naar *done* (zie [overleggen/README.md](../overleggen/README.md) schermafbeelding 1+2).
- **−** Ik had al in sprint 2 het idee dat een **e-mailnotificatie** bij caregiver-koppeling waardevol zou zijn voor begeleiders. Heb het pas in §10 van het projectverslag als post-examen verbetervoorstel gezet — had het ook expliciet kunnen voorleggen aan PO als mogelijke US-uitbreiding.
- **→** Anders doen: ideeën-voor-uitbreidingen direct als Trello-kaart op de backlog-lijst zetten (status: idee/voorstel), niet in mijn hoofd of in een markdown-bijlage.

### Eigen prestaties

- **+** 85 tests in een week + geen regressies in de sprint 1-suite — testdekking groeit cumulatief.
- **+** Self-demotion guard in `UserService` proactief toegevoegd — voorkomt dat een teamleider zichzelf demoteert en daarna geen team meer kan beheren.
- **−** Te lang aaneengesloten gecodeerd zonder pauze → enkele typo-commits (`teamleidder`, `cliëntn`) die ik later moest fixen.
- **−** Ik heb in deze sprint geen tussentijdse demo aan PO gegeven, alleen einde-sprint. Voor US-07 (cliënt aanmaken met persoonsgegevens) had een vroege demo de AC's rond verplichte/optionele velden sneller kunnen valideren.
- **→** Anders doen: Pomodoro of vergelijkbare pauze-discipline, en bij US's met UI-formulieren een halverwege-demo (vroege screenshot of localhost-doorloop op Zoom).

---

## 4. Retrospective Sprint 3 — Cliënt compleet + uren basis (US-09..12)

**Sprint-feiten:** 4 PRs (#11..#14), 117 Pest-tests, 280 asserts. Tag: [`sprint-3`](https://github.com/abii2024/nexora/tree/sprint-3). Beste sprint qua testdekking.

### Proces

- **+** N+1-regressietests via `DB::listen` als standaardgewoonte ingevoerd (in US-09 en US-14). Eén keer geschreven, copy-paste voor volgende lijst-endpoints.
- **+** State-machine voor uren clean opgezet: `UrenregistratieService::transition()` met een centrale allowed-matrix — sprint 4 (goedkeuren/afkeuren) kon erop voortbouwen zonder de matrix te wijzigen (Open/Closed-principe).
- **+** Route-volgordeprobleem (`/clients/archive` botste met `/clients/{id}`) opgelost met `whereNumber('client')` — leereffect: routes definitie-volgorde matters.
- **−** Enums én losse constants halfslachtig gecombineerd: ik begon `UrenStatus` als losse strings (`'concept'`, `'ingediend'`), realiseerde halverwege dat een backed-enum cleaner is, en moest 3 bestanden refactoren.
- **−** Het route-volgordeprobleem ontdekte ik pas via een 422-error in handmatige tests; er was geen automatische test die zou hebben gefaald op route-conflict.
- **→** Anders doen: bij modellen met een vast vocabularium (status, type, rol) **direct** een backed enum starten — niet eerst stringly-typed.
- **→** Anders doen: route-volgorde-gevoeligheid expliciet maken via een handvol "feature route-conflict" tests die de gevoelige paden treffen.

### Samenwerking met PO

- **+** PO was zichtbaar enthousiast over de uren-tabs (status-tabs via URL i.p.v. JS-state) — shareable links en werkt zonder JavaScript.
- **+** PO accepteerde alle 4 sprint-3-kaarten in één review-sessie (Trello-log toont batch-overgang naar *done*).
- **−** Ik heb screenshots van het scrumboard niet dagelijks per US gemaakt, alleen aan einde sprint in batch (afgesproken patroon volgens §11 oud-projectverslag). PO heeft later gevraagd of er ook tussentijdse momentopnames waren — die had ik gemist.
- **→** Anders doen: één scrumboard-screenshot per dag in een aparte map (per-sprint), naast de bestaande batch aan einde sprint. Kost 10 seconden per dag en geeft examinator én PO meer granulariteit.

### Eigen prestaties

- **+** Meest productieve sprint qua testdekking (117 tests in 4 US's = ~29 tests per US).
- **+** Notification-pattern netjes toegepast: `UrenIngediendNotification` (database channel, team-scoped recipients) zonder mailer-pollutie.
- **−** Focus op tests ging soms ten koste van docs-bijwerken — `projectverslag.md` liep 1 dag achter op de feitelijke voortgang.
- **−** `afkeur_reden` kolom + auto-clear bij resubmit had ik in US-12 al moeten plannen i.p.v. pas in US-13 te bedenken (sprint 4) — dat veroorzaakte een extra migratie.
- **→** Anders doen: bij start van elke US ook docs-tasks (projectverslag-update, US-readme) op de Trello-kaart als checklist-item zetten, niet alleen code-tasks.

---

## 5. Retrospective Sprint 4 + US-17 — Uren compleet + auth afronding (US-13..16, US-17)

**Sprint-feiten:** 4 PRs (#15..#18), 86 Pest-tests, 205 asserts. Tag: [`sprint-4`](https://github.com/abii2024/nexora/tree/sprint-4). Extra: US-17 (Resend-integratie) na PO-feedback op US-15.

### Proces

- **+** 4 US's afgerond in 1 sprint inclusief de eerste écht externe afhankelijkheid (mail-flow voor wachtwoord-reset).
- **+** Mass-assignment-probes als nieuw test-pattern (`role`, `is_active`, `team_id` structureel geblokkeerd) — security-by-default.
- **+** `AuthenticateSession`-middleware netjes geconfigureerd in `bootstrap/app.php` zodat US-16 (logout-other-devices) werkt zonder custom session-store.
- **− KRITIEK:** US-15 wachtwoord-reset was technisch klaar (alle AC's groen, 16 tests + 46 asserts) maar **NIET end-to-end gevalideerd in een echte inbox**. `MAIL_MAILER=log` bleef staan tot PO-feedback. Risico bij productie: gebruikers die hun wachtwoord vergeten kunnen vastlopen omdat SPF/DKIM/bounces nooit zijn getest.
- **−** Definition of Done dekte alleen automated tests + handmatige UI-test — **niet** "externe-flow gevalideerd in echte inbox/webhook/betaling".
- **→** Anders doen (al doorgevoerd via opdracht-4 VV-3): DoD uitbreiden met **"mail-/notificatie-flows handmatig gevalideerd in echte inbox"** — opgenomen in [opdracht-4-verbetervoorstellen/README.md](../uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md) §3.

### Samenwerking met PO

- **+** PO-comment op US-15 was concreet en actionable: *"Graag een gmail account aanmaken en deze als account opvoeren in het platform om te testen of het reset-my-password mailtje ook aankomt. Je kunt kijken naar SendGrid of Resend."* — leverde direct US-17 op.
- **+** Snelle feedback-loop: PO-comment → US-17 op backlog → uitgevoerd → bewijs in [opdracht-4-verbetervoorstellen/README.md](../uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md) — alles binnen één werkdag.
- **− KRITIEK:** dit verbetervoorstel had **ik zelf moeten signaleren** vóór de sprint-review. De DoD was mijn verantwoordelijkheid, niet die van PO. Dat PO het opmerkt is positief voor de samenwerking, maar het is een gemiste eigen-detectie.
- **→** Anders doen: voor elke US met externe afhankelijkheid (mail, API, betaling, webhook) een expliciete "validatie in echte omgeving"-checklist toevoegen voordat ik de kaart op *ready for review* zet.

### Eigen prestaties

- **+** Snelle response op PO-feedback: US-17 binnen 4 uur opgeleverd (1u Resend-setup + 1u code/.env + 1u testen + 1u documenteren) — schatting kwam exact uit.
- **+** Mass-assignment-probes uit US-16 zijn nu een herbruikbaar test-pattern voor toekomstige rol-velden.
- **−** Blind vertrouwen in `Password::sendResetLink` return-status `passwords.sent`: dat zegt alleen dat Laravel het bericht heeft afgeleverd aan de mail-transport-laag, **niet** dat het in de inbox is aangekomen. Klassieke "groene test ≠ werkende feature in productie".
- **−** Resend free tier blijkt mails alleen aan het Resend-account-eigenaar-emailadres af te leveren — dat had ik bij setup direct kunnen lezen in plaats van pas tijdens testen ontdekken.
- **→** Anders doen: bij externe SaaS-integraties eerst de free-tier-limits lezen vóór ik code schrijf — kost 5 minuten, voorkomt verrassing tijdens demo.

---

## 6. Overkoepelende eindreflectie (alle 16 US's + US-17)

| Dimensie | Wat ging goed (+) | Wat kan beter (−) | Wat ga ik anders doen (→) |
|---|---|---|---|
| **Proces** | 4 sprints volledig opgeleverd, 4 git-tags, 18 merged PR's, **358+ Pest-tests** allemaal groen, geen `--force-push`, geen `--amend`, scoped commit-prefixes consistent | Docs-batch-aanpak (screenshots aan einde) werkte goed voor UI-bewijs maar **slecht voor mail-flow validatie** — externe afhankelijkheden vragen om continue validatie, niet batch | DoD vóór sprint 1 vaststellen met **"externe-flow gevalideerd in echte omgeving"** als verplicht item; per US zowel code- als docs- als validatie-tasks op Trello-kaart |
| **Samenwerking met PO** | Korte feedback-loop via Trello-comments; PO heeft alle 16 kaarten zelf naar *done* verplaatst (bewijs in [overleggen/README.md](../overleggen/README.md) schermafbeelding 1+2); US-17 als zichtbare opvolging van PO-feedback | Te weinig **tussentijdse demo's** — alleen einde-sprint-review. Twijfels over AC-invulling losten zich vanzelf op i.p.v. proactief verifieerd; ideeën voor uitbreidingen niet altijd direct als Trello-kaart geopperd | Bij US's met UI-formulieren een halverwege-demo (5 min, localhost via Zoom); ideeën voor uitbreidingen direct op de backlog-lijst van Trello, niet "voor later" in een markdown |
| **Eigen prestaties** | 100% van de 16 user stories opgeleverd binnen 4 sprints; security-by-default (throttling, enumeration-protection, mass-assignment-probes) vanaf US-01; eigen verbetervoorstel doorgevoerd (US-17 in dezelfde dag als PO-feedback) | Neiging tot snel doorbouwen → **onderschatting van validatiediepte** (US-15 mail) en planning op gevoel i.p.v. uren-schattingen op de kaart | Per US **uren-schatting** op de Trello-kaart bij start (was sprint 2-belofte, niet consistent doorgezet); Pomodoro-pauzes; bij externe afhankelijkheden eerst de provider-docs lezen vóór code |

### Drie generieke lessen voor een volgend project

1. **Externe afhankelijkheden krijgen een eigen DoD-regel.** Een groene automated test bij een mail-/webhook-flow betekent niet dat het in productie werkt. "Handmatig gevalideerd in echte omgeving" hoort in de DoD, niet als afterthought.
2. **Korte feedback-loops slaan een latere refactor terug.** Twee minuten een vraag op de Trello-kaart of een 5-minuten tussentijdse demo bespaart later een rework-commit. Dat geldt voor solo-werk net zo goed als voor teams, omdat de PO altijd context heeft die ik niet heb.
3. **Ideeën horen op het bord, niet in mijn hoofd.** Elke "dit zou later handig zijn"-gedachte hoort meteen als kaart op de backlog-lijst (idee/voorstel-label). Anders verdwijnen ze in een markdown-appendix waar ze als post-examen-buitenscope-bullets eindigen.

---

## 7. Koppeling met examen-rubric (B1-K2-W3)

| Rubric-item | Bewijs in deze documentatie |
|---|---|
| Wanneer vindt de reflectie plaats? | §1 — Wanneer & hulpmiddelen (einde elke sprint) |
| Welke (hulp)middelen gebruik je? | §1 — Trello, projectverslag, commit-historie, testrapporten, PO-feedback |
| Positieve punten + verbeterpunten **proces** | §2.Proces · §3.Proces · §4.Proces · §5.Proces · §6 tabelrij *Proces* |
| Positieve punten + verbeterpunten **samenwerking PO** | §2.PO · §3.PO · §4.PO · §5.PO · §6 tabelrij *PO* |
| Positieve punten + verbeterpunten **eigen prestaties** | §2.Eigen prestaties · §3.Eigen prestaties · §4.Eigen prestaties · §5.Eigen prestaties · §6 tabelrij *Eigen prestaties* |
| Concreet "wat ga ik anders doen" | Per sprint een **→**-bullet + §6 tabelkolom + §6.1 drie generieke lessen |
| Resultaten in *Examenverslag* in `/docs` | Dit document + verwijzing in [projectverslag.md](../projectverslag.md) §10.3 en §11 |
