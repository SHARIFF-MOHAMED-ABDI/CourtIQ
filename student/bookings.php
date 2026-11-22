<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

$userId = (int)currentUser()['id'];
$conn = db();

// Get active allocations (bookings)
$stmt = $conn->prepare("SELECT a.id, a.allocated_at, a.status,
        r.room_number, r.room_type, r.capacity, r.occupied,
        h.name AS hostel_name, h.id AS hostel_id
    FROM room_allocations a
    JOIN rooms r ON r.id = a.room_id
    JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE a.student_id = ? AND a.status = 'active'
    ORDER BY a.allocated_at DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$allocations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get pending applications
$pendingStmt = $conn->prepare("SELECT id, hostel_preference, room_type, application_status, notes, created_at
    FROM accommodation_applications WHERE user_id = ? AND application_status IN ('submitted', 'pending')
    ORDER BY created_at DESC");
$pendingStmt->bind_param('i', $userId);
$pendingStmt->execute();
$pendingApplications = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pendingStmt->close();

// Get past allocations (history)
$historyStmt = $conn->prepare("SELECT a.id, a.allocated_at, a.ended_at, a.status,
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
    <title>My Bookings | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout has-sidebar">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">MY BOOKINGS</span>
                <h1 class="dashboard-header-title">My Room Bookings</h1>
                <p class="dashboard-header-subtitle">Track your active allocations and application requests.</p>
            </div>
            <a href="<?= projectUrl('student/rooms.php') ?>" class="btn btn-primary btn-block" style="max-width: 200px;">Browse Rooms</a>
        </header>

        <!-- Active Allocations -->
        <section class="visual-card" aria-labelledby="active-bookings-heading">
            <h2 id="active-bookings-heading" class="visual-card-title">Active Allocations</h2>
            <?php if ($allocations): ?>
                <div class="table-wrap">
                    <table class="dash-table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">Room</th>
                                <th scope="col">Hostel</th>
                                <th scope="col">Room Type</th>
                                <th scope="col">Allocated</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocations as $allocation): ?>
                                <tr>
                                    <td><strong><?= e($allocation['room_number']) ?></strong></td>
                                    <td><?= e($allocation['hostel_name']) ?></td>
                                    <td><?= e($allocation['room_type']) ?></td>
                                    <td><?= date('M j, Y', strtotime($allocation['allocated_at'])) ?></td>
                                    <td>
                                        <span class="status-badge status-badge--active">Active</span>
                                    </td>
                                    <td>
                                        <form method="post" action="actions.php" onsubmit="return confirm('Are you sure you want to move out? This will end your current room allocation.');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="moveout_request">
                                            <input type="hidden" name="allocation_id" value="<?= (int)$allocation['id'] ?>">
                                            <button type="submit" class="btn btn-outline btn-sm">Move Out</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h3 class="empty-state-title">No active bookings</h3>
                    <p class="empty-state-desc">You don't have any room allocations at the moment.</p>
                    <a href="<?= projectUrl('student/rooms.php') ?>" class="btn btn-primary mt-auto">Browse Available Rooms</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Pending Applications -->
        <?php if ($pendingApplications): ?>
            <section class="visual-card" style="margin-top: 24px;" aria-labelledby="pending-apps-heading">
                <h2 id="pending-apps-heading" class="visual-card-title">Pending Applications</h2>
                <div class="table-wrap">
                    <table class="dash-table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">Hostel Preference</th>
                                <th scope="col">Room Type</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted</th>
                                <th scope="col">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingApplications as $app): ?>
                                <tr>
                                    <td><?= e($app['hostel_preference']) ?></td>
                                    <td><?= e($app['room_type']) ?></td>
                                    <td>
                                        <span class="status-badge status-badge--pending"><?= e(ucfirst($app['application_status'])) ?></span>
                                    </td>
                                    <td><?= date('M j, Y H:i', strtotime($app['created_at'])) ?></td>
                                    <td><?= e($app['notes'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <!-- Allocation History -->
        <?php if ($history): ?>
            <section class="visual-card" style="margin-top: 24px;" aria-labelledby="history-heading">
                <h2 id="history-heading" class="visual-card-title">Allocation History</h2>
                <div class="table-wrap">
                    <table class="dash-table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">Room</th>
                                <th scope="col">Hostel</th>
                                <th scope="col">Room Type</th>
                                <th scope="col">Allocated</th>
                                <th scope="col">Ended</th>
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