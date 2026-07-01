<?php
declare(strict_types=1);

/**
 * Returns a MySQLi connection for the application database.
 */
function getDatabaseConnection(): mysqli
{
    // ============== INFINITYFREE DATABASE SETTINGS ==============
    //      REPLACE THE PLACEHOLDERS BELOW WITH YOUR      
    //      ACTUAL INFINITYFREE DATABASE CREDENTIALS.     
    // ==========================================================
    
    $host = 'sql203.infinityfree.com';      // e.g., 'sql123.epizy.com'
    $username = 'if0_42076321'; // e.g., 'epiz_12345678'
    $password = 'FiLFBHeyvwc'; // The password you set for the database
    $database = 'if0_42076321_nextdoorsa';  // e.g., 'epiz_12345678_mydb'
    $port = 3306;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // Connect to MySQL server
        $connection = new mysqli($host, $username, $password, $database, $port);
        
        $connection->set_charset('utf8mb4');

        // The tables should be created by importing your SQL file on InfinityFree,
        // but these checks can remain as they won't cause harm.
        initializeTables($connection);
        ensureProfileImageColumn($connection);
        ensureConditionColumn($connection);
        ensureProfileAddressColumns($connection);
        ensureUserManagementColumns($connection);
        ensureAdminAccount($connection);
        ensureOrderTables($connection);

        return $connection;
    } catch (mysqli_sql_exception $exception) {
        // Provide a clear error message for debugging on the live server
        throw new RuntimeException(
            "Database connection failed. Please check your credentials in `database.php`. Error: " . $exception->getMessage()
        );
    }
}

/**
 * NOTE FOR DEPLOYMENT: This function will only run if the tables are missing.
 * On InfinityFree, you should import your database schema manually via phpMyAdmin.
 * This function serves as a fallback.
 */
function initializeTables(mysqli $connection): void
{
    // Check if tables already exist to prevent re-inserting demo data
    $result = $connection->query("SHOW TABLES LIKE 'users'");
    if ($result && $result->num_rows > 0) {
        return; // Tables exist, do nothing.
    }

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
    `condition` VARCHAR(50),
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
    $result = $connection->query("SHOW COLUMNS FROM `users` LIKE 'profile_image_url'");
    if ($result && $result->num_rows === 0) {
        $connection->query("ALTER TABLE `users` ADD `profile_image_url` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`");
    }
}

/**
 * Ensures the condition column exists in the listings table.
 */
function ensureConditionColumn(mysqli $connection): void
{
    $result = $connection->query("SHOW COLUMNS FROM `listings` LIKE 'condition'");
    if ($result && $result->num_rows === 0) {
        $connection->query("ALTER TABLE `listings` ADD `condition` VARCHAR(50) NULL DEFAULT NULL AFTER `category`");
    }
}

/**
 * Ensures the optional address columns exist in the users table.
 */
function ensureProfileAddressColumns(mysqli $connection): void
{
    $columns = [
        'address'     => "ADD `address` VARCHAR(255) NULL DEFAULT NULL",
        'city'        => "ADD `city` VARCHAR(100) NULL DEFAULT NULL",
        'province'    => "ADD `province` VARCHAR(100) NULL DEFAULT NULL",
        'postal_code' => "ADD `postal_code` VARCHAR(20) NULL DEFAULT NULL",
    ];
    foreach ($columns as $name => $clause) {
        $result = $connection->query("SHOW COLUMNS FROM `users` LIKE '$name'");
        if ($result && $result->num_rows === 0) {
            $connection->query("ALTER TABLE `users` $clause");
        }
    }
}

/**
 * Ensures the columns the Admin dashboard manages exist:
 *   role   - Admin | Seller | Buyer (display/management label)
 *   status - active | suspended (account status)
 */
function ensureUserManagementColumns(mysqli $connection): void
{
    $columns = [
        'role'   => "ADD `role` VARCHAR(20) NOT NULL DEFAULT 'Buyer'",
        'status' => "ADD `status` VARCHAR(20) NOT NULL DEFAULT 'active'",
    ];
    foreach ($columns as $name => $clause) {
        $result = $connection->query("SHOW COLUMNS FROM `users` LIKE '$name'");
        if ($result && $result->num_rows === 0) {
            $connection->query("ALTER TABLE `users` $clause");
        }
    }
}

