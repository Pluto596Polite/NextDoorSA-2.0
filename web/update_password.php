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
    echo json_encode(['success' => false, 'error' => 'Not authenticated. Please log in again.']);
    exit;
}

$userId          = (int) $_SESSION['user_id'];
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword     = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please fill in all password fields.']);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'New password and confirmation do not match.']);
    exit;
}

if ($newPassword === $currentPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'New password must be different from the current one.']);
    exit;
}

try {
    $connection = getDatabaseConnection();

    // Fetch the stored hash for the logged-in user.
    $stmt = $connection->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Account not found.']);
        $stmt->close();
        $connection->close();
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify the current password before allowing a change.
    if (!password_verify($currentPassword, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Your current password is incorrect.']);
        $connection->close();
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $connection->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $newHash, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
    } else {
        throw new Exception('Failed to update password.');
    }

    $stmt->close();
    $connection->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A server error occurred. Please try again later.']);
}
