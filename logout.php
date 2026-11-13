<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Logout Handler
 */

require_once __DIR__ . '/includes/auth.php';

logoutUser();

header('Location: ' . projectUrl('login.php?logged_out=1'));
exit();