/**
 * Ensures an is_admin column exists and a default admin account is present.
 * Admin status is now determined server-side instead of via a client-side
 * credential check that exposed the password in the page source.
 */
function ensureAdminAccount(mysqli $connection): void
{
    $result = $connection->query("SHOW COLUMNS FROM `users` LIKE 'is_admin'");
    if ($result && $result->num_rows === 0) {
        $connection->query("ALTER TABLE `users` ADD `is_admin` TINYINT(1) NOT NULL DEFAULT 0");
    }

    $adminEmail = 'admin@nextdoor.com';
    $check = $connection->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $adminEmail);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if (!$exists) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $connection->prepare(
            "INSERT INTO users (first_name, last_name, email, password_hash, is_admin, role, status) VALUES ('Admin', 'User', ?, ?, 1, 'Admin', 'active')"
        );
        $stmt->bind_param("ss", $adminEmail, $hash);
        $stmt->execute();
        $stmt->close();
    } else {
        // Make sure the existing admin account keeps its admin flag and role.
        $stmt = $connection->prepare("UPDATE users SET is_admin = 1, role = 'Admin' WHERE email = ?");
        $stmt->bind_param("s", $adminEmail);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Ensures the orders + order_items tables exist so each purchase can be
 * persisted against the buying user for the Profile "Order history" popup.
 */
function ensureOrderTables(mysqli $connection): void
{
    $sqlOrders = <<<SQL
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL,
    invoice_number VARCHAR(50),
    payment_method VARCHAR(100),
    payment_ref VARCHAR(100),
    status VARCHAR(50) NOT NULL DEFAULT 'Paid',
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
    shipping DECIMAL(10, 2) NOT NULL DEFAULT 0,
    vat DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;
    $connection->query($sqlOrders);

    $sqlOrderItems = <<<SQL
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    image_url VARCHAR(500),
    price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
SQL;
    $connection->query($sqlOrderItems);
}

/**
 * Inserts demo products into the database if the listings table is empty.
 */
function insertDemoProducts(mysqli $connection): void
{
    $result = $connection->query("SELECT COUNT(*) as count FROM listings");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $sysUserResult = $connection->query("SELECT id FROM users WHERE email = 'system@nextdoorsa.com'");
        $sysUserId = null;
        
        if ($sysUserResult->num_rows == 0) {
            $hashedPassword = password_hash('system123', PASSWORD_DEFAULT);
            $connection->query("INSERT INTO users (first_name, last_name, email, password_hash) VALUES ('System', 'Admin', 'system@nextdoorsa.com', '$hashedPassword')");
            $sysUserId = $connection->insert_id;
        } else {
            $sysUserId = $sysUserResult->fetch_assoc()['id'];
        }

        $demoProducts = [
            [1, $sysUserId, 'Mechanical Keyboard', 'Mechanical gaming keyboard with RGB lighting', 2000.00, 'Electronics', 'active', 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600', 120],
            [2, $sysUserId, 'Office Chair', 'Ergonomic office chair with lumbar support', 2800.00, 'Furniture', 'active', 'https://images.unsplash.com/photo-1505843490538-5133c6c7d0e1?w=600', 210],
            [3, $sysUserId, 'Wooden Bookshelf', 'Wooden bookshelf with 5 shelves', 1800.00, 'Furniture', 'active', 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=600', 150],
            [4, $sysUserId, 'Dining Table Set', 'Modern minimalist dining table set', 3500.00, 'Furniture', 'active', 'https://images.unsplash.com/photo-1604578762246-41134e37f9cc?w=600', 85],
            [5, $sysUserId, 'Brass Desk Lamp', 'Vintage brass desk lamp with adjustable arm', 1200.00, 'Home', 'active', 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=600', 45]
        ];

        $stmt = $connection->prepare("INSERT INTO listings (id, user_id, title, description, price, category, status, image_url, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($demoProducts as $product) {
            $stmt->bind_param("iissdsssi", ...$product);
            $stmt->execute();
        }
        $stmt->close();
    }
}