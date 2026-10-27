# Ticketheaven - Kundeservice og Saksbehandlingssystem

Dette prosjektet er utviklet som en del av emnet **IS-115** ved Universitetet i Agder (UiA).

---

## 1. Prosjektidé og formål

**Ticketheaven** er et webbasert henvendelses- og saksbehandlingssystem (helpdesk / ticketing system) inspirert av kjente løsninger som *Request Tracker (RT)*. Formålet med løsningen er å gi:
1. **Kunder:** En enkel, åpen portal for å sende inn supportforespørsler uten krav om innlogging.
2. **Kundebehandlere (ansatte/staff):** Et oversiktlig internt kontrollpanel (Staff Dashboard) for å håndtere, delegere, besvare og endre status på innkomne saker.

---

## 2. Brukerroller og planlagt funksjonalitet

### A. Kunde (Offentlig tilgang)
* **Kontaktskjema (`index.php`):** Kunden fyller ut navn, e-post, telefonnummer, emne og saksbeskrivelse.
* **Validering:** Skjemaet validerer at påkrevde felter er fylt ut og at e-postadressen har gyldig format.
* **Kvittering:** Kunden mottar en bekreftelse på skjermen med et unikt saksnummer (Ticket ID) ved innsending.

### B. Kundebehandler / Ansatt (Krever innlogging)
* **Innlogging og autentisering (`login.php`, `logout.php`):** Sikker pålogging for ansatte med passordhashing (`password_hash` og `password_verify`).
* **Brukerhåndtering (`create_user.php`):** Mulighet for å opprette nye saksbehandler-kontoer.
* **Oversikt / Staff Dashboard (`tickets.php`):**
  - **Mine saker:** Viser saker som er tildelt den innloggede saksbehandleren.
  - **Ufordelte saker:** Viser nye saker som venter på å bli plukket opp.
  - **Alle saker:** Komplett tabell over alle henvendelser med tildelt behandler og opprettelsesdato.
  - **Status-teller:** Rask oversikt over antall åpne (`open`), pågående (`in_progress`) og lukkede (`closed`) saker.
* **Saksvisning og behandling (`ticket-view.php`):**
  - Detaljert visning av sakens innhold og kundeinformasjon.
  - Mulighet til å endre sakens status (`open`, `in_progress`, `closed`).
  - Mulighet til å delegere / tildele saken til en annen kollega.
* **Opprette intern sak (`create_ticket.php`):** Ansatte kan registrere saker direkte på vegne av kunder (f.eks. ved henvendelse på telefon).

---

## 3. Sprintoversikt og oppfylte krav

| Sprint | Navn | Oppfylte krav i prosjektet |
|---|---|---|
| **Sprint 1** | Prosjektoppstart og grensesnitt | Prosjektbeskrivelse i README, helhetlig grafisk grensesnitt og navigasjonsmeny (`header.php`, `footer.php`, `style.css`). |
| **Sprint 2** | Skjemaer og dynamisk innhold | HTML-skjemaer for kundehenvendelse, saksbehandling, innlogging og registrering. Behandling av `POST`-data med validering og feilmeldinger. |
| **Sprint 3** | Strukturvalg og kodearkitektur | Gjennomtenkt mappestruktur (`includes/`, `assets/`, `sql/`). Gjenbrukbare funksjoner i `includes/functions.php` for autentisering, databaserutiner og HTML-hjelpere. Konsekvent navngivning og ryddig kommentering. |
| **Sprint 4** | Database og brukerhåndtering | MariaDB-database satt opp via DDEV. Sikker tilkobling med PDO og prepared statements. Passordhashing og sesjonshåndtering. |

---

## 4. Prosjektstruktur

```text
IS115-prosjekt/
├── index.php             # Offentlig forside / innsendingsskjema for kunder
├── tickets.php           # Internt dashbord for saksbehandlere
├── ticket-view.php       # Detaljvisning og oppdatering av enkeltsak
├── create_ticket.php     # Registrering av ny sak (for ansatte)
├── login.php             # Innloggingsside for ansatte
├── logout.php            # Logger ut og avslutter sesjonen
├── create_user.php       # Registrering av nye ansatte
├── README.md             # Prosjektdokumentasjon og kravspesifikasjon
├── includes/
│   ├── db.php            # PDO-databasetilkobling
│   ├── functions.php     # Gjenbrukbare hjelpe- og databasefunksjoner
│   ├── header.php        # Felles toppmeny og navigasjon
│   └── footer.php        # Felles bunntekst
├── assets/
│   └── css/
│       └── style.css     # CSS-stilark for hele nettstedet
└── sql/
    └── database.sql      # Tabelloppsett og eksempeldata
```

---

## 5. Kjøring og testing lokalt (med DDEV)

1. Start DDEV-miljøet:
   ```bash
   ddev start
   ```
2. Importer databasen (hvis ikke allerede gjort):
   ```bash
   ddev import-db --file=sql/database.sql
   ```
3. Åpne prosjektet i nettleseren:
   ```bash
   ddev launch
   ```
4. Logg inn som ansatt (dersom det finnes en opprettet bruker, eller opprett via `create_user.php`).
