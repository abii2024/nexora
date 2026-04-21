# Ontwerpdocument — Nexora

> **Project:** Nexora — zorgbegeleidingssysteem voor beschermd wonen
> **Auteur:** Abdisamad (abii2024)
> **Examen:** PvB Software Developer Niveau 4 (14–25 april 2026)
> **Versie:** 1.0 — 21 april 2026

---

## Onderbouwing van gemaakte keuzes

Een onderbouwing van de gemaakte keuzes wordt toegevoegd aan het ontwerpdocument. Hierbij geef ik aan hoe Nexora omgaat met privacy, security en ethiek bij het verwerken van zorgdata. De onderbouwing is opgesplitst in drie deeldocumenten:

| Thema | Document | Kern |
|---|---|---|
| 🔒 Privacy | [ontwerpdocument/privacy.md](ontwerpdocument/privacy.md) | AVG-dataminimalisatie, least-privilege per rol, audit-trail, Wgbo-bewaartermijnen |
| 🛡️ Security | [ontwerpdocument/security.md](ontwerpdocument/security.md) | HTTPS, bcrypt, CSRF/SQL-injectie/XSS-bescherming, defense in depth, `.env`-secrets |
| ⚖️ Ethiek | [ontwerpdocument/ethiek.md](ontwerpdocument/ethiek.md) | Rolscheiding, continuïteit van zorg, transparantie, geen surveillance |

---

## Definition of Done

De Definition of Done die voor alle 16 user stories geldt staat in een apart bestand: **[definition-of-done.md](definition-of-done.md)**.

---

## Overige artefacten (volgen later)

Deze onderdelen van het ontwerpdocument worden gedurende de sprint aangevuld:

- Projectcontext & stakeholders
- User stories (verwijzing naar Trello-bord)
- Architectuurkeuzes (Laravel 12, SQLite, Blade)
- ERD
- Rol-matrix
- Wireframes / UI-sketches
- Urenworkflow flowchart
- Testplan-overzicht
- Verbetervoorstellen

---

**Einde index.**
