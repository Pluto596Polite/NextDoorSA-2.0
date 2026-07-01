<?php
declare(strict_types=1);
session_start();

require_once 'database.php';

header('Content-Type: application/json');

// Only a logged-in user can view their own order history.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    $connection = getDatabaseConnection();

    // Fetch this user's orders, newest first.
    $stmt = $connection->prepare(
        "SELECT id, order_number, invoice_number, payment_method, status, subtotal, shipping, vat, total, created_at
         FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    $orderIndex = [];
    while ($row = $result->fetch_assoc()) {
        $row['items'] = [];
        $orders[] = $row;
        $orderIndex[(int) $row['id']] = count($orders) - 1;
    }
    $stmt->close();

    // Attach the line items for every order in a single query.
    if (count($orderIndex) > 0) {
        $ids = array_keys($orderIndex);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $itemStmt = $connection->prepare(
            "SELECT order_id, title, image_url, price, quantity FROM order_items WHERE order_id IN ($placeholders)"
        );
        $itemStmt->bind_param($types, ...$ids);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        while ($item = $itemResult->fetch_assoc()) {
            $oid = (int) $item['order_id'];
            if (isset($orderIndex[$oid])) {
                $orders[$orderIndex[$oid]]['items'][] = $item;
            }
        }
        $itemStmt->close();
    }

    echo json_encode(['success' => true, 'data' => $orders]);

    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A server error occurred while fetching orders.']);
}
