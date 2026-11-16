<?php
/**
 * Render a compact, responsive SVG line chart for dashboard summaries.
 *
 * @param array<string> $labels
 * @param array<string, array<int>> $series
 */
function renderLineChart(string $title, array $labels, array $series): string
{
    $width = 720;
    $height = 250;
    $left = 42;
    $right = 14;
    $top = 16;
    $bottom = 42;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;
    $values = [];
    foreach ($series as $points) {
        $values = array_merge($values, array_map('intval', $points));
    }
    $maxValue = max(1, (int)ceil((max($values ?: [0])) * 1.15));
    $colors = ['#2563eb', '#0d9488', '#d97706', '#7c3aed', '#db2777', '#0891b2'];
    $svg = '<svg class="line-chart-svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . e($title) . '">';

    for ($tick = 0; $tick <= 3; $tick++) {
        $value = (int)round($maxValue * $tick / 3);
        $y = $top + $plotHeight - ($plotHeight * $tick / 3);
        $svg .= '<line x1="' . $left . '" y1="' . round($y, 1) . '" x2="' . ($width - $right) . '" y2="' . round($y, 1) . '" class="line-chart-grid"/>';
        $svg .= '<text x="' . ($left - 10) . '" y="' . round($y + 4, 1) . '" text-anchor="end" class="line-chart-axis">' . $value . '</text>';
    }

    $labelCount = count($labels);
    foreach ($labels as $index => $label) {
        $x = $labelCount > 1 ? $left + ($plotWidth * $index / ($labelCount - 1)) : $left + $plotWidth / 2;
        $svg .= '<text x="' . round($x, 1) . '" y="' . ($height - 12) . '" text-anchor="middle" class="line-chart-axis">' . e($label) . '</text>';
    }

    $seriesIndex = 0;
    foreach ($series as $name => $points) {
        $color = $colors[$seriesIndex % count($colors)];
        $coordinates = [];
        foreach (array_values($points) as $index => $point) {
            $x = count($points) > 1 ? $left + ($plotWidth * $index / (count($points) - 1)) : $left + $plotWidth / 2;
            $y = $top + $plotHeight - ($plotHeight * max(0, (int)$point) / $maxValue);
            $coordinates[] = round($x, 1) . ',' . round($y, 1);
        }
        if ($coordinates) {
            $svg .= '<polyline points="' . implode(' ', $coordinates) . '" fill="none" stroke="' . $color . '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>';
            foreach ($coordinates as $coordinate) {
                [$x, $y] = explode(',', $coordinate);
                $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="' . $color . '" stroke="#fff" stroke-width="2"/>';
            }
        }
        $seriesIndex++;
    }
    $svg .= '</svg><div class="line-chart-legend">';

    $seriesIndex = 0;
    foreach ($series as $name => $points) {
        $svg .= '<span><i style="--chart-series-color:' . $colors[$seriesIndex % count($colors)] . '"></i>' . e((string)$name) . '</span>';
        $seriesIndex++;
    }

    return $svg . '</div>';
}

