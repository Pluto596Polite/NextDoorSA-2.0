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
    echo json_encode(['success' => false, 'error' => 'You must be logged in to delete a listing.']);
    exit;
}

$userId = $_SESSION['user_id'];
$listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;

if ($listingId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid listing ID.']);
    exit;
}

try {
    $connection = getDatabaseConnection();

    // First, verify the user owns the listing
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

    $listing = $result->fetch_assoc();
    if ($listing['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You do not have permission to delete this listing.']);
        $stmt->close();
        $connection->close();
        exit;
    }
    $stmt->close();

    // Proceed with deletion
    $stmt = $connection->prepare("DELETE FROM listings WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $listingId, $userId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Listing deleted successfully.']);
        } else {
            throw new Exception("Deletion failed, or listing was already deleted.");
        }
    } else {
        throw new Exception("Failed to execute deletion.");
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
