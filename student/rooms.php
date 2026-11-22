<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

$userId = (int)currentUser()['id'];
$conn = db();

// Get available rooms with occupancy info
$stmt = $conn->prepare("SELECT r.id, r.hostel_id, r.room_number, r.room_type, r.capacity, r.occupied, r.status,
        h.name AS hostel_name
    FROM rooms r
    JOIN hostel_profiles h ON h.id = r.hostel_id
    WHERE r.status <> 'maintenance'
    ORDER BY h.name, r.room_number");
$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if student has active allocation (can't book another)
$allocationStmt = $conn->prepare("SELECT a.id FROM room_allocations a WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
$allocationStmt->bind_param('i', $userId);
$allocationStmt->execute();
$hasActiveAllocation = $allocationStmt->get_result()->fetch_assoc() !== null;
$allocationStmt->close();

// Check for pending applications
$pendingStmt = $conn->prepare("SELECT id FROM accommodation_applications WHERE user_id = ? AND application_status IN ('submitted', 'pending') LIMIT 1");
$pendingStmt->bind_param('i', $userId);
$pendingStmt->execute();
$hasPendingApplication = $pendingStmt->get_result()->fetch_assoc() !== null;
$pendingStmt->close();

$canBook = !$hasActiveAllocation && !$hasPendingApplication;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse Rooms | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout has-sidebar">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">ACCOMMODATION</span>
                <h1 class="dashboard-header-title">Available Rooms</h1>
                <p class="dashboard-header-subtitle">Real-time availability across all hostel blocks.</p>
            </div>
            <?php if (!$canBook): ?>
                <div class="alert alert--info" style="max-width: 380px;">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span><?= $hasActiveAllocation ? 'You already have an active room allocation.' : 'You have a pending accommodation application.' ?></span>
                </div>
            <?php endif; ?>
        </header>

        <!-- Rooms Grid -->
        <?php if ($rooms): ?>
            <div class="rooms-grid" role="list" aria-label="Available rooms">
                <?php foreach ($rooms as $room): 
                    $availableBeds = max(0, (int)$room['capacity'] - (int)$room['occupied']);
                    $isAvailable = $availableBeds > 0 && $room['status'] === 'available';
                    $blockName = $room['hostel_name'];
                    $roomNumber = $room['room_number'];
                    ?>
                    <article class="room-card" role="listitem">
                        <header class="room-card-header">
                            <span class="room-card-block"><?= e($blockName) ?></span>
                            <h2 class="room-card-number"><?= e($roomNumber) ?></h2>
                            <span class="room-card-beds-badge"><?= $availableBeds ?> bed<?= $availableBeds !== 1 ? 's' : '' ?> free</span>
                        </header>
                        <div class="room-card-body">
                            <div class="room-card-info">
                                <div class="room-card-info-item">
                                    <span class="room-card-info-label">Capacity</span>
                                    <span class="room-card-info-value"><?= (int)$room['capacity'] ?> students</span>
                                </div>
                                <div class="room-card-info-item">
                                    <span class="room-card-info-label">Occupied</span>
                                    <span class="room-card-info-value"><?= (int)$room['occupied'] ?> / <?= (int)$room['capacity'] ?></span>
                                </div>
                                <div class="room-card-info-item">
                                    <span class="room-card-info-label">Room Type</span>
                                    <span class="room-card-info-value"><?= e($room['room_type']) ?></span>
                                </div>
                                <div class="room-card-info-item">
                                    <span class="room-card-info-label">Status</span>
                                    <span class="room-card-status <?= $isAvailable ? 'room-card-status--available' : ($room['status'] === 'maintenance' ? 'room-card-status--maintenance' : 'room-card-status--unavailable') ?>">
                                        <?= e(ucfirst($room['status'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="room-card-footer">
                                <form method="post" action="actions.php" class="w-full">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="booking_request">
                                    <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                                    <button type="submit" class="btn btn-primary btn-block room-card-btn" 
                                            <?= !$canBook || !$isAvailable ? 'disabled' : '' ?>
                                            aria-label="<?= $isAvailable ? 'Request booking for ' . e($blockName) . ' ' . e($roomNumber) : 'Not available' ?>">
                                        <?= $isAvailable ? 'Request Booking' : ($canBook ? 'Fully Occupied' : 'Cannot Book') ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="visual-card">
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <h3 class="empty-state-title">No rooms available</h3>
                    <p class="empty-state-desc">There are currently no rooms configured in the system.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>