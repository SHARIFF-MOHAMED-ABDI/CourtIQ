<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Warden Operations Dashboard
 * Corresponds to Specification Figure 21 & 22 (Warden Dashboard Wireframe)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Enforce strict role-based access control
requireRole(['warden']);

$user = currentUser();
$userId = (int)($user['id'] ?? 0);
$pageTitle = 'Warden Portal | CourtIQ';
$studentSearch = trim((string)($_GET['student_q'] ?? ''));

$assignedHostelIds = [];
$assignmentStmt = db()->prepare('SELECT hostel_id FROM warden_assignments WHERE warden_id = ? AND active = 1');
$assignmentStmt->bind_param('i', $userId);
$assignmentStmt->execute();
$assignmentResult = $assignmentStmt->get_result();
while ($row = $assignmentResult->fetch_assoc()) {
    $assignedHostelIds[] = (int)$row['hostel_id'];
}
$assignmentStmt->close();

$hostelClause = $assignedHostelIds ? ' WHERE r.hostel_id IN (' . implode(',', $assignedHostelIds) . ')' : ' WHERE 1 = 0';
$maintenanceClause = $assignedHostelIds ? ' WHERE m.hostel_id IN (' . implode(',', $assignedHostelIds) . ')' : ' WHERE 1 = 0';
$studentClause = $assignedHostelIds ? ' AND r.hostel_id IN (' . implode(',', $assignedHostelIds) . ')' : ' AND 1 = 0';

// 1. Fetch Room Occupancy Metrics from Database
$roomStatsQuery = db()->query('SELECT 
    COUNT(*) AS total_rooms,
    SUM(capacity) AS total_capacity,
    SUM(occupied) AS total_occupied,
    SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) AS available_rooms,
    SUM(CASE WHEN status = "maintenance" THEN 1 ELSE 0 END) AS maintenance_rooms
FROM rooms' . str_replace('r.', '', $hostelClause));
$roomStats = $roomStatsQuery ? $roomStatsQuery->fetch_assoc() : null;

$totalCap = (int)($roomStats['total_capacity'] ?? 0);
$totalOcc = (int)($roomStats['total_occupied'] ?? 0);
$occupancyPct = $totalCap > 0 ? round(($totalOcc / $totalCap) * 100) : 0;
$availRooms = (int)($roomStats['available_rooms'] ?? 0);

