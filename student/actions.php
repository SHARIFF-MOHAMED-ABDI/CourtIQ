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
    if ($fullName === '' || strlen($fullName) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || ($year !== 0 && ($year < 1 || $year > 20))) { header('Location: profile.php?error=Invalid profile details'); exit; }
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
