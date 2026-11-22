<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['warden']);
require __DIR__ . '/partials/scope.php';
$studentSearch = trim((string)($_GET['student_q'] ?? ''));
$searchPattern = '%' . $studentSearch . '%';
$studentStmt = db()->prepare("SELECT u.id, u.username FROM users u
    JOIN room_allocations ra ON ra.student_id = u.id AND ra.status = 'active'
    JOIN rooms r ON r.id = ra.room_id
    WHERE u.role = 'student' AND u.status = 'active' AND u.approval_status = 'approved'
    AND (u.username ILIKE ? OR u.email ILIKE ?)" . $studentClause . "
    GROUP BY u.id, u.username ORDER BY u.username");
$studentStmt->bind_param('ss', $searchPattern, $searchPattern);
$studentStmt->execute();
$students = $studentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$studentStmt->close();
$availableRooms = db()->query("SELECT r.id, r.room_number, r.room_type, r.capacity, r.occupied, h.name AS hostel_name
    FROM rooms r JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE r.status = 'available' AND r.occupied < r.capacity" . $studentClause . "
    ORDER BY h.name, r.room_number")->fetch_all(MYSQLI_ASSOC);
$allocations = db()->query("SELECT u.username, r.room_number, r.room_type, h.name AS hostel_name, a.allocated_at
    FROM room_allocations a JOIN users u ON u.id = a.student_id
    JOIN rooms r ON r.id = a.room_id JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.status = 'active'" . $studentClause . "
    ORDER BY h.name, r.room_number, u.username")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Residents | CourtIQ</title><link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>"></head>
<body class="dashboard-layout"><?php require __DIR__ . '/partials/nav.php'; ?><main class="dashboard-content-main container">
    <section class="role-page-heading"><span class="dash-welcome-eyebrow">HOSTEL OPERATIONS</span><h1>Residents &amp; room assignments</h1><p>Search your residents and allocate available rooms.</p></section>
    <?php if (!empty($_GET['success'])): ?><div class="admin-alert is-success role-page-alert">Room allocation completed.</div><?php endif; ?>
    <?php if (!empty($_GET['error'])): ?><div class="admin-alert is-error role-page-alert"><?= e($_GET['error']) ?></div><?php endif; ?>
    <?php if (!$assignedHostelIds): ?><div class="admin-alert is-error">No hostel is assigned to this warden. Contact an administrator.</div><?php else: ?>
        <section class="visual-card"><h2>Allocate a room</h2><form method="get" class="admin-form"><input name="student_q" value="<?= e($studentSearch) ?>" placeholder="Search allocated student"><button class="btn btn-secondary btn-sm">Search</button></form><form method="post" action="actions.php" class="admin-form"><?= csrfField() ?><input type="hidden" name="action" value="allocate"><select name="student_id" required><option value="">Select resident</option><?php foreach ($students as $student): ?><option value="<?= (int)$student['id'] ?>"><?= e($student['username']) ?></option><?php endforeach; ?></select><select name="room_id" required><option value="">Select available room</option><?php foreach ($availableRooms as $room): ?><option value="<?= (int)$room['id'] ?>"><?= e($room['hostel_name']) ?> · <?= e($room['room_number']) ?> (<?= (int)$room['occupied'] ?>/<?= (int)$room['capacity'] ?>)</option><?php endforeach; ?></select><button class="btn btn-primary">Allocate room</button></form><p class="dash-card-desc">Search only finds residents with an active allocation in your assigned hostels.</p></section>
    <?php endif; ?>
    <section class="visual-card" style="margin-top:20px"><div class="admin-section-heading"><div><h2>Current room assignments</h2></div><span class="admin-count"><?= count($allocations) ?> residents</span></div><div class="admin-table-wrap"><table class="dash-table admin-table"><thead><tr><th>Student</th><th>Hostel</th><th>Room</th><th>Room type</th><th>Allocated</th></tr></thead><tbody><?php foreach ($allocations as $allocation): ?><tr><td><?= e($allocation['username']) ?></td><td><?= e($allocation['hostel_name']) ?></td><td><?= e($allocation['room_number']) ?></td><td><?= e($allocation['room_type']) ?></td><td><?= date('M j, Y', strtotime($allocation['allocated_at'])) ?></td></tr><?php endforeach; ?><?php if (!$allocations): ?><tr><td colspan="5" class="admin-empty">No active residents found in your assigned hostels.</td></tr><?php endif; ?></tbody></table></div></section>
</main></body></html>
