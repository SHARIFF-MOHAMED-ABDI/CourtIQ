<?php
$studentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
?>
<header class="dashboard-nav-bar role-topbar">
    <div class="container dashboard-nav-inner">
        <a href="<?= projectUrl('index.php') ?>" class="brand" aria-label="CourtIQ home"><span class="brand-icon-box"><svg class="brand-svg" viewBox="0 0 32 32" fill="none" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#102A43"/><path d="M16 6L7 13V25C7 25.5523 7.44772 26 8 26H13V19H19V26H24C24.5523 26 25 25 25 25V13L16 6Z" fill="#2563EB"/><path d="M16 11L11 15V23H13V18C13 17.45 13.45 17 14 17H18C18.55 17 19 17.45 19 18V23H21V15L16 11Z" fill="#0D9488"/></svg></span><span class="brand-text">Court<span>IQ</span></span></a>
        <nav class="role-nav-links" aria-label="Student navigation">
            <a class="<?= $studentPage === 'index.php' ? 'is-active' : '' ?>" href="index.php">Overview</a>
            <a class="<?= $studentPage === 'applications.php' ? 'is-active' : '' ?>" href="applications.php">Applications</a>
            <a class="<?= $studentPage === 'payments.php' ? 'is-active' : '' ?>" href="payments.php">Payments</a>
            <a class="<?= $studentPage === 'maintenance.php' ? 'is-active' : '' ?>" href="maintenance.php">Maintenance</a>
            <a class="<?= $studentPage === 'profile.php' ? 'is-active' : '' ?>" href="profile.php">Profile</a>
        </nav>
        <div class="user-badge-wrap"><div class="user-profile-pill"><span class="role-tag-badge badge-student">Student</span><span><?= e(currentUser()['full_name'] ?: currentUser()['username']) ?></span></div><a href="<?= projectUrl('logout.php') ?>" class="btn btn-secondary btn-sm">Logout</a></div>
    </div>
</header>
