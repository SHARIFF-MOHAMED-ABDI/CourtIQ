<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);

$userId = (int)currentUser()['id'];
$conn = db();

$stmt = $conn->prepare('SELECT reference_no, amount, balance, status, payment_date, notes FROM payment_records WHERE user_id = ? ORDER BY payment_date DESC, id DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate summary stats
$totalPaid = 0;
$totalBalance = 0;
$paidMonths = 0;
$monthsWithPayment = [];
foreach ($payments as $payment) {
    $totalPaid += (float)$payment['amount'];
    $totalBalance = max($totalBalance, (float)$payment['balance']);
    $month = date('Y-m', strtotime($payment['payment_date']));
    if (!in_array($month, $monthsWithPayment)) {
        $monthsWithPayment[] = $month;
    }
}
$paidMonths = count($monthsWithPayment);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payments | CourtIQ</title>
    <link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>">
</head>
<body class="dashboard-layout has-sidebar">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="dashboard-content-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="dashboard-header-content">
                <span class="dashboard-header-eyebrow">PAYMENTS</span>
                <h1 class="dashboard-header-title">Payment History</h1>
                <p class="dashboard-header-subtitle">Review your accommodation payment records and outstanding balances.</p>
            </div>
        </header>

        <!-- Payment Summary Cards -->
        <div class="stats-grid" aria-label="Payment summary">
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Total Paid</span>
                    <div class="stat-card-icon stat-card-icon--green" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value">KES <?= number_format($totalPaid, 2) ?></div>
                <p class="stat-card-desc">Total amount paid to date</p>
            </article>
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Months Paid</span>
                    <div class="stat-card-icon stat-card-icon--yellow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value"><?= $paidMonths ?></div>
                <p class="stat-card-desc">Months with payment records</p>
            </article>
            <article class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Outstanding Balance</span>
                    <div class="stat-card-icon" style="background: var(--status-danger-bg); color: var(--status-danger);" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                </div>
                <div class="stat-card-value">KES <?= number_format($totalBalance, 2) ?></div>
                <p class="stat-card-desc">Current outstanding balance</p>
            </article>
        </div>

        <!-- Payment Records Table -->
        <section class="visual-card" aria-labelledby="payment-records-heading">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <h2 id="payment-records-heading" class="visual-card-title" style="margin: 0;">Recorded Payments</h2>
                <span class="admin-count"><?= count($payments) ?> record(s)</span>
            </div>
            
            <?php if ($payments): ?>
                <div class="table-wrap">
                    <table class="dash-table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Reference</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Balance</th>
                                <th scope="col">Status</th>
                                <th scope="col">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td><?= e($payment['reference_no']) ?></td>
                                    <td><strong>KES <?= number_format((float)$payment['amount'], 2) ?></strong></td>
                                    <td>KES <?= number_format((float)$payment['balance'], 2) ?></td>
                                    <td>
                                        <span class="status-badge status-badge--<?= e($payment['status']) ?>">
                                            <?= e(str_replace('_', ' ', ucfirst($payment['status']))) ?>
                                        </span>
                                    </td>
                                    <td><?= e($payment['notes'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <h3 class="empty-state-title">No payments recorded</h3>
                    <p class="empty-state-desc">Your payment history will appear here once payments are recorded.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>