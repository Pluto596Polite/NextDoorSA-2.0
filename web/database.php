<?php
declare(strict_types=1);

/**
 * Returns a MySQLi connection for the application database.
 */
function getDatabaseConnection(): mysqli
{
    $host = '127.0.0.1';
    $port = 3306;
    $username = 'root';
    
    // --- IMPORTANT ---
    // PLEASE ENTER YOUR LOCAL MYSQL PASSWORD HERE
    $password = 'Adriaan123!';
    
    $database = 'nextdoorsa_db';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // Connect to MySQL server
        $connection = new mysqli($host, $username, $password, '', $port);
        
        // Create the database if it doesn't exist
        $connection->query("CREATE DATABASE IF NOT EXISTS `$database`");
        
        // Select the database
        $connection->select_db($database);
        
        $connection->set_charset('utf8mb4');

        // Automatically initialize tables if they don't exist
        initializeTables($connection);

        return $connection;
    } catch (mysqli_sql_exception $exception) {
        // Provide a clear error message
        throw new RuntimeException(
            "Database connection failed. Please check your credentials in `database.php`. The current username is '{$username}' and a password is " . 
            (empty($password) ? "not being used." : "being used.") .
            " Error: " . $exception->getMessage()
        );
    }
}

/**
 * Automatically creates necessary tables if they don't exist.
 */
function initializeTables(mysqli $connection): void
{
    // Create users table
    $sqlUsers = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SQL;
    $connection->query($sqlUsers);

    // Create listings table
    $sqlListings = <<<SQL
CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    status VARCHAR(50) DEFAULT 'active',
    image_url VARCHAR(255),
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;
    $connection->query($sqlListings);
}
