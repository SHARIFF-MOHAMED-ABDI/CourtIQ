<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Student Resident Dashboard
 * Corresponds to Specification Figure 20 (Student Navigation Wireframe)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Enforce strict role-based access control
requireRole(['student']);

$user = currentUser();
$pageTitle = 'Student Portal | CourtIQ';

// Retrieve student application and maintenance data
$userId = (int)$user['id'];

// 1. Accommodation Application status
$appStmt = db()->prepare('SELECT hostel_preference, room_type, application_status, notes FROM accommodation_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$appStmt->bind_param('i', $userId);
$appStmt->execute();
$application = $appStmt->get_result()->fetch_assoc();
$appStmt->close();

// 2. Payment Records summary
$payStmt = db()->prepare('SELECT reference_no, amount, balance, status, payment_date FROM payment_records WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$payStmt->bind_param('i', $userId);
$payStmt->execute();
$payment = $payStmt->get_result()->fetch_assoc();
$payStmt->close();

// 3. Maintenance Requests
$maintStmt = db()->prepare('SELECT title, priority, status, created_at FROM maintenance_requests WHERE user_id = ? ORDER BY id DESC LIMIT 5');
$maintStmt->bind_param('i', $userId);
$maintStmt->execute();
$maintenanceList = $maintStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$maintStmt->close();

$allocationStmt = db()->prepare("SELECT r.id AS room_id, r.hostel_id, r.room_number, r.room_type, h.name AS hostel_name, a.allocated_at
    FROM room_allocations a JOIN rooms r ON r.id = a.room_id JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
$allocationStmt->bind_param('i', $userId);
$allocationStmt->execute();
$allocation = $allocationStmt->get_result()->fetch_assoc();
$allocationStmt->close();

$applicationScore = $application && in_array(strtolower($application['application_status']), ['approved', 'allocated'], true) ? 92 : ($application ? 68 : 35);
$paymentScore = $payment ? 84 : 30;
$maintenanceScore = empty($maintenanceList) ? 96 : max(55, 96 - (count($maintenanceList) * 8));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout">
    <!-- Top Dashboard Header -->
    <header class="dashboard-nav-bar">
        <div class="container dashboard-nav-inner">
            <a href="<?= projectUrl('index.php') ?>" class="brand" aria-label="CourtIQ Home">
                <span class="brand-icon-box">
                    <svg class="brand-svg" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="32" height="32" rx="8" fill="#102A43"/>
                        <path d="M16 6L7 13V25C7 25.5523 7.44772 26 8 26H13V19H19V26H24C24.5523 26 25 25.5523 25 25V13L16 6Z" fill="#2563EB"/>
                        <path d="M16 11L11 15V23H13V18C13 17.4477 13.4477 17 14 17H18C18.5523 17 19 17.4477 19 18V23H21V15L16 11Z" fill="#0D9488"/>
                    </svg>
                </span>
                <span class="brand-text">Court<span>IQ</span></span>
            </a>

            <div class="user-badge-wrap">
                <div class="user-profile-pill">
                    <span class="role-tag-badge badge-student">Student</span>
                    <span><?= e($user['username']) ?></span>
                </div>
                <a href="<?= projectUrl('logout.php') ?>" class="btn btn-secondary btn-sm" title="End authenticated session">
                    <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                    </svg>
                    Logout
                </a>
                <a href="profile.php" class="btn btn-secondary btn-sm">My profile</a>
            </div>
        </div>
    </header>

    <!-- Student Dashboard Main Content -->
    <main class="dashboard-content-main container">
        <!-- Welcome Banner -->
        <div class="dashboard-banner">
            <div>
                <span class="dash-welcome-eyebrow">Student Accommodation Services</span>
                <h1 class="dash-welcome-title">Welcome, <?= e($user['username']) ?>.</h1>
                <p class="dash-welcome-sub">
                    Manage your accommodation profile, track application reviews, inspect rent ledger records, and log maintenance tickets.
                </p>
            </div>
            <div>
                <span class="live-status-pill">
                    <span class="pulse-dot"></span>
                    Account Active &bull; Verified
                </span>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="dashboard-cards-grid">
            <!-- Accommodation Application -->
            <div class="dash-metric-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Accommodation Status</span>
                    <span class="vcard-status">
                        <?= $application ? ucfirst($application['application_status']) : 'No Application' ?>
                    </span>
                </div>
                <div class="dash-card-val">
                    <?= $application ? e($application['hostel_preference']) : 'Not Assigned' ?>
                </div>
                <p class="dash-card-desc">
                    <?= $application ? 'Room Type: ' . e($application['room_type']) : 'Submit application through hostel office.' ?>
                </p>
            </div>

            <!-- Room Allocation -->
            <div class="dash-metric-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Assigned Room</span>
                    <span class="counter-badge">Allocated</span>
                </div>
                <div class="dash-card-val"><?= $allocation ? 'Room ' . e($allocation['room_number']) : 'Not Allocated' ?></div>
                <p class="dash-card-desc"><?= $allocation ? e($allocation['hostel_name']) . ' &bull; ' . e($allocation['room_type']) : 'Awaiting warden allocation' ?></p>
            </div>

            <!-- Recorded Rent Records -->
            <div class="dash-metric-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Rent Ledger Record</span>
                    <span class="demo-tag">RECORDED</span>
                </div>
                <div class="dash-card-val">
                    <?= $payment ? 'KES ' . number_format($payment['amount'], 2) : 'KES 0.00' ?>
                </div>
                <p class="dash-card-desc">
                    <?= $payment ? 'Balance: KES ' . number_format($payment['balance'], 2) . ' (' . e($payment['reference_no']) . ')' : 'No payment records on file.' ?>
                </p>
            </div>
        </div>

        <div class="dashboard-visual-grid">
            <section class="visual-card" aria-labelledby="student-rating-title">
                <h2 id="student-rating-title">Student experience indicators</h2>
                <div class="rating-row">
                    <span>High rating</span>
                    <div class="rating-track"><div class="rating-fill" style="--rating: <?= $applicationScore >= 85 ? $applicationScore : 18 ?>%; --rating-color: var(--color-teal);"></div></div>
                    <strong><?= $applicationScore >= 85 ? $applicationScore : 18 ?>%</strong>
                </div>
                <div class="rating-row">
                    <span>Above average</span>
                    <div class="rating-track"><div class="rating-fill" style="--rating: <?= $paymentScore ?>%;"></div></div>
                    <strong><?= $paymentScore ?>%</strong>
                </div>
                <div class="rating-row">
                    <span>Middle</span>
                    <div class="rating-track"><div class="rating-fill" style="--rating: 58%; --rating-color: var(--color-gold);"></div></div>
                    <strong>58%</strong>
                </div>
                <div class="rating-row">
                    <span>Low rating</span>
                    <div class="rating-track"><div class="rating-fill" style="--rating: <?= 100 - $maintenanceScore ?>%; --rating-color: var(--status-danger);"></div></div>
                    <strong><?= 100 - $maintenanceScore ?>%</strong>
                </div>
            </section>

            <section class="visual-card" aria-labelledby="student-activity-title">
                <h2 id="student-activity-title">Monthly activity</h2>
                <div class="activity-list">
                    <div class="activity-item">
                        <div><strong>Accommodation application</strong><span><?= $application ? ucfirst(e($application['application_status'])) : 'Not submitted' ?></span></div>
                        <span><?= $application ? 'This term' : 'Pending' ?></span>
                    </div>
                    <div class="activity-item">
                        <div><strong>Rent ledger update</strong><span><?= $payment ? e($payment['reference_no']) : 'No record' ?></span></div>
                        <span><?= $payment ? date('M d, Y', strtotime($payment['payment_date'])) : 'Pending' ?></span>
                    </div>
                    <div class="activity-item">
                        <div><strong>Maintenance requests</strong><span><?= count($maintenanceList) ?> recent ticket(s)</span></div>
                        <span><?= date('M Y') ?></span>
                    </div>
                </div>
            </section>
        </div>

        <?php if (!empty($_GET['success'])): ?><p class="vcard-status">Request submitted successfully.</p><?php endif; ?>
        <?php if (!empty($_GET['error'])): ?><p class="counter-badge"><?= e($_GET['error']) ?></p><?php endif; ?>
        <div class="dashboard-visual-grid">
            <section class="visual-card">
                <h2>Submit accommodation application</h2>
                <form method="post" action="actions.php">
                    <?= csrfField() ?><input type="hidden" name="action" value="application">
                    <p><label>Hostel preference<br><input name="hostel_preference" required maxlength="150"></label></p>
                    <p><label>Room type<br><input name="room_type" required maxlength="60"></label></p>
                    <p><label>Notes<br><textarea name="notes" rows="2"></textarea></label></p>
                    <button class="btn btn-primary btn-sm" type="submit">Submit application</button>
                </form>
            </section>
            <section class="visual-card">
                <h2>Report maintenance issue</h2>
                <form method="post" action="actions.php">
                    <?= csrfField() ?><input type="hidden" name="action" value="maintenance">
                    <p><label>Title<br><input name="title" required maxlength="180"></label></p>
                    <p><label>Description<br><textarea name="description" rows="2" required></textarea></label></p>
                    <p><label>Priority<br><select name="priority"><option>low</option><option selected>medium</option><option>high</option></select></label></p>
                    <button class="btn btn-primary btn-sm" type="submit">Create request</button>
                </form>
            </section>
        </div>

        <!-- Student Navigation Wireframe Sections (Modules) -->
        <div class="dashboard-panel-full">
            <div class="dash-panel-heading">
                <span>Active Maintenance Tickets</span>
                <span class="demo-tag"><?= count($maintenanceList) ?> Recorded</span>
            </div>

            <?php if (!empty($maintenanceList)): ?>
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Issue Title</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date Reported</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenanceList as $m): ?>
                            <tr>
                                <td><strong><?= e($m['title']) ?></strong></td>
                                <td>
                                    <span class="demo-tag"><?= strtoupper(e($m['priority'])) ?></span>
                                </td>
                                <td>
                                    <span class="vcard-status"><?= ucfirst(str_replace('_', ' ', $m['status'])) ?></span>
                                </td>
                                <td><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="dash-card-desc">No maintenance requests currently open.</p>
            <?php endif; ?>
        </div>

        <!-- Wireframe Navigation Notice -->
        <div class="contact-panel-card" style="text-align: left;">
            <h3 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 6px;">
                Student Navigation Wireframe Reference (Figure 20)
            </h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                This portal implements the core student accommodation functions defined in Chapter 5: Personal Profile, Accommodation Applications, Room Allocation Status, Payment Record Reviews, and Maintenance Reporting. Detailed transaction and request sub-modules are linked to your verified student account ID (<?= e($user['id']) ?>).
            </p>
        </div>
    </main>
</body>
</html>
