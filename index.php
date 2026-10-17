<?php
session_start();

$pageTitle = "Welcome";

require_once "header.php";
?>

<div class="page-header">
    <h1 class="page-title">Welcome to Ticketheaven</h1>
</div>

<div style="max-width: 800px; margin: 1rem auto;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Customer Support &amp; Ticket Tracking System</span>
        </div>
        <div class="card-body" style="font-size: 0.95rem; line-height: 1.7;">
            <p>
                <strong>Ticketheaven</strong> is a web-based service desk application built for IS-115 by Noah and Tobias.
                It allows customers and staff to submit support requests, track issues in real-time, and manage tickets efficiently.
            </p>

            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php if (isset($_SESSION["user_id"])): ?>
                    <a href="tickets.php" class="btn btn-primary">Go to Dashboard &rarr;</a>
                    <a href="create_ticket.php" class="btn btn-outline">+ Create Ticket</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Log In &rarr;</a>
                    <a href="create_ticket.php" class="btn btn-outline">+ Submit a Ticket</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once "footer.php";
?>