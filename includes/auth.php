<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Authentication & Session Management Module
 */

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    $lifetime = 1800;
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (isLoggedIn() && isset($_SESSION['courtiq_last_activity'])
    && time() - (int)$_SESSION['courtiq_last_activity'] > 1800) {
    logoutUser();
    header('Location: ' . projectUrl('login.php?error=session_timeout'));
    exit();
}
if (isLoggedIn()) {
    $_SESSION['courtiq_last_activity'] = time();
}

/**
 * Check if the user is authenticated.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['courtiq_user']) && !empty($_SESSION['courtiq_user']['id']);
}

/**
 * Retrieve current logged-in user details.
 */
function currentUser(): ?array
{
    return isLoggedIn() ? $_SESSION['courtiq_user'] : null;
}

/**
 * Log in a user securely and regenerate the session ID.
 */
function loginUser(array $user, bool $remember = false): void
{
    session_regenerate_id(true);
    $_SESSION['courtiq_user'] = [
        'id'         => (int) $user['id'],
        'username'   => $user['username'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'status'     => $user['status'] ?? 'active',
        'logged_in'  => time(),
    ];
    $_SESSION['courtiq_last_activity'] = time();
    $_SESSION['courtiq_csrf'] = bin2hex(random_bytes(32));
}

/**
 * Clear user session and destroy authentication state.
 */
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Calculate the project root URL path dynamically.
 * Works whether the application runs in /courtiq/, /CourTIQ/, or at web root.
 */
function projectUrl(string $path = ''): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $directory = str_replace('\\', '/', dirname($scriptName));

    // If currently inside subdirectories like student, warden, admin, config, includes
    $subdirs = ['student', 'warden', 'admin', 'includes', 'config', 'database'];
    $currentBase = basename($directory);
    if (in_array($currentBase, $subdirs, true)) {
        $directory = dirname($directory);
    }

    $root = rtrim(str_replace('\\', '/', $directory), '/');
    if ($root === '/' || $root === '\\') {
        $root = '';
    }

    $cleanPath = ltrim($path, '/');
    return $root === '' ? '/' . $cleanPath : $root . '/' . $cleanPath;
}

/**
 * Get the relative dashboard URL for a given role.
 */
function getRoleDashboardUrl(?string $role = null): string
{
    if ($role === null) {
        $user = currentUser();
        $role = $user['role'] ?? '';
    }

    return match (strtolower(trim($role))) {
        'administrator', 'admin' => projectUrl('admin/index.php'),
        'warden' => projectUrl('warden/index.php'),
        'student' => projectUrl('student/index.php'),
        default => projectUrl('login.php'),
    };
}

/**
 * Enforce authentication for protected pages.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . projectUrl('login.php?error=login_required'));
        exit();
    }
}

/**
 * Enforce role-based access control.
 *
 * @param array<string> $allowedRoles
 */
function requireRole(array $allowedRoles): void
{
    requireLogin();
    require_once __DIR__ . '/db.php';
    $userId = (int)currentUser()['id'];
    $stmt = db()->prepare('SELECT u.username, u.email, u.role, u.status, u.approval_status, sp.full_name, sp.student_number
        FROM users u LEFT JOIN student_profiles sp ON sp.user_id = u.id WHERE u.id = ?');
    if (!$stmt) {
        error_log('CourtIQ could not verify the current account authorization.');
        http_response_code(503);
        exit('Account authorization is temporarily unavailable.');
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        error_log('CourtIQ could not execute the current account authorization check: ' . $stmt->error);
        $stmt->close();
        http_response_code(503);
        exit('Account authorization is temporarily unavailable.');
    }
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || $user['status'] !== 'active' || $user['approval_status'] !== 'approved') {
        logoutUser();
        $error = !$user ? 'login_required' : ($user['approval_status'] === 'pending' ? 'approval_pending' : ($user['approval_status'] === 'rejected' ? 'approval_rejected' : 'account_inactive'));
        header('Location: ' . projectUrl('login.php?error=' . $error));
        exit();
    }

    $_SESSION['courtiq_user']['username'] = $user['username'];
    $_SESSION['courtiq_user']['email'] = $user['email'];
    $_SESSION['courtiq_user']['role'] = $user['role'];
    $_SESSION['courtiq_user']['status'] = $user['status'];
    $_SESSION['courtiq_user']['approval_status'] = $user['approval_status'];
    $_SESSION['courtiq_user']['full_name'] = $user['full_name'];
    $_SESSION['courtiq_user']['student_number'] = $user['student_number'];

    if (!$user || !in_array($user['role'], $allowedRoles, true)) {
        header('Location: ' . projectUrl('login.php?error=unauthorized'));
        exit();
    }
}

/**
 * Redirect helper using projectUrl.
 */
function redirectTo(string $path): void
{
    header('Location: ' . projectUrl($path));
    exit();
}
