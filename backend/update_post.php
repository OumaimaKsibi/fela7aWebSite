<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $title = $_POST['title'];
    $content = $_POST['content'];
    $status = $_POST['status'];

    // Check if a new image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExt, $allowedExts)) {
            $safeName = uniqid() . '.' . $fileExt;
            $uploadPath = __DIR__ . '/../uploads/' . $safeName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $imagePath = 'uploads/' . $safeName;

                // Update with image
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, status = ?, image = ? WHERE id = ?");
                $stmt->execute([$title, $content, $status, $imagePath, $id]);
            }
        }
    } else {
        // Update without image
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, status = ? WHERE id = ?");
        $stmt->execute([$title, $content, $status, $id]);
    }

    header("Location: ../pages/profile.php?updated=1");
    exit;
}
?>
