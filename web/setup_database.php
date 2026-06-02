<?php
declare(strict_types=1);

require_once 'database.php';

try {
    $connection = getDatabaseConnection();

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

    if ($connection->query($sqlUsers) === TRUE) {
        echo "Table 'users' created successfully or already exists.\n";
    } else {
        echo "Error creating table users: " . $connection->error . "\n";
    }

    // Create listings table
    // Important for InfinityFree/MySQL: Use PRIMARY KEY (id) correctly
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

    if ($connection->query($sqlListings) === TRUE) {
        echo "Table 'listings' created successfully or already exists.\n";
        
        // Let's create a test user if one doesn't exist
        $checkUser = $connection->query("SELECT id FROM users WHERE email = 'test@example.com'");
        $userId = null;
        
        if ($checkUser->num_rows == 0) {
            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
            $stmtUser = $connection->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
            $firstName = "Test";
            $lastName = "User";
            $email = "test@example.com";
            $phone = "1234567890";
            $stmtUser->bind_param("sssss", $firstName, $lastName, $email, $phone, $hashedPassword);
            $stmtUser->execute();
            $userId = $connection->insert_id;
            $stmtUser->close();
            echo "Created test user.\n";
        } else {
            $row = $checkUser->fetch_assoc();
            $userId = $row['id'];
        }

        // Insert sample data for the test user
        if ($userId) {
            $stmt = $connection->prepare("INSERT INTO listings (user_id, title, description, price, category, status, image_url, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $listings = [
                [$userId, 'Mechanical Gaming Keyboard', 'Mechanical gaming keyboard with RGB lighting', 2000.00, 'Electronics', 'active', 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600', 48],
                [$userId, 'Ergonomic Office Chair', 'Ergonomic office chair with lumbar support', 2800.00, 'Furniture', 'active', 'https://images.unsplash.com/photo-1505843490538-5133c6c7d0e1?w=600', 32],
                [$userId, 'Wooden Bookshelf', 'Wooden bookshelf with 5 shelves', 1800.00, 'Furniture', 'sold', 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=600', 65]
            ];

            // Simple check to avoid duplicating data every time the script is run
            $checkListings = $connection->query("SELECT id FROM listings WHERE user_id = $userId LIMIT 1");
            if ($checkListings->num_rows == 0) {
                foreach ($listings as $listing) {
                    $stmt->bind_param("issdsssi", ...$listing);
                    $stmt->execute();
                }
                echo "Sample data inserted successfully.\n";
            } else {
                echo "Sample data already exists for this user.\n";
            }
            $stmt->close();
        }

    } else {
        echo "Error creating table listings: " . $connection->error . "\n";
    }

    $connection->close();
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}
