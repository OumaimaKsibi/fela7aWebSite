<?php
session_start();
require_once 'config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    die('You must be logged in to submit a post.');
}

$userId = $_SESSION['user_id'];

// Handle file upload
$imagePath = null;

if (isset($_FILES['postImage']) && $_FILES['postImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/'; // absolute path
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $originalName = basename($_FILES["postImage"]["name"]);
    $safeName = time() . '_' . preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $originalName);
    $targetPath = $uploadDir . $safeName;

    if (move_uploaded_file($_FILES["postImage"]["tmp_name"], $targetPath)) {
        $imagePath = 'uploads/' . $safeName; // this goes into DB
    } else {
        die("Failed to move uploaded file.");
    }
} else {
    if ($_FILES['postImage']['error'] !== 4) { // 4 = no file uploaded
        die("File upload error code: " . $_FILES['postImage']['error']);
    }
}


// Insert post with user ID
$stmt = $pdo->prepare("INSERT INTO posts (title, content, image, status, user_id) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
    $_POST['postTitle'],
    $_POST['postContent'],
    $imagePath,
    $_POST['postStatus'],
    $userId
]);

header("Location: ../");
exit();
?>
