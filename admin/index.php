<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard_charts.php';
requireRole(['administrator']);

$user = currentUser();
$conn = db();

// Get stats for cards
$stats = $conn->query("SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active' AND approval_status = 'approved') AS students,
    (SELECT COUNT(*) FROM room_allocations WHERE status = 'active') AS residents,
    (SELECT COUNT(*) FROM maintenance_requests WHERE status <> 'resolved') AS open_maintenance,
    (SELECT COUNT(*) FROM hostel_profiles) AS hostels,
    (SELECT COALESCE(SUM(capacity), 0) FROM rooms) AS beds,
    (SELECT COALESCE(SUM(occupied), 0) FROM rooms) AS occupied_beds")->fetch_assoc();

$recentApplications = $conn->query("SELECT a.hostel_preference, a.room_type, a.application_status, u.username
    FROM accommodation_applications a JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$hostelSummary = $conn->query("SELECT hp.id, hp.name, COUNT(r.id) AS rooms, COALESCE(SUM(r.capacity), 0) AS capacity, COALESCE(SUM(r.occupied), 0) AS occupied
    FROM hostel_profiles hp LEFT JOIN rooms r ON r.hostel_id = hp.id GROUP BY hp.id ORDER BY hp.name LIMIT 4")->fetch_all(MYSQLI_ASSOC);

$roleCounts = $conn->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role ORDER BY role")->fetch_all(MYSQLI_ASSOC);

// Get all charts
$dashboardCharts = getAdminDashboardChartData($conn);
$adminChart = renderLineChart(
    $dashboardCharts['activity']['title'],
    $dashboardCharts['activity']['labels'],
    $dashboardCharts['activity']['series']
);
$resolutionChart = renderLineChart(
    $dashboardCharts['maintenance']['title'],
    $dashboardCharts['maintenance']['labels'],
    $dashboardCharts['maintenance']['series']
);
$occupancyChart = renderLineChart(
    $dashboardCharts['occupancy']['title'],
    $dashboardCharts['occupancy']['labels'],
    $dashboardCharts['occupancy']['series']
);
$approvalChart = renderLineChart(
    $dashboardCharts['approvals']['title'],
    $dashboardCharts['approvals']['labels'],
    $dashboardCharts['approvals']['series']
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator | CourtIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout has-sidebar admin-dashboard" data-dashboard-chart-endpoint="<?= e(projectUrl('admin/dashboard-data.php')) ?>">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <?php if (!empty($_GET['error'])): ?><div class="alert alert--error"><?= e($_GET['error']) ?></div><?php endif; ?>
        <?php if (!empty($_GET['success'])): ?><div class="alert alert--success">Changes saved successfully.</div><?php endif; ?>

        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">COURTIQ ADMINISTRATION</span>
                <h1 class="dashboard-header-title">Good day, <?= e($user['username']) ?>.</h1>
                <p class="dashboard-header-subtitle">Monitor your hostel operations from one control centre.</p>
            </div>
            <span class="admin-live" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; background: var(--color-secondary-soft); color: var(--color-secondary); border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; white-space: nowrap;">
                <i style="width: 8px; height: 8px; border-radius: 50%; background: var(--status-success); box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.18);"></i> System live
            </span>
        </header>

        <!-- Statistics Cards (KPIs) -->
        <div class="stats-grid" aria-label="Key metrics">
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Active Students</span>
                    <div class="stat-card-icon stat-card-icon--blue" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value"><?= (int)$stats['students'] ?></div>
                <p class="stat-card-desc">Registered student accounts</p>
            </article>

            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Residents Allocated</span>
                    <div class="stat-card-icon stat-card-icon--green" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value"><?= (int)$stats['residents'] ?></div>
                <p class="stat-card-desc"><?= (int)$stats['occupied_beds'] ?> of <?= (int)$stats['beds'] ?> beds occupied</p>
            </article>

            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Open Maintenance</span>
                    <div class="stat-card-icon stat-card-icon--yellow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value"><?= (int)$stats['open_maintenance'] ?></div>
                <p class="stat-card-desc">Requests needing attention</p>
            </article>

            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Hostels</span>
                    <div class="stat-card-icon stat-card-icon--purple" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value"><?= (int)$stats['hostels'] ?></div>
                <p class="stat-card-desc">Facilities configured</p>
            </article>
        </div>

        <!-- Charts Grid -->
        <div class="admin-chart-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px;">
            <section class="visual-card line-chart-card" aria-labelledby="activity-chart-title">
                <h2 id="activity-chart-title" class="visual-card-title">Access & Hostel Activity</h2>
                <p class="visual-card-subtitle">Sign-ins, registrations, room allocations, maintenance requests, and payments over the last six months.</p>
                <div data-dashboard-chart="activity"><?= $adminChart ?></div>
            </section>

            <section class="visual-card line-chart-card" aria-labelledby="maintenance-chart-title">
                <h2 id="maintenance-chart-title" class="visual-card-title">Issue Resolution Status</h2>
                <p class="visual-card-subtitle">Current request statuses, grouped by the month each issue was submitted.</p>
                <div data-dashboard-chart="maintenance"><?= $resolutionChart ?></div>
            </section>

            <section class="visual-card line-chart-card" aria-labelledby="occupancy-chart-title">
                <h2 id="occupancy-chart-title" class="visual-card-title">Hostel Occupancy</h2>
                <p class="visual-card-subtitle">Occupied and vacant beds, refreshed as rooms or allocations change.</p>
                <div data-dashboard-chart="occupancy"><?= $occupancyChart ?></div>
            </section>

            <section class="visual-card line-chart-card" aria-labelledby="approval-chart-title">
                <h2 id="approval-chart-title" class="visual-card-title">Student Account Approvals</h2>
                <p class="visual-card-subtitle">Pending, approved, and rejected accounts update as decisions are made.</p>
                <div data-dashboard-chart="approvals"><?= $approvalChart ?></div>
            </section>
        </div>

        <p class="line-chart-subtitle" data-dashboard-refresh-status aria-live="polite" style="text-align: center; margin-top: -12px; margin-bottom: 24px;">Live charts refresh automatically.</p>

        <!-- Quick Actions / Overview Sections -->
        <div class="dashboard-visual-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Recent Applications -->
            <section class="visual-card" aria-labelledby="recent-apps-heading">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                    <h2 id="recent-apps-heading" class="visual-card-title" style="margin: 0;">Recent Applications</h2>
                    <a href="<?= projectUrl('admin/manage.php') ?>" class="btn btn-outline btn-sm">View All</a>
                </div>
                
                <?php if ($recentApplications): ?>
                    <div class="admin-list">
                        <?php foreach ($recentApplications as $application): ?>
                            <div class="admin-list-row">
                                <div>
                                    <strong><?= e($application['username']) ?></strong>
                                    <span style="color: var(--text-muted); font-size: 0.825rem;"><?= e($application['hostel_preference']) ?> · <?= e($application['room_type']) ?></span>
                                </div>
                                <span class="status-badge status-badge--<?= e($application['application_status']) ?>">
                                    <?= ucfirst(e($application['application_status'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state" style="padding: 24px;">No recent applications.</p>
                <?php endif; ?>
            </section>

            <!-- Hostels Summary -->
            <section class="visual-card" aria-labelledby="hostels-heading">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                    <h2 id="hostels-heading" class="visual-card-title" style="margin: 0;">Hostels Overview</h2>
                    <a href="<?= projectUrl('admin/manage.php') ?>" class="btn btn-outline btn-sm">Manage All</a>
                </div>
                
                <?php if ($hostelSummary): ?>
                    <div class="admin-list">
                        <?php foreach ($hostelSummary as $hostel): ?>
                            <div class="admin-list-row">
                                <div>
                                    <strong><?= e($hostel['name']) ?></strong>
                                    <span style="color: var(--text-muted); font-size: 0.825rem;"><?= (int)$hostel['rooms'] ?> rooms · <?= (int)$hostel['capacity'] ?> beds</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span class="room-chip"><?= (int)$hostel['occupied'] ?> occupied</span>
                                    <a class="text-link" href="<?= projectUrl('admin/residents.php?hostel_id=' . (int)$hostel['id']) ?>">Manage</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state" style="padding: 24px;">No hostel records available.</p>
                <?php endif; ?>
            </section>
        </div>

        <!-- Reports / Role Distribution -->
        <section class="visual-card" aria-labelledby="reports-heading">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <h2 id="reports-heading" class="visual-card-title" style="margin: 0;">System Distribution</h2>
                <a href="<?= projectUrl('admin/reports.php') ?>" class="btn btn-outline btn-sm">Full Reports</a>
            </div>
            
            <div class="admin-report-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <?php foreach ($roleCounts as $role): ?>
                    <article class="admin-report-card" style="background: var(--bg-white); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px; text-align: center;">
                        <span style="display: block; color: var(--text-muted); font-size: 0.75rem; text-transform: capitalize; font-weight: 600;"><?= ucfirst(e($role['role'])) ?></span>
                        <strong style="display: block; color: var(--text-heading); font-size: 2.5rem; margin-top: 8px; font-weight: 800;"><?= (int)$role['total'] ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <script src="<?= projectUrl('assets/js/admin-dashboard.js') ?>" defer></script>
</body>
</html>