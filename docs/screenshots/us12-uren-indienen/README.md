# Screenshots — US-12 Uren indienen, terugtrekken en opnieuw indienen

Checklist voor de handmatige verificatie aan het einde van het project (batch-oplevering).

## Checklist

- [ ] `01-concept-indienen-knop.png` — `/uren` Concept-tab als zorgbegeleider met "Indienen"-knop per concept-rij
- [ ] `02-ingediend-flash.png` — na POST → Ingediend-tab + groene "Uren ingediend voor goedkeuring"-alert
- [ ] `03-teamleider-notifications-tabel.png` — Tinker of SQL-viewer: rij in `notifications` met `type='uren_ingediend'`
- [ ] `04-terugtrekken-confirm.png` — JS-confirm-dialog "Deze uren-registratie terugtrekken naar concept?"
- [ ] `05-terugtrekken-flash.png` — na terugtrekken → Concepten-tab + "Uren teruggetrokken — nu weer bewerkbaar als concept"-alert
- [ ] `06-goedgekeurd-readonly.png` — Goedgekeurd-tab → rijen tonen "Read-only" tekst, geen knoppen
- [ ] `07-afkeur-banner-edit.png` — `/uren/{id}/edit` met gele banner "Teamleider-notitie bij afkeur" + reden-tekst
- [ ] `08-opnieuw-indienen-knop.png` — zelfde edit-pagina → submit-knop heet "Opslaan en opnieuw indienen"
- [ ] `09-opnieuw-indienen-flash.png` — na resubmit → Ingediend-tab + "Uren gecorrigeerd en opnieuw ingediend"-alert
- [ ] `10-non-owner-403.png` — zorgbeg B probeert via URL `/uren/{A-id}/indienen` → 403
- [ ] `11-goedgekeurd-terugtrekken-403.png` — goedgekeurde entry + POST `/terugtrekken` → 403
- [ ] `12-transitie-exception.png` — Browser-fout-page of dd-dump van `InvalidStateTransitionException` (alternatief: PHPStorm stacktrace)
- [ ] `13-pest-us12.png` — Terminal `./vendor/bin/pest tests/Feature/US-12.php` → 31 groene vinkjes
- [ ] `14-pest-full-suite-sprint3.png` — Terminal `./vendor/bin/pest` → 243 passed

Opnamen volgen pas wanneer alle 16 user stories klaar zijn — per afspraak in één batch.
