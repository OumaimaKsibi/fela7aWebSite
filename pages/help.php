<?php
session_start();
require_once '../backend/config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$statusText = 'Need Help';
$badge = 'badge bg-danger';
$bg = 'bg-danger';


$senderId = isset($_SESSION['user']['id'])? : null;
$senderName = isset($_SESSION['user']['name'])?: null;
$userId=isset($_SESSION['user']['id'])? : null;
$currentUserId = isset($_SESSION['user']['id'])? : null;

$stmt = $pdo->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id = ? AND seen = 0");
$stmt->execute([$currentUserId]);
$unreadCount = $stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT posts.*, users.first_name, users.last_name 
                       FROM posts 
                       JOIN users ON posts.user_id = users.id 
                       WHERE posts.status = 'help'
                       ORDER BY posts.created_at DESC");
$stmt->execute();

$posts = $stmt->fetchAll();



$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ?");
$stmt->execute([$userId]);
$userPosts = $stmt->fetchAll();


$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : null;
$receiverId = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : null;
$receiverName = '';
$postTitle = '';
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




$countpost = $pdo->query("SELECT status, COUNT(*) as count FROM posts GROUP BY status");
$counts = [
    'help' => 0,
    'donation' => 0,
    'sale' => 0
];

$total = 0;
while ($row = $countpost->fetch(PDO::FETCH_ASSOC)) {
    $status = $row['status'];
    $counts[$status] = $row['count'];
    $total += $row['count'];
}
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

    <link rel="stylesheet" href="../css/style.css">

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        rel="stylesheet">

    <title>Need Help</title>
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
                        <a class="nav-link active " href="../">Home</a>
                    </li>
                    

                    <li class="nav-item">
                        <a class="nav-link" href="./contact.php">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item">
                            <div class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle position-relative"
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

                                <ul class="dropdown-menu dropdown-menu-end" style=" width: 361px;" aria-labelledby="userDropdown">


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
                                    class="nav-link dropdown-toggle text-white active"
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
            <!-- 1 Column left -->

            <div class="col-md-3">
                <div class="bg-secondary p-3 mb-3 rounded-4 ">
                    <a href="../" class="sans_dec">
                        <p class="text-white">All (<?= $total ?>)</p>
                    </a>
                </div>
                <div class="bg-danger p-3 mb-3 rounded-4 active">
                    <a href="./help.php" class="sans_dec ">
                        <p class="text-white">Help Needed (<?= $counts['help'] ?>)</p>
                    </a>
                </div>
                <div class="bg-success p-3 mb-3 rounded-4">
                    <a href="./donation.php" class="sans_dec">
                        <p class="text-white">Donation (<?= $counts['donation'] ?>)</p>
                    </a>
                </div>
                <div class="bg-primary p-3 mb-3 rounded-4">
                    <a href="./sale.php" class="sans_dec">
                        <p class="text-white">For Sale (<?= $counts['sale'] ?>)</p>
                    </a>
                </div>
                <div class="bg-secondary p-3">
                    <video width="100%" height="auto" autoplay loop muted>
                        <source src="../img/nature.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
            <!-- 2 Column center -->
            <div class="col-md-6">
                <div class="container mt-5 ">
                    <div
                        class="d-flex align-items-center mb-3 m-4 bg-white rounded-4">
                        <a href="./profile.php" class="m-3">
                            <img src="../img/avatar.jpeg"
                                alt="User Profile"
                                class="rounded-circle" width="50"
                                height="50">
                        </a>
                        <div class="post rounded-4" id="openModalTrigger">
                            <a
                                class="mb-2 text-muted sans_dec m-4 fw-medium">
                                Create a post for donation or help or sale?
                            </a>
                        </div>
                    </div>
                </div>
                <div class="container mt-5">
                    <h2>Help Posts</h2>

                    <?php foreach ($posts as $post): ?>
                        <?php
                        $statusText = 'Need Help';
                        $badge = 'badge bg-danger';
                        $bg = 'bg-danger';

                        $postDate = new DateTime($post['created_at']);
                        $postDate->setTimezone(new DateTimeZone('Africa/Tunis'));
                        $formattedDate = $postDate->format('F j, Y, H:i:s');

                        $userName = htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); // Get user's full name
                        $image = '';
                        if (!empty($post['image'])) {
                            $image = "<img src='../" . htmlspecialchars($post['image']) . "' class='img-fluid mb-3'>";
                        }
                        ?>

                        <div class='card mb-3 shadow-sm'>
                            <div class='card-header <?= $bg ?> text-white'>
                                <h5><?= $statusText ?>: <?= htmlspecialchars($post['title']) ?></h5>
                            </div>
                            <div class='card-body'>
                                <div class='d-flex align-items-center mb-3'>
                                    <img src='../img/avatar.jpeg' alt='User Profile' class='rounded-circle' width='50' height='50'>
                                    <div class='ml-3'>
                                        <strong><?= $userName ?></strong>
                                        <p class='mb-0 text-muted'>Posted on: <?= $formattedDate ?></p>
                                    </div>
                                </div>
                                <p class='card-text'><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                <?= $image ?>
                                <span class='<?= $badge ?>'></span>
                                <div class='mt-3'>
                                    <a href='../pages/msg.php?post_id=<?= $post['id'] ?>&title=<?= urlencode($post['title']) ?>' style='color: black;'>
                                        <i class='fas fa-comment-alt' style='font-size:15px;color:rgb(60, 60, 60)'></i>
                                        Contact me
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- 3 Column right  -->

            <div class="col-md-3">
                <div class="bg-secondary p-3">
                    <p class="text-white p-3"> The jewelry of our
                        country</p>
                    <img src="../img/ads3.jpeg" class="rounded-4">
                </div>
                <div class="bg-secondary p-3 ">
                    <img src="../img/ads1.jpeg" class="rounded-4">
                </div>
                <div class="bg-secondary p-3">
                    <img src="../img/ads2.jpeg" class="rounded-4">
                </div>
            </div>
        </div>
    </div>

  <!-- Modal poste-->
  <div id="customModal" class="modal" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Post</h5>
                    <button type="button" class="btn-close"
                        id="closeButton"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="card-body">
                        <form action="../backend/submit-post.php" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="postTitle" class="text-black">Post Title:</label>
                                <input type="text" class="form-control" id="postTitle" name="postTitle" placeholder="Enter the title" required>
                            </div>

                            <div class="form-group">
                                <label for="postContent" class="text-black">Post Content:</label>
                                <textarea class="form-control" id="postContent" name="postContent" rows="5" placeholder="Write your content here" required></textarea>
                            </div>
                            <br>
                            <div class="form-group">
                                <label for="postImage" class="text-black">Upload Image:</label>
                                <input type="file" class="form-control-file" id="postImage" name="postImage" accept="image/*">
                            </div>

                            <div class="form-group">
                                <label for="postStatus" class="text-black">Post Status:</label>
                                <div class="form-check">
                                    <input class="form-check-input help" type="radio" name="postStatus" id="statusHelp" value="help" required>
                                    <label class="form-check-label text-danger" for="statusHelp">Help</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input donation" type="radio" name="postStatus" id="statusDonation" value="donation">
                                    <label class="form-check-label text-success" for="statusDonation">Donation</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input sale" type="radio" name="postStatus" id="statusSale" value="sale">
                                    <label class="form-check-label text-primary" for="statusSale">Sale</label>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-block" id="closeButton">Close</button>
                                <button type="submit" class="btn btn-success btn-block">Submit</button>
                            </div>
                        </form>

                    </div>

                </div>

            </div>
        </div>
    </div>
    <script>
        const modal = document.getElementById('customModal');
        const modalTrigger = document.getElementById('openModalTrigger');
        const closeButton = document.getElementById('closeButton');
        const closeIcon = document.getElementById('closeModal');

        // Open the modal
        modalTrigger.addEventListener('click', () => {
            modal.style.display = 'block';
            document.querySelector('.modal-overlay').style.display = 'block';
        });

        // Close the modal
        const closeModal = () => {
            modal.style.display = 'none';
            const overlay = document.querySelector('.modal-overlay');
            if (overlay) overlay.remove();
        };

        closeButton.addEventListener('click', closeModal);
        closeIcon.addEventListener('click', closeModal);

        // Close the modal when clicking outside the content
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        });
    </script>
</body>

</html>