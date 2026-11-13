<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request token.');
}

$userId = (int)currentUser()['id'];
$action = (string)($_POST['action'] ?? '');
$conn = db();
if ($action === 'profile_save') {
    $email = trim((string)($_POST['email'] ?? ''));
    $studentNo = trim((string)($_POST['student_number'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $course = trim((string)($_POST['course'] ?? ''));
    $year = (int)($_POST['year_of_study'] ?? 0);
    $emergency = trim((string)($_POST['emergency_contact'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($year !== 0 && ($year < 1 || $year > 20))) { header('Location: profile.php?error=Invalid profile details'); exit; }
    $conn->begin_transaction();
    try {
        $st=$conn->prepare('UPDATE users SET email=? WHERE id=?'); $st->bind_param('si',$email,$userId); $st->execute(); $st->close();
        $st=$conn->prepare('INSERT INTO student_profiles(user_id,student_number,phone,course,year_of_study,emergency_contact) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE student_number=VALUES(student_number),phone=VALUES(phone),course=VALUES(course),year_of_study=VALUES(year_of_study),emergency_contact=VALUES(emergency_contact)');
        $st->bind_param('isssis',$userId,$studentNo,$phone,$course,$year,$emergency); $st->execute(); $st->close(); $conn->commit(); header('Location: profile.php?success=1');
    } catch(Throwable $e) { $conn->rollback(); header('Location: profile.php?error=Unable to save profile'); } exit;
}
$conn->begin_transaction();
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
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $priority = (string)($_POST['priority'] ?? 'medium');
        if ($title === '' || $description === '' || !in_array($priority, ['low', 'medium', 'high'], true)) {
            throw new RuntimeException('Complete the maintenance form with a valid priority.');
        }
        $allocationStmt = $conn->prepare("SELECT r.hostel_id FROM room_allocations a JOIN rooms r ON r.id = a.room_id WHERE a.student_id = ? AND a.status = 'active' LIMIT 1");
        $allocationStmt->bind_param('i', $userId);
        $allocationStmt->execute();
        $allocation = $allocationStmt->get_result()->fetch_assoc();
        $allocationStmt->close();
        $hostelId = $allocation ? (int)$allocation['hostel_id'] : null;

        $stmt = $conn->prepare('INSERT INTO maintenance_requests (user_id, hostel_id, title, description, priority) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iisss', $userId, $hostelId, $title, $description, $priority);
        $stmt->execute();
        $stmt->close();
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
