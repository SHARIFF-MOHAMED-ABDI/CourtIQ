<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['administrator']);
$q = trim((string)($_GET['q'] ?? ''));
$approvalFilter = (string)($_GET['approval_filter'] ?? 'pending');
if (!in_array($approvalFilter, ['all', 'pending', 'approved', 'rejected'], true)) {
    $approvalFilter = 'pending';
}
$conn = db();
$hostelId = max(0, (int)($_GET['hostel_id'] ?? 0));
$hostelError = '';
$stmt = $conn->prepare("SELECT id, username, email, role, status FROM users WHERE username ILIKE CONCAT('%', ?, '%') OR email ILIKE CONCAT('%', ?, '%') ORDER BY username");
$stmt->bind_param('ss', $q, $q); $stmt->execute(); $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
$approvalStmt = $conn->prepare("SELECT u.id, u.email, u.approval_status, u.created_at, u.approval_decided_at,
        sp.full_name, sp.student_number, reviewer.username AS decided_by
    FROM users u
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    LEFT JOIN users reviewer ON reviewer.id = u.approval_decided_by
    WHERE u.role = 'student' AND (? = 'all' OR u.approval_status = ?)
    ORDER BY CASE u.approval_status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END, u.created_at DESC");
$approvalStmt->bind_param('ss', $approvalFilter, $approvalFilter);
$approvalStmt->execute();
$studentApprovals = $approvalStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$approvalStmt->close();
$hostels = $conn->query('SELECT id,name,location,capacity FROM hostel_profiles ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$selectedHostel = null;
foreach ($hostels as $hostel) {
    if ((int)$hostel['id'] === $hostelId) {
        $selectedHostel = $hostel;
        break;
    }
}
if ($hostelId > 0 && !$selectedHostel) {
    $hostelError = 'That hostel could not be found. Select a hostel from the list.';
    $hostelId = 0;
}
$rooms = [];
if ($selectedHostel) {
    $roomStmt = $conn->prepare('SELECT r.*, h.name AS hostel_name FROM rooms r JOIN hostel_profiles h ON h.id = r.hostel_id WHERE r.hostel_id = ? ORDER BY r.room_number');
    $roomStmt->bind_param('i', $hostelId);
    $roomStmt->execute();
    $rooms = $roomStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $roomStmt->close();
}
$students = $conn->query("SELECT id,username FROM users WHERE role='student' AND status='active' AND approval_status='approved' ORDER BY username")->fetch_all(MYSQLI_ASSOC);
$wardens = $conn->query("SELECT id, username FROM users WHERE role='warden' AND status='active' ORDER BY username")->fetch_all(MYSQLI_ASSOC);
$wardenAssignments = [];
if ($selectedHostel) {
    $assignmentStmt = $conn->prepare("SELECT wa.id, wa.warden_id, u.username AS warden_name, h.id AS hostel_id, h.name AS hostel_name
    FROM warden_assignments wa
    JOIN users u ON u.id = wa.warden_id
    JOIN hostel_profiles h ON h.id = wa.hostel_id
    WHERE wa.active = 1 AND wa.hostel_id = ?
    ORDER BY u.username");
    $assignmentStmt->bind_param('i', $hostelId);
    $assignmentStmt->execute();
    $wardenAssignments = $assignmentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $assignmentStmt->close();
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Settings &amp; Records | CourtIQ</title><link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>"></head>
<body class="dashboard-layout admin-dashboard"><?php require __DIR__ . '/partials/nav.php'; ?><main class="dashboard-content-main container admin-main">
<section class="admin-page-heading"><div><span class="admin-eyebrow">SETTINGS &amp; RECORDS</span><h1>Administration workspace</h1><p>Edit users, hostels, rooms and payment records.</p></div></section>
<section class="visual-card hostel-entry-card">
<div class="admin-section-heading"><div><span class="admin-eyebrow">HOSTEL FIRST</span><h2>Choose a hostel to manage</h2><p>Select one facility to review its rooms, vacancies, residents, allocations and warden assignments.</p></div></div>
<?php if ($hostelError): ?><div class="admin-alert is-error"><?= e($hostelError) ?></div><?php endif; ?>
<form method="get" action="manage.php" class="admin-form hostel-selector-form">
<label for="hostel-filter">Hostel</label>
<select id="hostel-filter" name="hostel_id" required><option value="">Select a hostel</option><?php foreach ($hostels as $hostel): ?><option value="<?= (int)$hostel['id'] ?>" <?= $hostelId === (int)$hostel['id'] ? 'selected' : '' ?>><?= e($hostel['name']) ?></option><?php endforeach; ?></select>
<button class="btn btn-primary">Open hostel</button>
</form>
<form method="post" action="actions.php" class="admin-form hostel-add-form"><?= csrfField() ?><input type="hidden" name="action" value="hostel_save"><input name="name" required maxlength="150" placeholder="New hostel name"><input name="location" maxlength="180" placeholder="Location"><input name="capacity" type="number" min="1" required placeholder="Total capacity"><button class="btn btn-secondary">Add hostel</button></form>
<?php if ($selectedHostel): ?>
<?php $roomCount = count($rooms); $occupiedBeds = 0; $availableBeds = 0; foreach ($rooms as $room) { $occupiedBeds += (int)$room['occupied']; if ($room['status'] !== 'maintenance') { $availableBeds += max(0, (int)$room['capacity'] - (int)$room['occupied']); } } ?>
<div class="hostel-summary-strip"><strong><?= e($selectedHostel['name']) ?></strong><span><?= e($selectedHostel['location'] ?: 'Location not set') ?></span><span><?= $roomCount ?> rooms</span><span><?= $occupiedBeds ?> occupied beds</span><span><?= $availableBeds ?> vacant beds</span><a class="btn btn-primary btn-sm" href="#selected-hostel-management">Manage hostel &amp; rooms</a><a class="btn btn-secondary btn-sm" href="residents.php?hostel_id=<?= $hostelId ?>">Residents &amp; room allocation</a><a class="btn btn-secondary btn-sm" href="maintenance.php?hostel_id=<?= $hostelId ?>">Hostel maintenance</a></div>
<?php else: ?>
<p class="admin-empty">Choose a hostel above to open its room and resident records. You can add a new hostel from this page.</p>
<?php endif; ?>
</section>
<h1>Administration</h1><?php if (!empty($_GET['error'])): ?><p class="counter-badge"><?= e($_GET['error']) ?></p><?php endif; ?><?php if (!empty($_GET['success'])): ?><p class="vcard-status">Saved successfully.</p><?php endif; ?>
<section class="visual-card"><h2>User Account Approvals</h2>
<form method="get" class="admin-form"><input type="hidden" name="hostel_id" value="<?= $hostelId ?>"><label for="approval_filter">Account status</label><select id="approval_filter" name="approval_filter"><option value="pending" <?= $approvalFilter === 'pending' ? 'selected' : '' ?>>Pending</option><option value="approved" <?= $approvalFilter === 'approved' ? 'selected' : '' ?>>Approved</option><option value="rejected" <?= $approvalFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option><option value="all" <?= $approvalFilter === 'all' ? 'selected' : '' ?>>All</option></select><button class="btn btn-primary btn-sm">View accounts</button></form>
<table class="dash-table"><thead><tr><th>Student</th><th>Registration number</th><th>Email</th><th>Registered</th><th>Status / decision</th><th>Action</th></tr></thead><tbody>
<?php foreach ($studentApprovals as $approval): ?><tr><td><?= e($approval['full_name'] ?: 'Name not supplied') ?></td><td><?= e($approval['student_number'] ?: '—') ?></td><td><?= e($approval['email']) ?></td><td><?= e(date('M j, Y H:i', strtotime($approval['created_at']))) ?></td><td><?= e(ucfirst($approval['approval_status'])) ?><?php if ($approval['approval_decided_at']): ?><br><small><?= e($approval['decided_by'] ?: 'Administrator') ?> · <?= e(date('M j, Y H:i', strtotime($approval['approval_decided_at']))) ?></small><?php endif; ?></td><td>
<?php if ($approval['approval_status'] === 'pending'): ?>
<form method="post" action="actions.php" class="inline-form"><?= csrfField() ?><input type="hidden" name="action" value="account_approval"><input type="hidden" name="user_id" value="<?= (int)$approval['id'] ?>"><button class="btn btn-primary btn-sm" name="decision" value="approved">Approve</button><button class="btn btn-secondary btn-sm" name="decision" value="rejected">Reject</button></form>
<?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$studentApprovals): ?><tr><td colspan="6" class="admin-empty">No student accounts match this status.</td></tr><?php endif; ?>
</tbody></table></section>
<section class="visual-card"><h2>User accounts</h2><form method="get"><input type="hidden" name="hostel_id" value="<?= $hostelId ?>"><input name="q" value="<?= e($q) ?>" placeholder="Search username or email"><button class="btn btn-primary btn-sm">Search</button></form>
<form method="post" action="actions.php" class="admin-form"><?= csrfField() ?><input type="hidden" name="action" value="user_save"><input type="hidden" name="id" value="0">
<input name="username" required maxlength="80" placeholder="Username"><input type="email" name="email" required maxlength="150" placeholder="Email"><input name="password" required minlength="8" type="password" placeholder="Password"><select name="role"><option>student</option><option>warden</option><option>administrator</option></select><select name="status"><option>active</option><option>inactive</option><option>suspended</option></select><button class="btn btn-primary btn-sm">Add user</button></form>
<p>Login names and email addresses are listed below. Passwords are stored as one-way hashes and cannot be viewed; an administrator can set a new password when needed.</p>
<table class="dash-table"><tr><th>Login</th><th>Role</th><th>Status</th><th>Password</th><th>Actions</th></tr><?php foreach($users as $u): ?><tr><td><?= e($u['username']) ?><br><small><?= e($u['email']) ?></small></td><td><?= e(formatRole($u['role'])) ?></td><td><?= e($u['status']) ?></td><td><form method="post" action="actions.php"><?= csrfField() ?><input type="hidden" name="action" value="user_password_reset"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><label class="sr-only" for="new-password-<?= (int)$u['id'] ?>">New password for <?= e($u['username']) ?></label><input id="new-password-<?= (int)$u['id'] ?>" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Set new password"><button class="btn btn-secondary btn-sm">Reset password</button></form><small>Current password is not viewable.</small></td><td><form method="post" action="actions.php"><?= csrfField() ?><input type="hidden" name="action" value="user_status"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><select name="status"><option <?= $u['status']==='active'?'selected':'' ?>>active</option><option <?= $u['status']==='inactive'?'selected':'' ?>>inactive</option><option <?= $u['status']==='suspended'?'selected':'' ?>>suspended</option></select><button class="btn btn-secondary btn-sm">Update</button></form><form method="post" action="actions.php"><?= csrfField() ?><input type="hidden" name="action" value="user_delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-secondary btn-sm">Delete</button></form></td></tr><?php endforeach; ?></table></section>
<?php if ($selectedHostel): ?>
<section class="visual-card" id="selected-hostel-management">
<div class="admin-section-heading"><div><span class="admin-eyebrow">SELECTED HOSTEL</span><h2><?= e($selectedHostel['name']) ?> details and rooms</h2><p>Review room capacity and vacancy, update hostel details, or add rooms to this facility.</p></div></div>
<form method="post" action="actions.php" class="admin-form hostel-edit-form"><?= csrfField() ?><input type="hidden" name="action" value="hostel_update"><input type="hidden" name="id" value="<?= $hostelId ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>"><input name="name" required maxlength="150" value="<?= e($selectedHostel['name']) ?>" aria-label="Hostel name"><input name="location" maxlength="180" value="<?= e($selectedHostel['location']) ?>" aria-label="Hostel location"><input name="capacity" type="number" min="1" value="<?= (int)$selectedHostel['capacity'] ?>" aria-label="Hostel capacity"><button class="btn btn-primary btn-sm">Update hostel</button></form>
<form method="post" action="actions.php" class="inline-form hostel-delete-form" onsubmit="return confirm('Delete this hostel? The system will reject deletion while rooms, maintenance history or active warden assignments are attached.');"><?= csrfField() ?><input type="hidden" name="action" value="hostel_delete"><input type="hidden" name="id" value="<?= $hostelId ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>"><button class="btn btn-danger btn-sm">Delete hostel</button></form>
<form method="post" action="actions.php" class="admin-form hostel-add-room-form"><?= csrfField() ?><input type="hidden" name="action" value="room_save"><input type="hidden" name="hostel_id" value="<?= $hostelId ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>"><input name="room_number" required maxlength="50" placeholder="Room number"><input name="room_type" maxlength="60" value="Standard" placeholder="Room type"><input name="capacity" type="number" min="1" required value="1" aria-label="Room capacity"><button class="btn btn-secondary btn-sm">Add room</button></form>
<div class="admin-table-wrap"><table class="dash-table"><thead><tr><th>Room</th><th>Type</th><th>Occupancy / vacancies</th><th>Status and actions</th></tr></thead><tbody>
<?php foreach ($rooms as $room): $roomFormId = 'room-update-' . (int)$room['id']; ?><tr><td><form id="<?= e($roomFormId) ?>" class="hostel-room-edit-form" method="post" action="actions.php"><?= csrfField() ?><input type="hidden" name="action" value="room_update"><input type="hidden" name="id" value="<?= (int)$room['id'] ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>"></form><input form="<?= e($roomFormId) ?>" name="room_number" required maxlength="50" value="<?= e($room['room_number']) ?>" aria-label="Room number"></td><td><input form="<?= e($roomFormId) ?>" name="room_type" maxlength="60" value="<?= e($room['room_type']) ?>" aria-label="Room type"></td><td><?= (int)$room['occupied'] ?> / <?= (int)$room['capacity'] ?><small><?= max(0, (int)$room['capacity'] - (int)$room['occupied']) ?> vacant beds</small><input form="<?= e($roomFormId) ?>" type="number" name="capacity" min="<?= max(1, (int)$room['occupied']) ?>" value="<?= (int)$room['capacity'] ?>" aria-label="Room capacity"></td><td><select form="<?= e($roomFormId) ?>" name="status"><option value="available" <?= $room['status'] === 'available' ? 'selected' : '' ?>>Available</option><option value="occupied" <?= $room['status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option><option value="maintenance" <?= $room['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option></select><button form="<?= e($roomFormId) ?>" class="btn btn-secondary btn-sm">Save room</button><form method="post" action="actions.php" onsubmit="return confirm('Delete this room? The system will reject deletion while allocation records exist.');"><?= csrfField() ?><input type="hidden" name="action" value="room_delete"><input type="hidden" name="id" value="<?= (int)$room['id'] ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>"><button class="btn btn-danger btn-sm">Delete room</button></form></td></tr><?php endforeach; ?>
<?php if (!$rooms): ?><tr><td colspan="4" class="admin-empty">No rooms are configured for this hostel yet.</td></tr><?php endif; ?>
</tbody></table></div></section>
<section class="visual-card"><div class="admin-section-heading"><div><span class="admin-eyebrow">WARDEN ACCESS</span><h2>Wardens assigned to <?= e($selectedHostel['name']) ?></h2></div></div>
<form method="post" action="actions.php" class="admin-form"><?= csrfField() ?><input type="hidden" name="action" value="warden_assignment_save"><input type="hidden" name="hostel_id" value="<?= $hostelId ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>"><select name="warden_id" required><option value="">Select warden</option><?php foreach ($wardens as $warden): ?><option value="<?= (int)$warden['id'] ?>"><?= e($warden['username']) ?></option><?php endforeach; ?></select><button class="btn btn-primary btn-sm">Assign warden</button></form>
<table class="dash-table"><thead><tr><th>Warden</th><th>Hostel</th><th>Action</th></tr></thead><tbody><?php foreach ($wardenAssignments as $assignment): ?><tr><td><?= e($assignment['warden_name']) ?></td><td><?= e($assignment['hostel_name']) ?></td><td><form method="post" action="actions.php"><?= csrfField() ?><input type="hidden" name="action" value="warden_assignment_delete"><input type="hidden" name="id" value="<?= (int)$assignment['id'] ?>"><input type="hidden" name="return_hostel_id" value="<?= $hostelId ?>"><button class="btn btn-secondary btn-sm">Remove</button></form></td></tr><?php endforeach; ?><?php if (!$wardenAssignments): ?><tr><td colspan="3" class="admin-empty">No warden is assigned to this hostel.</td></tr><?php endif; ?></tbody></table></section>
<?php endif; ?>
<section class="visual-card"><h2>Payment record entry</h2><form method="post" action="actions.php" class="admin-form"><?= csrfField() ?><input type="hidden" name="action" value="payment_save"><select name="user_id" required><option value="">Student</option><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['username']) ?></option><?php endforeach; ?></select><input name="reference_no" required maxlength="60" placeholder="Reference"><input name="amount" type="number" min="0" step=".01" required placeholder="Amount"><input name="balance" type="number" min="0" step=".01" required placeholder="Balance"><input name="payment_date" type="date" required value="<?= date('Y-m-d') ?>"><select name="status"><option>recorded</option><option>verified</option><option>pending_audit</option></select><button class="btn btn-primary btn-sm">Record payment</button></form></section>
</main></body></html>
