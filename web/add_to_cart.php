<?php
declare(strict_types=1);
session_start();

require_once 'database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to add items to your cart.']);
    exit;
}

$userId = $_SESSION['user_id'];
$listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($listingId <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid listing ID or quantity.']);
    exit;
}

try {
    $connection = getDatabaseConnection();

    // Verify the listing exists
    $stmt = $connection->prepare("SELECT id FROM listings WHERE id = ?");
    $stmt->bind_param("i", $listingId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Listing not found.']);
        $stmt->close();
        $connection->close();
        exit;
    }
    $stmt->close();

    // Insert or update cart
    $stmt = $connection->prepare("INSERT INTO cart (user_id, listing_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
    $stmt->bind_param("iii", $userId, $listingId, $quantity);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item added to cart successfully.']);
    } else {
        throw new Exception("Failed to add item to cart.");
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
