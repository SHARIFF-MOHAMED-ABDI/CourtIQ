<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Shared Header Component
 */

require_once __DIR__ . '/auth.php';

$currentUser = currentUser();
$pageTitle = $pageTitle ?? 'CourtIQ | Smart Hostel Management';
$activePage = $activePage ?? 'home';
$extraCss = $extraCss ?? [];
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CourtIQ is a web-based hostel management system designed to centralize and streamline student accommodation services.">
    <meta name="theme-color" content="#102A43">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
    <?php foreach ($extraCss as $cssFile): ?>
        <link rel="stylesheet" href="<?= projectUrl($cssFile) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
    <header class="site-header" id="siteHeader">
        <div class="container nav-wrap">
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

            <button class="mobile-toggle" aria-label="Toggle navigation menu" aria-expanded="false" id="mobileToggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <nav class="main-nav" id="mainNav" aria-label="Primary navigation">
                <a href="<?= projectUrl('index.php#home') ?>" class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>">Home</a>
                <a href="<?= projectUrl('index.php#about') ?>" class="nav-link">About</a>
                <a href="<?= projectUrl('index.php#features') ?>" class="nav-link">Features</a>
                <a href="<?= projectUrl('index.php#how-it-works') ?>" class="nav-link">How It Works</a>
                <a href="<?= projectUrl('index.php#roles') ?>" class="nav-link">User Roles</a>
                <a href="<?= projectUrl('index.php#contact') ?>" class="nav-link">Contact</a>
            </nav>

            <div class="nav-auth">
                <?php if ($currentUser): ?>
                    <a class="btn btn-primary btn-sm" href="<?= getRoleDashboardUrl() ?>">
                        <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        Dashboard
                    </a>
                <?php else: ?>
                    <a class="btn btn-primary btn-sm" href="<?= projectUrl('login.php') ?>">
                        <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
