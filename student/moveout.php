<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

$userId = (int)currentUser()['id'];
$conn = db();

// Get current active allocation
$stmt = $conn->prepare("SELECT a.id, a.allocated_at,
        r.room_number, r.room_type, r.capacity, r.occupied,
        h.name AS hostel_name, h.id AS hostel_id
    FROM room_allocations a
    JOIN rooms r ON r.id = a.room_id
    JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$allocation = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get move out history
$historyStmt = $conn->prepare("SELECT a.id, a.allocated_at, a.ended_at,
        r.room_number, r.room_type,
        h.name AS hostel_name
    FROM room_allocations a
    JOIN rooms r ON r.id = a.room_id
    JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.student_id = ? AND a.status = 'ended'
    ORDER BY a.ended_at DESC");
$historyStmt->bind_param('i', $userId);
$historyStmt->execute();
$history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$historyStmt->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Move Out | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout has-sidebar">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">MOVE OUT</span>
                <h1 class="dashboard-header-title">Move Out Request</h1>
                <p class="dashboard-header-subtitle">Submit a request to end your current room allocation.</p>
            </div>
        </header>

        <!-- Current Allocation -->
        <section class="visual-card" aria-labelledby="current-allocation-heading">
            <h2 id="current-allocation-heading" class="visual-card-title">Current Room Allocation</h2>
            <?php if ($allocation): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <div class="admin-mini-card">
                        <small>Room</small>
                        <strong><?= e($allocation['room_number']) ?></strong>
                        <span>Current room assignment</span>
                    </div>
                    <div class="admin-mini-card">
                        <small>Hostel</small>
                        <strong><?= e($allocation['hostel_name']) ?></strong>
                        <span>Hostel facility</span>
                    </div>
                    <div class="admin-mini-card">
                        <small>Room Type</small>
                        <strong><?= e($allocation['room_type']) ?></strong>
                        <span>Accommodation type</span>
                    </div>
                    <div class="admin-mini-card">
                        <small>Allocated Since</small>
                        <strong><?= date('M j, Y', strtotime($allocation['allocated_at'])) ?></strong>
                        <span>Allocation start date</span>
                    </div>
                </div>
                
                <form method="post" action="actions.php" onsubmit="return confirm('Are you sure you want to submit a move out request? This will end your current room allocation and you will need to apply for a new room if needed.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="moveout_request">
                    <input type="hidden" name="allocation_id" value="<?= (int)$allocation['id'] ?>">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">Submit Move Out Request</button>
                        <a href="<?= projectUrl('student/bookings.php') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h3 class="empty-state-title">No active allocation</h3>
                    <p class="empty-state-desc">You don't have an active room allocation to move out from.</p>
                    <a href="<?= projectUrl('student/rooms.php') ?>" class="btn btn-primary mt-auto">Browse Available Rooms</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Move Out History -->
        <?php if ($history): ?>
            <section class="visual-card" style="margin-top: 24px;" aria-labelledby="history-heading">
                <h2 id="history-heading" class="visual-card-title">Move Out History</h2>
                <div class="table-wrap">
                    <table class="dash-table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">Room</th>
                                <th scope="col">Hostel</th>
                                <th scope="col">Room Type</th>
                                <th scope="col">Allocated</th>
                                <th scope="col">Moved Out</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $record): ?>
                                <tr>
                                    <td><?= e($record['room_number']) ?></td>
                                    <td><?= e($record['hostel_name']) ?></td>
                                    <td><?= e($record['room_type']) ?></td>
                                    <td><?= date('M j, Y', strtotime($record['allocated_at'])) ?></td>
                                    <td><?= $record['ended_at'] ? date('M j, Y', strtotime($record['ended_at'])) : '—' ?></td>
                                    <td>
                                        <span class="status-badge status-badge--rejected">Ended</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>