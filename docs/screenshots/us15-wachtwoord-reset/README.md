# Screenshots — US-15 Wachtwoord vergeten & resetten via e-maillink

Checklist voor de handmatige verificatie aan het einde van het project.

## Checklist

- [ ] `01-login-link.png` — `/login` met "Wachtwoord vergeten?"-link zichtbaar
- [ ] `02-forgot-form.png` — `/wachtwoord-vergeten` formulier met e-mail-input + uitleg-tekst (60 minuten geldig)
- [ ] `03-flash-verstuurd.png` — na POST → groene flash "Als dit adres bij een Nexora-account hoort…"
- [ ] `04-flash-enumeration.png` — zelfde flash ook bij onbekend e-mailadres
- [ ] `05-mail-log.png` — `storage/logs/laravel.log` met gerenderde mail (onderwerp "Wachtwoord herstellen — Nexora")
- [ ] `06-mail-preview.png` — Gerenderde HTML-mail met knop "Kies nieuw wachtwoord"
- [ ] `07-reset-form.png` — `/wachtwoord-herstellen/{token}?email=...` met pre-filled email + 2 password-velden
- [ ] `08-validatie-min8.png` — "Het wachtwoord moet minstens 8 tekens bevatten"
- [ ] `09-validatie-confirmed.png` — "De wachtwoord-bevestiging komt niet overeen"
- [ ] `10-invalid-token.png` — Reset met expired/invalid token → session-error boven formulier
- [ ] `11-succes-zorgbeg.png` — Redirect naar `/dashboard` + succes-flash
- [ ] `12-succes-teamleider.png` — Teamleider → `/teamleider/dashboard`
- [ ] `13-bcrypt-tinker.png` — `php artisan tinker` → `User::first()->password` → begint met `$2y$`
- [ ] `14-pest-us15.png` — Terminal `./vendor/bin/pest tests/Feature/US-15.php` → 16 groen
- [ ] `15-pest-full-suite.png` — Terminal `./vendor/bin/pest` → groene totalen

Opnamen volgen aan einde van project.
