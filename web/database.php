<?php
declare(strict_types=1);

/**
 * Returns a MySQLi connection for the application database.
 *
 * Required environment variables:
 * - DB_HOST
 * - DB_USER
 * - DB_PASSWORD
 * - DB_NAME
 */
function getDatabaseConnection(): mysqli
{
    $host = trim((string) getenv('DB_HOST'));
    $port = (int) (getenv('DB_PORT') ?: 3306);
    $username = trim((string) getenv('DB_USER'));
    $password = (string) getenv('DB_PASSWORD');
    $database = trim((string) getenv('DB_NAME'));

    if ($host === '' || $username === '' || $database === '') {
        throw new RuntimeException('Missing required database environment variables. Set DB_HOST, DB_USER, and DB_NAME.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $connection = new mysqli($host, $username, $password, $database, $port);
        $connection->set_charset('utf8mb4');

        return $connection;
    } catch (mysqli_sql_exception $exception) {
        throw new RuntimeException('Database connection failed: ' . $exception->getMessage(), 0, $exception);
    }
}
