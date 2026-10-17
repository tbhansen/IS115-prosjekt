<?php
session_start();

require_once "db.php";

// Redirect to login if user is not authenticated
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Tickets at a glance";
$currentUserId = $_SESSION["user_id"];

// 1. Fetch tickets assigned to the logged-in user
$stmtOwned = $pdo->prepare(
    "SELECT * FROM tickets 
     WHERE assigned_to = ? 
     ORDER BY created_at DESC 
     LIMIT 10"
);
$stmtOwned->execute([$currentUserId]);
$ownedTickets = $stmtOwned->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch newest unassigned tickets (unowned)
$stmtUnassigned = $pdo->query(
    "SELECT * FROM tickets 
     WHERE assigned_to IS NULL 
     ORDER BY created_at DESC 
     LIMIT 10"
);
$unassignedTickets = $stmtUnassigned->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch all tickets for the complete overview
$stmtAll = $pdo->query(
    "SELECT tickets.*, users.name AS created_by_name 
     FROM tickets 
     LEFT JOIN users ON tickets.created_by = users.id 
     ORDER BY tickets.created_at DESC"
);
$allTickets = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

// 4. Status counts for the Queue list widget
$countOpen = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
$countInProgress = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn();
$countClosed = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'")->fetchColumn();
$countTotal = $countOpen + $countInProgress + $countClosed;

// Include standard header with navigation bar
require_once "header.php";
?>

<div class="page-header">
    <h1 class="page-title">Tickets at a glance</h1>
    <div>
        <a href="create_ticket.php" class="btn btn-primary">+ Create Ticket</a>
    </div>
</div>

<!-- Two-column dashboard layout inspired by Request Tracker (RT) -->
<div class="dashboard-grid">

    <!-- LEFT COLUMN: Main Ticket Lists -->
    <div class="dashboard-column">

        <!-- Widget 1: Tickets assigned to me -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    &#9662; Highest priority tickets I own
                </span>
                <span class="card-count">(Found <?php echo count($ownedTickets); ?> <?php echo count($ownedTickets) === 1 ? 'ticket' : 'tickets'; ?>)</span>
            </div>
            <div class="card-body-table table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Subject</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 150px;">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ownedTickets)): ?>
                            <tr>
                                <td colspan="4" class="table-empty">No tickets currently assigned to you.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ownedTickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ticket["id"]); ?></strong></td>
                                    <td>
                                        <a class="ticket-link" href="ticket-view.php?id=<?php echo htmlspecialchars($ticket["id"]); ?>">
                                            <?php echo htmlspecialchars($ticket["title"]); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($ticket["status"]); ?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $ticket["status"])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($ticket["created_at"])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Widget 2: Newest unowned tickets -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    &#9662; 10 newest unowned tickets
                </span>
                <span class="card-count">(Found <?php echo count($unassignedTickets); ?> <?php echo count($unassignedTickets) === 1 ? 'ticket' : 'tickets'; ?>)</span>
            </div>
            <div class="card-body-table table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Subject</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 150px;">Created</th>
                            <th style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($unassignedTickets)): ?>
                            <tr>
                                <td colspan="5" class="table-empty">No unassigned tickets found. Great job!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($unassignedTickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ticket["id"]); ?></strong></td>
                                    <td>
                                        <a class="ticket-link" href="ticket-view.php?id=<?php echo htmlspecialchars($ticket["id"]); ?>">
                                            <?php echo htmlspecialchars($ticket["title"]); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($ticket["status"]); ?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $ticket["status"])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($ticket["created_at"])); ?></td>
                                    <td>
                                        <a href="ticket-view.php?id=<?php echo htmlspecialchars($ticket["id"]); ?>" class="btn btn-outline btn-sm">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Widget 3: All Tickets -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    &#9662; All tickets in system
                </span>
                <span class="card-count">(Total <?php echo count($allTickets); ?>)</span>
            </div>
            <div class="card-body-table table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Subject</th>
                            <th>Created by</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 150px;">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allTickets)): ?>
                            <tr>
                                <td colspan="5" class="table-empty">No tickets created yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allTickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ticket["id"]); ?></strong></td>
                                    <td>
                                        <a class="ticket-link" href="ticket-view.php?id=<?php echo htmlspecialchars($ticket["id"]); ?>">
                                            <?php echo htmlspecialchars($ticket["title"]); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($ticket["created_by_name"] ?? 'Unknown'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($ticket["status"]); ?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $ticket["status"])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($ticket["created_at"])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: Queue stats & quick widgets -->
    <div class="dashboard-column">

        <!-- Queue Status Summary Widget -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Queue list</span>
            </div>
            <div class="card-body-table">
                <table class="queue-table">
                    <thead>
                        <tr>
                            <th>Status Queue</th>
                            <th style="text-align: right;">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-open">Open</span></td>
                            <td class="queue-count"><?php echo $countOpen; ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-in_progress">In Progress</span></td>
                            <td class="queue-count"><?php echo $countInProgress; ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-closed">Closed</span></td>
                            <td class="queue-count"><?php echo $countClosed; ?></td>
                        </tr>
                        <tr style="background-color: #f8fafc; font-weight: bold;">
                            <td>Total tickets</td>
                            <td class="queue-count"><?php echo $countTotal; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick actions widget -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Quick Actions</span>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 0.6rem;">
                <a href="create_ticket.php" class="btn btn-primary" style="text-align: center;">+ Create New Ticket</a>
                <a href="create_user.php" class="btn btn-outline" style="text-align: center;">+ Add New User</a>
                <a href="logout.php" class="btn btn-outline" style="text-align: center; color: #c5221f;">Log out</a>
            </div>
        </div>

        <!-- Quick tips / School project info -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">System Info</span>
            </div>
            <div class="card-body" style="font-size: 0.85rem; color: #475569;">
                <p>Welcome to <strong>Ticketheaven</strong>.</p>
                <p style="margin-top: 0.5rem;">Use this dashboard to manage support requests, track ticket status, and assign tasks across the team.</p>
            </div>
        </div>

    </div>

</div>

<?php
// Include standard footer
require_once "footer.php";
?>