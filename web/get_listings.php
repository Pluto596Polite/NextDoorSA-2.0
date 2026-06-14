<?php
declare(strict_types=1);
session_start();

require_once 'database.php';

header('Content-Type: application/json');

// Determine if we are fetching for a specific user or all listings
$scope = $_GET['scope'] ?? 'all'; // 'all' or 'user'

try {
    $connection = getDatabaseConnection();
    
    if ($scope === 'user') {
        // Fetch listings for the logged-in user
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }
        $userId = $_SESSION['user_id'];
        $stmt = $connection->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
    } else {
        // Fetch all active listings for the Explore page, EXCLUDING demo products (user_id = 1)
        // Ensure that the System admin's products are not shown if the user is exploring
        $stmt = $connection->prepare("SELECT * FROM listings WHERE status = 'active' ORDER BY created_at DESC");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $listings = [];
    while ($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $listings]);

    $stmt->close();
    $connection->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}