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

// Only a logged-in user can have an order saved to their history.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

// The PaymentGateway posts the order as a JSON body.
$raw = file_get_contents('php://input');
$order = json_decode($raw, true);

if (!is_array($order)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order payload.']);
    exit;
}

$orderNumber   = trim((string) ($order['orderNumber'] ?? ''));
$invoiceNumber = trim((string) ($order['invoiceNumber'] ?? ''));
$paymentMethod = trim((string) ($order['paymentMethod'] ?? ''));
$paymentRef    = trim((string) ($order['paystackRef'] ?? ''));
$status        = trim((string) ($order['status'] ?? 'Paid'));
$subtotal      = (float) ($order['subtotal'] ?? 0);
$shipping      = (float) ($order['shipping'] ?? 0);
$vat           = (float) ($order['vat'] ?? 0);
$total         = (float) ($order['total'] ?? 0);
$items         = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];

if ($orderNumber === '' || count($items) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order is missing a number or items.']);
    exit;
}

try {
    $connection = getDatabaseConnection();
    $connection->begin_transaction();

    $stmt = $connection->prepare(
        "INSERT INTO orders (user_id, order_number, invoice_number, payment_method, payment_ref, status, subtotal, shipping, vat, total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "isssssdddd",
        $userId, $orderNumber, $invoiceNumber, $paymentMethod, $paymentRef, $status,
        $subtotal, $shipping, $vat, $total
    );
    $stmt->execute();
    $orderId = $connection->insert_id;
    $stmt->close();

    $itemStmt = $connection->prepare(
        "INSERT INTO order_items (order_id, title, image_url, price, quantity) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title    = trim((string) ($item['title'] ?? $item['name'] ?? 'Item'));
        $imageUrl = trim((string) ($item['image_url'] ?? $item['image'] ?? $item['img'] ?? ''));
        $price    = (float) ($item['price'] ?? 0);
        $quantity = max(1, (int) ($item['quantity'] ?? $item['qty'] ?? 1));
        $itemStmt->bind_param("issdi", $orderId, $title, $imageUrl, $price, $quantity);
        $itemStmt->execute();
    }
    $itemStmt->close();

    $connection->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber]);

    $connection->close();

} catch (Exception $e) {
    if (isset($connection) && $connection instanceof mysqli) {
        $connection->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save the order.']);
}
