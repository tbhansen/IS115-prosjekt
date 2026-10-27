<?php
/**
 * includes/functions.php
 * 
 * Felles funksjonsbibliotek for Ticketheaven.
 * Inneholder gjenbrukbare funksjoner for:
 * 1. Sikkerhet og visning (XSS-beskyttelse, formatering, status-merker)
 * 2. Autentisering og tilgangskontroll (innloggingssjekk)
 * 3. Databaseoperasjoner (henting og oppdatering av saker og brukere)
 */

// ============================================================================
// 1. SIKKERHET OG VISNING (HELPERS)
// ============================================================================

/**
 * Renser tekst for sikker utskrift i HTML (beskytter mot XSS-angrep).
 * 
 * @param mixed $text Teksten som skal skrives ut.
 * @return string Sikret tekst.
 */
function e($text): string
{
    return htmlspecialchars((string)($text ?? ""), ENT_QUOTES, "UTF-8");
}

/**
 * Formaterer en MySQL TIMESTAMP eller datostreng til et leservennlig format.
 * Eksempel: "2026-10-19 14:43:00" -> "19.10.2026 14:43"
 * 
 * @param string|null $timestamp Datostrengen fra databasen.
 * @return string Formatert dato eller bindestrek hvis tom.
 */
function format_datetime(?string $timestamp): string
{
    if (empty($timestamp)) {
        return "-";
    }

    $time = strtotime($timestamp);
    if ($time === false) {
        return e($timestamp);
    }

    return date("d.m.Y H:i", $time);
}

/**
 * Returnerer en formatert HTML-badge (merkelapp) for en saksstatus.
 * 
 * @param string $status Statusen ('open', 'in_progress', 'closed').
 * @return string HTML-kode for status-badge.
 */
function render_status_badge(string $status): string
{
    switch ($status) {
        case "open":
            return '<span class="badge badge-open">Open</span>';
        case "in_progress":
            return '<span class="badge badge-progress">In Progress</span>';
        case "closed":
            return '<span class="badge badge-closed">Closed</span>';
        default:
            return '<span class="badge">' . e($status) . '</span>';
    }
}


// ============================================================================
// 2. AUTENTISERING OG SESJONSHÅNDTERING
// ============================================================================

/**
 * Sjekker om brukeren for øyeblikket er logget inn som ansatt.
 * 
 * @return bool True dersom brukeren er logget inn, ellers false.
 */
function is_logged_in(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION["user_id"]);
}

/**
 * Krever at brukeren er innlogget for å se siden.
 * Dersom brukeren ikke er logget inn, videresendes de til login.php.
 * 
 * @return void
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Returnerer ID-en til den innloggede brukeren, eller null dersom ikke innlogget.
 * 
 * @return int|null Bruker-ID eller null.
 */
function get_logged_in_user_id(): ?int
{
    return is_logged_in() ? (int)$_SESSION["user_id"] : null;
}


// ============================================================================
// 3. DATABASEOPERASJONER FOR SAKER (TICKETS)
// ============================================================================

/**
 * Henter saker som er tildelt en bestemt saksbehandler.
 * 
 * @param PDO $pdo Databasetilkobling.
 * @param int $userId Bruker-ID til saksbehandleren.
 * @param int $limit Maksimalt antall saker som skal hentes (standard: 10).
 * @return array Liste over saker.
 */
