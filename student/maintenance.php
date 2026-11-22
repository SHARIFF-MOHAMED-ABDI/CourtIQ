<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

$userId = (int)currentUser()['id'];
$conn = db();
$allocationStmt = $conn->prepare("SELECT h.name AS hostel_name, r.room_number
    FROM room_allocations a
    JOIN rooms r ON r.id = a.room_id
    JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
$allocationStmt->bind_param('i', $userId);
$allocationStmt->execute();
$allocation = $allocationStmt->get_result()->fetch_assoc();
$allocationStmt->close();

$requestStmt = $conn->prepare("SELECT id, reference_no, title, category, description, priority, status,
        hostel_name_snapshot, room_number_snapshot, latest_response, resolution_notes, created_at
    FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC");
$requestStmt->bind_param('i', $userId);
$requestStmt->execute();
$requests = $requestStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$requestStmt->close();
$historyByRequest = [];
if ($requests) {
    $requestIds = implode(',', array_map(static fn(array $request): int => (int)$request['id'], $requests));
    $historyResult = $conn->query("SELECT h.request_id, h.action, h.previous_status, h.new_status, h.response, h.created_at,
            COALESCE(sp.full_name, u.username, 'System') AS actor_name, u.role AS actor_role
        FROM maintenance_request_history h
        LEFT JOIN users u ON u.id = h.actor_id
        LEFT JOIN student_profiles sp ON sp.user_id = u.id
        WHERE h.request_id IN ($requestIds)
        ORDER BY h.request_id, h.created_at DESC, h.id DESC");
    if (!$historyResult) {
        error_log('CourtIQ student maintenance history query failed: ' . $conn->error);
        http_response_code(500);
        exit('Unable to load maintenance history.');
    }
    foreach ($historyResult->fetch_all(MYSQLI_ASSOC) as $entry) {
        $historyByRequest[(int)$entry['request_id']][] = $entry;
    }
}
$counts = ['pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0];
foreach ($requests as $request) {
    if (isset($counts[$request['status']])) {
        $counts[$request['status']]++;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout" data-maintenance-endpoint="<?= e(projectUrl('student/maintenance-updates.php')) ?>">
<?php require __DIR__ . '/partials/nav.php'; ?>
<main class="dashboard-content-main container">
    <section class="role-page-heading"><span class="dash-welcome-eyebrow">STUDENT SERVICES</span><h1>Maintenance requests</h1><p>Report an issue and track updates from your hostel team.</p></section>
    <?php if (!empty($_GET['success'])): ?><div class="admin-alert is-success role-page-alert" role="status">Maintenance request submitted successfully.</div><?php endif; ?>
    <?php if (!empty($_GET['error'])): ?><div class="admin-alert is-error role-page-alert" role="alert"><?= e((string)$_GET['error']) ?></div><?php endif; ?>
    <section class="dashboard-visual-grid">
        <div class="visual-card">
            <h2>Report an issue</h2>
            <p>Your allocated location: <?= e($allocation['hostel_name'] ?? 'No hostel assigned') ?><?= !empty($allocation['room_number']) ? ' · Room ' . e($allocation['room_number']) : '' ?></p>
            <form method="post" action="actions.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="maintenance">
                <p><label for="category">Issue category</label><br><select id="category" name="category" required>
                    <option value="Plumbing">Plumbing</option><option value="Electrical">Electrical</option>
                    <option value="Furniture">Furniture</option><option value="Cleaning">Cleaning</option>
                    <option value="Room Facilities">Room Facilities</option><option value="Security">Security</option>
                    <option value="Other">Other</option>
                </select></p>
                <p><label for="description">Issue description</label><br><textarea id="description" name="description" rows="4" maxlength="5000" required></textarea></p>
                <p><label for="priority">Priority</label><br><select id="priority" name="priority">
                    <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option>
                </select></p>
                <button class="btn btn-primary btn-sm">Submit request</button>
            </form>
        </div>
        <section class="visual-card">
            <h2>Your requests <span class="admin-count" data-maintenance-count="total"><?= count($requests) ?></span></h2>
            <p aria-live="polite" data-maintenance-refresh-status>Updates automatically every 20 seconds.</p>
            <div class="activity-list" data-maintenance-list>
                <?php foreach ($requests as $request): ?>
                    <?php $requestId = (int)$request['id']; ?>
                    <article class="activity-item"><div>
                        <strong><?= e($request['reference_no']) ?> · <?= e($request['category']) ?></strong>
                        <span><?= e($request['title']) ?> · <?= e(ucfirst($request['priority'])) ?> priority · <?= e(date('M j, Y H:i', strtotime($request['created_at']))) ?></span>
                        <span><?= e($request['hostel_name_snapshot'] ?: 'Hostel not assigned') ?><?= $request['room_number_snapshot'] ? ' · Room ' . e($request['room_number_snapshot']) : '' ?></span>
                        <p><?= nl2br(e($request['description'])) ?></p>
                        <?php if ($request['latest_response']): ?><p><strong>Latest response:</strong> <?= nl2br(e($request['latest_response'])) ?></p><?php endif; ?>
                        <?php if ($request['resolution_notes']): ?><p><strong>Resolution notes:</strong> <?= nl2br(e($request['resolution_notes'])) ?></p><?php endif; ?>
                        <details><summary>Progress history</summary>
                            <?php if (!empty($historyByRequest[$requestId])): ?>
                                <ul><?php foreach ($historyByRequest[$requestId] as $entry): ?>
                                    <li><strong><?= e(ucwords(str_replace('_', ' ', $entry['action']))) ?> · <?= e($entry['actor_name']) ?></strong>
                                        <span><?= e(date('M j, Y H:i', strtotime($entry['created_at']))) ?>
                                        <?php if ($entry['previous_status'] !== null || $entry['new_status'] !== null): ?> · <?= e($entry['previous_status'] ? ucfirst(str_replace('_', ' ', $entry['previous_status'])) : '—') ?> → <?= e($entry['new_status'] ? ucfirst(str_replace('_', ' ', $entry['new_status'])) : '—') ?><?php endif; ?></span>
                                        <?php if ($entry['response']): ?><p><?= nl2br(e($entry['response'])) ?></p><?php endif; ?>
                                    </li>
                                <?php endforeach; ?></ul>
                            <?php else: ?><p>No progress history yet.</p><?php endif; ?>
                        </details>
                    </div><span class="status-pill status-<?= e($request['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($request['status']))) ?></span></article>
                <?php endforeach; ?>
                <?php if (!$requests): ?><p class="dash-card-desc">You have not reported any issues.</p><?php endif; ?>
            </div>
        </section>
    </section>
</main>
<script src="<?= projectUrl('assets/js/maintenance-updates.js') ?>" defer></script>
</body>
</html>