// 2. Fetch Hostel Rooms List
$roomsQuery = db()->query('SELECT r.id, r.room_number, r.room_type, r.capacity, r.occupied, r.status, h.name AS hostel_name 
    FROM rooms r 
    JOIN hostel_profiles h ON r.hostel_id = h.id ' . $hostelClause . ' 
    ORDER BY r.room_number ASC LIMIT 10');
$roomsList = $roomsQuery ? $roomsQuery->fetch_all(MYSQLI_ASSOC) : [];

// 3. Fetch Pending Maintenance Requests
$maintQuery = db()->query('SELECT m.id, m.title, m.priority, m.status, m.created_at, u.username AS reported_by 
    FROM maintenance_requests m 
    JOIN users u ON m.user_id = u.id ' . $maintenanceClause . ' 
    ORDER BY m.id DESC LIMIT 5');
$maintList = $maintQuery ? $maintQuery->fetch_all(MYSQLI_ASSOC) : [];
$studentStmt = db()->prepare("SELECT u.id, u.username FROM users u JOIN room_allocations ra ON ra.student_id = u.id AND ra.status = 'active' JOIN rooms r ON r.id = ra.room_id WHERE u.role = 'student' AND u.status = 'active' AND (u.username LIKE CONCAT('%', ?, '%') OR u.email LIKE CONCAT('%', ?, '%')) " . $studentClause . " GROUP BY u.id, u.username ORDER BY u.username");
$studentStmt->bind_param('ss', $studentSearch, $studentSearch); $studentStmt->execute();
$studentsQuery = $studentStmt->get_result();
$studentsList = $studentsQuery ? $studentsQuery->fetch_all(MYSQLI_ASSOC) : [];
$availableRoomsQuery = db()->query("SELECT r.id, r.room_number, r.room_type, r.capacity, r.occupied FROM rooms r WHERE r.status = 'available' AND r.occupied < r.capacity " . $studentClause . " ORDER BY r.room_number");
$availableRooms = $availableRoomsQuery ? $availableRoomsQuery->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout">
    <header class="dashboard-nav-bar">
        <div class="container dashboard-nav-inner">
            <a href="<?= projectUrl('index.php') ?>" class="brand" aria-label="CourtIQ Home">
                <span class="brand-icon-box">
                    <svg class="brand-svg" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="32" height="32" rx="8" fill="#102A43"/>
                        <path d="M16 6L7 13V25C7 25.5523 7.44772 26 8 26H13V19H19V26H24C24.5523 26 25 25.5523 25 25V13L16 6Z" fill="#2563EB"/>
                        <path d="M16 11L11 15V23H13V18C13 17.4477 13.4477 17 14 17H18C18.5523 17 19 17.4477 19 18V23H21V15L16 11Z" fill="#0D9488"/>
                    </svg>
                </span>
                <span class="brand-text">Court<span>IQ</span></span>
            </a>

            <div class="user-badge-wrap">
                <div class="user-profile-pill">
                    <span class="role-tag-badge badge-warden">Warden</span>
                    <span><?= e($user['username']) ?></span>
                </div>
                <a href="<?= projectUrl('logout.php') ?>" class="btn btn-secondary btn-sm" title="End authenticated session">
                    <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <main class="dashboard-content-main container">
        <div class="dashboard-banner">
            <div>
                <span class="dash-welcome-eyebrow">Residential Operations Oversight</span>
                <h1 class="dash-welcome-title">Hostel Warden Dashboard</h1>
                <p class="dash-welcome-sub">
                    Monitor residential occupancy, supervise room availability, inspect student assignments, and coordinate maintenance repairs.
                </p>
            </div>
            <div>
                <span class="live-status-pill">
                    <span class="pulse-dot"></span>
                    Operational &bull; Shift Active
                </span>
            </div>
        </div>

        <div class="dashboard-cards-grid">
            <div class="dash-metric-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Occupancy Rate</span>
                    <span class="demo-tag">LIVE DB</span>
                </div>
                <div class="dash-card-val"><?= $occupancyPct ?>%</div>
                <p class="dash-card-desc"><?= $totalOcc ?> occupied of <?= $totalCap ?> bed capacity</p>
            </div>

            <div class="dash-metric-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Rooms Available</span>
                    <span class="vcard-status">Ready</span>
                </div>
                <div class="dash-card-val"><?= $availRooms ?> Rooms</div>
                <p class="dash-card-desc">Immediate capacity ready for allocation</p>
            </div>

            <div class="dash-metric-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Maintenance Queue</span>
                    <span class="counter-badge"><?= count($maintList) ?> Open</span>
                </div>
                <div class="dash-card-val"><?= count($maintList) ?> Issues</div>
                <p class="dash-card-desc">Facility tickets awaiting resolution</p>
            </div>
        </div>

        <?php if (!empty($_GET['success'])): ?><p class="vcard-status">Operation completed.</p><?php endif; ?>
        <?php if (!empty($_GET['error'])): ?><p class="counter-badge"><?= e($_GET['error']) ?></p><?php endif; ?>
        <div class="dashboard-visual-grid">
            <section class="visual-card">
                <h2>Allocate a room</h2>
                <?php if (empty($assignedHostelIds)): ?>
                    <p class="counter-badge">No hostel is assigned to this warden. Please contact the administrator to configure access.</p>
                <?php else: ?>
                    <form method="get" class="admin-form"><input name="student_q" value="<?= e($studentSearch) ?>" placeholder="Search students"><button class="btn btn-secondary btn-sm">Filter</button></form>
                    <form method="post" action="actions.php">
                        <?= csrfField() ?><input type="hidden" name="action" value="allocate">
                        <p><label>Student<br><select name="student_id" required><?php foreach ($studentsList as $student): ?><option value="<?= (int)$student['id'] ?>"><?= e($student['username']) ?></option><?php endforeach; ?></select></label></p>
                        <p><label>Available room<br><select name="room_id" required><?php foreach ($availableRooms as $room): ?><option value="<?= (int)$room['id'] ?>"><?= e($room['room_number']) ?> (<?= (int)$room['occupied'] ?>/<?= (int)$room['capacity'] ?>)</option><?php endforeach; ?></select></label></p>
                        <button class="btn btn-primary btn-sm" type="submit">Allocate room</button>
                    </form>
                <?php endif; ?>
            </section>
        </div>

        <div class="dashboard-visual-grid">
            <section class="visual-card" aria-labelledby="occupancy-chart-title">
                <h2 id="occupancy-chart-title">Live occupancy overview</h2>
                <div class="donut-chart" style="--chart-value: <?= $occupancyPct ?>%;">
                    <span class="donut-value"><?= $occupancyPct ?>%<small> occupied</small></span>
                </div>
                <div class="chart-legend">
                    <span><i class="legend-dot"></i><?= $totalOcc ?> occupied</span>
                    <span><i class="legend-dot muted"></i><?= max(0, $totalCap - $totalOcc) ?> available</span>
                </div>
            </section>
            <section class="visual-card" aria-labelledby="warden-activity-title">
                <h2 id="warden-activity-title">Operations snapshot</h2>
                <div class="activity-list">
                    <div class="activity-item"><div><strong>Rooms ready for allocation</strong><span>Current inventory</span></div><strong><?= $availRooms ?></strong></div>
                    <div class="activity-item"><div><strong>Maintenance queue</strong><span>Tickets requiring attention</span></div><strong><?= count($maintList) ?></strong></div>
                    <div class="activity-item"><div><strong>Monthly review</strong><span>Shift activity period</span></div><strong><?= date('M Y') ?></strong></div>
                </div>
            </section>
        </div>

        <!-- Room Inventory Overview -->
        <div class="dashboard-panel-full">
            <div class="dash-panel-heading">
                <span>Hostel Room Inventory & Occupancy</span>
                <span class="demo-tag">Active Quads</span>
            </div>

            <?php if (!empty($roomsList)): ?>
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Room #</th>
                            <th>Hostel</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Occupied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roomsList as $r): ?>
                            <tr>
                                <td><strong><?= e($r['room_number']) ?></strong></td>
                                <td><?= e($r['hostel_name']) ?></td>
                                <td><?= e($r['room_type']) ?></td>
                                <td><?= e($r['capacity']) ?> bed(s)</td>
                                <td><?= e($r['occupied']) ?> resident(s)</td>
                                <td>
                                    <?php if ($r['status'] === 'available'): ?>
                                        <span class="vcard-status">Available</span>
                                    <?php elseif ($r['status'] === 'occupied'): ?>
                                        <span class="demo-tag">Occupied</span>
                                    <?php else: ?>
                                        <span class="counter-badge">Maintenance</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="dash-card-desc">No room data recorded in the database.</p>
            <?php endif; ?>
        </div>

        <!-- Maintenance Follow-up -->
        <div class="dashboard-panel-full">
            <div class="dash-panel-heading">
                <span>Maintenance Follow-Up Queue</span>
                <span class="demo-tag">Operations Action</span>
            </div>

            <?php if (!empty($maintList)): ?>
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Reported By</th>
                            <th>Issue Description</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintList as $m): ?>
                            <tr>
                                <td><strong>#<?= e($m['id']) ?></strong></td>
                                <td><?= e($m['reported_by']) ?></td>
                                <td><?= e($m['title']) ?></td>
                                <td><span class="demo-tag"><?= strtoupper(e($m['priority'])) ?></span></td>
                                <td><form method="post" action="actions.php">
                                    <?= csrfField() ?><input type="hidden" name="action" value="maintenance"><input type="hidden" name="request_id" value="<?= (int)$m['id'] ?>">
                                    <select name="status"><option value="pending" <?= $m['status'] === 'pending' ? 'selected' : '' ?>>Pending</option><option value="in_progress" <?= $m['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option><option value="resolved" <?= $m['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option></select>
                                    <button class="btn btn-primary btn-sm" type="submit">Update</button>
                                </form></td>
                                <td><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="dash-card-desc">No pending maintenance tickets.</p>
            <?php endif; ?>
        </div>

        <div class="contact-panel-card" style="text-align: left;">
            <h3 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 6px;">
                Warden Navigation Wireframe Reference (Figure 21 & 22)
            </h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                As specified in the CourtIQ architecture, the Warden interface concentrates on day-to-day hostel facility operations: monitoring room availability, reviewing student residence arrangements, following up on repair requests, and updating operational records where authorized.
            </p>
        </div>
    </main>
</body>
</html>
