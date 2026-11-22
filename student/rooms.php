<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

$userId = (int)currentUser()['id'];
$conn = db();

// Check if student can book
$allocationStmt = $conn->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND status = 'active' LIMIT 1");
$allocationStmt->bind_param('i', $userId);
$allocationStmt->execute();
$hasActiveAllocation = $allocationStmt->get_result()->fetch_assoc() !== null;
$allocationStmt->close();

$pendingStmt = $conn->prepare("SELECT id FROM accommodation_applications WHERE user_id = ? AND application_status IN ('submitted', 'pending') LIMIT 1");
$pendingStmt->bind_param('i', $userId);
$pendingStmt->execute();
$hasPendingApplication = $pendingStmt->get_result()->fetch_assoc() !== null;
$pendingStmt->close();

$canBook = !$hasActiveAllocation && !$hasPendingApplication;

// Get selected hostel from query string
$selectedHostelId = isset($_GET['hostel_id']) ? (int)$_GET['hostel_id'] : 0;

// Get all hostels with vacant room counts
$hostelsStmt = $conn->prepare("SELECT h.id, h.name, h.location, h.capacity,
        COUNT(r.id) AS total_rooms,
        SUM(CASE WHEN r.status = 'available' AND r.occupied < r.capacity THEN 1 ELSE 0 END) AS vacant_rooms,
        SUM(CASE WHEN r.status = 'available' THEN GREATEST(r.capacity - r.occupied, 0) ELSE 0 END) AS vacant_beds
    FROM hostel_profiles h
    LEFT JOIN rooms r ON r.hostel_id = h.id
    GROUP BY h.id, h.name, h.location, h.capacity
    ORDER BY h.name");
$hostelsStmt->execute();
$hostels = $hostelsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$hostelsStmt->close();

// If hostel selected, get room types with availability
$roomTypes = [];
$rooms = [];
if ($selectedHostelId > 0) {
    // Get room types grouped by type with availability
    $typesStmt = $conn->prepare("SELECT r.room_type,
            COUNT(r.id) AS total_rooms,
            SUM(CASE WHEN r.status = 'available' AND r.occupied < r.capacity THEN 1 ELSE 0 END) AS vacant_rooms,
            SUM(CASE WHEN r.status = 'available' THEN GREATEST(r.capacity - r.occupied, 0) ELSE 0 END) AS vacant_beds,
            MIN(r.capacity) AS min_capacity,
            MAX(r.capacity) AS max_capacity
        FROM rooms r
        WHERE r.hostel_id = ? AND r.status <> 'maintenance'
        GROUP BY r.room_type
        ORDER BY r.room_type");
    $typesStmt->bind_param('i', $selectedHostelId);
    $typesStmt->execute();
    $roomTypes = $typesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $typesStmt->close();

    // Get individual rooms for the selected hostel
    $roomsStmt = $conn->prepare("SELECT r.id, r.room_number, r.room_type, r.capacity, r.occupied, r.status
        FROM rooms r
        WHERE r.hostel_id = ? AND r.status <> 'maintenance'
        ORDER BY r.room_type, r.room_number");
    $roomsStmt->bind_param('i', $selectedHostelId);
    $roomsStmt->execute();
    $rooms = $roomsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $roomsStmt->close();
}

$selectedHostel = null;
foreach ($hostels as $h) {
    if ((int)$h['id'] === $selectedHostelId) {
        $selectedHostel = $h;
        break;
    }
}
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
                <p class="dashboard-header-subtitle">Select a hostel, then choose a room type to view available rooms.</p>
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

        <!-- Step 1: Hostel Selection -->
        <section class="visual-card" aria-labelledby="hostel-selection-heading">
            <h2 id="hostel-selection-heading" class="visual-card-title">Step 1: Choose a Hostel</h2>
            <p class="visual-card-subtitle">Select a hostel to view available room types and vacancies.</p>
            
            <?php if ($hostels): ?>
                <div class="rooms-grid" role="list" aria-label="Available hostels">
                    <?php foreach ($hostels as $hostel): 
                        $vacantRooms = (int)($hostel['vacant_rooms'] ?? 0);
                        $vacantBeds = (int)($hostel['vacant_beds'] ?? 0);
                        $isSelected = $hostel['id'] === $selectedHostelId;
                        $hasVacancy = $vacantBeds > 0;
                        ?>
                        <article class="room-card <?= $isSelected ? 'is-selected' : '' ?>" role="listitem" style="<?= $isSelected ? 'border-color: var(--color-secondary); box-shadow: 0 0 0 2px var(--color-secondary);' : '' ?>">
                            <header class="room-card-header" style="cursor: pointer;">
                                <span class="room-card-block"><?= e($hostel['name']) ?></span>
                                <h2 class="room-card-number"><?= e($hostel['location'] ?: 'Location not specified') ?></h2>
                                <span class="room-card-beds-badge" style="background: <?= $hasVacancy ? 'rgba(22, 163, 74, 0.2)' : 'rgba(220, 38, 38, 0.2)' ?>; color: <?= $hasVacancy ? '#16A34A' : '#DC2626' ?>; border-color: <?= $hasVacancy ? '#86EFAC' : '#FCA5A5' ?>">
                                    <?= $vacantBeds ?> bed<?= $vacantBeds !== 1 ? 's' : '' ?> free
                                </span>
                            </header>
                            <div class="room-card-body">
                                <div class="room-card-info">
                                    <div class="room-card-info-item">
                                        <span class="room-card-info-label">Total Rooms</span>
                                        <span class="room-card-info-value"><?= (int)$hostel['total_rooms'] ?></span>
                                    </div>
                                    <div class="room-card-info-item">
                                        <span class="room-card-info-label">Vacant Rooms</span>
                                        <span class="room-card-info-value"><?= $vacantRooms ?></span>
                                    </div>
                                    <div class="room-card-info-item">
                                        <span class="room-card-info-label">Capacity</span>
                                        <span class="room-card-info-value"><?= (int)$hostel['capacity'] ?> students</span>
                                    </div>
                                    <div class="room-card-info-item">
                                        <span class="room-card-info-label">Status</span>
                                        <span class="room-card-status <?= $hasVacancy ? 'room-card-status--available' : 'room-card-status--unavailable' ?>">
                                            <?= $hasVacancy ? 'Has Vacancies' : 'Fully Occupied' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="room-card-footer">
                                    <a href="<?= projectUrl('student/rooms.php?hostel_id=' . (int)$hostel['id']) ?>" 
                                       class="btn <?= $isSelected ? 'btn-primary' : 'btn-outline' ?> btn-block room-card-btn" 
                                       <?= !$hasVacancy || !$canBook ? 'disabled' : '' ?>
                                       style="<?= $isSelected ? 'background: var(--color-secondary); color: white;' : '' ?>">
                                        <?= $isSelected ? 'View Room Types →' : 'Select Hostel' ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <h3 class="empty-state-title">No hostels configured</h3>
                    <p class="empty-state-desc">There are currently no hostels in the system.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Step 2: Room Types (shown when hostel selected) -->
        <?php if ($selectedHostel): ?>
            <section class="visual-card" style="margin-top: 24px;" aria-labelledby="room-types-heading">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h2 id="room-types-heading" class="visual-card-title" style="margin: 0;">Step 2: Room Types in <?= e($selectedHostel['name']) ?></h2>
                        <p class="visual-card-subtitle" style="margin: 4px 0 0;">Choose a room type to see individual rooms.</p>
                    </div>
                    <a href="<?= projectUrl('student/rooms.php') ?>" class="btn btn-outline btn-sm">← Change Hostel</a>
                </div>
                
                <?php if ($roomTypes): ?>
                    <div class="rooms-grid" role="list" aria-label="Room types">
                        <?php foreach ($roomTypes as $type): 
                            $vacantRooms = (int)($type['vacant_rooms'] ?? 0);
                            $vacantBeds = (int)($type['vacant_beds'] ?? 0);
                            $minCap = (int)($type['min_capacity'] ?? 0);
                            $maxCap = (int)($type['max_capacity'] ?? 0);
                            $hasVacancy = $vacantBeds > 0;
                            $capLabel = $minCap === $maxCap ? "$minCap-bed" : "$minCap-$maxCap beds";
                            ?>
                            <article class="room-card" role="listitem">
                                <header class="room-card-header">
                                    <span class="room-card-block"><?= e($type['room_type']) ?></span>
                                    <h2 class="room-card-number"><?= e($capLabel) ?></h2>
                                    <span class="room-card-beds-badge" style="background: <?= $hasVacancy ? 'rgba(22, 163, 74, 0.2)' : 'rgba(220, 38, 38, 0.2)' ?>; color: <?= $hasVacancy ? '#16A34A' : '#DC2626' ?>; border-color: <?= $hasVacancy ? '#86EFAC' : '#FCA5A5' ?>">
                                        <?= $vacantBeds ?> bed<?= $vacantBeds !== 1 ? 's' : '' ?> free
                                    </span>
                                </header>
                                <div class="room-card-body">
                                    <div class="room-card-info">
                                        <div class="room-card-info-item">
                                            <span class="room-card-info-label">Total Rooms</span>
                                            <span class="room-card-info-value"><?= (int)$type['total_rooms'] ?></span>
                                        </div>
                                        <div class="room-card-info-item">
                                            <span class="room-card-info-label">Available Rooms</span>
                                            <span class="room-card-info-value"><?= $vacantRooms ?></span>
                                        </div>
                                        <div class="room-card-info-item">
                                            <span class="room-card-info-label">Capacity per Room</span>
                                            <span class="room-card-info-value"><?= e($capLabel) ?></span>
                                        </div>
                                        <div class="room-card-info-item">
                                            <span class="room-card-info-label">Status</span>
                                            <span class="room-card-status <?= $hasVacancy ? 'room-card-status--available' : 'room-card-status--unavailable' ?>">
                                                <?= $hasVacancy ? 'Has Vacancies' : 'Fully Occupied' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="room-card-footer">
                                        <a href="<?= projectUrl('student/rooms.php?hostel_id=' . $selectedHostelId . '&room_type=' . urlencode($type['room_type'])) ?>" 
                                           class="btn btn-primary btn-block room-card-btn" 
                                           <?= !$hasVacancy || !$canBook ? 'disabled' : '' ?>>
                                            View Rooms →
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <h3 class="empty-state-title">No rooms in this hostel</h3>
                        <p class="empty-state-desc">This hostel doesn't have any configured rooms yet.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Step 3: Individual Rooms (shown when room type selected) -->
        <?php if ($selectedHostel && isset($_GET['room_type'])): 
            $selectedRoomType = $_GET['room_type'];
            $filteredRooms = array_filter($rooms, fn($r) => $r['room_type'] === $selectedRoomType);
            ?>
            <section class="visual-card" style="margin-top: 24px;" aria-labelledby="individual-rooms-heading">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h2 id="individual-rooms-heading" class="visual-card-title" style="margin: 0;">Step 3: Available <?= e($selectedRoomType) ?> Rooms</h2>
                        <p class="visual-card-subtitle" style="margin: 4px 0 0;">Select a room to request booking.</p>
                    </div>
                    <a href="<?= projectUrl('student/rooms.php?hostel_id=' . $selectedHostelId) ?>" class="btn btn-outline btn-sm">← Change Room Type</a>
                </div>
                
                <?php if ($filteredRooms): ?>
                    <div class="rooms-grid" role="list" aria-label="Available rooms">
                        <?php foreach ($filteredRooms as $room): 
                            $availableBeds = max(0, (int)$room['capacity'] - (int)$room['occupied']);
                            $isAvailable = $availableBeds > 0 && $room['status'] === 'available';
                            ?>
                            <article class="room-card" role="listitem">
                                <header class="room-card-header">
                                    <span class="room-card-block"><?= e($selectedHostel['name']) ?></span>
                                    <h2 class="room-card-number">Room <?= e($room['room_number']) ?></h2>
                                    <span class="room-card-beds-badge" style="background: <?= $isAvailable ? 'rgba(22, 163, 74, 0.2)' : 'rgba(220, 38, 38, 0.2)' ?>; color: <?= $isAvailable ? '#16A34A' : '#DC2626' ?>; border-color: <?= $isAvailable ? '#86EFAC' : '#FCA5A5' ?>">
                                        <?= $availableBeds ?> bed<?= $availableBeds !== 1 ? 's' : '' ?> free
                                    </span>
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
                                                    aria-label="<?= $isAvailable ? 'Request booking for Room ' . e($room['room_number']) : 'Not available' ?>">
                                                <?= $isAvailable ? 'Request Booking' : ($canBook ? 'Fully Occupied' : 'Cannot Book') ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <h3 class="empty-state-title">No rooms of this type</h3>
                        <p class="empty-state-desc">There are no <?= e($selectedRoomType) ?> rooms in this hostel.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>