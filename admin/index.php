<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard_charts.php';
requireRole(['administrator']);

$user = currentUser();
$conn = db();
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
<body class="dashboard-layout admin-dashboard" data-dashboard-chart-endpoint="<?= e(projectUrl('admin/dashboard-data.php')) ?>">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <main class="dashboard-content-main container admin-main">
        <?php if (!empty($_GET['error'])): ?><div class="admin-alert is-error"><?= e($_GET['error']) ?></div><?php endif; ?>
        <?php if (!empty($_GET['success'])): ?><div class="admin-alert is-success">Changes saved successfully.</div><?php endif; ?>
        <section class="admin-hero">
            <div><span class="admin-eyebrow">COURTIQ ADMINISTRATION</span><h1>Good day, <?= e($user['username']) ?>.</h1><p>Monitor your hostel operations from one control centre.</p></div>
            <div class="admin-hero-actions"><span class="admin-live"><i></i> System live</span></div>
        </section>

        <nav class="dashboard-section-tabs admin-dashboard-menu" role="tablist" aria-label="Admin dashboard sections">
            <button type="button" id="admin-tab-overview" role="tab" aria-controls="overview" aria-selected="true">Overview</button>
            <button type="button" id="admin-tab-applications" role="tab" aria-controls="applications" aria-selected="false" tabindex="-1">Applications</button>
            <button type="button" id="admin-tab-maintenance" role="tab" aria-controls="maintenance" aria-selected="false" tabindex="-1">Maintenance</button>
            <button type="button" id="admin-tab-hostels" role="tab" aria-controls="hostels" aria-selected="false" tabindex="-1">Hostels</button>
            <button type="button" id="admin-tab-reports" role="tab" aria-controls="reports" aria-selected="false" tabindex="-1">Reports</button>
        </nav>

        <section id="overview" class="admin-panel" role="tabpanel" aria-labelledby="admin-tab-overview">
            <div class="admin-panel-header">
                <div>
                    <span class="admin-eyebrow">OVERVIEW</span>
                    <h2>Administrative snapshot</h2>
                </div>
                <span class="admin-count"><?= count($recentApplications) ?> recent</span>
            </div>
            <div class="admin-kpi-grid" aria-label="Key metrics">
                <article class="admin-kpi"><span class="admin-kpi-icon blue">⌂</span><div><small>Active students</small><strong><?= (int)$stats['students'] ?></strong><span>Registered accounts</span></div></article>
                <article class="admin-kpi"><span class="admin-kpi-icon teal">◆</span><div><small>Residents allocated</small><strong><?= (int)$stats['residents'] ?></strong><span><?= (int)$stats['occupied_beds'] ?> of <?= (int)$stats['beds'] ?> beds occupied</span></div></article>
                <article class="admin-kpi"><span class="admin-kpi-icon amber">!</span><div><small>Open maintenance</small><strong><?= (int)$stats['open_maintenance'] ?></strong><span>Requests needing attention</span></div></article>
                <article class="admin-kpi"><span class="admin-kpi-icon purple">▦</span><div><small>Hostels</small><strong><?= (int)$stats['hostels'] ?></strong><span>Facilities configured</span></div></article>
            </div>
            <div class="admin-chart-grid">
                <div class="visual-card line-chart-card">
                    <h2>Access and hostel activity</h2>
                    <p class="line-chart-subtitle">Sign-ins, registrations, room allocations, maintenance requests, and payments over the last six months.</p>
                    <div data-dashboard-chart="activity"><?= $adminChart ?></div>
                </div>
                <div class="visual-card line-chart-card">
                    <h2>Issue resolution status</h2>
                    <p class="line-chart-subtitle">Current request statuses, grouped by the month each issue was submitted.</p>
                    <div data-dashboard-chart="maintenance"><?= $resolutionChart ?></div>
                </div>
                <div class="visual-card line-chart-card">
                    <h2>Hostel occupancy</h2>
                    <p class="line-chart-subtitle">Occupied and vacant beds, refreshed as rooms or allocations change.</p>
                    <div data-dashboard-chart="occupancy"><?= $occupancyChart ?></div>
                </div>
                <div class="visual-card line-chart-card">
                    <h2>Student account approvals</h2>
                    <p class="line-chart-subtitle">Pending, approved, and rejected accounts update as decisions are made.</p>
                    <div data-dashboard-chart="approvals"><?= $approvalChart ?></div>
                </div>
                <p class="line-chart-subtitle" data-dashboard-refresh-status aria-live="polite">Live charts refresh automatically.</p>
            </div>
        </section>

        <section id="applications" class="admin-panel" role="tabpanel" aria-labelledby="admin-tab-applications" hidden>
            <div class="admin-panel-header">
                <div>
                    <span class="admin-eyebrow">APPLICATIONS</span>
                    <h2>Accommodation applications</h2>
                </div>
                <span class="admin-count"><?= count($recentApplications) ?> current</span>
            </div>
            <div class="admin-list">
                <?php foreach ($recentApplications as $application): ?>
                    <div class="admin-list-row">
                        <div>
                            <strong><?= e($application['username']) ?></strong>
                            <span><?= e($application['hostel_preference']) ?> · <?= e($application['room_type']) ?></span>
                        </div>
                        <span class="status-pill status-<?= e($application['application_status']) ?>"><?= ucfirst(e($application['application_status'])) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$recentApplications): ?><p class="admin-empty">No recent applications.</p><?php endif; ?>
            </div>
        </section>

        <section id="maintenance" class="admin-panel" role="tabpanel" aria-labelledby="admin-tab-maintenance" hidden>
            <div class="admin-panel-header">
                <div>
                    <span class="admin-eyebrow">MAINTENANCE</span>
                    <h2>Service requests</h2>
                </div>
                <span class="admin-count"><?= (int)$stats['open_maintenance'] ?> open</span>
            </div>
            <div class="admin-mini-grid">
                <article class="admin-mini-card">
                    <small>Open tickets</small>
                    <strong><?= (int)$stats['open_maintenance'] ?></strong>
                    <span>Work requiring follow-up</span>
                </article>
                <article class="admin-mini-card">
                    <small>Resolved this period</small>
                    <strong><?= (int)array_sum($dashboardCharts['maintenance']['series']['Resolved']) ?></strong>
                    <span>Successfully closed over the chart period</span>
                </article>
                <article class="admin-mini-card">
                    <small>Average monthly load</small>
                    <strong><?= count($dashboardCharts['maintenance']['labels']) ? (int)round(array_sum($dashboardCharts['activity']['series']['Maintenance requests']) / count($dashboardCharts['maintenance']['labels'])) : 0 ?></strong>
                    <span>Across the last six months</span>
                </article>
            </div>
        </section>

        <section id="hostels" class="admin-panel" role="tabpanel" aria-labelledby="admin-tab-hostels" hidden>
            <div class="admin-panel-header">
                <div>
                    <span class="admin-eyebrow">HOSTELS</span>
                    <h2>Residential facilities</h2>
                </div>
                <span class="admin-count"><?= (int)$stats['hostels'] ?> hostels</span>
            </div>
            <div class="admin-list">
                <?php foreach ($hostelSummary as $hostel): ?>
                    <div class="admin-list-row">
                        <div>
                            <strong><?= e($hostel['name']) ?></strong>
                            <span><?= (int)$hostel['rooms'] ?> rooms · <?= (int)$hostel['capacity'] ?> beds</span>
                        </div>
                        <div><span class="room-chip"><?= (int)$hostel['occupied'] ?> occupied</span><a class="text-link" href="manage.php?hostel_id=<?= (int)$hostel['id'] ?>">Manage hostel</a></div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$hostelSummary): ?><p class="admin-empty">No hostel records available.</p><?php endif; ?>
            </div>
        </section>

        <section id="reports" class="admin-panel" role="tabpanel" aria-labelledby="admin-tab-reports" hidden>
            <div class="admin-panel-header">
                <div>
                    <span class="admin-eyebrow">REPORTS</span>
                    <h2>System distribution</h2>
                </div>
                <span class="admin-count"><?= count($roleCounts) ?> roles</span>
            </div>
            <div class="admin-report-grid">
                <?php foreach ($roleCounts as $role): ?>
                    <article class="admin-report-card">
                        <span><?= ucfirst(e($role['role'])) ?></span>
                        <strong><?= (int)$role['total'] ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <script src="<?= projectUrl('assets/js/admin-dashboard.js') ?>" defer></script>
</body>
</html>
