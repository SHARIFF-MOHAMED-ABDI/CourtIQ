<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Public Landing Page
 */

$pageTitle = 'CourtIQ | Smarter Hostel Management, Better Student Accommodation';
$activePage = 'home';
$bodyClass = 'landing-page';

require_once __DIR__ . '/includes/header.php';
?>

<main id="mainContent">
    <!-- Hero Section -->
    <section class="hero section-space" id="home">
        <div class="container hero-grid">
            <div class="hero-copy">
                <div class="badge-tag">
                    <span class="badge-dot"></span>
                    <span>Accommodation Management Platform</span>
                </div>
                <h1 class="hero-title">Smarter Hostel Management. Better Student Accommodation.</h1>
                <p class="hero-text">
                    Manage student accommodation services through one centralized platform. CourtIQ simplifies student registration, accommodation applications, room allocation, occupancy monitoring, rent records, and maintenance requests.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="<?= projectUrl('login.php') ?>">
                        Access CourtIQ
                        <svg class="btn-arrow" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                    <a class="btn btn-outline btn-lg" href="#features">
                        Explore Features
                    </a>
                </div>
                <ul class="hero-key-points">
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Centralized accommodation records
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Role-based administrative controls
                    </li>
                    <li>
                        <svg class="check-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Real-time occupancy tracking
                    </li>
                </ul>
            </div>

            <!-- Hero Interactive Dashboard Preview -->
            <div class="hero-preview-col">
                <div class="dashboard-preview-window" aria-label="CourtIQ sample dashboard demonstration">
                    <div class="preview-chrome">
                        <div class="window-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="window-title">CourtIQ &bull; Hostel Management Dashboard Preview</div>
                        <div class="window-badge">DEMO DATA</div>
                    </div>

                    <div class="preview-body">
                        <div class="preview-hub-header">
                            <div>
                                <span class="hub-eyebrow">RESIDENTIAL OPERATIONS HUB</span>
                                <h3 class="hub-title">Main Campus Accommodation</h3>
                            </div>
                            <span class="live-status-pill">
                                <span class="pulse-dot"></span>
                                Live System
                            </span>
                        </div>

                        <!-- 4 Key Stat Metrics -->
                        <div class="preview-metrics-grid">
                            <div class="metric-box accent-box">
                                <div class="metric-top">
                                    <span class="metric-label">Total Registered Students</span>
                                    <span class="demo-tag">SAMPLE</span>
                                </div>
                                <div class="metric-value">245</div>
                                <span class="metric-sub">Across 3 residential halls</span>
                            </div>

                            <div class="metric-box">
                                <div class="metric-top">
                                    <span class="metric-label">Room Occupancy</span>
                                    <span class="demo-tag">SAMPLE</span>
                                </div>
                                <div class="metric-value">78%</div>
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-fill" style="width: 78%;"></div>
                                </div>
                            </div>

                            <div class="metric-box">
                                <div class="metric-top">
                                    <span class="metric-label">Available Rooms</span>
                                    <span class="demo-tag">SAMPLE</span>
                                </div>
                                <div class="metric-value">18</div>
                                <span class="metric-sub text-teal">Ready for allocation</span>
                            </div>

                            <div class="metric-box">
                                <div class="metric-top">
                                    <span class="metric-label">Recorded Rent Info</span>
                                    <span class="demo-tag">SAMPLE</span>
                                </div>
                                <div class="metric-value">KES 1.42M</div>
                                <span class="metric-sub">Recorded in ledger</span>
                            </div>
                        </div>

                        <!-- Secondary Row: Maintenance & Activity -->
                        <div class="preview-details-grid">
                            <div class="preview-card-item">
                                <div class="preview-card-header">
                                    <span class="card-item-title">Pending Maintenance Requests</span>
                                    <span class="counter-badge">09 Active</span>
                                </div>
                                <div class="maintenance-breakdown">
                                    <div class="breakdown-row">
                                        <span class="cat-name">Electrical Services</span>
                                        <span class="cat-count">3 pending</span>
                                    </div>
                                    <div class="breakdown-row">
                                        <span class="cat-name">Plumbing & Water</span>
                                        <span class="cat-count">4 pending</span>
                                    </div>
                                    <div class="breakdown-row">
                                        <span class="cat-name">Carpentry & Locks</span>
                                        <span class="cat-count">2 pending</span>
                                    </div>
                                </div>
                            </div>

                            <div class="preview-card-item">
                                <div class="preview-card-header">
                                    <span class="card-item-title">Recent Activity</span>
                                    <span class="activity-status">Audited</span>
                                </div>
                                <ul class="activity-feed">
                                    <li>
                                        <span class="act-dot green"></span>
                                        <div class="act-details">
                                            <strong>Room A-104 allocated</strong>
                                            <small>Application approved &bull; Aberdare Court</small>
                                        </div>
                                    </li>
                                    <li>
                                        <span class="act-dot blue"></span>
                                        <div class="act-details">
                                            <strong>Rent Record Logged</strong>
                                            <small>REC-2026-0042 &bull; Term 1 recorded</small>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <details class="landing-content-menu">
        <summary>
            <span>
                <span class="section-tag">Explore CourtIQ</span>
                <strong>Platform details, workflows and support</strong>
            </span>
            <span class="landing-menu-hint">Open menu <span aria-hidden="true">↓</span></span>
        </summary>
        <div class="landing-content">

    <!-- About Section -->
    <section class="section-space light-bg" id="about">
        <div class="container">
            <div class="section-header-split">
                <div>
                    <span class="section-tag">About CourtIQ</span>
                    <h2 class="section-heading">One Platform. Organized Accommodation Management.</h2>
                </div>
                <div class="section-desc-lead">
                    <p>
                        CourtIQ is a web-based hostel management system designed to centralize and streamline student accommodation services. It replaces cumbersome paper trails and fragmented spreadsheets with a structured, reliable, and secure environment for students, wardens, and institutional administrators.
                    </p>
                </div>
            </div>

            <div class="problems-addressed-box">
                <h3 class="problems-title">Key Challenges CourtIQ Is Designed to Address</h3>
                <p class="problems-intro">
                    Traditional residential administration often suffers from administrative bottlenecks that impact student welfare and operational oversight. CourtIQ targets and resolves these primary pain points:
                </p>

                <div class="problems-grid">
                    <div class="problem-card">
                        <div class="problem-icon-wrap">
                            <svg class="problem-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h4>Disorganized Student Records</h4>
                        <p>Eliminates misplaced paper registration files and fragmented student profiles through a single searchable repository.</p>
                    </div>

                    <div class="problem-card">
                        <div class="problem-icon-wrap">
                            <svg class="problem-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h4>Coordination in Room Allocation</h4>
                        <p>Resolves conflicting assignments, double bookings, and manual allocation confusion with clear digital status tracking.</p>
                    </div>

                    <div class="problem-card">
                        <div class="problem-icon-wrap">
                            <svg class="problem-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h4>Occupancy Monitoring Bottlenecks</h4>
                        <p>Provides up-to-date visibility into hostel capacity, occupied rooms, and immediately available accommodation spaces.</p>
                    </div>

                    <div class="problem-card">
                        <div class="problem-icon-wrap">
                            <svg class="problem-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4>Fragmented Rent & Payment Records</h4>
                        <p>Maintains unified accommodation payment histories and outstanding balance tracking without disjointed spreadsheets.</p>
                    </div>

                    <div class="problem-card">
                        <div class="problem-icon-wrap">
                            <svg class="problem-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <h4>Untracked Maintenance Issues</h4>
                        <p>Prevents forgotten repairs with a transparent request lifecycle tracking issues from pending to in-progress and resolved.</p>
                    </div>

                    <div class="problem-card">
                        <div class="problem-icon-wrap">
                            <svg class="problem-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                            </svg>
                        </div>
                        <h4>Communication Breakdowns</h4>
                        <p>Bridges the communication gap between student residents and hostel administrators through role-specific portals.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Features Section -->
    <section class="section-space" id="features">
        <div class="container">
            <div class="section-center-heading">
                <span class="section-tag">Platform Capabilities</span>
                <h2 class="section-heading">Core Features Engineered for Excellence</h2>
                <p class="section-subtext">
                    CourtIQ integrates comprehensive modules tailored to streamline accommodation administration while preserving institutional rigor.
                </p>
            </div>

            <div class="features-grid">
                <!-- Feature 1 -->
                <article class="feature-card">
                    <div class="feature-icon-container icon-blue">
                        <svg class="feature-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Student Management</h3>
                    <p class="feature-desc">
                        Manage student profiles, registration details, and accommodation information in one secure system. Maintain accurate residential identities and demographic profiles.
                    </p>
                    <div class="feature-footer-tag">Role: Administrator &bull; Warden &bull; Student</div>
                </article>

                <!-- Feature 2 -->
                <article class="feature-card">
                    <div class="feature-icon-container icon-teal">
                        <svg class="feature-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Accommodation Applications</h3>
                    <p class="feature-desc">
                        Allow students to submit accommodation applications directly through the web platform. Enable administrators to review, approve, or adjust requests systematically.
                    </p>
                    <div class="feature-footer-tag">Digital Application Workflow</div>
                </article>

                <!-- Feature 3 -->
                <article class="feature-card">
                    <div class="feature-icon-container icon-navy">
                        <svg class="feature-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Room Allocation and Occupancy</h3>
                    <p class="feature-desc">
                        Manage room assignments, identify available accommodation spaces, and monitor occupancy records across hostels to maximize room utilization without overbooking.
                    </p>
                    <div class="feature-footer-tag">Live Occupancy Status</div>
                </article>

                <!-- Feature 4 -->
                <article class="feature-card">
                    <div class="feature-icon-container icon-gold">
                        <svg class="feature-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6m-5 4h5m-5 4h5M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Rent and Payment Records</h3>
                    <p class="feature-desc">
                        Maintain accommodation payment records, payment history, and outstanding balances where the necessary information is available.
                    </p>
                    <div class="feature-notice-pill">
                        <strong>Notice:</strong> CourtIQ records and tracks payment information. It does not process live M-Pesa, bank, or card payments.
                    </div>
                </article>

                <!-- Feature 5 -->
                <article class="feature-card">
                    <div class="feature-icon-container icon-red">
                        <svg class="feature-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Maintenance Request Management</h3>
                    <p class="feature-desc">
                        Allow students to report maintenance issues and monitor their progress transparently through pending, in-progress, and resolved statuses for rapid facility repairs.
                    </p>
                    <div class="feature-footer-tag">Lifecycle Tracking: Pending &rarr; In-Progress &rarr; Resolved</div>
                </article>

                <!-- Feature 6 -->
                <article class="feature-card">
                    <div class="feature-icon-container icon-indigo">
                        <svg class="feature-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Reporting and Administration</h3>
                    <p class="feature-desc">
                        Support authorized administrators in reviewing student records, hostel information, room allocations, occupancy rates, payment ledgers, and maintenance activities.
                    </p>
                    <div class="feature-footer-tag">Administrative Audit & Intelligence</div>
                </article>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="section-space light-bg" id="how-it-works">
        <div class="container">
            <div class="section-center-heading">
                <span class="section-tag">Operational Flow</span>
                <h2 class="section-heading">How CourtIQ Works</h2>
                <p class="section-subtext">
                    A streamlined three-step workflow engineered to make accommodation management simple, transparent, and structured.
                </p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-badge">Step 01</div>
                    <div class="step-icon-box">
                        <svg class="step-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <h3 class="step-title">Access the Platform</h3>
                    <p class="step-text">
                        Users sign in using assigned login credentials issued by the institution. The system verifies identity securely using PHP password verification.
                    </p>
                </div>

                <div class="step-card">
                    <div class="step-badge">Step 02</div>
                    <div class="step-icon-box">
                        <svg class="step-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="step-title">Manage Services by Role</h3>
                    <p class="step-text">
                        The platform routes each user directly to their authorized portal—enabling students to apply, wardens to oversee, and administrators to configure.
                    </p>
                </div>

                <div class="step-card">
                    <div class="step-badge">Step 03</div>
                    <div class="step-icon-box">
                        <svg class="step-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="step-title">Monitor & Update Records</h3>
                    <p class="step-text">
                        Monitor accommodation status in real-time, track maintenance resolution progress, inspect rent records, and ensure institutional accountability.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- User Roles Section -->
    <section class="section-space" id="roles">
        <div class="container">
            <div class="section-center-heading">
                <span class="section-tag">Role-Based Access Control</span>
                <h2 class="section-heading">Tailored Portals for Every Accommodation Stakeholder</h2>
                <p class="section-subtext">
                    CourtIQ enforces strict security boundaries ensuring users interact strictly within their authorized administrative scope.
                </p>
            </div>

            <div class="roles-grid">
                <!-- Administrator Role Card -->
                <div class="role-card card-admin">
                    <div class="role-header">
                        <div class="role-icon-box">
                            <svg class="role-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="role-pill pill-admin">Administrative Role</span>
                            <h3 class="role-title">Administrator</h3>
                        </div>
                    </div>
                    <p class="role-summary">
                        Responsible for comprehensive system governance, user permissions, and residential infrastructure configuration.
                    </p>
                    <ul class="role-features-list">
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Manage user accounts and role assignments.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Manage hostels, room types, and total capacity.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Oversee room allocation policies and approvals.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Manage payment records and balance histories.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Access system-wide reports and audits.
                        </li>
                    </ul>
                </div>

                <!-- Warden Role Card -->
                <div class="role-card card-warden">
                    <div class="role-header">
                        <div class="role-icon-box">
                            <svg class="role-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <span class="role-pill pill-warden">Operations Role</span>
                            <h3 class="role-title">Warden</h3>
                        </div>
                    </div>
                    <p class="role-summary">
                        Responsible for daily hostel monitoring, occupancy checks, and supervising facility maintenance tasks.
                    </p>
                    <ul class="role-features-list">
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Monitor room occupancy and residential check-ins.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Review relevant student accommodation information.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Follow up on maintenance and repair requests.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Update operational records where authorized.
                        </li>
                    </ul>
                </div>

                <!-- Student Role Card -->
                <div class="role-card card-student">
                    <div class="role-header">
                        <div class="role-icon-box">
                            <svg class="role-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="role-pill pill-student">Resident Role</span>
                            <h3 class="role-title">Student</h3>
                        </div>
                    </div>
                    <p class="role-summary">
                        Access personal accommodation records, submit applications, check assigned rooms, and file maintenance reports.
                    </p>
                    <ul class="role-features-list">
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Manage personal profile and contact information.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Submit accommodation applications online.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            View room allocation details and roommates.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Review rent and payment transaction records.
                        </li>
                        <li>
                            <svg class="role-bullet-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Submit and track maintenance requests in real-time.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact & Institutional Inquiries Section -->
    <section class="section-space light-bg" id="contact">
        <div class="container">
            <div class="contact-card-box">
                <div class="contact-content">
                    <span class="section-tag">Institutional Coordination</span>
                    <h2 class="contact-title">Accommodation Inquiries & Support</h2>
                    <p class="contact-desc">
                        CourtIQ is an institutional web-based platform. Student registration, room assignment revisions, and user credentials are administered exclusively by authorized university hostel personnel.
                    </p>
                    <div class="contact-info-list">
                        <div class="contact-item">
                            <span class="contact-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            </span>
                            <div>
                                <strong>Account Inquiries:</strong>
                                <span>Contact the hostel administrative office or assigned warden for credential allocation and profile verification.</span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <span class="contact-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884zM18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" clip-rule="evenodd"/></svg>
                            </span>
                            <div>
                                <strong>System Access:</strong>
                                <span>Ensure active enrollment status before submitting new term room allocation requests.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-action-panel">
                    <div class="contact-panel-card">
                        <h3>Already Registered?</h3>
                        <p>Sign in with your designated username or institutional email to access your role dashboard.</p>
                        <a href="<?= projectUrl('login.php') ?>" class="btn btn-primary btn-block">
                            Go to CourtIQ Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-inner">
                <div class="cta-text-wrap">
                    <span class="cta-tag">Ready to get started?</span>
                    <h2 class="cta-title">Simplify Your Hostel Management Experience.</h2>
                    <p class="cta-desc">
                        Bring student accommodation records and essential hostel management activities together with CourtIQ.
                    </p>
                </div>
                <div class="cta-button-wrap">
                    <a class="btn btn-accent btn-lg" href="<?= projectUrl('login.php') ?>">
                        Login to CourtIQ
                        <svg class="btn-arrow" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>
        </div>
        <?php $footerInLandingMenu = true; require_once __DIR__ . '/includes/footer.php'; ?>
    </details>
</main>
</body>
</html>
