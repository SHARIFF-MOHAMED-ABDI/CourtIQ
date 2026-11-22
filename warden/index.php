<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard_charts.php';
requireRole(['warden']);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require __DIR__ . '/partials/scope.php';

$roomStats = db()->query("SELECT COUNT(*) AS total_rooms, COALESCE(SUM(capacity), 0) AS total_capacity,
    COALESCE(SUM(occupied), 0) AS total_occupied,
    SUM(CASE WHEN status = 'available' AND occupied < capacity THEN 1 ELSE 0 END) AS available_rooms
    FROM rooms" . str_replace('r.', '', $hostelClause))->fetch_assoc();
$totalCapacity = (int)$roomStats['total_capacity'];
$totalOccupied = (int)$roomStats['total_occupied'];
$occupancy = $totalCapacity > 0 ? (int)round($totalOccupied / $totalCapacity * 100) : 0;
$ticketStats = db()->query("SELECT COUNT(*) AS total, COUNT(*) FILTER (WHERE status <> 'resolved') AS open_total
    FROM maintenance_requests m" . $maintenanceClause)->fetch_assoc();
$residentStmt = db()->prepare("SELECT COUNT(DISTINCT ra.student_id) AS total FROM room_allocations ra
    JOIN rooms r ON r.id = ra.room_id WHERE ra.status = 'active'" . $studentClause);
$residentStmt->execute();
$residentCount = (int)$residentStmt->get_result()->fetch_assoc()['total'];
$residentStmt->close();

$wardenCharts = getWardenDashboardChartData(db(), $assignedHostelIds);
$wardenActivityChart = $wardenCharts['warden-activity'];
$wardenChart = renderLineChart(
    $wardenActivityChart['title'],
    $wardenActivityChart['labels'],
    $wardenActivityChart['series']
);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Warden dashboard | CourtIQ</title><link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/css/style.css') ?>"></head>
<body class="dashboard-layout role-dashboard" data-dashboard-chart-endpoint="<?= e(projectUrl('warden/dashboard-data.php')) ?>">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <main class="dashboard-content-main container dashboard-overview">
        <section class="dashboard-banner"><div><span class="dash-welcome-eyebrow">WARDEN PORTAL</span><h1 class="dash-welcome-title">Welcome, <?= e(currentUser()['username']) ?>.</h1><p class="dash-welcome-sub">A summary of your assigned hostel operations.</p></div><span class="live-status-pill"><i class="pulse-dot"></i> Shift overview</span></section>
        <nav class="dashboard-section-tabs" role="tablist" aria-label="Warden dashboard sections">
                <button type="button" id="warden-tab-overview" role="tab" aria-controls="warden-overview" aria-selected="true">Overview</button>
                <button type="button" id="warden-tab-residents" role="tab" aria-controls="warden-residents" aria-selected="false" tabindex="-1">Residents</button>
                <button type="button" id="warden-tab-rooms" role="tab" aria-controls="warden-rooms" aria-selected="false" tabindex="-1">Rooms</button>
                <button type="button" id="warden-tab-maintenance" role="tab" aria-controls="warden-maintenance" aria-selected="false" tabindex="-1">Maintenance</button>
        </nav>

        <section id="warden-overview" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="warden-tab-overview">
                <?php if (!empty($assignedHostelIds)): ?>
                    <div class="dashboard-cards-grid" aria-label="Hostel summary">
                        <article class="dash-metric-card"><div class="dash-card-header"><span class="dash-card-title">Bed occupancy</span><span class="demo-tag">LIVE</span></div><div class="dash-card-val"><?= $occupancy ?>%</div><p class="dash-card-desc"><?= $totalOccupied ?> occupied of <?= $totalCapacity ?> beds</p></article>
                        <article class="dash-metric-card"><div class="dash-card-header"><span class="dash-card-title">Active residents</span></div><div class="dash-card-val"><?= $residentCount ?></div><p class="dash-card-desc">Students assigned to your hostels</p></article>
                        <article class="dash-metric-card"><div class="dash-card-header"><span class="dash-card-title">Open maintenance</span><span class="counter-badge"><?= (int)$ticketStats['open_total'] ?> open</span></div><div class="dash-card-val"><?= (int)$ticketStats['total'] ?> total</div><p class="dash-card-desc">Requests raised for assigned hostels</p></article>
                    </div>
                    <section class="visual-card line-chart-card">
                        <h2>Maintenance activity over the last six months</h2>
                        <p class="line-chart-subtitle">Tickets raised and resolved across your assigned hostels.</p>
                        <div data-dashboard-chart="warden-activity"><?= $wardenChart ?></div>
                        <p class="line-chart-subtitle" data-dashboard-refresh-status aria-live="polite">Live chart refreshes automatically.</p>
                    </section>
                <?php else: ?>
                    <h2>No hostel assignment</h2>
                    <p class="dash-card-desc">Contact an administrator to configure your hostel access. Your summary and graph will appear here once assigned.</p>
                <?php endif; ?>
        </section>

        <section id="warden-residents" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="warden-tab-residents" hidden>
                <div class="admin-panel-header"><div><span class="admin-eyebrow">RESIDENTS</span><h2>Room allocations</h2></div></div>
                <div class="admin-mini-grid">
                    <article class="admin-mini-card"><small>Active residents</small><strong><?= $residentCount ?></strong><span>Currently assigned to your hostels</span></article>
                </div>
                <p class="dashboard-tab-action"><a class="btn btn-primary btn-sm" href="residents.php">Open resident management</a></p>
        </section>

        <section id="warden-rooms" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="warden-tab-rooms" hidden>
                <div class="admin-panel-header"><div><span class="admin-eyebrow">ROOMS</span><h2>Room inventory</h2></div></div>
                <div class="admin-mini-grid">
                    <article class="admin-mini-card"><small>Total rooms</small><strong><?= (int)$roomStats['total_rooms'] ?></strong><span>Across assigned hostels</span></article>
                    <article class="admin-mini-card"><small>Available rooms</small><strong><?= (int)$roomStats['available_rooms'] ?></strong><span>Ready for allocation</span></article>
                    <article class="admin-mini-card"><small>Bed occupancy</small><strong><?= $occupancy ?>%</strong><span><?= $totalOccupied ?> of <?= $totalCapacity ?> beds occupied</span></article>
                </div>
                <p class="dashboard-tab-action"><a class="btn btn-primary btn-sm" href="rooms.php">Open room inventory</a></p>
        </section>

        <section id="warden-maintenance" class="dashboard-tab-panel" role="tabpanel" aria-labelledby="warden-tab-maintenance" hidden>
                <div class="admin-panel-header"><div><span class="admin-eyebrow">MAINTENANCE</span><h2>Hostel service requests</h2></div></div>
                <div class="admin-mini-grid">
                    <article class="admin-mini-card"><small>Open requests</small><strong><?= (int)$ticketStats['open_total'] ?></strong><span>Awaiting resolution</span></article>
                    <article class="admin-mini-card"><small>Total requests</small><strong><?= (int)$ticketStats['total'] ?></strong><span>Across assigned hostels</span></article>
                </div>
                <p class="dashboard-tab-action"><a class="btn btn-primary btn-sm" href="maintenance.php">Open maintenance requests</a></p>
        </section>
    </main>
    <script src="<?= projectUrl('assets/js/admin-dashboard.js') ?>?v=<?= (int)filemtime(__DIR__ . '/../assets/js/admin-dashboard.js') ?>" defer></script>
</body>
</html>
