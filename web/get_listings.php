<?php
declare(strict_types=1);
session_start();

require_once 'database.php';

header('Content-Type: application/json');

try {
    $connection = getDatabaseConnection();
    
    // In a real application, you should get the user ID from the active session.
    // Ensure that you set $_SESSION['user_id'] when the user logs in.
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        // For demonstration/testing purposes, if no user is logged in, 
        // we fallback to retrieving listings for our test user.
        // In production, you would return an error like this:
        // echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        // exit;
        
        $checkUser = $connection->query("SELECT id FROM users WHERE email = 'test@example.com'");
        if ($checkUser && $checkUser->num_rows > 0) {
             $userId = $checkUser->fetch_assoc()['id'];
        } else {
             echo json_encode(['success' => true, 'data' => []]);
             exit;
        }
    }

    $stmt = $connection->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
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
