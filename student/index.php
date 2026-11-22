<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard_charts.php';
requireRole(['student']);

$user = currentUser();
$userId = (int)$user['id'];
$conn = db();

// Get active allocation
$allocationStmt = $conn->prepare("SELECT r.room_number, r.room_type, h.name AS hostel_name FROM room_allocations a
    JOIN rooms r ON r.id = a.room_id JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
$allocationStmt->bind_param('i', $userId);
$allocationStmt->execute();
$allocation = $allocationStmt->get_result()->fetch_assoc();
$allocationStmt->close();

// Get latest payment
$paymentStmt = $conn->prepare('SELECT reference_no, amount, balance, payment_date FROM payment_records WHERE user_id = ? ORDER BY payment_date DESC, id DESC LIMIT 1');
$paymentStmt->bind_param('i', $userId);
$paymentStmt->execute();
$payment = $paymentStmt->get_result()->fetch_assoc();
$paymentStmt->close();

// Get maintenance stats
$statsStmt = $conn->prepare("SELECT COUNT(*) AS tickets,
    COUNT(*) FILTER (WHERE status = 'pending') AS pending_tickets,
    COUNT(*) FILTER (WHERE status = 'in_progress') AS in_progress_tickets,
    COUNT(*) FILTER (WHERE status = 'resolved') AS resolved_tickets
    FROM maintenance_requests WHERE user_id = ?");
$statsStmt->bind_param('i', $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

// Get latest maintenance requests
$latestMaintenanceStmt = $conn->prepare("SELECT reference_no, title, category, priority, status, latest_response,
        resolution_notes, created_at
    FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$latestMaintenanceStmt->bind_param('i', $userId);
$latestMaintenanceStmt->execute();
$latestMaintenance = $latestMaintenanceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$latestMaintenanceStmt->close();

// Get active bookings count
$bookingsStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM room_allocations WHERE student_id = ? AND status = 'active'");
$bookingsStmt->bind_param('i', $userId);
$bookingsStmt->execute();
$bookingsCount = (int)$bookingsStmt->get_result()->fetch_assoc()['cnt'];
$bookingsStmt->close();

// Get booking fee (from hostel profile or fixed value)
$feeStmt = $conn->prepare("SELECT COALESCE(h.booking_fee, 15000) AS fee FROM hostel_profiles h
    JOIN rooms r ON r.hostel_id = h.id
    JOIN room_allocations a ON a.room_id = r.id
    WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
$feeStmt->bind_param('i', $userId);
$feeStmt->execute();
$feeResult = $feeStmt->get_result()->fetch_assoc();
$bookingFee = $feeResult ? (float)$feeResult['fee'] : 15000;
$feeStmt->close();

// If no active allocation, get default fee from first hostel
if ($bookingFee === 15000 && !$allocation) {
    $defaultFeeStmt = $conn->query("SELECT COALESCE(booking_fee, 15000) AS fee FROM hostel_profiles ORDER BY id LIMIT 1");
    if ($defaultFeeStmt) {
        $defaultFee = $defaultFeeStmt->fetch_assoc();
        $bookingFee = $defaultFee ? (float)$defaultFee['fee'] : 15000;
    }
}

// Render student activity chart
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
    <title>Student Dashboard | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout has-sidebar">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">STUDENT PORTAL</span>
                <h1 class="dashboard-header-title">Student Dashboard</h1>
                <p class="dashboard-header-subtitle">Track bookings, payments, and maintenance in one place.</p>
            </div>
            <a href="<?= projectUrl('student/rooms.php') ?>" class="btn btn-primary btn-block" style="max-width: 200px;">Browse Rooms</a>
        </header>

        <!-- Statistics Cards -->
        <div class="stats-grid" aria-label="Dashboard statistics">
            <!-- Card 1: Active Bookings -->
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Active Bookings</span>
                    <div class="stat-card-icon stat-card-icon--green" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value"><?= $bookingsCount ?></div>
                <p class="stat-card-desc">Currently active room allocation<?= $bookingsCount !== 1 ? 's' : '' ?></p>
            </article>

            <!-- Card 2: Booking Fee -->
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Booking Fee</span>
                    <div class="stat-card-icon stat-card-icon--yellow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value">KES <?= number_format($bookingFee, 0) ?></div>
                <p class="stat-card-desc">Applicable accommodation fee</p>
            </article>

            <!-- Card 3: Maintenance Requests -->
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Maintenance Requests</span>
                    <div class="stat-card-icon stat-card-icon--cyan" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value"><?= (int)$stats['tickets'] ?></div>
                <p class="stat-card-desc"><?= (int)$stats['pending_tickets'] ?> pending, <?= (int)$stats['resolved_tickets'] ?> resolved</p>
            </article>
        </div>

        <!-- Dashboard Charts Section -->
        <section class="visual-card line-chart-card" aria-labelledby="activity-chart-heading">
            <h2 id="activity-chart-heading" class="visual-card-title">Your Activity (Last 6 Months)</h2>
            <p class="visual-card-subtitle"><?= (int)$stats['tickets'] ?> issues raised · <?= (int)($payment ? 1 : 0) ?> months with payments recorded.</p>
            <div data-dashboard-chart="student-activity"><?= $studentChart ?></div>
            <p class="visual-card-subtitle" aria-live="polite">Chart data updates automatically.</p>
        </section>

        <!-- My Bookings & Recent Maintenance Section -->
        <div class="dashboard-visual-grid">
            <!-- My Bookings -->
            <section class="visual-card" aria-labelledby="my-bookings-heading">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                    <h2 id="my-bookings-heading" class="visual-card-title" style="margin: 0;">My Bookings</h2>
                    <a href="<?= projectUrl('student/bookings.php') ?>" class="btn btn-outline btn-sm">View All</a>
                </div>
                
                <?php if ($allocation): ?>
                    <div class="table-wrap" style="border: none; overflow: visible;">
                        <table class="dash-table" role="table" style="border: none; box-shadow: none;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-light);">
                                    <th scope="col" style="background: transparent; border: none;">Room</th>
                                    <th scope="col" style="background: transparent; border: none;">Booking Date</th>
                                    <th scope="col" style="background: transparent; border: none;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong><?= e($allocation['room_number']) ?></strong><br><small style="color: var(--text-muted);"><?= e($allocation['hostel_name']) ?> · <?= e($allocation['room_type']) ?></small></td>
                                    <td><?= date('M j, Y', strtotime($allocationStmt ? 'now' : 'now')) ?></td>
                                    <td><span class="status-badge status-badge--active">Confirmed</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 32px 16px;">
                        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <h3 class="empty-state-title">No bookings yet</h3>
                        <p class="empty-state-desc">Browse available rooms to get started.</p>
                        <a href="<?= projectUrl('student/rooms.php') ?>" class="btn btn-primary mt-auto" style="width: auto;">Browse Rooms</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Recent Maintenance -->
            <section class="visual-card" aria-labelledby="recent-maintenance-heading">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                    <h2 id="recent-maintenance-heading" class="visual-card-title" style="margin: 0;">Recent Maintenance</h2>
                    <a href="<?= projectUrl('student/maintenance.php') ?>" class="btn btn-outline btn-sm">View All</a>
                </div>
                
                <?php if ($latestMaintenance): ?>
                    <div class="activity-list">
                        <?php foreach ($latestMaintenance as $request): ?>
                            <article class="activity-item">
                                <div>
                                    <strong><?= e($request['reference_no']) ?> · <?= e($request['category']) ?></strong>
                                    <span><?= e($request['title']) ?> · <?= e(date('M j, Y', strtotime($request['created_at']))) ?></span>
                                    <?php if ($request['latest_response']): ?>
                                        <p style="font-size: 0.825rem; color: var(--text-muted); margin-top: 4px;"><?= e($request['latest_response']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="status-badge status-badge--<?= e($request['status']) ?>">
                                    <?= e(str_replace('_', ' ', ucfirst($request['status']))) ?>
                                </span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 32px 16px;">
                        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                        <h3 class="empty-state-title">No maintenance requests</h3>
                        <p class="empty-state-desc">You haven't reported any issues yet.</p>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                    <a href="<?= projectUrl('student/maintenance.php') ?>" class="btn btn-outline btn-block" style="border-color: var(--color-secondary); color: var(--color-secondary);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Report an Issue
                    </a>
                </div>
            </section>
        </div>
    </main>
    <script src="<?= projectUrl('assets/js/maintenance-updates.js') ?>" defer></script>
</body>
</html>