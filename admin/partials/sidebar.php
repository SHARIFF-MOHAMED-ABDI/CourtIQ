<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$currentPage = str_replace('.php', '', $currentPage);
$currentPage = $currentPage ?: 'index';
$user = currentUser();
$initial = strtoupper(substr($user['username'], 0, 1));
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

<!-- Admin Sidebar -->
<aside class="student-sidebar" id="studentSidebar" aria-label="Admin navigation">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="<?= projectUrl('admin/index.php') ?>" class="sidebar-brand" aria-label="CourtIQ Admin Portal Home">
            <span class="brand-icon-box">
                <svg class="brand-svg" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect width="32" height="32" rx="8" fill="rgba(255,255,255,0.15)"/>
                    <path d="M16 6L7 13V25C7 25.5523 7.44772 26 8 26H13V19H19V26H24C24.5523 26 25 25.5523 25 25V13L16 6Z" fill="#2563EB"/>
                    <path d="M16 11L11 15V23H13V18C13 17.4477 13.4477 17 14 17H18C18.5523 17 19 17.4477 19 18V23H21V15L16 11Z" fill="#0D9488"/>
                </svg>
            </span>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-title">COURTIQ</span>
                <span class="sidebar-brand-subtitle">Administration</span>
            </div>
        </a>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="<?= projectUrl('admin/index.php') ?>" 
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
            <span class="sidebar-nav-text">Overview</span>
        </a>

        <a href="<?= projectUrl('admin/residents.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'residents' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'residents' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </span>
            <span class="sidebar-nav-text">Residents</span>
        </a>

        <a href="<?= projectUrl('admin/maintenance.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'maintenance' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'maintenance' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
            </span>
            <span class="sidebar-nav-text">Maintenance</span>
        </a>

        <a href="<?= projectUrl('admin/manage.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'manage' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'manage' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </span>
            <span class="sidebar-nav-text">Settings & Records</span>
        </a>

        <a href="<?= projectUrl('admin/reports.php') ?>" 
           class="sidebar-nav-item <?= $currentPage === 'reports' ? 'is-active' : '' ?>" 
           aria-current="<?= $currentPage === 'reports' ? 'page' : 'false' ?>">
            <span class="sidebar-nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </span>
            <span class="sidebar-nav-text">Reports</span>
        </a>
    </nav>

    <!-- Sidebar Footer - User Profile -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-avatar" aria-hidden="true"><?= e($initial) ?></div>
        <div class="sidebar-footer-info">
            <span class="sidebar-footer-name"><?= e($user['username']) ?></span>
            <span class="sidebar-footer-role">Administrator</span>
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
    
    sidebar.querySelectorAll('.sidebar-nav-item').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                closeSidebar();
            }
        });
    });
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
</script>