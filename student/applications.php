<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);
$userId = (int)currentUser()['id'];
$stmt = db()->prepare('SELECT hostel_preference, room_type, application_status, notes, created_at FROM accommodation_applications WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Applications | CourtIQ</title><link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>"></head>
<body class="dashboard-layout"><?php require __DIR__ . '/partials/nav.php'; ?><main class="dashboard-content-main container">
    <section class="role-page-heading"><span class="dash-welcome-eyebrow">STUDENT SERVICES</span><h1>Accommodation applications</h1><p>Submit a request and review its status.</p></section>
    <?php if (!empty($_GET['success'])): ?><div class="admin-alert is-success role-page-alert">Application submitted successfully.</div><?php endif; ?>
    <?php if (!empty($_GET['error'])): ?><div class="admin-alert is-error role-page-alert"><?= e($_GET['error']) ?></div><?php endif; ?>
    <section class="dashboard-visual-grid">
        <div class="visual-card"><h2>New application</h2><form method="post" action="actions.php"><?= csrfField() ?><input type="hidden" name="action" value="application"><p><label>Hostel preference<br><input name="hostel_preference" required maxlength="150"></label></p><p><label>Room type<br><input name="room_type" required maxlength="60"></label></p><p><label>Notes<br><textarea name="notes" rows="3"></textarea></label></p><button class="btn btn-primary btn-sm">Submit application</button></form></div>
        <section class="visual-card"><h2>Application history</h2><?php if ($applications): ?><div class="activity-list"><?php foreach ($applications as $application): ?><div class="activity-item"><div><strong><?= e($application['hostel_preference']) ?> · <?= e($application['room_type']) ?></strong><span><?= e($application['notes'] ?: 'No additional notes') ?></span></div><span class="status-pill status-<?= e($application['application_status']) ?>"><?= e(ucfirst($application['application_status'])) ?><br><?= date('M j, Y', strtotime($application['created_at'])) ?></span></div><?php endforeach; ?></div><?php else: ?><p class="dash-card-desc">You have not submitted an application yet.</p><?php endif; ?></section>
    </section>
</main></body></html>
