<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Authentication & Login Page
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// If user is already authenticated, redirect to their role-specific dashboard
if (isLoggedIn()) {
    $redirectUrl = getRoleDashboardUrl();
    header('Location: ' . $redirectUrl);
    exit();
}

$errors = [];
$usernameValue = '';

// Handle POST authentication request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session token expired. Please refresh and try again.';
    }
    $usernameValue = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    // Server-side validation
    if (empty($errors)) {
        if ($usernameValue === '' || $password === '') {
            $errors[] = 'Email address or username and password are required.';
        } else {
            // Query database securely using prepared statement
            $stmt = db()->prepare('SELECT id, username, email, password_hash, role, status FROM users WHERE (username = ? OR email = ?) LIMIT 1');

            if ($stmt) {
                $stmt->bind_param('ss', $usernameValue, $usernameValue);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Verify account active status
                    if ($user['status'] !== 'active') {
                        $errors[] = 'Your account is currently inactive or suspended. Please contact an authorized hostel administrator.';
                    } else {
                        // Valid credentials: initiate secure authenticated session
                        loginUser($user, $remember);

                        // Redirect strictly based on database role
                        $targetDashboard = match (strtolower(trim($user['role']))) {
                            'administrator', 'admin' => projectUrl('admin/index.php'),
                            'warden' => projectUrl('warden/index.php'),
                            'student' => projectUrl('student/index.php'),
                            default => projectUrl('index.php'),
                        };

                        header('Location: ' . $targetDashboard);
                        exit();
                    }
                } else {
                    // Authentication failed
                    $errors[] = 'Invalid credentials. Please verify your username/email and password.';
                }
            } else {
                error_log('Database statement preparation failed during login.');
                $errors[] = 'A system error occurred. Please try again shortly.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to CourtIQ Hostel Management System to access student accommodation services, room allocation, and maintenance records.">
    <meta name="theme-color" content="#102A43">
    <title>Login | CourtIQ Hostel Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= projectUrl('assets/css/login.css') ?>">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <!-- Left Side: Branding, Context & Dashboard Visual -->
        <aside class="login-visual-panel" aria-label="CourtIQ Information">
            <div class="visual-panel-content">
                <a href="<?= projectUrl('index.php') ?>" class="login-brand" aria-label="Return to CourtIQ home">
                    <span class="brand-icon-box">
                        <svg class="brand-svg" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect width="32" height="32" rx="8" fill="#FFFFFF"/>
                            <path d="M16 6L7 13V25C7 25.5523 7.44772 26 8 26H13V19H19V26H24C24.5523 26 25 25.5523 25 25V13L16 6Z" fill="#2563EB"/>
                            <path d="M16 11L11 15V23H13V18C13 17.4477 13.4477 17 14 17H18C18.5523 17 19 17.4477 19 18V23H21V15L16 11Z" fill="#0D9488"/>
                        </svg>
                    </span>
                    <span class="brand-text light">Court<span>IQ</span></span>
                </a>

                <div class="visual-intro">
                    <span class="visual-tag">Centralized Portal</span>
                    <h1 class="visual-title">Welcome to CourtIQ</h1>
                    <p class="visual-desc">
                        A web-based hostel management system engineered to centralize and streamline student accommodation services, room assignments, payment records, and maintenance requests.
                    </p>
                </div>

                <!-- Live Dashboard Visual Summary Card -->
                <div class="visual-card-preview" aria-label="Operational overview preview">
                    <div class="vcard-header">
                        <div class="vcard-icon-pill">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                            </svg>
                            <span>Hostel Operations Hub</span>
                        </div>
                        <span class="vcard-status">Online</span>
                    </div>

                    <div class="vcard-stats-row">
                        <div class="vstat-item">
                            <span class="vstat-num">245</span>
                            <span class="vstat-lbl">Students</span>
                        </div>
                        <div class="vstat-divider"></div>
                        <div class="vstat-item">
                            <span class="vstat-num">78%</span>
                            <span class="vstat-lbl">Occupancy</span>
                        </div>
                        <div class="vstat-divider"></div>
                        <div class="vstat-item">
                            <span class="vstat-num">18</span>
                            <span class="vstat-lbl">Available</span>
                        </div>
                        <div class="vstat-divider"></div>
                        <div class="vstat-item">
                            <span class="vstat-num">09</span>
                            <span class="vstat-lbl">Pending Req</span>
                        </div>
                    </div>

                    <div class="vcard-footer">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" class="vcard-lock">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        <span>Role-Based Access Control Enforced</span>
                    </div>
                </div>

                <div class="visual-bottom">
                    <p>&copy; 2026 CourtIQ. Final-Year University Project.</p>
                </div>
            </div>
        </aside>

        <!-- Right Side: Clean Professional Login Form -->
        <main class="login-form-panel">
            <div class="login-form-container">
                <div class="login-form-header">
                    <div class="mobile-brand-wrap">
                        <a href="<?= projectUrl('index.php') ?>" class="brand" aria-label="CourtIQ Home">
                            <span class="brand-icon-box">
                                <svg class="brand-svg" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <rect width="32" height="32" rx="8" fill="#102A43"/>
                                    <path d="M16 6L7 13V25C7 25.5523 7.44772 26 8 26H13V19H19V26H24C24.5523 26 25 25.5523 25 25V13L16 6Z" fill="#2563EB"/>
                                    <path d="M16 11L11 15V23H13V18C13 17.4477 13.4477 17 14 17H18C18.5523 17 19 17.4477 19 18V23H21V15L16 11Z" fill="#0D9488"/>
                                </svg>
                            </span>
                            <span class="brand-text">Court<span>IQ</span></span>
                        </a>
                    </div>
                    
                    <span class="form-tag">Authentication Portal</span>
                    <h2 class="form-title">Welcome Back</h2>
                    <p class="form-subtitle">Sign in to access your CourtIQ account.</p>
                </div>

                <!-- Feedback Notifications -->
                <?php if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1'): ?>
                    <div class="alert alert-success" role="status" aria-live="polite">
                        <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>You have been logged out successfully.</span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <?php
                    $errParam = $_GET['error'];
                    $alertMsg = 'Please sign in to continue.';
                    if ($errParam === 'login_required') {
                        $alertMsg = 'Authentication required. Please sign in to access that page.';
                    } elseif ($errParam === 'unauthorized') {
                        $alertMsg = 'Access Denied: You do not possess the required permissions for that role.';
                    }
                    ?>
                    <div class="alert alert-warning" role="alert">
                        <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span><?= e($alertMsg) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error" role="alert">
                        <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="alert-text-list">
                            <?php foreach ($errors as $error): ?>
                                <div><?= e($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Client-Side Error Box (Filled dynamically by login.js) -->
                <div class="alert alert-error is-hidden" id="clientErrorBox" role="alert">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span id="clientErrorMessage"></span>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="login-form" method="POST" action="<?= projectUrl('login.php') ?>" novalidate>
                    <?= csrfField() ?>
                    <!-- Username or Email Field -->
                    <div class="form-field">
                        <label for="username" class="field-label">
                            Email address or username
                            <span class="required-star">*</span>
                        </label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                placeholder="e.g. admin or student@courtiq.local" 
                                value="<?= e($usernameValue) ?>" 
                                required 
                                autocomplete="username"
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- Password Field with Toggle -->
                    <div class="form-field">
                        <label for="password" class="field-label">
                            Password
                            <span class="required-star">*</span>
                        </label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input password-input" 
                                placeholder="Enter your password" 
                                required 
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle-btn" id="passwordToggle" aria-label="Show password" title="Toggle password visibility">
                                <svg class="eye-show" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                                <svg class="eye-hide is-hidden" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                                    <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                    <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Meta Row: Remember Me & Back Link -->
                    <div class="form-meta-row">
                        <label class="remember-checkbox-label">
                            <input type="checkbox" name="remember" id="rememberMe" value="1">
                            <span class="custom-checkbox"></span>
                            <span class="checkbox-text">Remember Me</span>
                        </label>
                        <a href="<?= projectUrl('index.php') ?>" class="back-link">
                            &larr; Back to Home
                        </a>
                    </div>

                    <!-- Submit Button with Spinner -->
                    <button type="submit" id="loginSubmitBtn" class="btn btn-primary btn-block btn-lg">
                        <span class="btn-text">Sign In to CourtIQ</span>
                        <span class="btn-spinner is-hidden" id="loginSpinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="login-footer-notice">
                    <p>
                        User accounts are managed by authorized administrators. Registration and password recovery are processed through the campus hostel administration office.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= projectUrl('assets/js/login.js') ?>"></script>
</body>
</html>
