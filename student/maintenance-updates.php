<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard_charts.php';
requireRole(['student']);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
$userId = (int)currentUser()['id'];
$conn = db();
$requestStmt = $conn->prepare("SELECT id, reference_no, title, category, description, priority, status,
        hostel_name_snapshot, room_number_snapshot, latest_response, resolution_notes, created_at
    FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC");
if (!$requestStmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load maintenance requests.']);
    exit;
}
$requestStmt->bind_param('i', $userId);
if (!$requestStmt->execute()) {
    error_log('CourtIQ student maintenance refresh failed: ' . $requestStmt->error);
    $requestStmt->close();
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load maintenance requests.']);
    exit;
}
$requests = $requestStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$requestStmt->close();

$historyStmt = $conn->prepare("SELECT h.request_id, h.action, h.previous_status, h.new_status, h.response, h.created_at,
        COALESCE(sp.full_name, u.username, 'System') AS actor_name, u.role AS actor_role
    FROM maintenance_request_history h
    JOIN maintenance_requests m ON m.id = h.request_id
    LEFT JOIN users u ON u.id = h.actor_id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    WHERE m.user_id = ?
    ORDER BY h.request_id, h.created_at DESC, h.id DESC");
if (!$historyStmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load maintenance history.']);
    exit;
}
$historyStmt->bind_param('i', $userId);
if (!$historyStmt->execute()) {
    error_log('CourtIQ student maintenance history refresh failed: ' . $historyStmt->error);
    $historyStmt->close();
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load maintenance history.']);
    exit;
}
$historyRows = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$historyStmt->close();

$historyByRequest = [];
foreach ($historyRows as $entry) {
    $historyByRequest[(int)$entry['request_id']][] = $entry;
}
$counts = ['pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0];
foreach ($requests as &$request) {
    $request['id'] = (int)$request['id'];
    $request['history'] = $historyByRequest[$request['id']] ?? [];
    if (isset($counts[$request['status']])) {
        $counts[$request['status']]++;
    }
}
unset($request);

echo json_encode([
    'summary' => ['total' => count($requests)] + $counts,
    'requests' => $requests,
    'charts' => getStudentDashboardChartData($conn, $userId),
], JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
