<?php
declare(strict_types=1);

/**
 * Returns a MySQLi connection for the application database.
 */
function getDatabaseConnection(): mysqli
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = (int) (getenv('DB_PORT') ?: 3306);
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_NAME') ?: 'nextdoorsa';

    $connection = new mysqli($host, $username, $password, $database, $port);

    if ($connection->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
    }

    if (! $connection->set_charset('utf8mb4')) {
        throw new RuntimeException('Failed to set charset: ' . $connection->error);
    }

    return $connection;
}
