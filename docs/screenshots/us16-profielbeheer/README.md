# Screenshots — US-16 Profielbeheer (eigen gegevens + wachtwoord wijzigen)

Checklist voor de handmatige verificatie aan het einde van het project.

## Checklist

- [ ] `01-sidebar-profiel-link.png` — sidebar met actieve "Profiel"-link
- [ ] `02-profiel-index.png` — `/profiel` met twee cards (Accountgegevens + Wachtwoord wijzigen) + velden pre-filled
- [ ] `03-naam-gewijzigd.png` — na naam-wijziging → groene "Profiel bijgewerkt"-flash + nieuwe naam zichtbaar in sidebar
- [ ] `04-email-gewijzigd.png` — email-wijziging + login met nieuwe email werkt
- [ ] `05-email-duplicaat.png` — "Dit e-mailadres is al in gebruik"-fout
- [ ] `06-password-zonder-current.png` — "Vul je huidige wachtwoord in om een nieuw wachtwoord te kunnen kiezen"
- [ ] `07-password-wrong-current.png` — "Je huidige wachtwoord klopt niet"
- [ ] `08-password-min8.png` — "Minstens 8 tekens"-fout
- [ ] `09-password-confirmation-mismatch.png` — "De wachtwoord-bevestiging komt niet overeen"
- [ ] `10-password-succes.png` — na succes → "Profiel bijgewerkt — je bent uitgelogd op andere apparaten"-flash
- [ ] `11-login-met-nieuw-password.png` — Logout → login met nieuw password slaagt
- [ ] `12-mass-assign-tinker.png` — Tinker-check: `User::find(x)->role` blijft zorgbegeleider na POST met `role=teamleider`
- [ ] `13-2browser-invalidation.png` — Browser A wijzigt password → browser B wordt uitgelogd op volgende request
- [ ] `14-guest-redirect.png` — Logout → direct `/profiel` → redirect naar login
- [ ] `15-pest-us16.png` — Terminal `./vendor/bin/pest tests/Feature/US-16.php` → 21 groen
- [ ] `16-pest-full-suite.png` — Terminal `./vendor/bin/pest` → groene totalen

Opnamen volgen aan einde van project (alle 16 US's batch).