function getAdminDashboardChartData(mysqli $conn): array
{
    $periods = [];
    $labels = [];
    for ($offset = 5; $offset >= 0; $offset--) {
        $period = date('Y-m', strtotime('-' . $offset . ' months'));
        $periods[] = $period;
        $labels[] = date('M', strtotime($period . '-01'));
    }

    $trendStart = date('Y-m-01', strtotime('-5 months'));
    $activity = [
        'Warden sign-ins' => array_fill_keys($periods, 0),
        'Student sign-ins' => array_fill_keys($periods, 0),
        'Student registrations' => array_fill_keys($periods, 0),
        'Room allocations' => array_fill_keys($periods, 0),
        'Maintenance requests' => array_fill_keys($periods, 0),
        'Payments recorded' => array_fill_keys($periods, 0),
    ];

    $monthlyQueries = [
        "SELECT DATE_FORMAT(a.created_at, '%Y-%m') AS period, u.role AS series_name, COUNT(*) AS total
            FROM audit_log a JOIN users u ON u.id = a.user_id
            WHERE a.action = 'login' AND u.role IN ('warden', 'student') AND a.created_at >= ?
            GROUP BY period, u.role",
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS total
            FROM users WHERE role = 'student' AND created_at >= ? GROUP BY period",
        "SELECT DATE_FORMAT(allocated_at, '%Y-%m') AS period, COUNT(*) AS total
            FROM room_allocations WHERE allocated_at >= ? GROUP BY period",
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS total
            FROM maintenance_requests WHERE created_at >= ? GROUP BY period",
        "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS period, COUNT(*) AS total
            FROM payment_records WHERE payment_date >= ? GROUP BY period",
    ];
    $seriesNames = ['Student registrations', 'Room allocations', 'Maintenance requests', 'Payments recorded'];
    foreach ($monthlyQueries as $queryIndex => $sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Unable to prepare administrator chart data.');
        }
        $stmt->bind_param('s', $trendStart);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Unable to load administrator chart data.');
        }
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $period = $row['period'];
            if ($queryIndex === 0) {
                $seriesName = $row['series_name'] === 'warden' ? 'Warden sign-ins' : 'Student sign-ins';
            } else {
                $seriesName = $seriesNames[$queryIndex - 1];
            }
            if (isset($activity[$seriesName][$period])) {
                $activity[$seriesName][$period] = (int)$row['total'];
            }
        }
        $stmt->close();
    }

    $maintenance = [
        'Pending' => array_fill_keys($periods, 0),
        'In progress' => array_fill_keys($periods, 0),
        'Resolved' => array_fill_keys($periods, 0),
        'Rejected' => array_fill_keys($periods, 0),
    ];
    $stmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS period,
            SUM(status = 'pending') AS pending,
            SUM(status = 'in_progress') AS in_progress,
            SUM(status = 'resolved') AS resolved,
            SUM(status = 'rejected') AS rejected
        FROM maintenance_requests WHERE created_at >= ? GROUP BY period");
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare maintenance chart data.');
    }
    $stmt->bind_param('s', $trendStart);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Unable to load maintenance chart data.');
    }
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $period = $row['period'];
        if (isset($maintenance['Pending'][$period])) {
            $maintenance['Pending'][$period] = (int)$row['pending'];
            $maintenance['In progress'][$period] = (int)$row['in_progress'];
            $maintenance['Resolved'][$period] = (int)$row['resolved'];
            $maintenance['Rejected'][$period] = (int)$row['rejected'];
        }
    }
    $stmt->close();

    $occupancyLabels = [];
    $occupiedBeds = [];
    $availableBeds = [];
    $result = $conn->query("SELECT h.name, COALESCE(SUM(r.occupied), 0) AS occupied,
            COALESCE(SUM(CASE WHEN r.status <> 'maintenance' THEN GREATEST(r.capacity - r.occupied, 0) ELSE 0 END), 0) AS available
        FROM hostel_profiles h
        LEFT JOIN rooms r ON r.hostel_id = h.id
        GROUP BY h.id, h.name ORDER BY h.name");
    if (!$result) {
        throw new RuntimeException('Unable to load hostel occupancy chart data.');
    }
    while ($row = $result->fetch_assoc()) {
        $occupancyLabels[] = $row['name'];
        $occupiedBeds[] = (int)$row['occupied'];
        $availableBeds[] = (int)$row['available'];
    }

    $approvalStatuses = ['Pending', 'Approved', 'Rejected'];
    $approvalCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $result = $conn->query("SELECT approval_status, COUNT(*) AS total
        FROM users WHERE role = 'student' GROUP BY approval_status");
    if (!$result) {
        throw new RuntimeException('Unable to load account approval chart data.');
    }
    while ($row = $result->fetch_assoc()) {
        if (isset($approvalCounts[$row['approval_status']])) {
            $approvalCounts[$row['approval_status']] = (int)$row['total'];
        }
    }

    return [
        'activity' => [
            'title' => 'Monthly sign-ins and hostel activity',
            'labels' => $labels,
            'series' => array_map('array_values', $activity),
        ],
        'maintenance' => [
            'title' => 'Maintenance requests by current status',
            'labels' => $labels,
            'series' => array_map('array_values', $maintenance),
        ],
        'occupancy' => [
            'title' => 'Occupied and vacant beds by hostel',
            'labels' => $occupancyLabels,
            'series' => [
                'Occupied beds' => $occupiedBeds,
                'Vacant beds' => $availableBeds,
            ],
        ],
        'approvals' => [
            'title' => 'Student account approval status',
            'labels' => $approvalStatuses,
            'series' => ['Students' => array_values($approvalCounts)],
        ],
    ];
}

function getStudentDashboardChartData(mysqli $conn, int $userId): array
{
    $periods = [];
    $labels = [];
    for ($offset = 5; $offset >= 0; $offset--) {
        $period = date('Y-m', strtotime('-' . $offset . ' months'));
        $periods[] = $period;
        $labels[] = date('M', strtotime($period . '-01'));
    }

    $tickets = array_fill_keys($periods, 0);
    $payments = array_fill_keys($periods, 0);
    $trendStart = date('Y-m-01', strtotime('-5 months'));
    $loadMonthlyCounts = static function (string $table, string $dateColumn, array &$counts) use ($conn, $userId, $trendStart): void {
        $stmt = $conn->prepare("SELECT DATE_FORMAT($dateColumn, '%Y-%m') AS period, COUNT(*) AS total
            FROM $table WHERE user_id = ? AND $dateColumn >= ? GROUP BY period");
        if (!$stmt) {
            throw new RuntimeException('Unable to prepare student chart data.');
        }
        $stmt->bind_param('is', $userId, $trendStart);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Unable to load student chart data.');
        }
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            if (isset($counts[$row['period']])) {
                $counts[$row['period']] = (int)$row['total'];
            }
        }
        $stmt->close();
    };
    $loadMonthlyCounts('maintenance_requests', 'created_at', $tickets);
    $loadMonthlyCounts('payment_records', 'payment_date', $payments);

    return [
        'student-activity' => [
            'title' => 'Your monthly tickets and paid months',
            'labels' => $labels,
            'series' => [
                'Issues raised' => array_values($tickets),
                'Months paid' => array_map(static function (int $count): int {
                    return $count > 0 ? 1 : 0;
                }, array_values($payments)),
            ],
        ],
    ];
}

