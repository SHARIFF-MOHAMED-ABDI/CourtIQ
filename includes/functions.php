<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * General Utility Functions
 */

/**
 * Escape HTML output securely.
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format user roles into human-readable titles.
 */
function formatRole(string $role): string
{
    return match (strtolower(trim($role))) {
        'administrator', 'admin' => 'Administrator',
        'warden' => 'Hostel Warden',
        'student' => 'Student Resident',
        default => ucfirst($role),
    };
}

/**
 * Return role badge class for UI styling.
 */
function roleBadgeClass(string $role): string
{
    return match (strtolower(trim($role))) {
        'administrator', 'admin' => 'badge-admin',
        'warden' => 'badge-warden',
        'student' => 'badge-student',
        default => 'badge-default',
    };
}

function csrfToken(): string
{
    if (empty($_SESSION['courtiq_csrf'])) {
        $_SESSION['courtiq_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['courtiq_csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(?string $token): bool
{
    return is_string($token) && isset($_SESSION['courtiq_csrf'])
        && hash_equals($_SESSION['courtiq_csrf'], $token);
}
