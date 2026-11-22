<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['administrator']);

$conn = db();
$statusFilter = (string)($_GET['status'] ?? '');
$categoryFilter = (string)($_GET['category'] ?? '');
$hostelFilter = (int)($_GET['hostel_id'] ?? 0);
$search = trim((string)($_GET['q'] ?? ''));
$statuses = ['pending', 'in_progress', 'resolved', 'rejected'];
$categories = ['Plumbing', 'Electrical', 'Furniture', 'Cleaning', 'Room Facilities', 'Security', 'Other'];
if (!in_array($statusFilter, $statuses, true)) {
    $statusFilter = '';
}
if (!in_array($categoryFilter, $categories, true)) {
    $categoryFilter = '';
}
$hostels = $conn->query('SELECT id, name FROM hostel_profiles ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT m.id, m.reference_no, m.title, m.category, m.description, m.priority,
        m.status, m.latest_response, m.resolution_notes, m.created_at,
        m.hostel_id, COALESCE(m.hostel_name_snapshot, h.name) AS hostel_display,
        COALESCE(m.room_number_snapshot, r.room_number) AS room_display,
        u.username, u.email, sp.full_name, sp.student_number
    FROM maintenance_requests m
    JOIN users u ON u.id = m.user_id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    LEFT JOIN hostel_profiles h ON h.id = m.hostel_id
    LEFT JOIN rooms r ON r.id = m.room_id
    WHERE (? = '' OR m.status = ?)
      AND (? = '' OR m.category = ?)
      AND (? = 0 OR m.hostel_id = ?)
      AND (? = '' OR COALESCE(sp.full_name, u.username) ILIKE CONCAT('%', ?, '%')
           OR m.reference_no ILIKE CONCAT('%', ?, '%'))
    ORDER BY CASE m.status WHEN 'pending' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'resolved' THEN 3 WHEN 'rejected' THEN 4 ELSE 5 END, m.created_at DESC");
$stmt->bind_param(
    'ssssiisss',
    $statusFilter,
    $statusFilter,
    $categoryFilter,
    $categoryFilter,
    $hostelFilter,
    $hostelFilter,
    $search,
    $search,
    $search
);
if (!$stmt->execute()) {
    error_log('CourtIQ admin maintenance query failed: ' . $stmt->error);
    http_response_code(500);
    exit('Unable to load maintenance requests.');
}
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
        error_log('CourtIQ admin maintenance history query failed: ' . $conn->error);
        http_response_code(500);
        exit('Unable to load maintenance history.');
    }
    foreach ($historyResult->fetch_all(MYSQLI_ASSOC) as $entry) {
        $historyByRequest[(int)$entry['request_id']][] = $entry;
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
<body class="dashboard-layout admin-dashboard">
<?php require __DIR__ . '/partials/nav.php'; ?>
<main class="dashboard-content-main container admin-main">
    <?php if (!empty($_GET['error'])): ?><div class="admin-alert is-error"><?= e((string)$_GET['error']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['success'])): ?><div class="admin-alert is-success">Maintenance request updated.</div><?php endif; ?>
    <section class="admin-page-heading">
        <div><span class="admin-eyebrow">SERVICE DESK</span><h1>Maintenance requests</h1><p>Review reports, record responses and track every status change.</p></div>
        <a class="btn btn-primary" href="residents.php">View residents</a>
    </section>
    <section class="admin-section">
        <div class="admin-section-heading">
            <div><h2>Issue register</h2><p>All student reports are listed, including requests without a room assignment.</p></div>
            <span class="admin-count"><?= count($requests) ?> records</span>
        </div>
        <form method="get" class="admin-form" role="search">
            <label for="issue-search">Student name or reference</label>
            <input id="issue-search" name="q" value="<?= e($search) ?>" placeholder="Search reports">
            <label for="issue-status">Status</label>
            <select id="issue-status" name="status"><option value="">All statuses</option>
                <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?>
            </select>
            <label for="issue-category">Category</label>
            <select id="issue-category" name="category"><option value="">All categories</option>
                <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?>
            </select>
            <label for="issue-hostel">Hostel</label>
            <select id="issue-hostel" name="hostel_id"><option value="0">All hostels</option>
                <?php foreach ($hostels as $hostel): ?><option value="<?= (int)$hostel['id'] ?>" <?= $hostelFilter === (int)$hostel['id'] ? 'selected' : '' ?>><?= e($hostel['name']) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm">Filter</button>
            <a class="btn btn-secondary btn-sm" href="maintenance.php">Clear</a>
        </form>
        <div class="admin-table-wrap">
            <table class="dash-table admin-table">
                <thead><tr><th>Issue</th><th>Student</th><th>Hostel / room</th><th>Priority</th><th>Status</th><th>Submitted</th><th>Manage / history</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $request): ?>
                    <?php $requestId = (int)$request['id']; ?>
                    <tr>
                        <td>
                            <strong><?= e($request['reference_no']) ?></strong>
                            <small><?= e($request['category']) ?> · <?= e($request['title']) ?></small>
                            <details><summary>Issue details</summary><p><?= nl2br(e($request['description'])) ?></p></details>
                        </td>
                        <td><?= e($request['full_name'] ?: $request['username']) ?><small><?= e($request['student_number'] ?: 'Registration number not supplied') ?><br><?= e($request['email']) ?></small></td>
                        <td><?= e($request['hostel_display'] ?: 'Unassigned') ?><small><?= e($request['room_display'] ?: 'Room not assigned') ?></small></td>
                        <td><span class="priority-<?= e($request['priority']) ?>"><?= e(ucfirst($request['priority'])) ?></span></td>
                        <td><span class="status-pill status-<?= e($request['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($request['status']))) ?></span>
                            <?php if ($request['latest_response']): ?><small>Latest response: <?= e($request['latest_response']) ?></small><?php endif; ?>
                            <?php if ($request['resolution_notes']): ?><small>Resolution: <?= e($request['resolution_notes']) ?></small><?php endif; ?>
                        </td>
                        <td><?= e(date('M j, Y H:i', strtotime($request['created_at']))) ?></td>
                        <td>
                            <details><summary>Update / history</summary>
                                <form method="post" action="actions.php" class="admin-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="maintenance_update">
                                    <input type="hidden" name="request_id" value="<?= $requestId ?>">
                                    <label for="status-<?= $requestId ?>">Status</label>
                                    <select id="status-<?= $requestId ?>" name="status">
                                        <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?>
                                    </select>
                                    <label for="response-<?= $requestId ?>">Administrator response</label>
                                    <textarea id="response-<?= $requestId ?>" name="response" rows="2" maxlength="5000" placeholder="Add a response (optional)"></textarea>
                                    <label for="resolution-<?= $requestId ?>">Resolution notes</label>
                                    <textarea id="resolution-<?= $requestId ?>" name="resolution_notes" rows="2" maxlength="5000" placeholder="Record resolution details (optional)"></textarea>
                                    <button class="btn btn-secondary btn-sm">Save update</button>
                                </form>
                                <h3>Progress history</h3>
                                <?php if (!empty($historyByRequest[$requestId])): ?>
                                    <ul class="activity-list">
                                    <?php foreach ($historyByRequest[$requestId] as $entry): ?>
                                        <li class="activity-item"><div>
                                            <strong><?= e(ucwords(str_replace('_', ' ', $entry['action']))) ?> · <?= e($entry['actor_name']) ?><?= $entry['actor_role'] ? ' (' . e(formatRole($entry['actor_role'])) . ')' : '' ?></strong>
                                            <span><?= e(date('M j, Y H:i', strtotime($entry['created_at']))) ?>
                                            <?php if ($entry['previous_status'] !== null || $entry['new_status'] !== null): ?> · <?= e($entry['previous_status'] ? ucfirst(str_replace('_', ' ', $entry['previous_status'])) : '—') ?> → <?= e($entry['new_status'] ? ucfirst(str_replace('_', ' ', $entry['new_status'])) : '—') ?><?php endif; ?></span>
                                            <?php if ($entry['response']): ?><p><?= nl2br(e($entry['response'])) ?></p><?php endif; ?>
                                        </div></li>
                                    <?php endforeach; ?>
                                    </ul>
                                <?php else: ?><p>No history recorded yet.</p><?php endif; ?>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$requests): ?><tr><td colspan="7" class="admin-empty">No maintenance requests match these filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
