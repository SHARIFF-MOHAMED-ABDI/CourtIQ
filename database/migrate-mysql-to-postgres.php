<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!extension_loaded('pdo_mysql') || !extension_loaded('pdo_pgsql')) {
    fwrite(STDERR, "This migration requires the PDO MySQL and PostgreSQL extensions.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/postgres.php';

$tables = [
    'users',
    'hostel_profiles',
    'rooms',
    'accommodation_applications',
    'maintenance_requests',
    'maintenance_request_history',
    'payment_records',
    'student_profiles',
    'warden_assignments',
    'room_allocations',
    'notifications',
    'audit_log',
];

try {
    $sourceHost = getenv('COURTIQ_SOURCE_DB_HOST') ?: '127.0.0.1';
    $sourcePort = (int)(getenv('COURTIQ_SOURCE_DB_PORT') ?: 3306);
    $sourceName = getenv('COURTIQ_SOURCE_DB_NAME') ?: 'courtiq';
    $sourceUser = getenv('COURTIQ_SOURCE_DB_USER') ?: 'root';
    $sourcePassword = getenv('COURTIQ_SOURCE_DB_PASSWORD') ?: '';

    $source = new PDO(
        'mysql:host=' . $sourceHost . ';port=' . $sourcePort . ';dbname=' . $sourceName . ';charset=utf8mb4',
        $sourceUser,
        $sourcePassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $targetConfig = require __DIR__ . '/../config/database.php';
    $target = createPostgresPdo($targetConfig);

    $schema = file_get_contents(__DIR__ . '/courtiq.postgres.sql');
    if ($schema === false) {
        throw new RuntimeException('Unable to read the PostgreSQL schema file.');
    }
    $target->exec($schema);

    foreach ($tables as $table) {
        $tableName = '"' . $table . '"';
        $count = (int)$target->query('SELECT COUNT(*) FROM ' . $tableName)->fetchColumn();
        if ($count !== 0) {
            throw new RuntimeException('The PostgreSQL database is not empty; refusing to merge or overwrite records.');
        }
    }

    $targetSchema = (string)$target->query('SELECT current_schema()')->fetchColumn();
    $target->beginTransaction();
    $target->exec('SET CONSTRAINTS ALL DEFERRED');

    $migratedCounts = [];
    foreach ($tables as $table) {
        $sourceColumns = $source->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN, 0);
        $columnQuery = $target->prepare(
            "SELECT column_name FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = ?
               AND is_generated = 'NEVER'
             ORDER BY ordinal_position"
        );
        $columnQuery->execute([$table]);
        $targetColumns = $columnQuery->fetchAll(PDO::FETCH_COLUMN);
        $columns = array_values(array_intersect($targetColumns, $sourceColumns));
        if (!$columns) {
            throw new RuntimeException('No matching columns were found for table ' . $table . '.');
        }

        $quotedColumns = array_map(static function (string $column): string {
            return '"' . str_replace('"', '""', $column) . '"';
        }, $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $insert = $target->prepare(
            'INSERT INTO "' . $table . '" (' . implode(', ', $quotedColumns) . ') VALUES (' . $placeholders . ')'
        );
        $sourceRows = $source->query('SELECT * FROM `' . $table . '` ORDER BY `id`');
        $migratedCounts[$table] = 0;
        while ($row = $sourceRows->fetch()) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = $row[$column];
            }
            $insert->execute($values);
            $migratedCounts[$table]++;
        }
        $sourceRows->closeCursor();
    }

    foreach ($tables as $table) {
        $relation = $target->quote($targetSchema . '.' . $table);
        $target->query(
            "SELECT setval(pg_get_serial_sequence($relation, 'id'), COALESCE(MAX(id), 1), COUNT(*) > 0)
             FROM \"$table\""
        );
    }

    $target->commit();
    foreach ($migratedCounts as $table => $count) {
        fwrite(STDOUT, $table . ': ' . $count . " rows\n");
    }
    fwrite(STDOUT, "MySQL-to-PostgreSQL data migration completed.\n");
} catch (Throwable $exception) {
    if (isset($target) && $target instanceof PDO && $target->inTransaction()) {
        $target->rollBack();
    }
    fwrite(STDERR, "Migration failed: " . $exception->getMessage() . "\n");
    exit(1);
}
