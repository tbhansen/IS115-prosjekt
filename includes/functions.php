<?php
/**
 * Ticketheaven - Shared Helper & Data-Access Library
 * 
 * Purpose:
 * Centralizes the application's reusable service layer into three dedicated domains:
 * 1. Security and Presentation (output escaping, date formatting, visual status badges)
 * 2. Authentication and Access Control (session state verification and route guarding)
 * 3. Database Operations (CRUD abstraction for tickets and staff records)
 * 
 * Architectural & Design Choices:
 * - Decouples SQL queries and business logic from HTML presentation files (index.php, tickets.php,
 *   ticket_view.php). This prevents code duplication, simplifies maintenance, and promotes DRY principles.
 * - Enforces parameter binding on all dynamic database operations using PDO prepared statements to completely
 *   eliminate SQL injection vulnerabilities.
 * - Implements strict type declarations on function signatures to improve reliability and catch type mismatches early.
 * 
 * Assumptions & Limitations:
 * - Assumes an active PDO database connection ($pdo) is supplied to all data-access functions.
 * - Relies on native PHP sessions ($_SESSION) for state management.
 * - Functions are currently organized procedurally; as the application expands in Sprints 4 and 5,
 *   they can be grouped into dedicated domain classes or service repositories (e.g., TicketService, AuthService).
 */

// ============================================================================
// 1. SECURITY AND PRESENTATION HELPERS
// ============================================================================

/**
 * Escapes output strings safely for HTML rendering to prevent Cross-Site Scripting (XSS).
 * 
 * Why this is written this way:
 * - Uses htmlspecialchars() with ENT_QUOTES to encode both double quotes (") and single quotes ('),
 *   ensuring safety inside HTML tag attributes as well as within standard text nodes.
 * - Specifies UTF-8 explicitly to guarantee consistent multi-byte character encoding.
 * - Accepts mixed types and casts null/integers safely to strings, preventing type errors in PHP 8.1+.
 * 
 * Assumptions & Limitations:
 * - Assumption: Designed for standard HTML context. If injecting into inline JavaScript, JSON, or CSS,
 *   specialized encoding mechanisms (e.g., json_encode) should be used instead.
 * 
 * @param mixed $text The string or value to sanitize.
 * @return string Safe HTML escaped string.
 */
function e($text): string
{
    return htmlspecialchars((string)($text ?? ""), ENT_QUOTES, "UTF-8");
}

/**
 * Formats a MySQL TIMESTAMP or date string into a localized, human-readable date and time.
 * 
 * Why this is written this way:
 * - Handles null or empty timestamp values gracefully, returning a neutral dash ("-") rather than
 *   raising a deprecation warning in PHP 8.x when passing null to strtotime().
 * - Checks whether strtotime() parsing succeeds; if it encounters an invalid format, it falls back
 *   to escaping and returning the raw string rather than crashing or displaying 01.01.1970.
 * 
 * Assumptions & Limitations:
 * - Assumption: Assumes server timezone configuration matches the intended display timezone.
 *   Does not currently support per-user international timezone offsets.
 * 
 * @param string|null $timestamp Raw database timestamp string (e.g., '2026-11-04 14:30:00').
 * @return string Formatted date string (e.g., '04.11.2026 14:30'), or '-' if empty.
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
 * Generates an HTML status badge styled with the appropriate CSS class for a given ticket state.
 * 
 * Why this is written this way:
 * - Centralizes status badge HTML markup so that styling updates or new status designs propagate
 *   instantly across all views (dashboard, ticket lists, and single ticket view) without markup edits.
 * - The default branch sanitizes unknown statuses with e(), protecting the page if a custom or unexpected
 *   status value exists in the database.
 * 
 * Assumptions & Limitations:
 * - Assumption: Ticket statuses are expected to match 'open', 'in_progress', or 'closed'.
 * 
 * @param string $status Ticket status identifier.
 * @return string Rendered HTML span element with contextual CSS classes.
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
 * Checks whether the current visitor is authenticated as a staff member.
 * 
 * Why this is written this way:
 * - Inspects session_status() before invoking session_start() to prevent "session already started"
 *   notices when this function is called from files that include common headers.
 * - Checks for the existence of $_SESSION['user_id'] rather than a simple boolean flag, guaranteeing
 *   that an identifiable user primary key exists in session memory.
 * 
 * Assumptions & Limitations:
 * - Assumption: A valid user_id in the session implies valid staff authentication.
 * - Limitation: Does not verify whether the staff member's account has been disabled or deleted
 *   in the database during an active session lifetime.
 * 
 * @return bool True if authenticated staff session exists, false otherwise.
 */
function is_logged_in(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION["user_id"]);
}

/**
 * Guards private staff routes by redirecting unauthenticated visitors to the login page.
 * 
 * Why this is written this way:
 * - Immediately issues an HTTP 302 redirection header to login.php and halts execution with exit.
 *   Calling exit is critical; without it, PHP would continue executing the subsequent lines of the
 *   calling script and render confidential ticket data to unauthenticated visitors.
 * 
 * Assumptions & Limitations:
 * - Assumption: Assumes login.php is located in the root web directory relative to the current script.
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
 * Retrieves the currently authenticated staff member's primary key ID.
 * 
 * @return int|null Staff user ID if authenticated, or null if unauthenticated.
 */
function get_logged_in_user_id(): ?int
{
    return is_logged_in() ? (int)$_SESSION["user_id"] : null;
}

// ============================================================================
// 3. DATABASE OPERATIONS FOR TICKETS & STAFF
// ============================================================================

