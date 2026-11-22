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

require_once __DIR__ . '/postgres.php';

try {
    $db = new PgConnection(createPostgresPdo($dbConfig));
} catch (Throwable $t) {
    error_log('CourtIQ Database Exception: ' . $t->getMessage());
    http_response_code(503);
    die('The CourtIQ database service is temporarily unavailable. Verify the PostgreSQL connection settings.');
}

/**
 * Access the shared database instance.
 */
function db(): PgConnection
{
    global $db;
    return $db;
}
