<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['administrator']);

$conn = db();
$hostelId = (int)($_GET['hostel_id'] ?? 0);
if ($hostelId < 1) {
    header('Location: manage.php');
    exit;
}
$hostelStmt = $conn->prepare('SELECT id, name FROM hostel_profiles WHERE id = ?');
$hostelStmt->bind_param('i', $hostelId);
$hostelStmt->execute();
$selectedHostel = $hostelStmt->get_result()->fetch_assoc();
$hostelStmt->close();
if (!$selectedHostel) {
    header('Location: manage.php?error=' . rawurlencode('Select a valid hostel first.'));
    exit;
}

$residents = [];
$rooms = [];
$unassigned = [];
if ($selectedHostel) {
    $residentStmt = $conn->prepare("SELECT a.id AS allocation_id, u.id AS student_id, u.username, u.email,
            sp.student_number, sp.phone, sp.course, sp.year_of_study, r.id AS room_id, r.room_number,
            r.room_type, a.allocated_at
        FROM room_allocations a
        JOIN users u ON u.id = a.student_id
        JOIN rooms r ON r.id = a.room_id
        LEFT JOIN student_profiles sp ON sp.user_id = u.id
        WHERE a.status = 'active' AND r.hostel_id = ?
        ORDER BY r.room_number, u.username");
    $residentStmt->bind_param('i', $hostelId);
    $residentStmt->execute();
    $residents = $residentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $residentStmt->close();

    $roomStmt = $conn->prepare("SELECT id, room_number, room_type, capacity, occupied, status
        FROM rooms WHERE hostel_id = ? ORDER BY room_number");
    $roomStmt->bind_param('i', $hostelId);
    $roomStmt->execute();
    $rooms = $roomStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $roomStmt->close();

    $unassignedStmt = $conn->prepare("SELECT u.id, u.username
        FROM users u
        LEFT JOIN room_allocations a ON a.student_id = u.id AND a.status = 'active'
        WHERE u.role = 'student' AND u.status = 'active' AND u.approval_status = 'approved'
            AND a.id IS NULL
        ORDER BY u.username");
    $unassignedStmt->execute();
    $unassigned = $unassignedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $unassignedStmt->close();
}
$availableRooms = array_values(array_filter($rooms, static function (array $room): bool {
    return $room['status'] !== 'maintenance' && (int)$room['occupied'] < (int)$room['capacity'];
}));
$occupiedBeds = array_sum(array_map(static function (array $room): int {
    return (int)$room['occupied'];
}, $rooms));
$vacantBeds = array_sum(array_map(static function (array $room): int {
    return $room['status'] === 'maintenance' ? 0 : max(0, (int)$room['capacity'] - (int)$room['occupied']);
}, $rooms));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Residents &amp; Room Allocation | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout admin-dashboard">
<?php require __DIR__ . '/partials/nav.php'; ?>
<main class="dashboard-content-main container admin-main">
    <?php if (!empty($_GET['error'])): ?><div class="admin-alert is-error"><?= e($_GET['error']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['success'])): ?><div class="admin-alert is-success">Resident records saved.</div><?php endif; ?>
    <section class="admin-page-heading">
        <div><span class="admin-eyebrow">HOSTEL OPERATIONS</span><h1>Residents &amp; room allocation</h1><p>Inspect vacancies, allocate residents and manage room assignments for the selected hostel.</p></div>
        <a class="btn btn-secondary" href="manage.php?hostel_id=<?= $hostelId ?>">Back to <?= e($selectedHostel['name']) ?> settings</a>
    </section>
    <section class="visual-card hostel-entry-card">
        <div class="hostel-summary-strip">
            <strong><?= e($selectedHostel['name']) ?></strong>
            <span><?= count($rooms) ?> rooms</span>
            <span><?= $occupiedBeds ?> occupied beds</span>
            <span><?= $vacantBeds ?> vacant beds</span>
            <span><?= count($residents) ?> active residents</span>
        </div>
    </section>

        <section class="admin-section">
            <div class="admin-section-heading"><div><h2>Residents in <?= e($selectedHostel['name']) ?></h2><p>Reassign a resident within this hostel or remove their current allocation.</p></div><span class="admin-count"><?= count($residents) ?> allocated</span></div>
            <div class="admin-table-wrap">
                <table class="dash-table admin-table resident-table">
                    <thead><tr><th>Student details</th><th>Room</th><th>Reassign</th><th>Remove</th></tr></thead>
                    <tbody>
                    <?php foreach ($residents as $resident): ?>
                        <tr>
                            <td><strong><?= e($resident['username']) ?></strong><small><?= e($resident['email']) ?></small><small><?= e($resident['student_number'] ?: 'Student number not added') ?> · <?= e($resident['course'] ?: 'Course not added') ?></small><small><?= e($resident['phone'] ?: 'Phone not added') ?><?= $resident['year_of_study'] ? ' · Year ' . (int)$resident['year_of_study'] : '' ?></small></td>
                            <td><span class="room-chip"><?= e($selectedHostel['name']) ?> · <?= e($resident['room_number']) ?></span><small><?= e($resident['room_type']) ?> · Allocated <?= e(date('M j, Y', strtotime($resident['allocated_at']))) ?></small></td>
                            <td>
                                <form method="post" action="actions.php" class="inline-form">
                                    <?= csrfField() ?><input type="hidden" name="action" value="allocation_save"><input type="hidden" name="student_id" value="<?= (int)$resident['student_id'] ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>">
                                    <select name="room_id" aria-label="New room for <?= e($resident['username']) ?>"><option value="<?= (int)$resident['room_id'] ?>">Keep current</option><?php foreach ($availableRooms as $room): ?><?php if ((int)$room['id'] !== (int)$resident['room_id']): ?><option value="<?= (int)$room['id'] ?>"><?= e($room['room_number']) ?> · <?= max(0, (int)$room['capacity'] - (int)$room['occupied']) ?> free</option><?php endif; ?><?php endforeach; ?></select>
                                    <button class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </td>
                            <td><form method="post" action="actions.php" onsubmit="return confirm('Remove this resident from the room?');"><?= csrfField() ?><input type="hidden" name="action" value="allocation_end"><input type="hidden" name="allocation_id" value="<?= (int)$resident['allocation_id'] ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>"><button class="btn btn-danger btn-sm">Remove</button></form></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$residents): ?><tr><td colspan="4" class="admin-empty">No active residents are allocated to this hostel.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="admin-section">
            <div class="admin-section-heading"><div><span class="admin-eyebrow">NEW ASSIGNMENT</span><h2>Allocate an unassigned student</h2><p>Only rooms belonging to <?= e($selectedHostel['name']) ?> are offered here.</p></div></div>
            <form method="post" action="actions.php" class="admin-form">
                <?= csrfField() ?><input type="hidden" name="action" value="allocation_save"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>">
                <select name="student_id" required><option value="">Select student</option><?php foreach ($unassigned as $student): ?><option value="<?= (int)$student['id'] ?>"><?= e($student['username']) ?></option><?php endforeach; ?></select>
                <select name="room_id" required><option value="">Select available room</option><?php foreach ($availableRooms as $room): ?><option value="<?= (int)$room['id'] ?>"><?= e($room['room_number']) ?> · <?= e($room['room_type']) ?> (<?= max(0, (int)$room['capacity'] - (int)$room['occupied']) ?> free)</option><?php endforeach; ?></select>
                <button class="btn btn-primary" <?= !$unassigned || !$availableRooms ? 'disabled' : '' ?>>Assign resident</button>
            </form>
            <?php if (!$unassigned): ?><p class="admin-empty">There are no unassigned approved students.</p><?php elseif (!$availableRooms): ?><p class="admin-empty">This hostel has no vacant rooms available for allocation.</p><?php endif; ?>
        </section>
        <section class="admin-section">
            <div class="admin-section-heading"><div><h2>Room availability</h2><p>Current room status and vacant bed counts for this hostel.</p></div><span class="admin-count"><?= count($availableRooms) ?> rooms with vacancies</span></div>
            <div class="admin-table-wrap"><table class="dash-table admin-table"><thead><tr><th>Room</th><th>Type</th><th>Occupancy</th><th>Vacant beds</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($rooms as $room): ?><tr><td><?= e($room['room_number']) ?></td><td><?= e($room['room_type']) ?></td><td><?= (int)$room['occupied'] ?> / <?= (int)$room['capacity'] ?></td><td><?= $room['status'] === 'maintenance' ? 0 : max(0, (int)$room['capacity'] - (int)$room['occupied']) ?></td><td><span class="status-pill status-<?= e($room['status']) ?>"><?= e(ucfirst($room['status'])) ?></span></td></tr><?php endforeach; ?>
                <?php if (!$rooms): ?><tr><td colspan="5" class="admin-empty">No rooms are configured for this hostel.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
</main>
</body>
</html>
