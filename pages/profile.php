<?php
session_start();
require_once '../backend/config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ?");
$stmt->execute([$userId]);
$userPosts = $stmt->fetchAll();
$senderId = isset($_SESSION['user']['id'])? : null;
$senderName = isset($_SESSION['user']['name'])?: null;
$receiverId = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : null;


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

    <title>Profile</title>

    <style>
        .form-label {
            color: black !important;
        }
    </style>
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
                                <a href="#"
                                    class="nav-link dropdown-toggle "
                                    id="userDropdown"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">

                                    <strong>Message</strong>
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
            <div class="col-3 text-center">
                <div class="container mt-3 ">
                    <div class="bg-white rounded-2  ">
                        <img src="../img/avatar.jpeg"
                            alt="User Profile"
                            class="m-3">
                        <br>

                        <?php if (isset($_SESSION['user'])): ?>

                            <strong><?= htmlspecialchars($_SESSION['user']['name']) ?></strong>
                        <?php endif; ?>
                        <br>
                        <p>peasant(farmers)</p>
                        <br>

                        <br>
                    </div>
                </div>
            </div>
            <!-- 2 Column center -->
            <div class="col-md-8">
                <div class="container mt-5 ">
                    <?php if (isset($_GET['deleted']) || isset($_GET['updated'])): ?>
                        <div class="alert alert-<?= isset($_GET['deleted']) ? 'danger' : 'success' ?>" id="statusAlert">
                            <?= isset($_GET['deleted']) ? 'Post deleted successfully.' : 'Post updated successfully.' ?>
                        </div>

                        <script>
                            setTimeout(function() {
                                const url = new URL(window.location.href);
                                url.searchParams.delete('deleted');
                                url.searchParams.delete('updated');
                                window.history.replaceState({}, document.title, url.pathname);

                                const alert = document.getElementById('statusAlert');
                                if (alert) {
                                    alert.style.transition = 'opacity 0.5s';
                                    alert.style.opacity = '0';
                                    setTimeout(() => alert.remove(), 500);
                                }
                            }, 3000);
                        </script>
                    <?php endif; ?>


                    <div
                        class="d-flex align-items-center mb-3 m-4 bg-white rounded-4">
                        <a href="#" class="m-3">
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
                <?php foreach ($userPosts as $post): ?>
                    <?php
                    // Define class based on status
                    $badge = $bg = '';
                    if ($post['status'] === 'donation') {
                        $badge = 'badge-success';
                        $bg = 'bg-success';
                    } elseif ($post['status'] === 'sale') {
                        $badge = 'badge-primary';
                        $bg = 'bg-primary';
                    } elseif ($post['status'] === 'help') {
                        $badge = 'badge-danger';
                        $bg = 'bg-danger';
                    }
                    ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header text-white <?= $bg ?>">
                            <h5><?= htmlspecialchars($post['title']) ?></h5>
                        </div>
                        <div class="card-body">
                            <p><?= htmlspecialchars($post['content']) ?></p>
                            <?php if ($post['image']): ?>
                                <img src="../<?= htmlspecialchars($post['image']) ?>" class="img-fluid mb-3">
                            <?php endif; ?>
                            <span class="badge <?= $badge ?>"><?= ucfirst($post['status']) ?></span>
                            <div>
                                <a href="#"
                                    class="edit-post-link"
                                    data-id="<?= $post['id'] ?>"
                                    data-title="<?= htmlspecialchars($post['title']) ?>"
                                    data-content="<?= htmlspecialchars($post['content']) ?>"
                                    data-status="<?= $post['status'] ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editPostModal"
                                    style="color: black;">
                                    <i class="fas fa-edit"></i> Edit post
                                </a>

                                &nbsp;&nbsp;&nbsp;
                                <a href="../backend/delete_post.php?id=<?= $post['id'] ?>" onclick="return confirm('Are you sure?');" style="color: red;">
                                    <i class="fas fa-trash"></i> Delete post
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>



            </div>

        </div>
    </div>
    <!-- Modal -->
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


    <!-- Edit Modal -->
    <div class="modal fade" id="editPostModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="../backend/update_post.php" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <input type="hidden" name="post_id" id="editPostId">

                    <div class="mb-3">
                        <label for="editPostTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" name="postTitle" id="editPostTitle" required>
                    </div>

                    <div class="mb-3">
                        <label for="editPostContent" class="form-label">Content</label>
                        <textarea class="form-control" name="postContent" id="editPostContent" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editPostImage" class="form-label">Change Image (optional)</label>
                        <input type="file" class="form-control" name="postImage" id="editPostImage">
                    </div>

                    <div class="mb-3">
                        <label>Status</label><br>
                        <input type="radio" name="postStatus" value="help" id="editHelp"> <label for="editHelp" class="text-danger">Help</label>
                        <input type="radio" name="postStatus" value="donation" id="editDonation"> <label for="editDonation" class="text-success">Donation</label>
                        <input type="radio" name="postStatus" value="sale" id="editSale"> <label for="editSale" class="text-primary">Sale</label>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update Post</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-post-link').forEach(link => {
                link.addEventListener('click', function() {
                    document.getElementById('editPostId').value = this.dataset.id;
                    document.getElementById('editPostTitle').value = this.dataset.title;
                    document.getElementById('editPostContent').value = this.dataset.content;

                    // Set status radio buttons
                    document.getElementById('editHelp').checked = this.dataset.status === 'help';
                    document.getElementById('editDonation').checked = this.dataset.status === 'donation';
                    document.getElementById('editSale').checked = this.dataset.status === 'sale';
                });
            });
        });


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