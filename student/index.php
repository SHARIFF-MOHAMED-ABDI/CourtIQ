<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard_charts.php';
requireRole(['student']);

$user = currentUser();
$userId = (int)$user['id'];
$conn = db();
$applicationStmt = $conn->prepare('SELECT hostel_preference, room_type, application_status FROM accommodation_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$applicationStmt->bind_param('i', $userId);
$applicationStmt->execute();
$application = $applicationStmt->get_result()->fetch_assoc();
$applicationStmt->close();
$allocationStmt = $conn->prepare("SELECT r.room_number, r.room_type, h.name AS hostel_name FROM room_allocations a
    JOIN rooms r ON r.id = a.room_id JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
$allocationStmt->bind_param('i', $userId);
$allocationStmt->execute();
$allocation = $allocationStmt->get_result()->fetch_assoc();
$allocationStmt->close();
$paymentStmt = $conn->prepare('SELECT reference_no, amount, balance, payment_date FROM payment_records WHERE user_id = ? ORDER BY payment_date DESC, id DESC LIMIT 1');
$paymentStmt->bind_param('i', $userId);
$paymentStmt->execute();
$payment = $paymentStmt->get_result()->fetch_assoc();
$paymentStmt->close();
$statsStmt = $conn->prepare("SELECT COUNT(*) AS tickets,
    COALESCE(SUM(status = 'pending'), 0) AS pending_tickets,
    COALESCE(SUM(status = 'in_progress'), 0) AS in_progress_tickets,
    COALESCE(SUM(status = 'resolved'), 0) AS resolved_tickets
    FROM maintenance_requests WHERE user_id = ?");
$statsStmt->bind_param('i', $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();
$latestMaintenanceStmt = $conn->prepare("SELECT reference_no, title, category, priority, status, latest_response,
        resolution_notes, created_at
    FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$latestMaintenanceStmt->bind_param('i', $userId);
$latestMaintenanceStmt->execute();
$latestMaintenance = $latestMaintenanceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$latestMaintenanceStmt->close();
$paidMonthsStmt = $conn->prepare("SELECT COUNT(DISTINCT DATE_FORMAT(payment_date, '%Y-%m')) AS paid_months FROM payment_records WHERE user_id = ?");
$paidMonthsStmt->bind_param('i', $userId);
$paidMonthsStmt->execute();
$paidMonths = $paidMonthsStmt->get_result()->fetch_assoc();
$paidMonthsStmt->close();

$studentCharts = getStudentDashboardChartData($conn, $userId);
$studentChart = renderLineChart(
    $studentCharts['student-activity']['title'],
    $studentCharts['student-activity']['labels'],
    $studentCharts['student-activity']['series']
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student dashboard | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout role-dashboard" data-maintenance-endpoint="<?= e(projectUrl('student/maintenance-updates.php')) ?>" data-dashboard-chart-endpoint="<?= e(projectUrl('student/maintenance-updates.php')) ?>">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <main class="dashboard-content-main container dashboard-overview">
        <section class="dashboard-banner">
            <div>
                <span class="dash-welcome-eyebrow">STUDENT PORTAL</span>
                <h1 class="dash-welcome-title">Welcome, <?= e($user['full_name'] ?: $user['username']) ?>.</h1>
                <p class="dash-welcome-sub">Your accommodation, payment, and service-request overview.</p>
            </div>
            <span class="live-status-pill"><i class="pulse-dot"></i> Account active · Approval status: <?= e(ucfirst($user['approval_status'] ?? 'approved')) ?></span>
        </section>
        <nav class="dashboard-section-tabs" role="tablist" aria-label="Student dashboard sections">
            <button type="button" id="student-tab-overview" role="tab" aria-controls="student-overview" aria-selected="true">Overview</button>
            <button type="button" id="student-tab-applications" role="tab" aria-controls="student-applications" aria-selected="false" tabindex="-1">Applications</button>
            <button type="button" id="student-tab-payments" role="tab" aria-controls="student-payments" aria-selected="false" tabindex="-1">Payments</button>
            <button type="button" id="student-tab-maintenance" role="tab" aria-controls="student-maintenance" aria-selected="false" tabindex="-1">Maintenance</button>
            <button type="button" id="student-tab-profile" role="tab" aria-controls="student-profile" aria-selected="false" tabindex="-1">Profile</button>
        </nav>

        <section id="student-overview" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="student-tab-overview">
            <div class="dashboard-cards-grid" aria-label="Student summary">
                <article class="dash-metric-card"><div class="dash-card-header"><span class="dash-card-title">Application</span><span class="demo-tag"><?= $application ? e(ucfirst($application['application_status'])) : 'NONE' ?></span></div><div class="dash-card-val"><?= $application ? e($application['hostel_preference']) : 'Not submitted' ?></div><p class="dash-card-desc"><?= $application ? e($application['room_type']) : 'Submit an accommodation application.' ?></p></article>
                <article class="dash-metric-card"><div class="dash-card-header"><span class="dash-card-title">Assigned room</span><span class="vcard-status"><?= $allocation ? 'Allocated' : 'Pending' ?></span></div><div class="dash-card-val"><?= $allocation ? e($allocation['room_number']) : 'Not allocated' ?></div><p class="dash-card-desc"><?= $allocation ? e($allocation['hostel_name']) . ' · ' . e($allocation['room_type']) : 'Your room assignment will appear here.' ?></p></article>
                <article class="dash-metric-card"><div class="dash-card-header"><span class="dash-card-title">Latest payment</span><span class="demo-tag"><?= $payment ? e(date('M Y', strtotime($payment['payment_date']))) : 'NONE' ?></span></div><div class="dash-card-val"><?= $payment ? 'KES ' . number_format((float)$payment['amount'], 2) : 'KES 0.00' ?></div><p class="dash-card-desc"><?= $payment ? 'Balance: KES ' . number_format((float)$payment['balance'], 2) : 'No payment recorded yet.' ?></p></article>
            </div>
            <section class="visual-card line-chart-card">
                <h2>Activity over the last six months</h2>
                <p class="line-chart-subtitle"><?= (int)$stats['tickets'] ?> issues raised · <?= (int)$paidMonths['paid_months'] ?> months with payments recorded overall.</p>
                <div data-dashboard-chart="student-activity"><?= $studentChart ?></div>
                <p class="line-chart-subtitle" data-dashboard-refresh-status aria-live="polite">Live chart refreshes automatically.</p>
            </section>
        </section>

        <section id="student-applications" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="student-tab-applications" hidden>
            <div class="admin-panel-header"><div><span class="admin-eyebrow">APPLICATIONS</span><h2>Accommodation application</h2></div></div>
            <?php if ($application): ?>
                <div class="admin-mini-grid">
                    <article class="admin-mini-card"><small>Status</small><strong><?= e(ucfirst($application['application_status'])) ?></strong><span>Current application progress</span></article>
                    <article class="admin-mini-card"><small>Hostel preference</small><strong><?= e($application['hostel_preference']) ?></strong><span>Requested accommodation</span></article>
                    <article class="admin-mini-card"><small>Room type</small><strong><?= e($application['room_type']) ?></strong><span>Requested room type</span></article>
                </div>
            <?php else: ?>
                <p class="dash-card-desc">You have not submitted an accommodation application.</p>
            <?php endif; ?>
            <p class="dashboard-tab-action"><a class="btn btn-primary btn-sm" href="applications.php">Open applications</a></p>
        </section>

        <section id="student-payments" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="student-tab-payments" hidden>
            <div class="admin-panel-header"><div><span class="admin-eyebrow">PAYMENTS</span><h2>Latest payment summary</h2></div></div>
            <div class="admin-mini-grid">
                <article class="admin-mini-card"><small>Payments recorded</small><strong><?= (int)$paidMonths['paid_months'] ?> months</strong><span>Months with a payment record</span></article>
                <article class="admin-mini-card"><small>Latest amount</small><strong><?= $payment ? 'KES ' . number_format((float)$payment['amount'], 2) : 'KES 0.00' ?></strong><span><?= $payment ? e($payment['reference_no']) : 'No payment recorded' ?></span></article>
                <article class="admin-mini-card"><small>Outstanding balance</small><strong><?= $payment ? 'KES ' . number_format((float)$payment['balance'], 2) : 'KES 0.00' ?></strong><span><?= $payment ? date('M j, Y', strtotime($payment['payment_date'])) : 'No recent payment' ?></span></article>
            </div>
            <p class="dashboard-tab-action"><a class="btn btn-primary btn-sm" href="payments.php">Open payment history</a></p>
        </section>

        <section id="student-maintenance" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="student-tab-maintenance" hidden>
            <div class="admin-panel-header"><div><span class="admin-eyebrow">MAINTENANCE</span><h2>Your service requests</h2></div></div>
            <div class="admin-mini-grid">
                <article class="admin-mini-card"><small>Total issues</small><strong data-maintenance-count="total"><?= (int)$stats['tickets'] ?></strong><span>Requests submitted</span></article>
                <article class="admin-mini-card"><small>Pending</small><strong data-maintenance-count="pending"><?= (int)$stats['pending_tickets'] ?></strong><span>Awaiting review</span></article>
                <article class="admin-mini-card"><small>In progress</small><strong data-maintenance-count="in_progress"><?= (int)$stats['in_progress_tickets'] ?></strong><span>Being handled</span></article>
                <article class="admin-mini-card"><small>Resolved issues</small><strong data-maintenance-count="resolved"><?= (int)$stats['resolved_tickets'] ?></strong><span>Marked resolved</span></article>
            </div>
            <div class="activity-list" data-maintenance-list data-maintenance-limit="5">
                <?php foreach ($latestMaintenance as $request): ?>
                    <article class="activity-item"><div>
                        <strong><?= e($request['reference_no']) ?> · <?= e($request['category']) ?></strong>
                        <span><?= e($request['title']) ?> · <?= e(date('M j, Y H:i', strtotime($request['created_at']))) ?></span>
                        <?php if ($request['latest_response']): ?><p><?= e($request['latest_response']) ?></p><?php endif; ?>
                    </div><span class="status-pill status-<?= e($request['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($request['status']))) ?></span></article>
                <?php endforeach; ?>
                <?php if (!$latestMaintenance): ?><p class="dash-card-desc">No maintenance requests yet.</p><?php endif; ?>
            </div>
            <p aria-live="polite" data-maintenance-refresh-status>Updates automatically every 20 seconds.</p>
            <p class="dashboard-tab-action"><a class="btn btn-primary btn-sm" href="maintenance.php">Open maintenance requests</a></p>
        </section>

        <section id="student-profile" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="student-tab-profile" hidden>
            <div class="admin-panel-header"><div><span class="admin-eyebrow">PROFILE</span><h2>Account details</h2></div></div>
            <div class="admin-mini-grid">
                <article class="admin-mini-card"><small>Username</small><strong><?= e($user['username']) ?></strong><span>Student account</span></article>
                <article class="admin-mini-card"><small>Email</small><strong><?= e($user['email']) ?></strong><span>Contact address</span></article>
            </div>
            <p class="dashboard-tab-action"><a class="btn btn-primary btn-sm" href="profile.php">Open profile settings</a></p>
        </section>
    </main>
    <script src="<?= projectUrl('assets/js/admin-dashboard.js') ?>" defer></script>
    <script src="<?= projectUrl('assets/js/maintenance-updates.js') ?>" defer></script>
</body>
</html>
