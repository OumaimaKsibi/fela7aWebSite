<?php
session_start();
require_once '../backend/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../");
    exit();
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../");
    exit();
}

$senderId = $_SESSION['user']['id'];
$senderName = $_SESSION['user']['name'];

$currentUserId = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id = ? AND seen = 0");
$stmt->execute([$currentUserId]);
$unreadCount = $stmt->fetchColumn();
$receiverId = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : null;


// Get identifiers from URL
$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : null;
$receiverId = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : null;
$receiverName = '';
$postTitle = '';

// If we came from a post, get its details
if ($postId) {
    $stmt = $pdo->prepare("
        SELECT posts.title, posts.user_id AS receiver_id, users.first_name, users.last_name 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.id = ?
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("Post not found");
    }

    $receiverId = $post['receiver_id'];
    $receiverName = htmlspecialchars($post['first_name'] . ' ' . $post['last_name']);
    $postTitle = htmlspecialchars($post['title']);
} elseif ($receiverId) {
    // Came from sidebar/chat
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $receiverName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
    } else {
        die("User not found");
    }
} else {
    die("No conversation selected.");
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message) && $receiverId) {
        $stmt = $pdo->prepare("INSERT INTO messages (post_id, sender_id, receiver_id, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$postId, $senderId, $receiverId, $message]);
    }
}

// Load conversation messages
if ($receiverId) {
    $msgStmt = $pdo->prepare("
        SELECT messages.*, users.first_name, users.last_name 
        FROM messages 
        JOIN users ON messages.sender_id = users.id 
        WHERE 
            ((sender_id = :me AND receiver_id = :them) OR (sender_id = :them AND receiver_id = :me))
            AND (:postId IS NULL OR post_id = :postId)
        ORDER BY created_at ASC
    ");
    $msgStmt->execute([
        'me' => $senderId,
        'them' => $receiverId,
        'postId' => $postId,
    ]);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $messages = [];
}

// Fetch contact list (for sidebar)
$contactStmt = $pdo->prepare("
    SELECT DISTINCT 
        IF(sender_id = ?, receiver_id, sender_id) AS other_user_id,
        u.first_name, u.last_name
    FROM messages m
    JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
    WHERE m.sender_id = ? OR m.receiver_id = ?
");
$contactStmt->execute([$senderId, $senderId, $senderId, $senderId]);
$contacts = $contactStmt->fetchAll(PDO::FETCH_ASSOC);

// Last message preview
$lastMessages = [];
foreach ($contacts as $contact) {
    $otherUserId = $contact['other_user_id'];
    $lastMsgStmt = $pdo->prepare("
        SELECT content FROM messages 
        WHERE 
            (sender_id = :me AND receiver_id = :other) OR 
            (sender_id = :other AND receiver_id = :me)
        ORDER BY created_at DESC LIMIT 1
    ");
    $lastMsgStmt->execute(['me' => $senderId, 'other' => $otherUserId]);
    $lastMessages[$otherUserId] = $lastMsgStmt->fetchColumn();
}

$viewAll = isset($_GET['view']) && $_GET['view'] === 'all';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="../css/msg.css">
    <link rel="stylesheet" href="../css/style.css">

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        rel="stylesheet">

    <title>Message</title>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark custom-navbar py-3">
        <div class="container-fluid">
            <a class="navbar-brand me-5" href="#">
                <img src="../img/logo.png" alt="Logo" height="30">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto" style="margin-left: 58%;">
                    <li class="nav-item">
                        <a class="nav-link " href="../">Home</a>
                    </li>
                    

                    <li class="nav-item">
                        <a class="nav-link" href="./contact.php">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item">
                            <div class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle position-relative active"
                                    id="userDropdown"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <strong>Message</strong>

                                    <?php if ($unreadCount > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?= $unreadCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>

                                <ul class="dropdown-menu dropdown-menu-end" style="width: 361px;" aria-labelledby="userDropdown">


                                    <?php foreach ($contacts as $contact):
                                        $otherUserId = $contact['other_user_id'];
                                        $name = htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']);
                                        $lastMsg = htmlspecialchars($lastMessages[$otherUserId] ?? '');
                                    ?>
                                        <div class="message bg-light mb-2 p-2 rounded">
                                            <div class="message-header d-flex align-items-center">
                                                <img src="../img/avatar.jpeg" alt="User Profile" class="rounded-circle" width="20" height="20">
                                                <div class="ms-2">
                                                    <a href="msg.php?receiver_id=<?= $contact['other_user_id'] ?>" class="fw-bold text-decoration-none text-dark">
                                                        <?= $name ?>
                                                    </a>
                                                    <div class="text-muted" style="font-size: 13px;"><?= $lastMsg ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>

                    <div class="d-flex">
                        <?php if (isset($_SESSION['user'])): ?>
                            <div class="dropdown">
                                <a href="#"
                                    class="nav-link dropdown-toggle text-white"
                                    id="userDropdown"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <img src="../img/avatar.jpeg" alt="User Profile"
                                        class="rounded-circle me-2" width="24" height="24">
                                    <strong><?= htmlspecialchars($_SESSION['user']['name']) ?></strong>
                                </a>

                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">

                                    <li><a class="dropdown-item" href="./profile.php">Profile</a></li>

                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="?logout=1">Déconnexion</a></li>
                                </ul>
                            </div>


                        <?php else: ?>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#loginModal">
                                Login
                            </button>
                        <?php endif; ?>
                    </div>
                </ul>

            </div>
        </div>
    </nav>
    <div class="container-fluid mt-3">
        <div class="row">
            <!-- LEFT COLUMN: Contacts -->

            <div class="col-md-4">
                <div class="bg-secondary p-3 mb-3 rounded-4">
                    <h3>Message</h3>
                    <?php foreach ($contacts as $contact):
                        $otherUserId = $contact['other_user_id'];
                        $name = htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']);
                        $lastMsg = htmlspecialchars($lastMessages[$otherUserId] ?? '');
                    ?>
                        <div class="message bg-light mb-2 p-2 rounded">
                            <div class="message-header d-flex align-items-center">
                                <img src="../img/avatar.jpeg" alt="User Profile" class="rounded-circle" width="20" height="20">
                                <div class="ms-2">
                                    <a href="msg.php?receiver_id=<?= $contact['other_user_id'] ?>" class="fw-bold text-decoration-none text-dark">
                                        <?= $name ?>
                                    </a>
                                    <div class="text-muted" style="font-size: 13px;"><?= $lastMsg ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>




            <!-- RIGHT COLUMN: Chat Box -->
            <div class="col-md-8">
                <div class="container mt-5">
                    <div class="card shadow-sm">
                        <div class="chat-container p-3">
                            <h5 class="text-muted">Chat about: <?= $postTitle ?></h5>
                            <hr>

                            <?php foreach ($messages as $msg): ?>
                                <div class="message mb-3">
                                    <div class="message-header fw-bold">
                                        <img src="../img/avatar.jpeg" class="rounded-circle" width="30" height="30" alt="avatar">
                                        <?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?>
                                        <span class="text-muted small ms-2"><?= $msg['created_at'] ?></span>
                                    </div>
                                    <div class="message-content">
                                        <?= htmlspecialchars($msg['content']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Message reply box -->
                            <form method="post" class="mt-4">
                                <textarea name="message" class="form-control mb-2" placeholder="Type your reply..." required></textarea>
                                <button type="submit" class="btn btn-primary">Send</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>