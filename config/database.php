<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Database Configuration
 */

return [
    'host'     => getenv('COURTIQ_DB_HOST') ?: '127.0.0.1',
    'username' => getenv('COURTIQ_DB_USER') ?: 'root',
    'password' => getenv('COURTIQ_DB_PASSWORD') ?: '',
    'database' => getenv('COURTIQ_DB_NAME') ?: 'courtiq',
    'port'     => (int)(getenv('COURTIQ_DB_PORT') ?: 3306),
    'charset'  => 'utf8mb4',
];
