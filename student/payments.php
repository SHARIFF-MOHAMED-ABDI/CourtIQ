<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole(['student']);
$userId = (int)currentUser()['id'];
$stmt = db()->prepare('SELECT reference_no, amount, balance, status, payment_date, notes FROM payment_records WHERE user_id = ? ORDER BY payment_date DESC, id DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payments | CourtIQ</title><link rel="stylesheet" href="<?= projectUrl('assets/css/style.css') ?>"></head>
<body class="dashboard-layout"><?php require __DIR__ . '/partials/nav.php'; ?><main class="dashboard-content-main container">
    <section class="role-page-heading"><span class="dash-welcome-eyebrow">STUDENT SERVICES</span><h1>Payment history</h1><p>Review your accommodation payment records and outstanding balances.</p></section>
    <section class="visual-card"><div class="admin-section-heading"><div><h2>Recorded payments</h2><p><?= count($payments) ?> payment record(s)</p></div></div><div class="admin-table-wrap"><table class="dash-table admin-table"><thead><tr><th>Date</th><th>Reference</th><th>Amount</th><th>Balance</th><th>Status</th><th>Notes</th></tr></thead><tbody><?php foreach ($payments as $payment): ?><tr><td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td><td><?= e($payment['reference_no']) ?></td><td>KES <?= number_format((float)$payment['amount'], 2) ?></td><td>KES <?= number_format((float)$payment['balance'], 2) ?></td><td><?= e(str_replace('_', ' ', ucfirst($payment['status']))) ?></td><td><?= e($payment['notes'] ?: '—') ?></td></tr><?php endforeach; ?><?php if (!$payments): ?><tr><td colspan="6" class="admin-empty">No payments have been recorded for your account.</td></tr><?php endif; ?></tbody></table></div></section>
</main></body></html>
