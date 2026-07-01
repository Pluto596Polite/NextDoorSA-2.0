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

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$password  = (string) ($_POST['password'] ?? '');
$role      = trim($_POST['role'] ?? '');
$status    = trim($_POST['status'] ?? '');

if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please provide a valid name and email.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters.']);
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

$isAdmin = $role === 'Admin' ? 1 : 0;

try {
    $connection = getDatabaseConnection();

    // Reject duplicate email up front for a friendly message.
    $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'An account with that email already exists.']);
        $stmt->close();
        $connection->close();
        exit;
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $connection->prepare(
        "INSERT INTO users (first_name, last_name, email, phone, password_hash, role, status, is_admin)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssssssi", $firstName, $lastName, $email, $phone, $hash, $role, $status, $isAdmin);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User created successfully.', 'id' => $connection->insert_id]);
    } else {
        throw new Exception('Failed to create user.');
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A server error occurred. Please try again later.']);
}