function getWardenDashboardChartData(mysqli $conn, array $hostelIds): array
{
    $periods = [];
    $labels = [];
    for ($offset = 5; $offset >= 0; $offset--) {
        $period = date('Y-m', strtotime('-' . $offset . ' months'));
        $periods[] = $period;
        $labels[] = date('M', strtotime($period . '-01'));
    }

    $raised = array_fill_keys($periods, 0);
    $resolved = array_fill_keys($periods, 0);
    $safeHostelIds = array_values(array_filter(array_map('intval', $hostelIds), static function (int $id): bool {
        return $id > 0;
    }));
    if ($safeHostelIds) {
        $trendStart = date('Y-m-01', strtotime('-5 months'));
        $hostelList = implode(',', $safeHostelIds);
        $stmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS period,
                COUNT(*) AS raised, SUM(status = 'resolved') AS resolved
            FROM maintenance_requests
            WHERE hostel_id IN ($hostelList) AND created_at >= ?
            GROUP BY period");
        if (!$stmt) {
            throw new RuntimeException('Unable to prepare warden chart data.');
        }
        $stmt->bind_param('s', $trendStart);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Unable to load warden chart data.');
        }
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            if (isset($raised[$row['period']])) {
                $raised[$row['period']] = (int)$row['raised'];
                $resolved[$row['period']] = (int)$row['resolved'];
            }
        }
        $stmt->close();
    }

    return [
        'warden-activity' => [
            'title' => 'Monthly hostel maintenance requests',
            'labels' => $labels,
            'series' => [
                'Tickets raised' => array_values($raised),
                'Tickets resolved' => array_values($resolved),
            ],
        ],
    ];
}
