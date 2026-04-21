# Ethiek — Ontwerpdocument Nexora

> Onderdeel van het [ontwerpdocument](../ontwerpdocument.md) — PvB Software Developer Niveau 4

---

**Kernprincipe** — *Het platform biedt alleen geautoriseerde gebruikers toegang tot de juiste informatie op basis van hun rol. Zorgbegeleiders zien alleen hun eigen cliënten en teamleiders beheren alleen hun eigen team. Dit waarborgt een eerlijke en verantwoorde verwerking van zorgdata.*

---

## 1. Rolscheiding als zorgprincipe

In de zorg is het schadelijk en oneerlijk wanneer een medewerker inzage heeft in dossiers van cliënten die niet onder zijn verantwoordelijkheid vallen. Niet alleen juridisch (AVG), maar ook **professioneel**: het beroepsgeheim van een zorgprofessional beperkt zich tot de eigen zorgrelatie.

Nexora vertaalt dit in:

- **Technische onmogelijkheid** i.p.v. beleidsregel — een zorgbegeleider kan andere cliënten niet inzien, óók niet uit nieuwsgierigheid
- **Een centraal beleidsbestand** (`app/Policies/`) — elke autorisatie-beslissing staat op één plek en is auditeerbaar
- **Expliciete 403-pagina** — bevestigt dat toegang geweigerd is, zonder details te lekken over waarom

## 2. Continuïteit van zorg

Cliënten zijn kwetsbaar; onderbreking van zorg is ethisch onverdedigbaar.

- **Primair / secundair / tertiair begeleider** — elke cliënt heeft minstens één primaire + één vervanger. Bij vakantie of ziekte van de primair blijft er altijd iemand verantwoordelijk.
- **Soft delete i.p.v. hard delete** — als een medewerker uit dienst gaat, blijft de historie traceerbaar voor de cliënt (*"wie was mijn begeleider in 2026?"*).
- **Status-logs bij cliënten** — als een cliënt op de wachtlijst wordt gezet, is zichtbaar waarom en door wie.

## 3. Transparantie & autonomie

- **Verplichte reden bij afkeuring van uren** (min 10 tekens) — een zorgbegeleider heeft recht op uitleg waarom zijn gewerkte uren worden afgewezen. Silent rejects zijn oneerlijk.
- **Eigenaarschap over eigen tijd** — een zorgbegeleider kan ingediende uren **terugtrekken** tot goedkeuring. De teamleider heeft geen absolute macht tijdens de review-fase.
- **Notificaties bij elke koppeling** — een zorgbegeleider wordt **gewaarschuwd** wanneer hij aan een nieuwe cliënt wordt gekoppeld. Geen stille toewijzingen achter zijn rug om.
- **Zelfbeheer van profiel** — gebruikers kunnen eigen naam, e-mail en wachtwoord bijwerken zonder beheerder. Tegelijkertijd kunnen ze hun eigen rol of `is_active`-status **niet** wijzigen: geen privilege escalation, maar ook geen selfsabotage.

## 4. Eerlijke werkverdeling

Het teamleider-overzicht toont per medewerker de totalen per week. Dit maakt workload-verdeling **zichtbaar** — een teamleider die stelselmatig structureel meer aan één begeleider toeschuift kan daarop aangesproken worden.

## 5. Geen surveillance

Bewust **niet** geïmplementeerd in Nexora:

- Geen GPS-tracking bij uren-registratie
- Geen automatische check-in via IP-detectie
- Geen fine-grained activity logging ("wie klikt wanneer op welke knop")
- Geen screenshots of keylogging

De zorgbegeleider registreert **zelf** zijn tijd. Vertrouwen > controle.

## 6. Rechtvaardige beslissingen

- **Self-demotion guard** — een teamleider kan zijn eigen rol niet verlagen naar zorgbegeleider. Dit voorkomt een complete lockout van het teamleider-dashboard en beschermt de organisatie tegen per-ongeluk-verlies van beheerders.
- **Herstelbare archivering** — een gearchiveerde cliënt kan zonder dataverlies hersteld worden. Een fout wordt niet bestraft met datavernietiging.
- **Geen permanente delete vanuit UI** — voorkomt impulsief of boos wissen van dossiers.

## 7. Evenwicht tussen beheer en autonomie

Nexora is een **beheertool**, geen **surveillancesysteem**. Elke feature is getoetst aan de vraag:

> *"Helpt dit de zorgbegeleider zijn werk beter te doen, of controleert het alleen zijn werk?"*

Features die enkel controleren zonder te helpen — zoals GPS-verificatie, automatische aanwezigheidsdetectie, tijd-tracking op minuten-niveau — zijn bewust buiten scope gehouden en **zullen ook niet** als verbetervoorstel worden ingediend.

---

**Zie ook:** [Privacy](privacy.md) · [Security](security.md) · [Ontwerpdocument (index)](../ontwerpdocument.md)
