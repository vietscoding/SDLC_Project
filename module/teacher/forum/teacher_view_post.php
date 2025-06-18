<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$post_id = $_GET['post_id'];
$teacher_id = $_SESSION['user_id']; // Added to check for like status

// Fetch the post details, including like count and user's like status
$post_stmt = $conn->prepare("
    SELECT p.*, u.fullname, c.title AS course_name,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) AS is_liked_by_user
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.id = ?
");
$post_stmt->bind_param("ii", $teacher_id, $post_id); // Bind teacher_id for is_liked_by_user
$post_stmt->execute();
$post_result = $post_stmt->get_result();
if ($post_result->num_rows === 0) {
    die("Post not found.");
}
$post = $post_result->fetch_assoc();

// Add Comment Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $comment = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    $stmt->execute();
    header("Location: teacher_view_post.php?post_id=$post_id");
    exit;
}

// Delete Comment Logic
if (isset($_GET['delete_comment'])) {
    $comment_id = $_GET['delete_comment'];
    $user_id = $_SESSION['user_id']; // Ensure only the comment owner can delete
    $delete_stmt = $conn->prepare("DELETE FROM comments WHERE id=? AND user_id=?");
    $delete_stmt->bind_param("ii", $comment_id, $user_id);
    $delete_stmt->execute();
    header("Location: teacher_view_post.php?post_id=$post_id");
    exit;
}

// Load Comments
$comments = $conn->query("
    SELECT c.*, u.fullname
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = $post_id
    ORDER BY c.created_at ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Post - <?= htmlspecialchars($post['course_name']) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_view_post.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-comments"></i> Post in: <?= htmlspecialchars($post['course_name']) ?></h2>
        </div>

        <div class="post-detail-container">
            <div class="post-author-info">
                <strong><?= htmlspecialchars($post['fullname']) ?></strong>
                <span>(<?= $post['created_at'] ?>)</span>
            </div>

            <div class="post-content">
                <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            </div>

            <?php if ($post['media_url']): ?>
                <div class="post-media">
                    <?php
                    $ext = strtolower(pathinfo($post['media_url'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                    ?>
                        <img src="<?= $post['media_url'] ?>" alt="Image">
                    <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                        <video src="<?= $post['media_url'] ?>" controls></video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($post['attachment']): ?>
                <div class="post-attachment">
                    <a href="<?= $post['attachment'] ?>" download>
                        <i class="fas fa-paperclip"></i> Download Attachment
                    </a>
                </div>
            <?php endif; ?>

            <div class="post-actions">
                <button class="like-btn <?= $post['is_liked_by_user'] > 0 ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>">
                    <i class="fas fa-thumbs-up"></i> Like (<span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?></span>)
                </button>
            </div>
        </div>

        <div class="comments-section">
            <h3><i class="fas fa-comments"></i> Comments</h3>
            <?php if ($comments->num_rows > 0): ?>
                <?php while ($cmt = $comments->fetch_assoc()): ?>
                    <div class="comment-card">
                        <div class="comment-author-info">
                            <strong><?= htmlspecialchars($cmt['fullname']) ?></strong>
                            <span><?= $cmt['created_at'] ?></span>
                        </div>
                        <div class="comment-content">
                            <p><?= nl2br(htmlspecialchars($cmt['content'])) ?></p>
                        </div>
                        <?php if ($cmt['user_id'] == $_SESSION['user_id']): ?>
                            <div class="comment-actions">
                                <a href="teacher_edit_comment.php?post_id=<?= $post_id ?>&comment_id=<?= $cmt['id'] ?>">Edit</a>
                                <a href="teacher_view_post.php?post_id=<?= $post_id ?>&delete_comment=<?= $cmt['id'] ?>"
                                   onclick="return confirm('Are you sure you want to delete this comment?')">Delete</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-posts-message" style="box-shadow: none; border: none; background: none;"><i class="fas fa-exclamation-circle"></i> No comments yet. Be the first to comment!</p>
            <?php endif; ?>
        </div>

        <div class="add-comment-section">
            <h4><i class="fas fa-plus-circle"></i> Add Your Comment</h4>
            <form method="POST">
                <textarea name="content" rows="4" placeholder="Write your comment here..." required></textarea><br>
                <button type="submit"><i class="fas fa-paper-plane"></i> Post Comment</button>
            </form>
        </div>

        <div class="back-button-container">
            <a href="teacher_forum.php?course_id=<?= $post['course_id'] ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Forum
            </a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>
    <script src="js/like.js?v=<?= filemtime('js/like.js') ?>"></script>
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                console.log('Page restored from BFcache, forcing reload.');
                window.location.reload();
            }
        });
    </script>
</body>
</html>