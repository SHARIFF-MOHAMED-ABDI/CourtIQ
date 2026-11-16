<?php

function recordMaintenanceHistory(
    mysqli $conn,
    int $requestId,
    ?int $actorId,
    string $action,
    ?string $previousStatus,
    ?string $newStatus,
    ?string $response = null
): void {
    $stmt = $conn->prepare(
        'INSERT INTO maintenance_request_history
            (request_id, actor_id, action, previous_status, new_status, response)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare maintenance history.');
    }
    $stmt->bind_param('iissss', $requestId, $actorId, $action, $previousStatus, $newStatus, $response);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        error_log('CourtIQ maintenance history insert failed: ' . $error);
        throw new RuntimeException('Unable to save maintenance history.');
    }
    $stmt->close();
}

function createUserNotification(mysqli $conn, int $userId, string $title, string $message): void
{
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare maintenance notification.');
    }
    $stmt->bind_param('iss', $userId, $title, $message);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        error_log('CourtIQ maintenance notification insert failed: ' . $error);
        throw new RuntimeException('Unable to save maintenance notification.');
    }
    $stmt->close();
}
