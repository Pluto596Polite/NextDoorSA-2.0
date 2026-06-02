<?php
declare(strict_types=1);
session_start();

require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $connection = getDatabaseConnection();

    $stmt = $connection->prepare(
        "SELECT c.listing_id, c.quantity, l.title, l.price, l.image_url, l.user_id as seller_id
         FROM cart c
         JOIN listings l ON c.listing_id = l.id
         WHERE c.user_id = ?"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $cartItems = [];
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $cartItems]);

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