function get_tickets_assigned_to(PDO $pdo, int $userId, int $limit = 10): array
{
    $stmt = $pdo->prepare(
        "SELECT * FROM tickets 
         WHERE assigned_to = ? 
         ORDER BY created_at DESC 
         LIMIT " . (int)$limit
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Henter ufordelte saker (saker som ikke er tildelt noen saksbehandler ennå).
 * 
 * @param PDO $pdo Databasetilkobling.
 * @param int $limit Maksimalt antall saker som skal hentes (standard: 10).
 * @return array Liste over ufordelte saker.
 */
function get_unassigned_tickets(PDO $pdo, int $limit = 10): array
{
    $stmt = $pdo->prepare(
        "SELECT * FROM tickets 
         WHERE assigned_to IS NULL 
         ORDER BY created_at DESC 
         LIMIT " . (int)$limit
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Henter alle saker i systemet med navn på tildelt saksbehandler og oppretter.
 * 
 * @param PDO $pdo Databasetilkobling.
 * @return array Liste over alle saker.
 */
function get_all_tickets(PDO $pdo): array
{
    $sql = "SELECT tickets.*, 
                   assignee.name AS assigned_to_name,
                   creator.name AS created_by_staff_name
            FROM tickets 
            LEFT JOIN users AS assignee ON tickets.assigned_to = assignee.id
            LEFT JOIN users AS creator ON tickets.created_by = creator.id
            ORDER BY tickets.created_at DESC";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Henter en enkelt sak basert på ID, inkludert oppretter og tildelt saksbehandler.
 * 
 * @param PDO $pdo Databasetilkobling.
 * @param int $ticketId Sakens unike ID.
 * @return array|false Saksdata som assosiativt array, eller false dersom ikke funnet.
 */
function get_ticket_by_id(PDO $pdo, int $ticketId)
{
    $stmt = $pdo->prepare(
        "SELECT tickets.*,
                creator.name AS created_by_staff_name,
                assignee.name AS assigned_to_name
         FROM tickets
         LEFT JOIN users AS creator ON tickets.created_by = creator.id
         LEFT JOIN users AS assignee ON tickets.assigned_to = assignee.id
         WHERE tickets.id = ?"
    );
    $stmt->execute([$ticketId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Teller antall saker gruppert etter status (open, in_progress, closed).
 * 
 * @param PDO $pdo Databasetilkobling.
 * @return array Assosiativt array med nøklene 'open', 'in_progress', 'closed', 'total'.
 */
function get_ticket_status_counts(PDO $pdo): array
{
    $countOpen       = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
    $countInProgress = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn();
    $countClosed     = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'")->fetchColumn();

    return [
        "open"        => $countOpen,
        "in_progress" => $countInProgress,
        "closed"      => $countClosed,
        "total"       => $countOpen + $countInProgress + $countClosed
    ];
}

/**
 * Henter alle saksbehandlere/ansatte i systemet (sortert etter navn).
 * Brukes for eksempel i nedtrekksmenyer for å tildele saker.
 * 
 * @param PDO $pdo Databasetilkobling.
 * @return array Liste over brukere med id, name og username.
 */
function get_all_staff(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, name, username FROM users ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Oppretter en ny sak fra en kunde (uten tilknyttet intern bruker).
 * 
 * @param PDO $pdo Databasetilkobling.
 * @param string $title Sakens tittel/emne.
 * @param string $description Beskrivelse av henvendelsen.
 * @param string $name Kundens navn.
 * @param string $email Kundens e-postadresse.
 * @param string|null $phone Kundens telefonnummer (valgfritt).
 * @return int ID-en til den nyopprettede saken.
 */
function create_customer_ticket(PDO $pdo, string $title, string $description, string $name, string $email, ?string $phone): int
{
    $stmt = $pdo->prepare(
        "INSERT INTO tickets (title, description, customer_name, customer_email, customer_phone, status, created_by)
         VALUES (?, ?, ?, ?, ?, 'open', NULL)"
    );
    $stmt->execute([
        $title,
        $description,
        $name,
        $email,
        $phone ?: null
    ]);
    return (int)$pdo->lastInsertId();
}

/**
 * Oppretter en sak internt av en saksbehandler.
 * 
 * @param PDO $pdo Databasetilkobling.
 * @param string $title Sakens tittel/emne.
 * @param string $description Beskrivelse av henvendelsen.
 * @param string $name Kundens navn.
 * @param string $email Kundens e-postadresse.
 * @param string|null $phone Kundens telefonnummer.
 * @param string $status Sakens status ('open', 'in_progress', 'closed').
 * @param int|null $assignedTo ID til saksbehandler saken skal tildeles til.
 * @param int $createdBy ID til saksbehandler som oppretter saken.
 * @return int ID-en til den nyopprettede saken.
 */
function create_staff_ticket(
    PDO $pdo,
    string $title,
    string $description,
    string $name,
    string $email,
    ?string $phone,
    string $status,
    ?int $assignedTo,
    int $createdBy
): int {
    $stmt = $pdo->prepare(
        "INSERT INTO tickets (title, description, customer_name, customer_email, customer_phone, status, created_by, assigned_to)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $title,
        $description,
        $name,
        $email,
        $phone ?: null,
        $status,
        $createdBy,
        $assignedTo
    ]);
    return (int)$pdo->lastInsertId();
}

/**
 * Oppdaterer status og tildeling på en eksisterende sak.
 * 
 * @param PDO $pdo Databasetilkobling.
 * @param int $ticketId ID til saken.
 * @param string $status Ny status ('open', 'in_progress', 'closed').
 * @param int|null $assignedTo ID til ny saksbehandler, eller null for ufordelt.
 * @return bool True dersom oppdateringen var vellykket.
 */
function update_ticket_status_and_assignee(PDO $pdo, int $ticketId, string $status, ?int $assignedTo): bool
{
    $stmt = $pdo->prepare(
        "UPDATE tickets SET status = ?, assigned_to = ? WHERE id = ?"
    );
    return $stmt->execute([$status, $assignedTo, $ticketId]);
}
