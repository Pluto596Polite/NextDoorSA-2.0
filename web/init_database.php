<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'nextdoorsa';

$connection = new mysqli($host, $username, $password, '', $port);

if ($connection->connect_error) {
    exit('Database server connection failed: ' . $connection->connect_error . PHP_EOL);
}

if (! $connection->set_charset('utf8mb4')) {
    exit('Failed to set charset: ' . $connection->error . PHP_EOL);
}

$createDatabaseSql = sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    $connection->real_escape_string($database)
);

if (! $connection->query($createDatabaseSql)) {
    exit('Failed to create database: ' . $connection->error . PHP_EOL);
}

if (! $connection->select_db($database)) {
    exit('Failed to select database: ' . $connection->error . PHP_EOL);
}

$createUsersTableSql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
SQL;

if (! $connection->query($createUsersTableSql)) {
    exit('Failed to create users table: ' . $connection->error . PHP_EOL);
}

echo 'Database setup complete.' . PHP_EOL;
