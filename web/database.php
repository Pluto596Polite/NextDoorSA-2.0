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
        
        // Ensure profile_image_url column exists
        ensureProfileImageColumn($connection);

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

    // Create cart table
    $sqlCart = <<<SQL
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, listing_id)
);
SQL;
    $connection->query($sqlCart);
    
    // Add Demo Products if none exist
    insertDemoProducts($connection);
}

/**
 * Ensures the profile_image_url column exists in the users table.
 */
function ensureProfileImageColumn(mysqli $connection): void
{
    // Check if the column exists
    $result = $connection->query("SHOW COLUMNS FROM `users` LIKE 'profile_image_url'");
    if ($result && $result->num_rows === 0) {
        // Column does not exist, add it
        $connection->query("ALTER TABLE `users` ADD `profile_image_url` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`");
    }
}

/**
 * Inserts demo products into the database if the listings table is empty.
 */
function insertDemoProducts(mysqli $connection): void
{
    // Check if there are any listings
    $result = $connection->query("SELECT COUNT(*) as count FROM listings");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Create a 'System' user to own these demo products if one doesn't exist
        $sysUserResult = $connection->query("SELECT id FROM users WHERE email = 'system@nextdoorsa.com'");
        $sysUserId = null;
        
        if ($sysUserResult->num_rows == 0) {
            $hashedPassword = password_hash('system123', PASSWORD_DEFAULT);
            $connection->query("INSERT INTO users (first_name, last_name, email, password_hash) VALUES ('System', 'Admin', 'system@nextdoorsa.com', '$hashedPassword')");
            $sysUserId = $connection->insert_id;
        } else {
            $sysUserId = $sysUserResult->fetch_assoc()['id'];
        }

        // The 6 Demo Products
        $demoProducts = [
            // Ensure these IDs match the data-listing-id in ExploreProductsPage.html exactly!
            // Format: ID, user_id, title, description, price, category, status, image_url, views
            [1, $sysUserId, 'Mechanical Keyboard', 'Mechanical gaming keyboard with RGB lighting', 2000.00, 'Electronics', 'active', 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600', 120],
            [2, $sysUserId, 'Office Chair', 'Ergonomic office chair with lumbar support', 2800.00, 'Furniture', 'active', 'https://images.unsplash.com/photo-1505843490538-5133c6c7d0e1?w=600', 210],
            [3, $sysUserId, 'Wooden Bookshelf', 'Wooden bookshelf with 5 shelves', 1800.00, 'Furniture', 'active', 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=600', 150],
            [4, $sysUserId, 'Dining Table Set', 'Modern minimalist dining table set', 3500.00, 'Furniture', 'active', 'https://images.unsplash.com/photo-1604578762246-41134e37f9cc?w=600', 85],
            [5, $sysUserId, 'Brass Desk Lamp', 'Vintage brass desk lamp with adjustable arm', 1200.00, 'Home', 'active', 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=600', 45],
            [6, $sysUserId, 'Gaming Mouse', 'Wireless gaming mouse with precision sensor', 950.00, 'Electronics', 'active', 'https://images.unsplash.com/photo-1615663245857-ac1eeb536fcb?w=600', 300]
        ];

        $stmt = $connection->prepare("INSERT INTO listings (id, user_id, title, description, price, category, status, image_url, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($demoProducts as $product) {
            $stmt->bind_param("iissdsssi", ...$product);
            $stmt->execute();
        }
        $stmt->close();
    }
}
