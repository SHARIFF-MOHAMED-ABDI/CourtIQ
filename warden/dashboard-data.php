<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard_charts.php';
requireRole(['warden']);
require __DIR__ . '/partials/scope.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

echo json_encode(
    ['charts' => getWardenDashboardChartData(db(), $assignedHostelIds)],
    JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
