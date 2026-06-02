<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("This script is for CLI deployment only.\n");
}

$schemaFile = __DIR__ . '/schema.sql';

if (! is_file($schemaFile)) {
    exit('Missing schema file: ' . $schemaFile . PHP_EOL);
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
$username = getenv('DB_USER') ?: '';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: '';

if ($username === '' || $database === '') {
    exit('DB_USER and DB_NAME must be set for deployment.' . PHP_EOL);
}

$connection = new mysqli($host, $username, $password, $database, $port);

if ($connection->connect_error) {
    exit('Database server connection failed: ' . $connection->connect_error . PHP_EOL);
}

if (! $connection->set_charset('utf8mb4')) {
    exit('Failed to set charset: ' . $connection->error . PHP_EOL);
}

if (! $connection->ping()) {
    exit('Database connection is not available.' . PHP_EOL);
}

$schemaSql = file_get_contents($schemaFile);

if ($schemaSql === false || trim($schemaSql) === '') {
    exit('Failed to read schema file: ' . $schemaFile . PHP_EOL);
}

if (! $connection->multi_query($schemaSql)) {
    exit('Failed to deploy schema: ' . $connection->error . PHP_EOL);
}

do {
    if ($result = $connection->store_result()) {
        $result->free();
    }

    if ($connection->errno) {
        exit('Failed to deploy schema: ' . $connection->error . PHP_EOL);
    }
} while ($connection->more_results() && $connection->next_result());

if ($connection->errno) {
    exit('Failed to deploy schema: ' . $connection->error . PHP_EOL);
}

echo 'Database schema deployed successfully.' . PHP_EOL;
