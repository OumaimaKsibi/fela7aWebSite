<?php
// Connect to the database
require './config.php'; // adjust path if needed
if (isset($_GET['id'])) {
    $postId = intval($_GET['id']);

    try {
        // First delete all messages linked to this post
        $stmt = $pdo->prepare("DELETE FROM messages WHERE post_id = ?");
        $stmt->execute([$postId]);

        // Then delete the post
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);

        header("Location: ../pages/profile.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
