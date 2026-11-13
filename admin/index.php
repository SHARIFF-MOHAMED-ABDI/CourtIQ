<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['administrator']);

$user = currentUser();
$conn = db();
$stats = $conn->query("SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active') AS students,
    (SELECT COUNT(*) FROM room_allocations WHERE status = 'active') AS residents,
    (SELECT COUNT(*) FROM maintenance_requests WHERE status <> 'resolved') AS open_maintenance,
    (SELECT COUNT(*) FROM hostel_profiles) AS hostels,
    (SELECT COALESCE(SUM(capacity), 0) FROM rooms) AS beds,
    (SELECT COALESCE(SUM(occupied), 0) FROM rooms) AS occupied_beds")->fetch_assoc();
$recentApplications = $conn->query("SELECT a.hostel_preference, a.room_type, a.application_status, u.username
    FROM accommodation_applications a JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
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
<body class="dashboard-layout admin-dashboard">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <main class="dashboard-content-main container admin-main">
        <?php if (!empty($_GET['error'])): ?><div class="admin-alert is-error"><?= e($_GET['error']) ?></div><?php endif; ?>
        <?php if (!empty($_GET['success'])): ?><div class="admin-alert is-success">Changes saved successfully.</div><?php endif; ?>
        <section class="admin-hero">
            <div><span class="admin-eyebrow">COURTIQ ADMINISTRATION</span><h1>Good day, <?= e($user['username']) ?>.</h1><p>Monitor your hostel operations from one central dashboard.</p></div>
            <div class="admin-hero-actions"><a class="btn btn-light" href="residents.php">View residents <span aria-hidden="true">→</span></a><span class="admin-live"><i></i> System live</span></div>
        </section>
        <section class="admin-kpi-grid" aria-label="Key metrics">
            <article class="admin-kpi"><span class="admin-kpi-icon blue">⌂</span><div><small>Active students</small><strong><?= (int)$stats['students'] ?></strong><span>Registered accounts</span></div></article>
            <article class="admin-kpi"><span class="admin-kpi-icon teal">◆</span><div><small>Residents allocated</small><strong><?= (int)$stats['residents'] ?></strong><span><?= (int)$stats['occupied_beds'] ?> of <?= (int)$stats['beds'] ?> beds occupied</span></div></article>
            <article class="admin-kpi"><span class="admin-kpi-icon amber">!</span><div><small>Open maintenance</small><strong><?= (int)$stats['open_maintenance'] ?></strong><span>Requests needing attention</span></div></article>
            <article class="admin-kpi"><span class="admin-kpi-icon purple">▦</span><div><small>Hostels</small><strong><?= (int)$stats['hostels'] ?></strong><span>Facilities configured</span></div></article>
        </section>
        <section class="admin-landing-grid">
            <div class="admin-section compact"><div class="admin-section-heading"><div><span class="admin-eyebrow">WORKSPACE</span><h2>Choose an area to manage</h2><p>Each area opens on its own page so your work stays focused.</p></div></div><div class="admin-menu-cards">
                <a href="residents.php"><strong>Residents</strong><span>Student details, rooms and allocations →</span></a>
                <a href="maintenance.php"><strong>Maintenance</strong><span>Review and update reported issues →</span></a>
                <a href="manage.php"><strong>Settings &amp; records</strong><span>Users, hostels, rooms and payments →</span></a>
                <a href="reports.php"><strong>Reports</strong><span>Occupancy and operational summaries →</span></a>
            </div></div>
            <div class="admin-section compact"><div class="admin-section-heading"><div><span class="admin-eyebrow">LATEST</span><h2>Recent applications</h2></div></div><div class="admin-list"><?php foreach ($recentApplications as $application): ?><div class="admin-list-row"><div><strong><?= e($application['username']) ?></strong><span><?= e($application['hostel_preference']) ?> · <?= e($application['room_type']) ?></span></div><span class="status-pill status-<?= e($application['application_status']) ?>"><?= ucfirst(e($application['application_status'])) ?></span></div><?php endforeach; ?><?php if (!$recentApplications): ?><p class="admin-empty">No recent applications.</p><?php endif; ?></div></div>
        </section>
    </main>
</body>
</html>
