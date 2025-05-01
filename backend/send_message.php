<?php
session_start();
include '../config.php'; 

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $post_id = $_POST['post_id'];
    $message = trim($_POST['message']);
    $sender_id = $_SESSION['user_id'];

    if (empty($message)) {
        echo "Message cannot be empty.";
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo "Post not found.";
        exit;
    }

    $receiver_id = $post['user_id'];

    // Save the message
    $stmt = $pdo->prepare("INSERT INTO messages (post_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$post_id, $sender_id, $receiver_id, $message]);

    // Redirect back to the msg page
    header("Location: msg.php?post_id=$post_id");
    exit;
} else {
    echo "Invalid request.";
}
?>
