<?php

return [
    'url'      => getenv('DATABASE_URL') ?: getenv('COURTIQ_DATABASE_URL') ?: '',
    'host'     => getenv('COURTIQ_DB_HOST') ?: '127.0.0.1',
    'username' => getenv('COURTIQ_DB_USER') ?: 'postgres',
    'password' => getenv('COURTIQ_DB_PASSWORD') ?: '',
    'database' => getenv('COURTIQ_DB_NAME') ?: 'courtiq',
    'port'     => (int)(getenv('COURTIQ_DB_PORT') ?: 5432),
    'sslmode'  => getenv('COURTIQ_DB_SSLMODE') ?: '',
];