<?php
declare(strict_types=1);
session_start();

require_once 'database.php';

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $connection = getDatabaseConnection();

    $stmt = $connection->prepare("SELECT id, first_name, last_name, email, phone, profile_image_url, address, city, province, postal_code FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found.']);
        $stmt->close();
        $connection->close();
        exit;
    }

    $user = $result->fetch_assoc();

    echo json_encode(['success' => true, 'data' => $user]);

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A server error occurred while fetching profile data.']);
}
