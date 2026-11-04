<?php
/**
 * includes/functions.php
 * 
 * Shared helper function library for Ticketheaven.
 * Contains reusable functions for:
 * 1. Security and presentation (XSS escaping, formatting, status badges)
 * 2. Authentication and access control (session login verification)
 * 3. Database operations (fetching, inserting, and updating tickets and staff)
 */

// ============================================================================
// 1. SECURITY AND PRESENTATION (HELPERS)
// ============================================================================

/**
 * Escapes output strings safely for HTML rendering to prevent XSS attacks.
 * 
 * @param mixed $text The string or value to sanitize.
 * @return string Safe HTML escaped string.
 */
function e($text): string
{
    return htmlspecialchars((string)($text ?? ""), ENT_QUOTES, "UTF-8");
}

/**
 * Formats a MySQL TIMESTAMP or date string into a readable date and time.
 * Example: "2026-10-19 14:43:00" -> "19.10.2026 14:43"
 * 
 * @param string|null $timestamp Raw database timestamp.
 * @return string Formatted date string, or '-' if empty.
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
 * Returns an HTML status badge for a given ticket status.
 * 
 * @param string $status Ticket status ('open', 'in_progress', 'closed').
 * @return string Rendered HTML badge span.
 */
function render_status_badge(string $status): string
{
    switch ($status) {
        case "open":
            return '<span class="badge badge-open">Open</span>';
        case "in_progress":
            return '<span class="badge badge-in_progress">In Progress</span>';
        case "closed":
            return '<span class="badge badge-closed">Closed</span>';
        default:
            return '<span class="badge">' . e($status) . '</span>';
    }
}


// ============================================================================
// 2. AUTHENTICATION AND ACCESS CONTROL
// ============================================================================

/**
 * Checks whether the current visitor is logged in as staff.
 * 
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION["user_id"]);
}

/**
 * Restricts access to authenticated staff members.
 * Redirects unauthenticated visitors to login.php.
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
 * Returns the active staff member's ID from session, or null if not logged in.
 * 
 * @return int|null Staff user ID or null.
 */
function get_logged_in_user_id(): ?int
{
    return is_logged_in() ? (int)$_SESSION["user_id"] : null;
}


// ============================================================================
// 3. DATABASE OPERATIONS FOR TICKETS
// ============================================================================

/**
 * Retrieves tickets assigned to a specific staff member.
 * 
 * @param PDO $pdo Active database connection.
 * @param int $userId Staff member user ID.
 * @param int $limit Maximum number of tickets to retrieve (default: 10).
 * @return array List of tickets.
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
 * Retrieves unassigned tickets awaiting assignment.
 * 
 * @param PDO $pdo Active database connection.
 * @param int $limit Maximum number of tickets to retrieve (default: 10).
 * @return array List of unassigned tickets.
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
 * Retrieves all tickets in the system with creator and assignee staff names.
 * 
 * @param PDO $pdo Active database connection.
 * @return array Complete list of tickets.
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
 * Retrieves a single ticket by its ID, joined with creator and assignee names.
 * 
 * @param PDO $pdo Active database connection.
 * @param int $ticketId Unique ticket ID.
 * @return array|false Ticket record as associative array, or false if not found.
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
 * Counts tickets grouped by status (open, in_progress, closed).
 * 
 * @param PDO $pdo Active database connection.
 * @return array Associative array with keys 'open', 'in_progress', 'closed', and 'total'.
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
 * Retrieves all staff members in the system ordered alphabetically.
 * Useful for building assignment dropdown lists.
 * 
 * @param PDO $pdo Active database connection.
 * @return array List of staff users with id, name, and username.
 */
function get_all_staff(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, name, username FROM users ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Inserts a new customer inquiry submitted through the public support form.
 * 
 * @param PDO $pdo Active database connection.
 * @param string $title Ticket title/subject.
 * @param string $description Detailed message description.
 * @param string $name Customer full name.
 * @param string $email Customer email address.
 * @param string|null $phone Customer phone number (optional).
 * @return int Newly created ticket ID.
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
 * Inserts a new ticket created internally by a staff member.
 * 
 * @param PDO $pdo Active database connection.
 * @param string $title Ticket title/subject.
 * @param string $description Detailed message description.
 * @param string $name Customer full name.
 * @param string $email Customer email address.
 * @param string|null $phone Customer phone number.
 * @param string $status Initial status ('open', 'in_progress', 'closed').
 * @param int|null $assignedTo User ID of staff member assigned, or null.
 * @param int $createdBy User ID of staff member creating the ticket.
 * @return int Newly created ticket ID.
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
 * Updates status and assignee for an existing ticket.
 * 
 * @param PDO $pdo Active database connection.
 * @param int $ticketId Ticket ID.
 * @param string $status New status ('open', 'in_progress', 'closed').
 * @param int|null $assignedTo Staff user ID, or null for unassigned.
 * @return bool True on success.
 */
function update_ticket_status_and_assignee(PDO $pdo, int $ticketId, string $status, ?int $assignedTo): bool
{
    $stmt = $pdo->prepare(
        "UPDATE tickets SET status = ?, assigned_to = ? WHERE id = ?"
    );
    return $stmt->execute([$status, $assignedTo, $ticketId]);
}
