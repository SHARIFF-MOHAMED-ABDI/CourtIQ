<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Shared Footer Component
 */
?>
    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand-col">
                <a href="<?= projectUrl('index.php') ?>" class="brand brand-footer" aria-label="CourtIQ Home">
                    <span class="brand-icon-box">
                        <svg class="brand-svg" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect width="32" height="32" rx="8" fill="#102A43"/>
                            <path d="M16 6L7 13V25C7 25.5523 7.44772 26 8 26H13V19H19V26H24C24.5523 26 25 25.5523 25 25V13L16 6Z" fill="#2563EB"/>
                            <path d="M16 11L11 15V23H13V18C13 17.4477 13.4477 17 14 17H18C18.5523 17 19 17.4477 19 18V23H21V15L16 11Z" fill="#0D9488"/>
                        </svg>
                    </span>
                    <span class="brand-text">Court<span>IQ</span></span>
                </a>
                <p class="footer-desc">
                    A web-based hostel management system designed to centralize and streamline student accommodation services, room allocation, occupancy monitoring, rent records, and maintenance requests.
                </p>
                <div class="footer-badge">
                    <span class="status-indicator"></span>
                    <span>System Operational &bull; Version 1.0</span>
                </div>
            </div>

            <div class="footer-links-col">
                <h4 class="footer-heading">Quick Links</h4>
                <ul class="footer-menu">
                    <li><a href="<?= projectUrl('index.php#home') ?>">Home</a></li>
                    <li><a href="<?= projectUrl('index.php#about') ?>">About CourtIQ</a></li>
                    <li><a href="<?= projectUrl('index.php#features') ?>">Core Features</a></li>
                    <li><a href="<?= projectUrl('index.php#how-it-works') ?>">How It Works</a></li>
                    <li><a href="<?= projectUrl('index.php#roles') ?>">User Roles</a></li>
                    <li><a href="<?= projectUrl('index.php#contact') ?>">Contact & Support</a></li>
                </ul>
            </div>

            <div class="footer-links-col">
                <h4 class="footer-heading">Core Modules</h4>
                <ul class="footer-menu">
                    <li><a href="<?= projectUrl('index.php#features') ?>">Student Management</a></li>
                    <li><a href="<?= projectUrl('index.php#features') ?>">Accommodation Applications</a></li>
                    <li><a href="<?= projectUrl('index.php#features') ?>">Room Allocation & Occupancy</a></li>
                    <li><a href="<?= projectUrl('index.php#features') ?>">Rent & Payment Records</a></li>
                    <li><a href="<?= projectUrl('index.php#features') ?>">Maintenance Management</a></li>
                    <li><a href="<?= projectUrl('index.php#features') ?>">Reporting & Administration</a></li>
                </ul>
            </div>

            <div class="footer-links-col">
                <h4 class="footer-heading">Access Portal</h4>
                <p class="footer-subtext">Access restricted to authorized institutional users with registered credentials.</p>
                <div class="footer-auth-box">
                    <a href="<?= projectUrl('login.php') ?>" class="btn btn-primary btn-sm btn-block">
                        <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Sign In to CourtIQ
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container footer-bottom-inner">
                <p>&copy; 2026 CourtIQ. All rights reserved.</p>
                <div class="footer-notes">
                    <span>Hostel Accommodation Management System</span>
                    <span>&bull;</span>
                    <span>Role-Based Access Control</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="<?= projectUrl('assets/js/main.js') ?>"></script>
<?php if (!($footerInLandingMenu ?? false)): ?>
</body>
</html>
<?php endif; ?>
