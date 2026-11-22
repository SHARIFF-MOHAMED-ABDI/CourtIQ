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

// Check for active/pending application
$hasActiveApplication = false;
foreach ($applications as $app) {
    if (in_array($app['application_status'], ['submitted', 'pending'])) {
        $hasActiveApplication = true;
        break;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Applications | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout has-sidebar">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">APPLICATIONS</span>
                <h1 class="dashboard-header-title">Accommodation Applications</h1>
                <p class="dashboard-header-subtitle">Submit a request and review its status.</p>
            </div>
        </header>

        <!-- Alerts -->
        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert--success" role="status">
                <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>Application submitted successfully.</span>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert--error" role="alert">
                <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span><?= e($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <!-- New Application Form -->
        <section class="visual-card" aria-labelledby="new-application-heading">
            <h2 id="new-application-heading" class="visual-card-title">New Application</h2>
            <p class="visual-card-subtitle">Submit an accommodation application for review by the administration.</p>
            
            <?php if ($hasActiveApplication): ?>
                <div class="alert alert--warning" style="margin-bottom: 20px;">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>You already have a pending application. Please wait for it to be processed before submitting a new one.</span>
                </div>
            <?php endif; ?>
            
            <form method="post" action="actions.php" class="form-row" style="margin-top: 16px;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="application" <?= $hasActiveApplication ? 'disabled' : '' ?>>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="hostel_preference" class="form-label">Hostel Preference <span aria-hidden="true">*</span></label>
                    <input type="text" id="hostel_preference" name="hostel_preference" class="form-input" required maxlength="150" placeholder="e.g., Block A, Main Hostel" <?= $hasActiveApplication ? 'disabled' : '' ?>>
                </div>
                
                <div class="form-group">
                    <label for="room_type" class="form-label">Room Type <span aria-hidden="true">*</span></label>
                    <input type="text" id="room_type" name="room_type" class="form-input" required maxlength="60" placeholder="e.g., Single, Double, Suite" <?= $hasActiveApplication ? 'disabled' : '' ?>>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-textarea" rows="3" placeholder="Any additional information..."></textarea>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1; display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary" <?= $hasActiveApplication ? 'disabled' : '' ?>>Submit Application</button>
                    <?php if ($hasActiveApplication): ?>
                        <a href="<?= projectUrl('student/index.php') ?>" class="btn btn-secondary">Back to Dashboard</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Application History -->
        <section class="visual-card" style="margin-top: 24px;" aria-labelledby="application-history-heading">
            <h2 id="application-history-heading" class="visual-card-title">Application History</h2>
            
            <?php if ($applications): ?>
                <div class="activity-list">
                    <?php foreach ($applications as $application): ?>
                        <article class="activity-item">
                            <div>
                                <strong><?= e($application['hostel_preference']) ?> · <?= e($application['room_type']) ?></strong>
                                <span><?= e($application['notes'] ?: 'No additional notes') ?></span>
                                <span style="font-size: 0.825rem; color: var(--text-muted);"><?= date('M j, Y H:i', strtotime($application['created_at'])) ?></span>
                            </div>
                            <span class="status-badge status-badge--<?= e($application['application_status']) ?>">
                                <?= e(ucfirst($application['application_status'])) ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <h3 class="empty-state-title">No applications submitted</h3>
                    <p class="empty-state-desc">Submit your first accommodation application above.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>