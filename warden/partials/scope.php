<?php
$wardenId = (int)currentUser()['id'];
$assignedHostelIds = [];
$assignmentStmt = db()->prepare('SELECT hostel_id FROM warden_assignments WHERE warden_id = ? AND active = 1');
$assignmentStmt->bind_param('i', $wardenId);
$assignmentStmt->execute();
$assignmentResult = $assignmentStmt->get_result();
while ($row = $assignmentResult->fetch_assoc()) {
    $assignedHostelIds[] = (int)$row['hostel_id'];
}
$assignmentStmt->close();

$hostelClause = $assignedHostelIds ? ' WHERE r.hostel_id IN (' . implode(',', $assignedHostelIds) . ')' : ' WHERE 1 = 0';
$maintenanceHostelColumn = db()->query("SHOW COLUMNS FROM maintenance_requests LIKE 'hostel_id'");
if (!$maintenanceHostelColumn) {
    throw new RuntimeException('Unable to inspect maintenance request schema: ' . db()->error);
}
$maintenanceClause = ' WHERE 1 = 0';
if ($assignedHostelIds) {
    $hostelIds = implode(',', $assignedHostelIds);
    $maintenanceClause = $maintenanceHostelColumn->num_rows > 0
        ? ' WHERE m.hostel_id IN (' . $hostelIds . ')'
        : " WHERE EXISTS (SELECT 1 FROM room_allocations ra JOIN rooms r ON r.id = ra.room_id
            WHERE ra.student_id = m.user_id AND ra.status = 'active' AND r.hostel_id IN (" . $hostelIds . '))';
}
$studentClause = $assignedHostelIds ? ' AND r.hostel_id IN (' . implode(',', $assignedHostelIds) . ')' : ' AND 1 = 0';
