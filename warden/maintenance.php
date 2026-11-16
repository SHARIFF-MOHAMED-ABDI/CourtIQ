<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['warden']);
require __DIR__ . '/partials/scope.php';
$requests = db()->query("SELECT m.id, m.reference_no, m.title, m.category, m.description, m.hostel_name_snapshot, m.room_number_snapshot,
        m.latest_response, m.priority, m.status, m.created_at, u.username AS reported_by
    FROM maintenance_requests m JOIN users u ON u.id = m.user_id" . $maintenanceClause . "
    ORDER BY FIELD(m.status, 'pending', 'in_progress', 'resolved', 'rejected'), m.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Maintenance | CourtIQ</title><link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>"></head>
<body class="dashboard-layout"><?php require __DIR__ . '/partials/nav.php'; ?><main class="dashboard-content-main container">
    <section class="role-page-heading"><span class="dash-welcome-eyebrow">HOSTEL OPERATIONS</span><h1>Maintenance follow-up</h1><p>Review and update requests raised in your assigned hostels.</p></section>
    <?php if (!empty($_GET['success'])): ?><div class="admin-alert is-success role-page-alert">Maintenance status updated.</div><?php endif; ?>
    <?php if (!empty($_GET['error'])): ?><div class="admin-alert is-error role-page-alert"><?= e($_GET['error']) ?></div><?php endif; ?>
    <section class="visual-card">
        <div class="admin-section-heading"><div><h2>Service queue</h2></div><span class="admin-count"><?= count($requests) ?> requests</span></div>
        <div class="admin-table-wrap"><table class="dash-table admin-table">
            <thead><tr><th>Ticket</th><th>Reported by</th><th>Priority</th><th>Status</th><th>Update / response</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><strong><?= e($request['reference_no']) ?></strong><small><?= e($request['category']) ?> · <?= e($request['description']) ?></small><small><?= e($request['hostel_name_snapshot'] ?: 'Hostel not assigned') ?><?= $request['room_number_snapshot'] ? ' · Room ' . e($request['room_number_snapshot']) : '' ?></small></td>
                    <td><?= e($request['reported_by']) ?><small>Reported <?= e(date('M j, Y H:i', strtotime($request['created_at']))) ?></small></td>
                    <td><span class="priority-<?= e($request['priority']) ?>"><?= e(ucfirst($request['priority'])) ?></span></td>
                    <td><span class="status-pill status-<?= e($request['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($request['status']))) ?></span><?php if ($request['latest_response']): ?><small><?= e($request['latest_response']) ?></small><?php endif; ?></td>
                    <td><form method="post" action="actions.php" class="inline-form"><?= csrfField() ?><input type="hidden" name="action" value="maintenance"><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><select name="status"><option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Pending</option><option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option><option value="resolved" <?= $request['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option><option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option></select><textarea name="response" rows="2" maxlength="5000" placeholder="Add a response (optional)"></textarea><button class="btn btn-secondary btn-sm">Save</button></form></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?><tr><td colspan="5" class="admin-empty">No maintenance requests in your assigned hostels.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
</main></body></html>
