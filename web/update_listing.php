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
    echo json_encode(['success' => false, 'error' => 'You must be logged in to edit a listing.']);
    exit;
}

$userId = $_SESSION['user_id'];
$listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$category = trim($_POST['category'] ?? '');
$status = trim($_POST['status'] ?? 'active');

if ($listingId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid listing ID.']);
    exit;
}

if ($title === '' || $price <= 0 || $category === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}

// Only allow known status values.
if (!in_array($status, ['active', 'sold'], true)) {
    $status = 'active';
}

try {
    $connection = getDatabaseConnection();

    // Verify the listing exists and is owned by the current user.
    $stmt = $connection->prepare("SELECT user_id FROM listings WHERE id = ?");
    $stmt->bind_param("i", $listingId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Listing not found.']);
        $stmt->close();
        $connection->close();
        exit;
    }

    $owner = $result->fetch_assoc();
    if ((int)$owner['user_id'] !== (int)$userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You do not have permission to edit this listing.']);
        $stmt->close();
        $connection->close();
        exit;
    }
    $stmt->close();

    $stmt = $connection->prepare(
        "UPDATE listings SET title = ?, description = ?, price = ?, category = ?, status = ? WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ssdssii", $title, $description, $price, $category, $status, $listingId, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Listing updated successfully.']);
    } else {
        throw new Exception("Failed to update listing.");
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
