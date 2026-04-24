@component('mail::message')
# Wachtwoord herstellen

{{ $naam ? 'Hoi '.$naam.',' : 'Hoi,' }}

Je ontving deze e-mail omdat er een verzoek is ingediend om het wachtwoord van jouw Nexora-account te herstellen.

@component('mail::button', ['url' => $url])
Kies nieuw wachtwoord
@endcomponent

Deze link is **{{ $minuten }} minuten** geldig vanaf het moment van versturen.

Heb je dit niet zelf aangevraagd? Dan hoef je niets te doen — je wachtwoord blijft ongewijzigd.

Groet,
Nexora
@endcomponent
