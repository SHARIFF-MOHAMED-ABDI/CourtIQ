<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['warden']);
require __DIR__ . '/partials/scope.php';
$rooms = db()->query('SELECT r.room_number, r.room_type, r.capacity, r.occupied, r.status, h.name AS hostel_name
    FROM rooms r JOIN hostel_profiles h ON h.id = r.hostel_id' . $hostelClause . ' ORDER BY h.name, r.room_number')->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Rooms | CourtIQ</title><link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>"></head>
<body class="dashboard-layout"><?php require __DIR__ . '/partials/nav.php'; ?><main class="dashboard-content-main container">
    <section class="role-page-heading"><span class="dash-welcome-eyebrow">HOSTEL OPERATIONS</span><h1>Room inventory</h1><p>Room capacity and occupancy across your assigned hostels.</p></section>
    <section class="visual-card"><div class="admin-section-heading"><div><h2>All rooms</h2></div><span class="admin-count"><?= count($rooms) ?> rooms</span></div><div class="admin-table-wrap"><table class="dash-table admin-table"><thead><tr><th>Hostel</th><th>Room</th><th>Type</th><th>Capacity</th><th>Occupied</th><th>Status</th></tr></thead><tbody><?php foreach ($rooms as $room): ?><tr><td><?= e($room['hostel_name']) ?></td><td><strong><?= e($room['room_number']) ?></strong></td><td><?= e($room['room_type']) ?></td><td><?= (int)$room['capacity'] ?></td><td><?= (int)$room['occupied'] ?></td><td><span class="status-pill status-<?= e($room['status']) ?>"><?= e(ucfirst($room['status'])) ?></span></td></tr><?php endforeach; ?><?php if (!$rooms): ?><tr><td colspan="6" class="admin-empty">No rooms are available for your assigned hostels.</td></tr><?php endif; ?></tbody></table></div></section>
</main></body></html>
