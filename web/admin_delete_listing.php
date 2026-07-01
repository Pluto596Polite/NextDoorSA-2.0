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

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required.']);
    exit;
}

$listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;

if ($listingId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid listing ID.']);
    exit;
}

try {
    $connection = getDatabaseConnection();

    $stmt = $connection->prepare("DELETE FROM listings WHERE id = ?");
    $stmt->bind_param("i", $listingId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Listing deleted.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Listing not found.']);
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
