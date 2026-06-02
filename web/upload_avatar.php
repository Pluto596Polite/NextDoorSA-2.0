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
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

// Define upload directory
$uploadDir = 'uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Check if a file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or an upload error occurred.']);
    exit;
}

$file = $_FILES['avatar'];
$fileSize = $file['size'];
$fileTmpPath = $file['tmp_name'];
$fileName = $file['name'];

// Validate file size (max 5MB)
if ($fileSize > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File size exceeds the 5MB limit.']);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$fileType = mime_content_type($fileTmpPath);

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
    exit;
}

// Generate a unique file name
$extension = pathinfo($fileName, PATHINFO_EXTENSION);
$newFileName = uniqid('avatar_' . $userId . '_') . '.' . $extension;
$destination = $uploadDir . $newFileName;

// Move the file
if (move_uploaded_file($fileTmpPath, $destination)) {
    try {
        $connection = getDatabaseConnection();
        
        // Retrieve old avatar to delete it (optional but good practice)
        $stmt = $connection->prepare("SELECT profile_image_url FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
             $oldImage = $row['profile_image_url'];
             if ($oldImage && strpos($oldImage, $uploadDir) === 0 && file_exists($oldImage)) {
                 unlink($oldImage);
             }
        }
        $stmt->close();

        // Update database with new image URL
        $stmt = $connection->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
        $stmt->bind_param("si", $destination, $userId);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Avatar updated successfully!',
                'image_url' => $destination
            ]);
        } else {
            throw new Exception("Failed to update avatar in database.");
        }

        $stmt->close();
        $connection->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file. Check directory permissions.']);
}
