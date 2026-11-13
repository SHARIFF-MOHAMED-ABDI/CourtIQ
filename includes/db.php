<?php
/**
 * CourtIQ: A Web-Based Hostel Management System
 * Database Connection Module
 */

$dbConfigFile = __DIR__ . '/../config/database.php';
if (!file_exists($dbConfigFile)) {
    http_response_code(500);
    exit('Database configuration file is missing.');
}

$dbConfig = require $dbConfigFile;

mysqli_report(MYSQLI_REPORT_OFF);

try {
    $db = @new mysqli(
        $dbConfig['host'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database'],
        (int)$dbConfig['port']
    );

    if ($db->connect_errno) {
        error_log('CourtIQ Database Connection Error [' . $db->connect_errno . ']: ' . $db->connect_error);
        http_response_code(503);
        die('The CourtIQ database service is temporarily unavailable. Please verify MySQL service status in XAMPP.');
    }

    if (!empty($dbConfig['charset'])) {
        $db->set_charset($dbConfig['charset']);
    }
} catch (Throwable $t) {
    error_log('CourtIQ Database Exception: ' . $t->getMessage());
    http_response_code(503);
    die('The CourtIQ database service is temporarily unavailable.');
}

/**
 * Access the shared database instance.
 */
function db(): mysqli
{
    global $db;
    return $db;
}