/**
 * Retrieves tickets assigned to a specific staff member.
 * 
 * Why this is written this way:
 * - Uses a prepared statement with a positional placeholder (?) for the staff user ID to prevent SQL injection.
 * - Enforces an explicit integer cast on the limit parameter to prevent parameter manipulation.
 * - Orders results by created_at DESC so that the newest tickets appear at the top of the queue.
 * 
 * @param PDO $pdo Active database connection instance.
 * @param int $userId Primary key ID of the assigned staff user.
 * @param int $limit Maximum number of tickets to retrieve (default: 10).
 * @return array Array of associative arrays containing ticket rows.
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
 * Retrieves unassigned tickets awaiting triage by support staff.
 * 
 * Why this is written this way:
 * - Filters by 'assigned_to IS NULL', allowing staff to quickly identify incoming customer inquiries
 *   that have not yet been assigned to a handler.
 * 
 * @param PDO $pdo Active database connection instance.
 * @param int $limit Maximum number of unassigned tickets to return (default: 10).
 * @return array Array of associative arrays representing unassigned tickets.
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
 * Retrieves all tickets in the system, joined with creator and assignee staff names.
 * 
 * Why this is written this way:
 * - Uses two LEFT JOIN operations against the users table (once for the assignee and once for the staff creator).
 *   A LEFT JOIN is necessary because customer-submitted tickets have created_by = NULL and unassigned tickets
 *   have assigned_to = NULL; an INNER JOIN would incorrectly exclude those records.
 * - Resolves display names directly in SQL, avoiding the N+1 query problem of fetching user records per ticket in PHP.
 * 
 * Assumptions & Limitations:
 * - Limitation: Fetches all records without pagination. In high-volume production systems, pagination (LIMIT/OFFSET)
 *   should be implemented to prevent memory exhaustion.
 * 
 * @param PDO $pdo Active database connection instance.
 * @return array Complete list of ticket records with user names.
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
 * Retrieves a single ticket record by its unique ID, joined with creator and assignee names.
 * 
 * Why this is written this way:
 * - Parameterized query with where id = ? ensures secure lookup without SQL injection risk.
 * - Joins staff names in the same query to supply complete metadata to the ticket view in one round-trip.
 * 
 * @param PDO $pdo Active database connection instance.
 * @param int $ticketId Primary key of the ticket.
 * @return array|false Associative array of ticket data, or false if not found.
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
 * Aggregates ticket count metrics grouped by status.
 * 
 * Why this is written this way:
 * - Uses COUNT(*) queries with fetchColumn() to calculate totals directly in the database engine,
 *   which is significantly faster and more memory-efficient than fetching all ticket rows into PHP arrays.
 * 
 * @param PDO $pdo Active database connection instance.
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
 * Retrieves all staff members ordered alphabetically by full name.
 * 
 * Why this is written this way:
 * - Only selects necessary columns (id, name, username) rather than SELECT *, reducing data transfer
 *   and preventing sensitive fields like password_hash from ever being loaded into memory for this list.
 * 
 * @param PDO $pdo Active database connection instance.
 * @return array List of staff records with id, name, and username.
 */
function get_all_staff(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, name, username FROM users ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Inserts a new customer support ticket submitted via the public portal (index.php).
 * 
 * Why this is written this way:
 * - Pre-sets status to 'open' and created_by to NULL, identifying the ticket as external customer intake.
 * - Converts an empty phone string to NULL using ($phone ?: null) so that database foreign keys or column
 *   defaults are respected rather than inserting blank string values.
 * - Returns the newly generated primary key via lastInsertId() so the caller can display a confirmation receipt.
 * 
 * @param PDO $pdo Active database connection instance.
 * @param string $title Ticket title / inquiry subject.
 * @param string $description Detailed message contents.
 * @param string $name Customer full name.
 * @param string $email Customer contact email.
 * @param string|null $phone Optional customer phone number.
 * @return int Newly created ticket primary key ID.
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
 * Inserts a new ticket created internally by an authenticated staff member.
 * 
 * Why this is written this way:
 * - Allows staff to explicitly specify the initial status and assign the ticket directly to a colleague.
 * - Records created_by with the staff user's ID, establishing an audit trail of who created the ticket.
 * 
 * @param PDO $pdo Active database connection instance.
 * @param string $title Ticket title / inquiry subject.
 * @param string $description Detailed message contents.
 * @param string $name Customer full name.
 * @param string $email Customer contact email.
 * @param string|null $phone Customer phone number.
 * @param string $status Initial status ('open', 'in_progress', 'closed').
 * @param int|null $assignedTo Staff user ID to assign, or null for unassigned.
 * @param int $createdBy Staff user ID creating the ticket.
 * @return int Newly created ticket primary key ID.
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
 * Updates the lifecycle status and assigned staff member for an existing ticket.
 * 
 * Why this is written this way:
 * - Executes an atomic UPDATE query binding both fields simultaneously, preventing partial updates.
 * - Parameterized query prevents SQL injection when updating ticket states.
 * 
 * @param PDO $pdo Active database connection instance.
 * @param int $ticketId Primary key of the ticket to update.
 * @param string $status Validated status ('open', 'in_progress', 'closed').
 * @param int|null $assignedTo Staff user ID, or null to unassign.
 * @return bool True if statement executed successfully.
 */
function update_ticket_status_and_assignee(PDO $pdo, int $ticketId, string $status, ?int $assignedTo): bool
{
    $stmt = $pdo->prepare(
        "UPDATE tickets SET status = ?, assigned_to = ? WHERE id = ?"
    );
    return $stmt->execute([$status, $assignedTo, $ticketId]);
}
