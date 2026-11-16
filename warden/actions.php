<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/maintenance.php';
requireRole(['warden']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request token.');
}
$conn = db();
$action = (string)($_POST['action'] ?? '');
$returnPage = $action === 'allocate' ? 'residents.php' : 'maintenance.php';
$wardenId = (int)currentUser()['id'];
$assignedHostelIds = [];
$assignmentStmt = $conn->prepare('SELECT hostel_id FROM warden_assignments WHERE warden_id = ? AND active = 1');
$assignmentStmt->bind_param('i', $wardenId);
$assignmentStmt->execute();
$assignmentResult = $assignmentStmt->get_result();
while ($row = $assignmentResult->fetch_assoc()) {
    $assignedHostelIds[] = (int)$row['hostel_id'];
}
$assignmentStmt->close();
if (!$conn->begin_transaction()) {
    header('Location: ' . $returnPage . '?error=' . rawurlencode('Unable to begin the requested update.'));
    exit;
}
try {
    if ($action === 'maintenance') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        $response = trim((string)($_POST['response'] ?? ''));
        if ($requestId < 1 || !in_array($status, ['pending', 'in_progress', 'resolved', 'rejected'], true)
            || strlen($response) > 5000) {
            throw new RuntimeException('Invalid maintenance status.');
        }
        $stmt = $conn->prepare('SELECT user_id, reference_no, hostel_id, status FROM maintenance_requests WHERE id = ? FOR UPDATE');
        if (!$stmt) {
            throw new RuntimeException('Unable to prepare maintenance update.');
        }
        $stmt->bind_param('i', $requestId);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to read the maintenance request.');
        }
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$request) {
            throw new RuntimeException('Maintenance request not found.');
        }
        if (empty($assignedHostelIds)) {
            throw new RuntimeException('This warden has no active hostel assignments.');
        }
        if (!in_array((int)$request['hostel_id'], $assignedHostelIds, true)) {
            throw new RuntimeException('You are not assigned to this hostel.');
        }
        $stmt = $conn->prepare("UPDATE maintenance_requests
            SET status = ?, latest_response = IF(? = '', latest_response, ?)
            WHERE id = ?");
        if (!$stmt) {
            throw new RuntimeException('Unable to prepare maintenance update.');
        }
        $stmt->bind_param('sssi', $status, $response, $response, $requestId);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to save maintenance changes.');
        }
        $stmt->close();
        if ($request['status'] !== $status) {
            recordMaintenanceHistory($conn, $requestId, $wardenId, 'status_changed', $request['status'], $status);
        }
        if ($response !== '') {
            recordMaintenanceHistory($conn, $requestId, $wardenId, 'response_added', $request['status'], $status, $response);
        }
        if ($request['status'] !== $status || $response !== '') {
            createUserNotification(
                $conn,
                (int)$request['user_id'],
                'Maintenance request updated',
                'Request ' . $request['reference_no'] . ' is now ' . str_replace('_', ' ', $status) . '.'
            );
        }
    } elseif ($action === 'allocate') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $stmt = $conn->prepare("SELECT r.id, r.capacity, r.occupied, r.status, r.hostel_id FROM rooms r WHERE r.id = ? FOR UPDATE");
        $stmt->bind_param('i', $roomId);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$room || $room['status'] === 'maintenance' || (int)$room['occupied'] >= (int)$room['capacity']) {
            throw new RuntimeException('Room is unavailable or full.');
        }
        if (empty($assignedHostelIds)) {
            throw new RuntimeException('This warden has no active hostel assignments.');
        }
        if (!in_array((int)$room['hostel_id'], $assignedHostelIds, true)) {
            throw new RuntimeException('This room belongs to a hostel outside your assignment.');
        }
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' AND status = 'active' AND approval_status = 'approved'");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new RuntimeException('Invalid student account.');
        }
        $stmt->close();
        $stmt = $conn->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND status = 'active' FOR UPDATE");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new RuntimeException('Student already has an active allocation.');
        }
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO room_allocations (room_id, student_id, allocated_by) VALUES (?, ?, ?)");
        $stmt->bind_param('iii', $roomId, $studentId, $wardenId);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE rooms SET occupied = occupied + 1, status = IF(occupied + 1 >= capacity, 'occupied', 'available') WHERE id = ?");
        $stmt->bind_param('i', $roomId);
        $stmt->execute();
        $stmt->close();
        $title = 'Room allocation confirmed';
        $message = 'You have been allocated room ' . $roomId . '.';
        $notice = $conn->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notice->bind_param('iss', $studentId, $title, $message);
        $notice->execute();
        $notice->close();
    } else {
        throw new RuntimeException('Unknown operation.');
    }
    if (!$conn->commit()) {
        throw new RuntimeException('Unable to complete the requested update.');
    }
    header('Location: ' . $returnPage . '?success=1');
} catch (Throwable $e) {
    $conn->rollback();
    header('Location: ' . $returnPage . '?error=' . rawurlencode($e->getMessage()));
}
exit();
