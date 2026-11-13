<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['administrator']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) { http_response_code(400); exit('Invalid request token.'); }
$conn=db(); $action=(string)($_POST['action']??'');
if ($action === '' && isset($_POST['application_id'])) { $action = 'application_status'; }
try {
    if ($action === 'maintenance_status') {
        $id = (int)($_POST['request_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        if ($id < 1 || !in_array($status, ['pending', 'in_progress', 'resolved'], true)) {
            throw new RuntimeException('Invalid maintenance status.');
        }
        $st = $conn->prepare('UPDATE maintenance_requests SET status = ? WHERE id = ?');
        $st->bind_param('si', $status, $id);
        $st->execute();
        $st->close();
        header('Location: index.php?success=1');
        exit;
    }
    if ($action === 'allocation_save') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $adminId = (int)currentUser()['id'];
        if ($studentId < 1 || $roomId < 1) {
            throw new RuntimeException('Select a student and a room.');
        }

        $conn->begin_transaction();
        $st = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' AND status = 'active'");
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
            $conn->commit();
            header('Location: index.php?success=1');
            exit;
        }

        $st = $conn->prepare("SELECT id, capacity, occupied, status FROM rooms WHERE id = ? FOR UPDATE");
        $st->bind_param('i', $roomId);
        $st->execute();
        $targetRoom = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$targetRoom || $targetRoom['status'] === 'maintenance' || (int)$targetRoom['occupied'] >= (int)$targetRoom['capacity']) {
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
        $conn->commit();
        header('Location: index.php?success=1');
        exit;
    }
    if ($action === 'allocation_end') {
        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        if ($allocationId < 1) {
            throw new RuntimeException('Invalid resident allocation.');
        }
        $conn->begin_transaction();
        $st = $conn->prepare("SELECT room_id FROM room_allocations WHERE id = ? AND status = 'active' FOR UPDATE");
        $st->bind_param('i', $allocationId);
        $st->execute();
        $allocation = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$allocation) {
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
        $conn->commit();
        header('Location: index.php?success=1');
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
        if ($wardenId < 1 || $hostelId < 1) {
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
        if ($id < 1) {
            throw new RuntimeException('Invalid hostel assignment.');
        }
        $st = $conn->prepare('UPDATE warden_assignments SET active = 0 WHERE id = ? AND active = 1');
        $st->bind_param('i', $id);
        $st->execute();
        $st->close();
    }
    if ($action === 'user_save') {
        $username=trim((string)$_POST['username']); $email=trim((string)$_POST['email']); $password=(string)$_POST['password']; $role=(string)$_POST['role']; $status=(string)$_POST['status'];
        if(!preg_match('/^[A-Za-z0-9._-]{3,80}$/',$username)||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8||!in_array($role,['student','warden','administrator'],true)||!in_array($status,['active','inactive','suspended'],true)) throw new RuntimeException('Invalid user details');
        $hash=password_hash($password,PASSWORD_DEFAULT); $st=$conn->prepare('INSERT INTO users(username,email,password_hash,role,status) VALUES(?,?,?,?,?)'); $st->bind_param('sssss',$username,$email,$hash,$role,$status); $st->execute(); $st->close();
    } elseif ($action === 'user_status') {
        $id=(int)$_POST['id']; $status=(string)$_POST['status']; if($id<1||!in_array($status,['active','inactive','suspended'],true)) throw new RuntimeException('Invalid status'); $st=$conn->prepare('UPDATE users SET status=? WHERE id=? AND id<>?'); $me=(int)currentUser()['id']; $st->bind_param('sii',$status,$id,$me); $st->execute(); $st->close();
    } elseif ($action === 'hostel_save') {
        $name=trim((string)$_POST['name']); $location=trim((string)$_POST['location']); $capacity=(int)$_POST['capacity']; if($name===''||$capacity<1) throw new RuntimeException('Invalid hostel details'); $st=$conn->prepare('INSERT INTO hostel_profiles(name,location,capacity) VALUES(?,?,?)'); $st->bind_param('ssi',$name,$location,$capacity); $st->execute(); $st->close();
    } elseif ($action === 'hostel_update') {
        $id=(int)$_POST['id']; $name=trim((string)$_POST['name']); $location=trim((string)$_POST['location']); $capacity=(int)$_POST['capacity']; if($id<1||$name===''||$capacity<1) throw new RuntimeException('Invalid hostel details'); $st=$conn->prepare('UPDATE hostel_profiles SET name=?,location=?,capacity=? WHERE id=?'); $st->bind_param('ssii',$name,$location,$capacity,$id); $st->execute(); $st->close();
    } elseif ($action === 'hostel_delete') {
        $id=(int)$_POST['id']; if($id<1) throw new RuntimeException('Invalid hostel'); $st=$conn->prepare('DELETE FROM hostel_profiles WHERE id=?'); $st->bind_param('i',$id); $st->execute(); $st->close();
    } elseif ($action === 'room_save') {
        $hostel=(int)$_POST['hostel_id']; $number=trim((string)$_POST['room_number']); $type=trim((string)$_POST['room_type']); $capacity=(int)$_POST['capacity']; if($hostel<1||$number===''||$capacity<1) throw new RuntimeException('Invalid room details'); $st=$conn->prepare('INSERT INTO rooms(hostel_id,room_number,room_type,capacity) VALUES(?,?,?,?)'); $st->bind_param('issi',$hostel,$number,$type,$capacity); $st->execute(); $st->close();
    } elseif ($action === 'room_update') {
        $id=(int)$_POST['id']; $number=trim((string)$_POST['room_number']); $type=trim((string)$_POST['room_type']); $capacity=(int)$_POST['capacity']; $status=(string)$_POST['status']; if($id<1||$number===''||$capacity<1||!in_array($status,['available','occupied','maintenance'],true)) throw new RuntimeException('Invalid room details'); $st=$conn->prepare('UPDATE rooms SET room_number=?,room_type=?,capacity=?,status=? WHERE id=?'); $st->bind_param('ssisi',$number,$type,$capacity,$status,$id); $st->execute(); $st->close();
    } elseif ($action === 'room_delete') {
        $id=(int)$_POST['id']; if($id<1) throw new RuntimeException('Invalid room'); $st=$conn->prepare('DELETE FROM rooms WHERE id=?'); $st->bind_param('i',$id); $st->execute(); $st->close();
    } elseif ($action === 'payment_save') {
        $uid=(int)$_POST['user_id']; $ref=trim((string)$_POST['reference_no']); $amount=(float)$_POST['amount']; $balance=(float)$_POST['balance']; $date=(string)$_POST['payment_date']; $status=(string)$_POST['status']; if($uid<1||$ref===''||$amount<0||$balance<0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!in_array($status,['recorded','verified','pending_audit'],true)) throw new RuntimeException('Invalid payment details'); $st=$conn->prepare("INSERT INTO payment_records(user_id,reference_no,amount,balance,status,payment_date) VALUES(?,?,?,?,?,?)"); $st->bind_param('isddss',$uid,$ref,$amount,$balance,$status,$date); $st->execute(); $st->close();
    } elseif ($action === 'user_delete') {
        $id=(int)$_POST['id']; $me=(int)currentUser()['id']; if($id<1||$id===$me) throw new RuntimeException('Cannot delete this account'); $st=$conn->prepare('DELETE FROM users WHERE id=?'); $st->bind_param('i',$id); $st->execute(); $st->close();
    } else throw new RuntimeException('Unknown operation');
    header('Location: manage.php?success=1');
} catch(Throwable $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    header('Location: index.php?error='.rawurlencode($e->getMessage()));
}
exit;
