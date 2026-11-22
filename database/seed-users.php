<?php
require_once __DIR__ . '/../includes/db.php';

$users = [
    ['username' => 'admin', 'email' => 'admin@courtiq.local', 'password' => 'admin123', 'role' => 'administrator'],
    ['username' => 'warden', 'email' => 'warden@courtiq.local', 'password' => 'warden123', 'role' => 'warden'],
    ['username' => 'student', 'email' => 'student@courtiq.local', 'password' => 'student123', 'role' => 'student'],
];

try {
    $conn = db();

    foreach ($users as $user) {
        // Check if user exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->bind_param('s', $user['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();

        if ($exists) {
            echo "User {$user['username']} already exists, skipping.\n";
            continue;
        }

        $passwordHash = password_hash($user['password'], PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            echo "Failed to hash password for {$user['username']}\n";
            continue;
        }

        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password_hash, role, status, approval_status)
             VALUES (?, ?, ?, ?, 'active', 'approved')"
        );
        $stmt->bind_param('ssss', $user['username'], $user['email'], $passwordHash, $user['role']);
        if ($stmt->execute()) {
            echo "Created user: {$user['username']} ({$user['role']})\n";
        } else {
            echo "Failed to create user {$user['username']}: " . $stmt->error . "\n";
        }
        $stmt->close();
    }

    echo "Default users seeded.\n";
} catch (Throwable $e) {
    echo "Error seeding users: " . $e->getMessage() . "\n";
    exit(1);
}