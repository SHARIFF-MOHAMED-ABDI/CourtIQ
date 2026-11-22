<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

$userId = (int)currentUser()['id'];
$conn = db();

// Check if student has confirmed room allocation
$allocationStmt = $conn->prepare("SELECT h.name AS hostel_name, r.room_number, r.id AS room_id
    FROM room_allocations a
    JOIN rooms r ON r.id = a.room_id
    JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
$allocationStmt->bind_param('i', $userId);
$allocationStmt->execute();
$allocation = $allocationStmt->get_result()->fetch_assoc();
$allocationStmt->close();

// Get maintenance requests
$requestStmt = $conn->prepare("SELECT id, reference_no, title, category, description, priority, status,
        hostel_name_snapshot, room_number_snapshot, latest_response, resolution_notes, created_at
    FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC");
$requestStmt->bind_param('i', $userId);
$requestStmt->execute();
$requests = $requestStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$requestStmt->close();

// Get history for each request
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

// Count requests by status
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
<body class="dashboard-layout has-sidebar" data-maintenance-endpoint="<?= e(projectUrl('student/maintenance-updates.php')) ?>">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">MAINTENANCE</span>
                <h1 class="dashboard-header-title">Maintenance Requests</h1>
                <p class="dashboard-header-subtitle">Track faults reported for your room.</p>
            </div>
            <button type="button" class="btn btn-primary btn-block" style="max-width: 180px;" id="newRequestBtn">+ New Request</button>
        </header>

        <!-- Status Summary Cards -->
        <div class="stats-grid" aria-label="Maintenance statistics">
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Total Issues</span>
                    <div class="stat-card-icon stat-card-icon--cyan" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value" data-maintenance-count="total"><?= count($requests) ?></div>
                <p class="stat-card-desc">Requests submitted</p>
            </article>
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Pending</span>
                    <div class="stat-card-icon" style="background: var(--status-warning-bg); color: var(--status-warning);" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value" data-maintenance-count="pending"><?= (int)$counts['pending'] ?></div>
                <p class="stat-card-desc">Awaiting review</p>
            </article>
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">In Progress</span>
                    <div class="stat-card-icon" style="background: var(--color-secondary-soft); color: var(--color-secondary);" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12a9 9 0 11-6.219-8.56"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value" data-maintenance-count="in_progress"><?= (int)$counts['in_progress'] ?></div>
                <p class="stat-card-desc">Being handled</p>
            </article>
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Resolved</span>
                    <div class="stat-card-icon stat-card-icon--green" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value" data-maintenance-count="resolved"><?= (int)$counts['resolved'] ?></div>
                <p class="stat-card-desc">Marked resolved</p>
            </article>
        </div>

        <!-- New Request Form (Hidden by default) -->
        <section class="visual-card" id="newRequestForm" hidden aria-labelledby="new-request-heading">
            <h2 id="new-request-heading" class="visual-card-title">Report an Issue</h2>
            <p class="visual-card-subtitle">
                <?php if ($allocation): ?>
                    Your allocated location: <?= e($allocation['hostel_name']) ?> · Room <?= e($allocation['room_number']) ?>
                <?php else: ?>
                    You need a confirmed room booking to log maintenance requests.
                <?php endif; ?>
            </p>
            
            <form method="post" action="actions.php" class="form-row" style="margin-top: 16px;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="maintenance">
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="category" class="form-label">Issue Category <span aria-hidden="true">*</span></label>
                    <select id="category" name="category" class="form-select" required <?= !$allocation ? 'disabled' : '' ?>>
                        <option value="">Select category</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Cleaning">Cleaning</option>
                        <option value="Room Facilities">Room Facilities</option>
                        <option value="Security">Security</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="description" class="form-label">Issue Description <span aria-hidden="true">*</span></label>
                    <textarea id="description" name="description" class="form-textarea" rows="4" maxlength="5000" required <?= !$allocation ? 'disabled' : '' ?> placeholder="Describe the issue in detail..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="priority" class="form-label">Priority</label>
                    <select id="priority" name="priority" class="form-select" <?= !$allocation ? 'disabled' : '' ?>>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1; display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary" <?= !$allocation ? 'disabled' : '' ?>>Submit Request</button>
                    <button type="button" class="btn btn-secondary" id="cancelRequestBtn">Cancel</button>
                </div>
            </form>
        </section>

        <!-- Maintenance Requests List -->
        <section class="visual-card" aria-labelledby="requests-heading">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <h2 id="requests-heading" class="visual-card-title" style="margin: 0;">Your Requests <span class="admin-count" data-maintenance-count="total"><?= count($requests) ?></span></h2>
            </div>
            
            <?php if ($requests): ?>
                <div class="table-wrap">
                    <table class="dash-table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">Room</th>
                                <th scope="col">Category</th>
                                <th scope="col">Description</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): 
                                $requestId = (int)$request['id'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= e($request['hostel_name_snapshot'] ?: 'Unassigned') ?></strong>
                                        <?php if ($request['room_number_snapshot']): ?>
                                            <br><small style="color: var(--text-muted);">Room <?= e($request['room_number_snapshot']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($request['category']) ?></td>
                                    <td>
                                        <strong><?= e($request['title']) ?></strong>
                                        <br><small style="color: var(--text-muted);"><?= e($request['description']) ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-badge--<?= e($request['status']) ?>">
                                            <?= e(str_replace('_', ' ', ucfirst($request['status']))) ?>
                                        </span>
                                    </td>
                                    <td><?= e(date('M j, Y H:i', strtotime($request['created_at']))) ?></td>
                                    <td>
                                        <details>
                                            <summary style="cursor: pointer; color: var(--color-secondary); font-weight: 500;">View Details</summary>
                                            <div style="margin-top: 12px; padding: 12px; background: var(--bg-light); border-radius: var(--radius-md);">
                                                <p><strong>Priority:</strong> <?= e(ucfirst($request['priority'])) ?></p>
                                                <p><strong>Reference:</strong> <?= e($request['reference_no']) ?></p>
                                                <?php if ($request['latest_response']): ?>
                                                    <p><strong>Latest Response:</strong> <?= nl2br(e($request['latest_response'])) ?></p>
                                                <?php endif; ?>
                                                <?php if ($request['resolution_notes']): ?>
                                                    <p><strong>Resolution Notes:</strong> <?= nl2br(e($request['resolution_notes'])) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($historyByRequest[$requestId])): ?>
                                                    <div style="margin-top: 12px;">
                                                        <strong>Progress History:</strong>
                                                        <ul style="margin-top: 8px; padding-left: 20px; font-size: 0.825rem;">
                                                            <?php foreach ($historyByRequest[$requestId] as $entry): ?>
                                                                <li style="margin-bottom: 8px;">
                                                                    <strong><?= e(ucwords(str_replace('_', ' ', $entry['action']))) ?> · <?= e($entry['actor_name']) ?></strong>
                                                                    <span style="color: var(--text-muted);"><?= e(date('M j, Y H:i', strtotime($entry['created_at']))) ?></span>
                                                                    <?php if ($entry['previous_status'] !== null || $entry['new_status'] !== null): ?>
                                                                        · <?= e($entry['previous_status'] ? ucfirst(str_replace('_', ' ', $entry['previous_status'])) : '—') ?> → <?= e($entry['new_status'] ? ucfirst(str_replace('_', ' ', $entry['new_status'])) : '—') ?>
                                                                    <?php endif; ?>
                                                                    <?php if ($entry['response']): ?>
                                                                        <p style="margin-top: 4px;"><?= nl2br(e($entry['response'])) ?></p>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php else: ?>
                                                    <p style="margin-top: 8px; color: var(--text-muted);">No history recorded yet.</p>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                    <h3 class="empty-state-title">No maintenance requests yet</h3>
                    <p class="empty-state-desc">Click "New Request" to report an issue.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="<?= projectUrl('assets/js/maintenance-updates.js') ?>" defer></script>
    <script>
    (function() {
        const newRequestBtn = document.getElementById('newRequestBtn');
        const newRequestForm = document.getElementById('newRequestForm');
        const cancelRequestBtn = document.getElementById('cancelRequestBtn');
        
        if (newRequestBtn && newRequestForm) {
            newRequestBtn.addEventListener('click', function() {
                newRequestForm.hidden = false;
                newRequestBtn.hidden = true;
                newRequestForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
        
        if (cancelRequestBtn && newRequestForm && newRequestBtn) {
            cancelRequestBtn.addEventListener('click', function() {
                newRequestForm.hidden = true;
                newRequestBtn.hidden = false;
            });
        }
        
        // Auto-refresh maintenance counts (handled by maintenance-updates.js)
    })();
    </script>
</body>
</html>