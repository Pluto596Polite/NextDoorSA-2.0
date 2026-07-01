<?php
declare(strict_types=1);
session_start();

require_once 'database.php';

header('Content-Type: application/json');

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required.']);
    exit;
}

try {
    $connection = getDatabaseConnection();

    $sql = "SELECT l.id, l.title, l.price, l.category, l.status,
                   TRIM(CONCAT(u.first_name, ' ', u.last_name)) AS seller_name
            FROM listings l
            JOIN users u ON l.user_id = u.id
            ORDER BY l.created_at DESC";
    $result = $connection->query($sql);

    $listings = [];
    while ($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $listings]);
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
