<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json');

// Lightweight endpoint for the navbar: reports whether a user is logged in.
// Reads from the session only (set by login_handler.php / create_user.php), no DB hit.
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'name' => $_SESSION['user_name'] ?? 'Account',
        'isAdmin' => !empty($_SESSION['is_admin']),
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
