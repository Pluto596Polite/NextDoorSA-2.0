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
$title = trim($_POST['title'] ?? '');
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
if (!in_array($status, ['active', 'sold'], true)) {
    $status = 'active';
}

try {
    $connection = getDatabaseConnection();

    $stmt = $connection->prepare(
        "UPDATE listings SET title = ?, price = ?, category = ?, status = ? WHERE id = ?"
    );
    $stmt->bind_param("sdssi", $title, $price, $category, $status, $listingId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows >= 0) {
            echo json_encode(['success' => true, 'message' => 'Listing updated.']);
        } else {
            throw new Exception('Failed to update listing.');
        }
    } else {
        throw new Exception('Failed to update listing.');
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
