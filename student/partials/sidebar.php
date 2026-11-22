<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$currentPage = str_replace('.php', '', $currentPage);
$currentPage = $currentPage ?: 'index';
$user = currentUser();
$initial = strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1));
?>
<!-- Mobile Sidebar Toggle -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation menu" aria-expanded="false">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

<!-- Student Sidebar -->
<aside class="student-sidebar" id="studentSidebar" aria-label="Student navigation">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="<?= projectUrl('student/index.php') ?>" class="sidebar-brand" aria-label="CourtIQ Student Portal Home">
            <span class="brand-icon-box">
                <svg class="brand-svg" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect width="32" height="32" rx="8" fill="rgba(255,255,255,0.15)"/>
                    <path d="M16 6L7 13V25C7 25.5523 7.44772 26 8 26H13V19H19V26H24C24.5523 26 25 25.5523 25 25V13L16 6Z" fill="#2563EB"/>
                    <path d="M16 11L11 15V23H13V18C13 17.4477 13.4477 17 14 17H18C18.5523 17 19 17.4477 19 18V23H21V15L16 11Z" fill="#0D9488"/>
                </svg>
            </span>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-title">COURTIQ</span>
                <span class="sidebar-brand-subtitle">Student Portal</span>
            </div>
        </a>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="<?= projectUrl('student/index.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'index' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'index' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                </svg>
            </span>
            <span class="sidebar-nav-text">Dashboard</span>
        </a>

        <a href="<?= projectUrl('student/rooms.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'rooms' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'rooms' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </span>
            <span class="sidebar-nav-text">Browse Rooms</span>
        </a>

        <a href="<?= projectUrl('student/bookings.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'bookings' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'bookings' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </span>
            <span class="sidebar-nav-text">My Bookings</span>
        </a>

        <a href="<?= projectUrl('student/payments.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'payments' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'payments' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </span>
            <span class="sidebar-nav-text">Payments</span>
        </a>

        <a href="<?= projectUrl('student/maintenance.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'maintenance' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'maintenance' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
            </span>
            <span class="sidebar-nav-text">Maintenance</span>
        </a>

        <a href="<?= projectUrl('student/moveout.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'moveout' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'moveout' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 19H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2"></path>
                    <polyline points="5 15 9 15 9 11 13 7 17 11 17 15 21 15 21 19 5 19 5 7 9 7 9 3"></polyline>
                </svg>
            </span>
            <span class="sidebar-nav-text">Move Out</span>
        </a>
    </nav>

    <!-- Sidebar Footer - User Profile -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-avatar" aria-hidden="true"><?= e($initial) ?></div>
        <div class="sidebar-footer-info">
            <span class="sidebar-footer-name"><?= e($user['full_name'] ?: $user['username']) ?></span>
            <span class="sidebar-footer-role">Student</span>
        </div>
        <a href="<?= projectUrl('logout.php') ?>" class="sidebar-footer-logout" aria-label="Sign out">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="18" height="18">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </a>
    </div>
</aside>

<script>
(function() {
    const sidebar = document.getElementById('studentSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');
    
    if (!sidebar || !overlay || !toggle) return;
    
    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('visible');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('visible');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }
    
    toggle.addEventListener('click', function() {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    
    overlay.addEventListener('click', closeSidebar);
    
    // Close sidebar when clicking a nav link on mobile
    sidebar.querySelectorAll('.sidebar-nav-item').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                closeSidebar();
            }
        });
    });
    
    // Handle resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });
    
    // Keyboard accessibility
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
</script>