<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['warden']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request token.');
}
$conn = db();
$action = (string)($_POST['action'] ?? '');
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
$conn->begin_transaction();
try {
    if ($action === 'maintenance') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        if ($requestId < 1 || !in_array($status, ['pending', 'in_progress', 'resolved'], true)) {
            throw new RuntimeException('Invalid maintenance status.');
        }
        $stmt = $conn->prepare('SELECT hostel_id FROM maintenance_requests WHERE id = ?');
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
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
        $stmt = $conn->prepare('UPDATE maintenance_requests SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $requestId);
        $stmt->execute();
        $stmt->close();
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
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' AND status = 'active'");
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
    $conn->commit();
    header('Location: index.php?success=1');
} catch (Throwable $e) {
    $conn->rollback();
    header('Location: index.php?error=' . rawurlencode($e->getMessage()));
}
exit();
