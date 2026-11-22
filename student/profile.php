<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

$userId = (int)currentUser()['id'];
$stmt = db()->prepare('SELECT u.username, u.email, p.full_name, p.student_number, p.phone, p.course, p.year_of_study, p.emergency_contact FROM users u LEFT JOIN student_profiles p ON p.user_id = u.id WHERE u.id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user = currentUser();
$initial = strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout has-sidebar">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">PROFILE</span>
                <h1 class="dashboard-header-title">My Profile</h1>
                <p class="dashboard-header-subtitle">Keep your student contact details up to date.</p>
            </div>
        </header>

        <!-- Alerts -->
        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert--success" role="status">
                <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>Profile saved successfully.</span>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert--error" role="alert">
                <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span><?= e($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <!-- Profile Header Card -->
        <section class="visual-card" style="margin-bottom: 24px;" aria-labelledby="profile-info-heading">
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap; margin-bottom: 24px;">
                <div class="sidebar-footer-avatar" style="width: 72px; height: 72px; font-size: 1.75rem;"><?= e($initial) ?></div>
                <div>
                    <h2 id="profile-info-heading" class="visual-card-title" style="margin: 0;"><?= e($user['full_name'] ?: $user['username']) ?></h2>
                    <p style="color: var(--text-muted); margin: 4px 0 0;"><?= e($user['email']) ?></p>
                    <span class="status-badge status-badge--student" style="margin-top: 8px; display: inline-block;">Student</span>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div class="admin-mini-card">
                    <small>Username</small>
                    <strong><?= e($user['username']) ?></strong>
                    <span>Login identifier</span>
                </div>
                <div class="admin-mini-card">
                    <small>Email</small>
                    <strong><?= e($user['email']) ?></strong>
                    <span>Contact address</span>
                </div>
                <div class="admin-mini-card">
                    <small>Role</small>
                    <strong>Student</strong>
                    <span>Account type</span>
                </div>
                <div class="admin-mini-card">
                    <small>Status</small>
                    <strong><?= e(ucfirst($user['approval_status'] ?? 'approved')) ?></strong>
                    <span>Account status</span>
                </div>
            </div>
        </section>

        <!-- Edit Profile Form -->
        <section class="visual-card" aria-labelledby="edit-profile-heading">
            <h2 id="edit-profile-heading" class="visual-card-title">Edit Student Profile</h2>
            <p class="visual-card-subtitle">Update your personal and academic information.</p>
            
            <form method="post" action="actions.php" class="form-row" style="margin-top: 16px;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="profile_save">
                
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name <span aria-hidden="true">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-input" required maxlength="150" value="<?= e($profile['full_name'] ?? '') ?>" placeholder="Your full name">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" class="form-input" required value="<?= e($profile['email'] ?? '') ?>" placeholder="your@email.com">
                </div>
                
                <div class="form-group">
                    <label for="student_number" class="form-label">Student Number</label>
                    <input type="text" id="student_number" name="student_number" class="form-input" maxlength="50" value="<?= e($profile['student_number'] ?? '') ?>" placeholder="Student registration number">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-input" maxlength="30" value="<?= e($profile['phone'] ?? '') ?>" placeholder="Phone number">
                </div>
                
                <div class="form-group">
                    <label for="course" class="form-label">Course</label>
                    <input type="text" id="course" name="course" class="form-input" maxlength="150" value="<?= e($profile['course'] ?? '') ?>" placeholder="Course of study">
                </div>
                
                <div class="form-group">
                    <label for="year_of_study" class="form-label">Year of Study</label>
                    <input type="number" id="year_of_study" name="year_of_study" class="form-input" min="1" max="20" value="<?= e($profile['year_of_study'] ?? '') ?>" placeholder="Year">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="emergency_contact" class="form-label">Emergency Contact</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" class="form-input" maxlength="180" value="<?= e($profile['emergency_contact'] ?? '') ?>" placeholder="Emergency contact name and phone">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1; display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>