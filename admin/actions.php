<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/maintenance.php';
requireRole(['administrator']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) { http_response_code(400); exit('Invalid request token.'); }
$conn=db(); $action=(string)($_POST['action']??'');
$returnHostelId = max(0, (int)($_POST['return_hostel_id'] ?? $_POST['hostel_id'] ?? 0));
$createdHostelId = 0;
$transactionStarted = false;
if ($action === '' && isset($_POST['application_id'])) { $action = 'application_status'; }
try {
    if ($action === 'account_approval') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $decision = (string)($_POST['decision'] ?? '');
        $adminId = (int)currentUser()['id'];
        if ($userId < 1 || !in_array($decision, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Invalid student account decision.');
        }
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Unable to begin account approval.');
        }
        $transactionStarted = true;
        $st = $conn->prepare("SELECT id, email, approval_status FROM users WHERE id = ? AND role = 'student' FOR UPDATE");
        if (!$st) {
            throw new RuntimeException('Unable to prepare account approval.');
        }
        $st->bind_param('i', $userId);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to read student account.');
        }
        $student = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$student) {
            throw new RuntimeException('Student account not found.');
        }
        if ($student['approval_status'] !== 'pending') {
            throw new RuntimeException('This student account has already been reviewed.');
        }
        $st = $conn->prepare('UPDATE users SET approval_status = ?, approval_decided_by = ?, approval_decided_at = NOW() WHERE id = ?');
        if (!$st) {
            throw new RuntimeException('Unable to prepare account decision.');
        }
        $st->bind_param('sii', $decision, $adminId, $userId);
        if (!$st->execute() || $st->affected_rows !== 1) {
            throw new RuntimeException('Unable to save the account decision.');
        }
        $st->close();
        $title = $decision === 'approved' ? 'Student account approved' : 'Student account not approved';
        $message = $decision === 'approved'
            ? 'Your account has been approved. You can now sign in.'
            : 'Your account registration was not approved. Contact the hostel administration office for assistance.';
        createUserNotification($conn, $userId, $title, $message);
        if (!$conn->commit()) {
            throw new RuntimeException('Unable to complete account decision.');
        }
        $transactionStarted = false;
        header('Location: manage.php?approval_filter=pending&success=1');
        exit;
    }
    if (in_array($action, ['maintenance_status', 'maintenance_update'], true)) {
        $id = (int)($_POST['request_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        $response = trim((string)($_POST['response'] ?? ''));
        $resolutionNotes = trim((string)($_POST['resolution_notes'] ?? ''));
        if ($id < 1 || !in_array($status, ['pending', 'in_progress', 'resolved', 'rejected'], true)
            || strlen($response) > 5000 || strlen($resolutionNotes) > 5000) {
            throw new RuntimeException('Invalid maintenance status.');
        }
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Unable to begin maintenance update.');
        }
        $transactionStarted = true;
        $st = $conn->prepare('SELECT user_id, reference_no, status FROM maintenance_requests WHERE id = ? FOR UPDATE');
        if (!$st) {
            throw new RuntimeException('Unable to prepare maintenance update.');
        }
        $st->bind_param('i', $id);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to read the maintenance request.');
        }
        $request = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$request) {
            throw new RuntimeException('Maintenance request not found.');
        }
        $oldStatus = $request['status'];
        $st = $conn->prepare("UPDATE maintenance_requests
            SET status = ?, latest_response = IF(? = '', latest_response, ?),
                resolution_notes = IF(? = '', resolution_notes, ?)
            WHERE id = ?");
        if (!$st) {
            throw new RuntimeException('Unable to prepare maintenance update.');
        }
        $st->bind_param('sssssi', $status, $response, $response, $resolutionNotes, $resolutionNotes, $id);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to save maintenance changes.');
        }
        $st->close();
        $adminId = (int)currentUser()['id'];
        if ($oldStatus !== $status) {
            recordMaintenanceHistory($conn, $id, $adminId, 'status_changed', $oldStatus, $status);
        }
        if ($response !== '') {
            recordMaintenanceHistory($conn, $id, $adminId, 'response_added', $oldStatus, $status, $response);
        }
        if ($resolutionNotes !== '') {
            recordMaintenanceHistory($conn, $id, $adminId, 'resolution_note_added', $oldStatus, $status, $resolutionNotes);
        }
        if ($oldStatus !== $status || $response !== '' || $resolutionNotes !== '') {
            $message = 'Request ' . $request['reference_no'] . ' is now ' . str_replace('_', ' ', $status) . '.';
            if ($response !== '' || $resolutionNotes !== '') {
                $message .= ' An update has been added by the hostel team.';
            }
            createUserNotification($conn, (int)$request['user_id'], 'Maintenance request updated', $message);
        }
        if (!$conn->commit()) {
            throw new RuntimeException('Unable to complete maintenance update.');
        }
        $transactionStarted = false;
        header('Location: maintenance.php?success=1');
        exit;
    }
    if ($action === 'allocation_save') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $adminId = (int)currentUser()['id'];
        if ($studentId < 1 || $roomId < 1) {
            throw new RuntimeException('Select a student and a room.');
        }

        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Unable to begin the room allocation update.');
        }
        $transactionStarted = true;
        $st = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' AND status = 'active' AND approval_status = 'approved'");
        $st->bind_param('i', $studentId);
        $st->execute();
        if (!$st->get_result()->fetch_assoc()) {
            throw new RuntimeException('Invalid student account.');
        }
        $st->close();

        $st = $conn->prepare("SELECT id, room_id FROM room_allocations WHERE student_id = ? AND status = 'active' FOR UPDATE");
        $st->bind_param('i', $studentId);
        $st->execute();
        $current = $st->get_result()->fetch_assoc();
        $st->close();

        if ($current && (int)$current['room_id'] === $roomId) {
            if (!$conn->commit()) {
                throw new RuntimeException('Unable to complete the room allocation update.');
            }
            $transactionStarted = false;
            header('Location: residents.php' . ($returnHostelId > 0 ? '?hostel_id=' . $returnHostelId . '&success=1' : '?success=1'));
            exit;
        }

        $st = $conn->prepare("SELECT id, hostel_id, capacity, occupied, status FROM rooms WHERE id = ? FOR UPDATE");
        $st->bind_param('i', $roomId);
        $st->execute();
        $targetRoom = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$targetRoom || ($returnHostelId > 0 && (int)$targetRoom['hostel_id'] !== $returnHostelId)
            || $targetRoom['status'] === 'maintenance' || (int)$targetRoom['occupied'] >= (int)$targetRoom['capacity']) {
            throw new RuntimeException('The selected room is unavailable or full.');
        }

        if ($current) {
            $oldRoomId = (int)$current['room_id'];
            $st = $conn->prepare("UPDATE room_allocations SET status = 'ended', ended_at = NOW() WHERE id = ?");
            $st->bind_param('i', $current['id']);
            $st->execute();
            $st->close();
            $st = $conn->prepare("UPDATE rooms SET occupied = GREATEST(occupied - 1, 0), status = IF(status = 'maintenance', status, IF(GREATEST(occupied - 1, 0) >= capacity, 'occupied', 'available')) WHERE id = ?");
            $st->bind_param('i', $oldRoomId);
            $st->execute();
            $st->close();
        }

        $st = $conn->prepare("INSERT INTO room_allocations (room_id, student_id, allocated_by) VALUES (?, ?, ?)");
        $st->bind_param('iii', $roomId, $studentId, $adminId);
        $st->execute();
        $st->close();
        $st = $conn->prepare("UPDATE rooms SET occupied = occupied + 1, status = IF(occupied + 1 >= capacity, 'occupied', 'available') WHERE id = ?");
        $st->bind_param('i', $roomId);
        $st->execute();
        $st->close();
        if (!$conn->commit()) {
            throw new RuntimeException('Unable to complete the room allocation update.');
        }
        $transactionStarted = false;
        header('Location: residents.php' . ($returnHostelId > 0 ? '?hostel_id=' . $returnHostelId . '&success=1' : '?success=1'));
        exit;
    }
    if ($action === 'allocation_end') {
        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        if ($allocationId < 1) {
            throw new RuntimeException('Invalid resident allocation.');
        }
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Unable to begin the allocation removal.');
        }
        $transactionStarted = true;
        $st = $conn->prepare("SELECT a.room_id, r.hostel_id FROM room_allocations a
            JOIN rooms r ON r.id = a.room_id WHERE a.id = ? AND a.status = 'active' FOR UPDATE");
        $st->bind_param('i', $allocationId);
        $st->execute();
        $allocation = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$allocation || ($returnHostelId > 0 && (int)$allocation['hostel_id'] !== $returnHostelId)) {
            throw new RuntimeException('This allocation is no longer active.');
        }
        $st = $conn->prepare("UPDATE room_allocations SET status = 'ended', ended_at = NOW() WHERE id = ?");
        $st->bind_param('i', $allocationId);
        $st->execute();
        $st->close();
        $roomId = (int)$allocation['room_id'];
        $st = $conn->prepare("UPDATE rooms SET occupied = GREATEST(occupied - 1, 0), status = IF(status = 'maintenance', status, IF(GREATEST(occupied - 1, 0) >= capacity, 'occupied', 'available')) WHERE id = ?");
        $st->bind_param('i', $roomId);
        $st->execute();
        $st->close();
        if (!$conn->commit()) {
            throw new RuntimeException('Unable to complete the allocation removal.');
        }
        $transactionStarted = false;
        header('Location: residents.php' . ($returnHostelId > 0 ? '?hostel_id=' . $returnHostelId . '&success=1' : '?success=1'));
        exit;
    }
    if ($action === 'application_status') {
        $id=(int)($_POST['application_id']??0); $status=(string)($_POST['status']??'');
        if($id<1||!in_array($status,['approved','rejected','pending'],true)) throw new RuntimeException('Invalid application update');
        $st=$conn->prepare('UPDATE accommodation_applications SET application_status=? WHERE id=?'); $st->bind_param('si',$status,$id); $st->execute(); $st->close(); header('Location: index.php?success=1'); exit;
    }
    if ($action === 'warden_assignment_save') {
        $wardenId = (int)($_POST['warden_id'] ?? 0);
        $hostelId = (int)($_POST['hostel_id'] ?? 0);
        if ($wardenId < 1 || $hostelId < 1 || ($returnHostelId > 0 && $returnHostelId !== $hostelId)) {
            throw new RuntimeException('Select a warden and hostel.');
        }
        $st = $conn->prepare('SELECT id FROM users WHERE id = ? AND role = "warden" AND status = "active"');
        $st->bind_param('i', $wardenId);
        $st->execute();
        if (!$st->get_result()->fetch_assoc()) {
            throw new RuntimeException('The selected account is not an active warden.');
        }
        $st->close();
        $st = $conn->prepare('SELECT id FROM hostel_profiles WHERE id = ?');
        $st->bind_param('i', $hostelId);
        $st->execute();
        if (!$st->get_result()->fetch_assoc()) {
            throw new RuntimeException('The selected hostel does not exist.');
        }
        $st->close();
        $st = $conn->prepare('INSERT INTO warden_assignments (warden_id, hostel_id, active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE active = 1, assigned_at = CURRENT_TIMESTAMP');
        $st->bind_param('ii', $wardenId, $hostelId);
        $st->execute();
        $st->close();
    } elseif ($action === 'warden_assignment_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1 || $returnHostelId < 1) {
            throw new RuntimeException('Invalid hostel assignment.');
        }
        $st = $conn->prepare('UPDATE warden_assignments SET active = 0 WHERE id = ? AND hostel_id = ? AND active = 1');
        $st->bind_param('ii', $id, $returnHostelId);
        $st->execute();
        $st->close();
    }
    if ($action === 'user_save') {
        $username=trim((string)$_POST['username']); $email=trim((string)$_POST['email']); $password=(string)$_POST['password']; $role=(string)$_POST['role']; $status=(string)$_POST['status'];
        if(!preg_match('/^[A-Za-z0-9._-]{3,80}$/',$username)||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8||!in_array($role,['student','warden','administrator'],true)||!in_array($status,['active','inactive','suspended'],true)) throw new RuntimeException('Invalid user details');
        $hash=password_hash($password,PASSWORD_DEFAULT); $st=$conn->prepare('INSERT INTO users(username,email,password_hash,role,status) VALUES(?,?,?,?,?)'); $st->bind_param('sssss',$username,$email,$hash,$role,$status); $st->execute(); $st->close();
    } elseif ($action === 'user_password_reset') {
        $id = (int)($_POST['id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');
        if ($id < 1 || strlen($password) < 8) {
            throw new RuntimeException('Choose a password with at least 8 characters.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $st->bind_param('si', $hash, $id);
        $st->execute();
        if ($st->affected_rows !== 1) {
            $st->close();
            throw new RuntimeException('User account not found or password unchanged.');
        }
        $st->close();
    } elseif ($action === 'user_status') {
        $id=(int)$_POST['id']; $status=(string)$_POST['status']; if($id<1||!in_array($status,['active','inactive','suspended'],true)) throw new RuntimeException('Invalid status'); $st=$conn->prepare('UPDATE users SET status=? WHERE id=? AND id<>?'); $me=(int)currentUser()['id']; $st->bind_param('sii',$status,$id,$me); $st->execute(); $st->close();
    } elseif ($action === 'hostel_save') {
        $name = trim((string)($_POST['name'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $capacity = (int)($_POST['capacity'] ?? 0);
        if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || mb_strlen($location, 'UTF-8') > 180 || $capacity < 1) {
            throw new RuntimeException('Enter a hostel name, location and valid capacity.');
        }
        $st = $conn->prepare('INSERT INTO hostel_profiles (name, location, capacity) VALUES (?, ?, ?)');
        if (!$st) {
            throw new RuntimeException('Unable to prepare the hostel record.');
        }
        $st->bind_param('ssi', $name, $location, $capacity);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to add this hostel.');
        }
        $createdHostelId = (int)$conn->insert_id;
        $st->close();
    } elseif ($action === 'hostel_update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $capacity = (int)($_POST['capacity'] ?? 0);
        if ($id < 1 || $name === '' || mb_strlen($name, 'UTF-8') > 150 || mb_strlen($location, 'UTF-8') > 180 || $capacity < 1
            || ($returnHostelId > 0 && $returnHostelId !== $id)) {
            throw new RuntimeException('Enter valid details for the selected hostel.');
        }
        $st = $conn->prepare('SELECT h.id, COALESCE(SUM(r.occupied), 0) AS occupied
            FROM hostel_profiles h LEFT JOIN rooms r ON r.hostel_id = h.id
            WHERE h.id = ? GROUP BY h.id');
        $st->bind_param('i', $id);
        $st->execute();
        $hostel = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$hostel) {
            throw new RuntimeException('The selected hostel no longer exists.');
        }
        $occupied = (int)$hostel['occupied'];
        if ($capacity < $occupied) {
            throw new RuntimeException('Hostel capacity cannot be lower than its current occupied beds.');
        }
        $st = $conn->prepare('UPDATE hostel_profiles SET name = ?, location = ?, capacity = ? WHERE id = ?');
        $st->bind_param('ssii', $name, $location, $capacity, $id);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to update the selected hostel.');
        }
        $st->close();
        $returnHostelId = $id;
    } elseif ($action === 'hostel_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1 || ($returnHostelId > 0 && $returnHostelId !== $id)) {
            throw new RuntimeException('Select a valid hostel to delete.');
        }
        $st = $conn->prepare('SELECT COUNT(*) AS total FROM rooms WHERE hostel_id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $roomCount = (int)$st->get_result()->fetch_assoc()['total'];
        $st->close();
        if ($roomCount > 0) {
            throw new RuntimeException('Remove room records without allocation history before deleting this hostel.');
        }
        $st = $conn->prepare('SELECT COUNT(*) AS total FROM maintenance_requests WHERE hostel_id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $maintenanceCount = (int)$st->get_result()->fetch_assoc()['total'];
        $st->close();
        if ($maintenanceCount > 0) {
            throw new RuntimeException('A hostel with maintenance history cannot be deleted.');
        }
        $st = $conn->prepare('SELECT COUNT(*) AS total FROM warden_assignments WHERE hostel_id = ? AND active = 1');
        $st->bind_param('i', $id);
        $st->execute();
        $wardenCount = (int)$st->get_result()->fetch_assoc()['total'];
        $st->close();
        if ($wardenCount > 0) {
            throw new RuntimeException('Remove active warden assignments before deleting this hostel.');
        }
        $st = $conn->prepare('DELETE FROM hostel_profiles WHERE id = ?');
        $st->bind_param('i', $id);
        if (!$st->execute() || $st->affected_rows !== 1) {
            throw new RuntimeException('Unable to delete the selected hostel.');
        }
        $st->close();
        $returnHostelId = 0;
    } elseif ($action === 'room_save') {
        $hostelId = (int)($_POST['hostel_id'] ?? 0);
        $number = trim((string)($_POST['room_number'] ?? ''));
        $type = trim((string)($_POST['room_type'] ?? ''));
        $capacity = (int)($_POST['capacity'] ?? 0);
        if ($hostelId < 1 || $number === '' || mb_strlen($number, 'UTF-8') > 50 || mb_strlen($type, 'UTF-8') > 60
            || $capacity < 1 || ($returnHostelId > 0 && $returnHostelId !== $hostelId)) {
            throw new RuntimeException('Enter valid details for a room in the selected hostel.');
        }
        $st = $conn->prepare('SELECT id FROM hostel_profiles WHERE id = ?');
        $st->bind_param('i', $hostelId);
        $st->execute();
        if (!$st->get_result()->fetch_assoc()) {
            $st->close();
            throw new RuntimeException('The selected hostel does not exist.');
        }
        $st->close();
        $st = $conn->prepare('SELECT id FROM rooms WHERE hostel_id = ? AND room_number = ?');
        $st->bind_param('is', $hostelId, $number);
        $st->execute();
        if ($st->get_result()->fetch_assoc()) {
            $st->close();
            throw new RuntimeException('That room number already exists in this hostel.');
        }
        $st->close();
        $st = $conn->prepare('INSERT INTO rooms (hostel_id, room_number, room_type, capacity) VALUES (?, ?, ?, ?)');
        $st->bind_param('issi', $hostelId, $number, $type, $capacity);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to add the room.');
        }
        $st->close();
        $returnHostelId = $hostelId;
    } elseif ($action === 'room_update') {
        $id = (int)($_POST['id'] ?? 0);
        $number = trim((string)($_POST['room_number'] ?? ''));
        $type = trim((string)($_POST['room_type'] ?? ''));
        $capacity = (int)($_POST['capacity'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        if ($id < 1 || $number === '' || mb_strlen($number, 'UTF-8') > 50 || mb_strlen($type, 'UTF-8') > 60
            || $capacity < 1 || !in_array($status, ['available', 'occupied', 'maintenance'], true)) {
            throw new RuntimeException('Enter valid room details.');
        }
        $st = $conn->prepare('SELECT hostel_id, occupied FROM rooms WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $room = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$room || ($returnHostelId > 0 && (int)$room['hostel_id'] !== $returnHostelId)
            || $capacity < (int)($room['occupied'] ?? 0)) {
            throw new RuntimeException('Room capacity cannot be lower than occupancy, or the room is outside the selected hostel.');
        }
        $hostelId = (int)$room['hostel_id'];
        $st = $conn->prepare('SELECT id FROM rooms WHERE hostel_id = ? AND room_number = ? AND id <> ?');
        $st->bind_param('isi', $hostelId, $number, $id);
        $st->execute();
        if ($st->get_result()->fetch_assoc()) {
            $st->close();
            throw new RuntimeException('That room number already exists in this hostel.');
        }
        $st->close();
        $resolvedStatus = $status === 'maintenance'
            ? 'maintenance'
            : ((int)$room['occupied'] >= $capacity ? 'occupied' : 'available');
        $st = $conn->prepare('UPDATE rooms SET room_number = ?, room_type = ?, capacity = ?, status = ? WHERE id = ?');
        $st->bind_param('ssisi', $number, $type, $capacity, $resolvedStatus, $id);
        if (!$st->execute()) {
            throw new RuntimeException('Unable to update the room.');
        }
        $st->close();
        $returnHostelId = $hostelId;
    } elseif ($action === 'room_delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $conn->prepare('SELECT hostel_id FROM rooms WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $room = $st->get_result()->fetch_assoc();
        $st->close();
        if ($id < 1 || !$room || ($returnHostelId > 0 && (int)$room['hostel_id'] !== $returnHostelId)) {
            throw new RuntimeException('Select a valid room in the current hostel.');
        }
        $st = $conn->prepare('SELECT COUNT(*) AS total FROM room_allocations WHERE room_id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $allocationCount = (int)$st->get_result()->fetch_assoc()['total'];
        $st->close();
        if ($allocationCount > 0) {
            throw new RuntimeException('A room with allocation history cannot be deleted.');
        }
        $st = $conn->prepare('DELETE FROM rooms WHERE id = ?');
        $st->bind_param('i', $id);
        if (!$st->execute() || $st->affected_rows !== 1) {
            throw new RuntimeException('Unable to delete the room.');
        }
        $st->close();
        $returnHostelId = (int)$room['hostel_id'];
    } elseif ($action === 'payment_save') {
        $uid=(int)$_POST['user_id']; $ref=trim((string)$_POST['reference_no']); $amount=(float)$_POST['amount']; $balance=(float)$_POST['balance']; $date=(string)$_POST['payment_date']; $status=(string)$_POST['status']; if($uid<1||$ref===''||$amount<0||$balance<0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!in_array($status,['recorded','verified','pending_audit'],true)) throw new RuntimeException('Invalid payment details'); $st=$conn->prepare("INSERT INTO payment_records(user_id,reference_no,amount,balance,status,payment_date) VALUES(?,?,?,?,?,?)"); $st->bind_param('isddss',$uid,$ref,$amount,$balance,$status,$date); $st->execute(); $st->close();
    } elseif ($action === 'user_delete') {
        $id=(int)$_POST['id']; $me=(int)currentUser()['id']; if($id<1||$id===$me) throw new RuntimeException('Cannot delete this account'); $st=$conn->prepare('DELETE FROM users WHERE id=?'); $st->bind_param('i',$id); $st->execute(); $st->close();
    } else throw new RuntimeException('Unknown operation');
    if (in_array($action, ['allocation_save', 'allocation_end'], true)) {
        $returnUrl = 'residents.php';
        if ($returnHostelId > 0) {
            $returnUrl .= '?hostel_id=' . $returnHostelId;
        }
        header('Location: ' . $returnUrl . ($returnUrl === 'residents.php' ? '?success=1' : '&success=1'));
    } elseif (in_array($action, [
        'hostel_save', 'hostel_update', 'hostel_delete', 'room_save', 'room_update', 'room_delete',
        'warden_assignment_save', 'warden_assignment_delete',
    ], true)) {
        $targetHostelId = $createdHostelId > 0 ? $createdHostelId : $returnHostelId;
        $returnUrl = 'manage.php';
        if ($targetHostelId > 0) {
            $returnUrl .= '?hostel_id=' . $targetHostelId;
        }
        header('Location: ' . $returnUrl . (strpos($returnUrl, '?') === false ? '?success=1' : '&success=1'));
    } else {
        header('Location: manage.php?success=1');
    }
} catch(Throwable $e) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    $errorPage = in_array($action, ['maintenance_status', 'maintenance_update'], true)
        ? 'maintenance.php'
        : (in_array($action, ['user_password_reset', 'account_approval'], true)
            ? 'manage.php?approval_filter=pending'
            : (in_array($action, ['allocation_save', 'allocation_end'], true)
                ? 'residents.php' . ($returnHostelId > 0 ? '?hostel_id=' . $returnHostelId : '')
                : (in_array($action, [
                    'hostel_save', 'hostel_update', 'hostel_delete', 'room_save', 'room_update', 'room_delete',
                    'warden_assignment_save', 'warden_assignment_delete',
                ], true)
                    ? 'manage.php' . ($returnHostelId > 0 ? '?hostel_id=' . $returnHostelId : '')
                    : 'index.php')));
    $separator = strpos($errorPage, '?') === false ? '?' : '&';
    header('Location: ' . $errorPage . $separator . 'error=' . rawurlencode($e->getMessage()));
}
exit;
