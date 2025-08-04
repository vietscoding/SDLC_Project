<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$post_id = $_GET['post_id'];
$student_id = $_SESSION['user_id']; // Get current student ID for like status

// Lấy thông tin post
$post_stmt = $conn->prepare("SELECT p.*, u.fullname,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) AS is_liked_by_user
    FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id=? AND p.status='approved'");
$post_stmt->bind_param("ii", $student_id, $post_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result();
$post = $post_result->fetch_assoc();

if (!$post) {
    echo "Post not found or not approved.";
    exit;
}

// Get course name for header
$course_id = $post['course_id'];
$course_result = $conn->query("SELECT title FROM courses WHERE id = $course_id");
$course = $course_result->fetch_assoc();
$course_name = $course ? $course['title'] : 'Unknown Course';


// Thêm bình luận mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $comment_content = trim($_POST['comment_content']); // Trim whitespace
    $user_id = $_SESSION['user_id'];

    if (!empty($comment_content)) { // Only insert if comment content is not empty
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $user_id, $comment_content);
        $stmt->execute();
        // Redirect to prevent form resubmission on refresh
        header("Location: student_view_post.php?post_id=" . $post_id);
        exit;
    }
}

// Lấy danh sách bình luận
$comments = $conn->query("
    SELECT c.*, u.fullname, u.role
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = $post_id
    ORDER BY c.created_at ASC
");

// Get user fullname and role for the header (assuming these are in $_SESSION from login)
$fullname = htmlspecialchars($_SESSION['fullname'] ?? 'Student');
$role = htmlspecialchars($_SESSION['role'] ?? 'student');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Post - <?= htmlspecialchars($post['content'] ? substr($post['content'], 0, 50) . '...' : 'Post') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/student_view_post.css">
    
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-comments"></i> Forum for <?= htmlspecialchars($course_name) ?></h2>
        </div>

        <div class="post-detail-card">
            <div class="post-author-info">
                <strong><?= htmlspecialchars($post['fullname']) ?></strong>
                <span>(<?= date('Y-m-d H:i', strtotime($post['created_at'])) ?>)</span>
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
                        <img src="<?= htmlspecialchars($post['media_url']) ?>" alt="Post media">
                    <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                        <video src="<?= htmlspecialchars($post['media_url']) ?>" controls></video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($post['attachment']): ?>
                <div class="post-attachment">
                    <a href="<?= htmlspecialchars($post['attachment']) ?>" download><i class="fas fa-paperclip"></i> Download Attachment</a>
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
                <?php while ($c = $comments->fetch_assoc()): ?>
                    <div class="comment-item">
                        <div class="comment-meta">
                            <div>
                                <strong><?= htmlspecialchars($c['fullname']) ?></strong>
                                <?php if ($c['role'] == 'teacher'): ?>
                                    <span class="user-role">Teacher</span>
                                <?php endif; ?>
                            </div>
                            <span><?= date('Y-m-d H:i', strtotime($c['created_at'])) ?></span>
                        </div>
                        <div class="comment-content-text">
                            <p><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                        </div>
                        <?php if ($c['user_id'] == $_SESSION['user_id']): ?>
                            <div class="comment-actions">
                                <a href="student_edit_comment.php?comment_id=<?= $c['id'] ?>&post_id=<?= $post_id ?>"><i class="fas fa-edit"></i> Edit</a>
                                <a href="student_delete_comment.php?comment_id=<?= $c['id'] ?>&post_id=<?= $post_id ?>" onclick="return confirm('Delete this comment?');"><i class="fas fa-trash-alt"></i> Delete</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-comments-message"><i class="fas fa-info-circle"></i> No comments yet. Be the first to comment!</p>
            <?php endif; ?>
        </div>

        <div class="add-comment-section">
            <h3><i class="fas fa-pen-alt"></i> Add a Comment</h3>
            <form method="POST">
                <textarea name="comment_content" rows="4" placeholder="Write your comment here..." required></textarea><br>
                <button type="submit"><i class="fas fa-paper-plane"></i> Post Comment</button>
            </form>
        </div>

        <div class="back-button-container">
            <a href="student_forum.php?course_id=<?= $post['course_id'] ?>" class="back-button"><i class="fas fa-arrow-left"></i> Back to Forum</a>
            <a href="student_forum_courses.php" class="back-button"><i class="fas fa-book-open"></i> Back to Courses</a>
            <a href="../dashboard/student_dashboard.php" class="back-button"><i class="fas fa-tachometer-alt"></i> Back to Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/like.js"></script>
    <script src="../../../js/student_sidebar.js"></script>
    <script>
        window.addEventListener('pageshow', function (event) {
            // If the page was restored from the BFcache
            if (event.persisted) {
                console.log('Page restored from BFcache, forcing reload.');
                window.location.reload();
            }
        });
    </script>
</body>
</html>