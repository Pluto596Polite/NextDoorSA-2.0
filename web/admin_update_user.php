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

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$status = trim($_POST['status'] ?? '');

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user ID.']);
    exit;
}

if (!in_array($role, ['Admin', 'Seller', 'Buyer'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid role.']);
    exit;
}
if (!in_array($status, ['active', 'suspended'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status.']);
    exit;
}
if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please provide a valid name and email.']);
    exit;
}

$isAdmin = $role === 'Admin' ? 1 : 0;

try {
    $connection = getDatabaseConnection();

    // Ensure the email isn't taken by a different user.
    $stmt = $connection->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'That email is already in use by another account.']);
        $stmt->close();
        $connection->close();
        exit;
    }
    $stmt->close();

    $stmt = $connection->prepare(
        "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, status = ?, is_admin = ? WHERE id = ?"
    );
    $stmt->bind_param("sssssii", $firstName, $lastName, $email, $role, $status, $isAdmin, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
    } else {
        throw new Exception('Failed to update user.');
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
