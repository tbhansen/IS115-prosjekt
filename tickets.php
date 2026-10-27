<?php
/**
 * tickets.php
 * 
 * Internt dashbord for saksbehandlere (Staff Dashboard).
 * Gir full oversikt over egne saker, ufordelte saker, alle saker og status-statistikk.
 */

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/functions.php";

// Krever at brukeren er innlogget som ansatt
require_login();

$pageTitle = "Staff Dashboard";
$currentUserId = get_logged_in_user_id();

// 1. Hent saker tildelt innlogget saksbehandler
$ownedTickets = get_tickets_assigned_to($pdo, $currentUserId);

// 2. Hent ufordelte saker som venter på oppfølging
$unassignedTickets = get_unassigned_tickets($pdo);

// 3. Hent alle saker med tilknyttet saksbehandlernavn
$allTickets = get_all_tickets($pdo);

// 4. Hent statistikk over saker fordelt på status
$statusCounts = get_ticket_status_counts($pdo);

require_once __DIR__ . "/includes/header.php";
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Staff Dashboard</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Overview of customer inquiries, assignments, and ticket statuses.</p>
    </div>
    <div>
        <a href="create_ticket.php" class="btn btn-primary">+ Create Ticket (for Customer)</a>
    </div>
</div>

<!-- To-kolonners layout for dashbord -->
<div class="dashboard-grid">

    <!-- VENSTRE KOLONNE: Hovedlister med saker -->
    <div class="dashboard-column">

        <!-- Modul 1: Saker tildelt meg -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">&#9662; Tickets Assigned to Me</span>
                <span class="card-count">(Found <?php echo count($ownedTickets); ?> <?php echo count($ownedTickets) === 1 ? 'ticket' : 'tickets'; ?>)</span>
            </div>
            <div class="card-body-table table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 140px;">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ownedTickets)): ?>
                            <tr>
                                <td colspan="5" class="table-empty">No tickets currently assigned to you.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ownedTickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo e($ticket["id"]); ?></strong></td>
                                    <td>
                                        <a class="ticket-link" href="ticket-view.php?id=<?php echo e($ticket["id"]); ?>">
                                            <?php echo e($ticket["title"]); ?>
                                        </a>
                                    </td>
                                    <td><?php echo e($ticket["customer_name"]); ?></td>
                                    <td><?php echo render_status_badge($ticket["status"]); ?></td>
                                    <td><?php echo format_datetime($ticket["created_at"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modul 2: Ufordelte saker (trenger oppfølging) -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">&#9662; Unassigned Tickets (Need Attention)</span>
                <span class="card-count">(Found <?php echo count($unassignedTickets); ?> <?php echo count($unassignedTickets) === 1 ? 'ticket' : 'tickets'; ?>)</span>
            </div>
            <div class="card-body-table table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 140px;">Created</th>
                            <th style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($unassignedTickets)): ?>
                            <tr>
                                <td colspan="6" class="table-empty">All tickets are assigned. Great job!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($unassignedTickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo e($ticket["id"]); ?></strong></td>
                                    <td>
                                        <a class="ticket-link" href="ticket-view.php?id=<?php echo e($ticket["id"]); ?>">
                                            <?php echo e($ticket["title"]); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo e($ticket["customer_name"]); ?><br>
                                        <small style="color: #64748b;"><?php echo e($ticket["customer_email"]); ?></small>
                                    </td>
                                    <td><?php echo render_status_badge($ticket["status"]); ?></td>
                                    <td><?php echo format_datetime($ticket["created_at"]); ?></td>
                                    <td>
                                        <a href="ticket-view.php?id=<?php echo e($ticket["id"]); ?>" class="btn btn-outline btn-sm">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modul 3: Alle saker i systemet -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">&#9662; All Tickets Overview</span>
                <span class="card-count">(Total <?php echo count($allTickets); ?>)</span>
            </div>
            <div class="card-body-table table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th>Assigned Staff</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 140px;">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allTickets)): ?>
                            <tr>
                                <td colspan="6" class="table-empty">No tickets in system yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allTickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo e($ticket["id"]); ?></strong></td>
                                    <td>
                                        <a class="ticket-link" href="ticket-view.php?id=<?php echo e($ticket["id"]); ?>">
                                            <?php echo e($ticket["title"]); ?>
                                        </a>
                                    </td>
                                    <td><?php echo e($ticket["customer_name"]); ?></td>
                                    <td>
                                        <?php if (!empty($ticket["assigned_to_name"])): ?>
                                            <?php echo e($ticket["assigned_to_name"]); ?>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-style: italic;">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo render_status_badge($ticket["status"]); ?></td>
                                    <td><?php echo format_datetime($ticket["created_at"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- HØYRE KOLONNE: Statistikk og hurtighandlinger -->
    <div class="dashboard-column">

        <!-- Modul: Kø- og statusoversikt -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Queue Status</span>
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
                            <td class="queue-count"><?php echo $statusCounts["open"]; ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-in_progress">In Progress</span></td>
                            <td class="queue-count"><?php echo $statusCounts["in_progress"]; ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-closed">Closed</span></td>
                            <td class="queue-count"><?php echo $statusCounts["closed"]; ?></td>
                        </tr>
                        <tr style="background-color: #f8fafc; font-weight: bold;">
                            <td>Total tickets</td>
                            <td class="queue-count"><?php echo $statusCounts["total"]; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modul: Hurtighandlinger -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Quick Actions</span>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 0.6rem;">
                <a href="create_ticket.php" class="btn btn-primary" style="text-align: center;">+ New Ticket (Internal)</a>
                <a href="index.php" target="_blank" class="btn btn-outline" style="text-align: center;">Open Customer Form &UpperRightArrow;</a>
                <a href="create_user.php" class="btn btn-outline" style="text-align: center;">+ Add Staff User</a>
                <a href="logout.php" class="btn btn-outline" style="text-align: center; color: #c5221f;">Log Out</a>
            </div>
        </div>

    </div>

</div>

<?php
require_once __DIR__ . "/includes/footer.php";
?>
