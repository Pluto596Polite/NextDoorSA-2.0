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

    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status,
                   (SELECT COUNT(*) FROM listings l WHERE l.user_id = u.id) AS listings_count
            FROM users u
            ORDER BY u.id";
    $result = $connection->query($sql);

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $users]);
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
