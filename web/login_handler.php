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

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please enter both email and password.']);
    exit;
}

try {
    $connection = getDatabaseConnection();

    $stmt = $connection->prepare("SELECT id, first_name, password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No account found with that email address.']);
        $stmt->close();
        $connection->close();
        exit;
    }

    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password_hash'])) {
        // Password is correct, start the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'];
        
        echo json_encode(['success' => true, 'message' => 'Login successful!']);
    } else {
        // Incorrect password
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Incorrect password. Please try again.']);
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A server error occurred. Please try again later.']);
}
