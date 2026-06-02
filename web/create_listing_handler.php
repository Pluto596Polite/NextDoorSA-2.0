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
    echo json_encode(['success' => false, 'error' => 'You must be logged in to create a listing.']);
    exit;
}

$userId = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$category = trim($_POST['category'] ?? '');
$condition = trim($_POST['condition'] ?? '');

if (empty($title) || $price <= 0 || empty($category) || empty($condition)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}

// Handle file upload
$imageUrl = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/listings/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $fileName = uniqid('listing_' . $userId . '_') . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $destination = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $imageUrl = $destination;
    }
}

try {
    $connection = getDatabaseConnection();

    $stmt = $connection->prepare(
        "INSERT INTO listings (user_id, title, description, price, category, `condition`, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    // Note: `condition` is a reserved keyword, so it's wrapped in backticks.
    $stmt->bind_param("issdsss", $userId, $title, $description, $price, $category, $condition, $imageUrl);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Listing created successfully!']);
    } else {
        throw new Exception("Failed to create listing.");
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
