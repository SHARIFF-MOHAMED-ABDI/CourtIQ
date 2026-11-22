<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/maintenance.php';
requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request token.');
}

$userId = (int)currentUser()['id'];
$action = (string)($_POST['action'] ?? '');
$returnPage = match ($action) {
    'application' => 'applications.php',
    'maintenance' => 'maintenance.php',
    'booking_request' => 'rooms.php',
    'moveout_request' => 'bookings.php',
    'profile_save' => 'profile.php',
    default => 'index.php',
};
$conn = db();

if ($action === 'profile_save') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $studentNo = trim((string)($_POST['student_number'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $course = trim((string)($_POST['course'] ?? ''));
    $year = (int)($_POST['year_of_study'] ?? 0);
    $emergency = trim((string)($_POST['emergency_contact'] ?? ''));
    if ($fullName === '' || strlen($fullName) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || ($year !== 0 && ($year < 1 || $year > 20))) {
        header('Location: profile.php?error=Invalid profile details');
        exit;
    }
    if (!$conn->begin_transaction()) {
        header('Location: profile.php?error=Unable to save profile');
        exit;
    }
    try {
        $st = $conn->prepare('UPDATE users SET email = ? WHERE id = ?');
        if (!$st) {
            throw new RuntimeException('Unable to prepare profile update.');
        }
        $st->bind_param('si', $email, $userId);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to update the profile email.');
        }
        $st->close();
        $st = $conn->prepare('INSERT INTO student_profiles(user_id,full_name,student_number,phone,course,year_of_study,emergency_contact)
            VALUES(?,?,?,?,?,?,?)
            ON CONFLICT (user_id) DO UPDATE SET full_name=EXCLUDED.full_name,student_number=EXCLUDED.student_number,phone=EXCLUDED.phone,
                course=EXCLUDED.course,year_of_study=EXCLUDED.year_of_study,emergency_contact=EXCLUDED.emergency_contact');
        if (!$st) {
            throw new RuntimeException('Unable to prepare student profile update.');
        }
        $st->bind_param('issssis', $userId, $fullName, $studentNo, $phone, $course, $year, $emergency);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to update student profile.');
        }
        $st->close();
        if (!$conn->commit()) {
            throw new RuntimeException('Unable to complete profile update.');
        }
        header('Location: profile.php?success=1');
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('CourtIQ student profile update failed: ' . $e->getMessage());
        header('Location: profile.php?error=Unable to save profile');
    }
    exit;
}

if (!$conn->begin_transaction()) {
    http_response_code(503);
    exit('Unable to start the requested operation.');
}
try {
    if ($action === 'application') {
        $hostel = trim((string)($_POST['hostel_preference'] ?? ''));
        $roomType = trim((string)($_POST['room_type'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));
        if ($hostel === '' || $roomType === '') {
            throw new RuntimeException('Hostel and room type are required.');
        }
        $stmt = $conn->prepare("INSERT INTO accommodation_applications (user_id, hostel_preference, room_type, application_status, notes) VALUES (?, ?, ?, 'submitted', ?)");
        $stmt->bind_param('isss', $userId, $hostel, $roomType, $notes);
        $stmt->execute();
        $stmt->close();
        $title = 'Accommodation application received';
        $message = 'Your accommodation application has been submitted for review.';
        $notice = $conn->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notice->bind_param('iss', $userId, $title, $message);
        $notice->execute();
        $notice->close();
    } elseif ($action === 'maintenance') {
        $category = trim((string)($_POST['category'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $priority = (string)($_POST['priority'] ?? 'medium');
        $categories = ['Plumbing', 'Electrical', 'Furniture', 'Cleaning', 'Room Facilities', 'Security', 'Other'];
        if (!in_array($category, $categories, true) || $description === '' || strlen($description) > 5000
            || !in_array($priority, ['low', 'medium', 'high'], true)) {
            throw new RuntimeException('Complete the maintenance form with a valid category, description, and priority.');
        }
        $allocationStmt = $conn->prepare("SELECT r.id AS room_id, r.room_number, r.hostel_id, h.name AS hostel_name
            FROM room_allocations a
            JOIN rooms r ON r.id = a.room_id
            JOIN hostel_profiles h ON h.id = r.hostel_id
            WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
        if (!$allocationStmt) {
            throw new RuntimeException('Unable to prepare the room lookup.');
        }
        $allocationStmt->bind_param('i', $userId);
        if (!$allocationStmt->execute()) {
            throw new RuntimeException('Unable to read the allocated room.');
        }
        $allocation = $allocationStmt->get_result()->fetch_assoc();
        $allocationStmt->close();
        $hostelId = $allocation ? (int)$allocation['hostel_id'] : null;
        $roomId = $allocation ? (int)$allocation['room_id'] : null;
        $hostelName = $allocation ? (string)$allocation['hostel_name'] : null;
        $roomNumber = $allocation ? (string)$allocation['room_number'] : null;
        $referenceNo = 'CIQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(8)));
        $title = $category . ' issue';

        $stmt = $conn->prepare("INSERT INTO maintenance_requests
            (reference_no, user_id, hostel_id, room_id, hostel_name_snapshot, room_number_snapshot,
             title, category, description, priority, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        if (!$stmt) {
            throw new RuntimeException('Unable to prepare the maintenance request.');
        }
        $stmt->bind_param(
            'siiissssss',
            $referenceNo,
            $userId,
            $hostelId,
            $roomId,
            $hostelName,
            $roomNumber,
            $title,
            $category,
            $description,
            $priority
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            error_log('CourtIQ maintenance request insert failed: ' . $error);
            throw new RuntimeException('Unable to save the maintenance request.');
        }
        $requestId = (int)$stmt->insert_id;
        $stmt->close();
        if ($requestId < 1) {
            throw new RuntimeException('Unable to confirm the maintenance request.');
        }
        recordMaintenanceHistory($conn, $requestId, $userId, 'submitted', null, 'pending');
    } elseif ($action === 'booking_request') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        if ($roomId < 1) {
            throw new RuntimeException('Invalid room selection.');
        }

        // Check if student already has active allocation
        $checkStmt = $conn->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND status = 'active' LIMIT 1");
        $checkStmt->bind_param('i', $userId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->fetch_assoc()) {
            $checkStmt->close();
            throw new RuntimeException('You already have an active room allocation.');
        }
        $checkStmt->close();

        // Check for pending application
        $pendingStmt = $conn->prepare("SELECT id FROM accommodation_applications WHERE user_id = ? AND application_status IN ('submitted', 'pending') LIMIT 1");
        $pendingStmt->bind_param('i', $userId);
        $pendingStmt->execute();
        if ($pendingStmt->get_result()->fetch_assoc()) {
            $pendingStmt->close();
            throw new RuntimeException('You have a pending accommodation application.');
        }
        $pendingStmt->close();

        // Verify room exists and has availability
        $roomStmt = $conn->prepare("SELECT r.id, r.capacity, r.occupied, r.status, r.room_number, r.hostel_id, h.name AS hostel_name
            FROM rooms r JOIN hostel_profiles h ON h.id = r.hostel_id WHERE r.id = ? FOR UPDATE");
        $roomStmt->bind_param('i', $roomId);
        $roomStmt->execute();
        $room = $roomStmt->get_result()->fetch_assoc();
        $roomStmt->close();

        if (!$room) {
            throw new RuntimeException('Selected room does not exist.');
        }
        if ($room['status'] !== 'available') {
            throw new RuntimeException('This room is not available for booking.');
        }
        $availableBeds = max(0, (int)$room['capacity'] - (int)$room['occupied']);
        if ($availableBeds < 1) {
            throw new RuntimeException('This room is fully occupied.');
        }

        // Check user approval status
        $userStmt = $conn->prepare("SELECT approval_status, status FROM users WHERE id = ?");
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if (!$user || $user['approval_status'] !== 'approved' || $user['status'] !== 'active') {
            throw new RuntimeException('Your account must be approved and active to book a room.');
        }

        // Create room allocation
        $allocStmt = $conn->prepare("INSERT INTO room_allocations (room_id, student_id, allocated_by, status) VALUES (?, ?, ?, 'active')");
        $allocStmt->bind_param('iii', $roomId, $userId, $userId);
        if (!$allocStmt->execute()) {
            $allocStmt->close();
            throw new RuntimeException('Unable to create room allocation.');
        }
        $allocStmt->close();

        // Update room occupancy
        $updateRoomStmt = $conn->prepare("UPDATE rooms SET occupied = occupied + 1 WHERE id = ?");
        $updateRoomStmt->bind_param('i', $roomId);
        $updateRoomStmt->execute();
        $updateRoomStmt->close();

        // Create notification
        $title = 'Room booking confirmed';
        $message = 'Your booking for ' . $room['hostel_name'] . ' - Room ' . $room['room_number'] . ' has been confirmed.';
        $notice = $conn->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notice->bind_param('iss', $userId, $title, $message);
        $notice->execute();
        $notice->close();

    } elseif ($action === 'moveout_request') {
        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        if ($allocationId < 1) {
            throw new RuntimeException('Invalid allocation.');
        }

        // Verify allocation belongs to student and is active
        $allocStmt = $conn->prepare("SELECT a.id, a.room_id, r.room_number, h.name AS hostel_name
            FROM room_allocations a
            JOIN rooms r ON r.id = a.room_id
            JOIN hostel_profiles h ON h.id = r.hostel_id
            WHERE a.id = ? AND a.student_id = ? AND a.status = 'active' LIMIT 1");
        $allocStmt->bind_param('ii', $allocationId, $userId);
        $allocStmt->execute();
        $allocation = $allocStmt->get_result()->fetch_assoc();
        $allocStmt->close();

        if (!$allocation) {
            throw new RuntimeException('Allocation not found or already ended.');
        }

        // End the allocation
        $endStmt = $conn->prepare("UPDATE room_allocations SET status = 'ended', ended_at = NOW() WHERE id = ?");
        $endStmt->bind_param('i', $allocationId);
        if (!$endStmt->execute()) {
            $endStmt->close();
            throw new RuntimeException('Unable to end room allocation.');
        }
        $endStmt->close();

        // Update room occupancy
        $updateRoomStmt = $conn->prepare("UPDATE rooms SET occupied = GREATEST(occupied - 1, 0) WHERE id = ?");
        $updateRoomStmt->bind_param('i', $allocation['room_id']);
        $updateRoomStmt->execute();
        $updateRoomStmt->close();

        // Create notification
        $title = 'Move out completed';
        $message = 'Your allocation for ' . $allocation['hostel_name'] . ' - Room ' . $allocation['room_number'] . ' has been ended.';
        $notice = $conn->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notice->bind_param('iss', $userId, $title, $message);
        $notice->execute();
        $notice->close();

    } else {
        throw new RuntimeException('Unknown operation.');
    }

    if (!$conn->commit()) {
        throw new RuntimeException('Unable to save the request. Please try again.');
    }
    header('Location: ' . $returnPage . '?success=1');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('CourtIQ student action failed: ' . $e->getMessage());
    header('Location: ' . $returnPage . '?error=' . rawurlencode($e->getMessage()));
}
exit();