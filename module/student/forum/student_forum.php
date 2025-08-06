<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$student_id = $_SESSION['user_id']; // Lấy ID của sinh viên hiện tại
$course_id = $_GET['course_id'] ?? 0;

// Lấy tên khóa học theo id
$course_result = $conn->query("SELECT title FROM courses WHERE id = $course_id");
$course = $course_result->fetch_assoc();
$course_name = $course ? $course['title'] : 'Unknown Course';

// Câu query lấy post + like count VÀ trạng thái like của người dùng hiện tại
$sql = "
    SELECT p.*, u.fullname,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = {$student_id}) AS is_liked_by_user
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.course_id = $course_id AND p.status = 'approved'
    ORDER BY p.created_at DESC
";

$posts = $conn->query($sql);

// Get user fullname and role for the header (assuming these are in $_SESSION from login)
$fullname = htmlspecialchars($_SESSION['fullname'] ?? 'Student');
$role = htmlspecialchars($_SESSION['role'] ?? 'student');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Forum - <?= htmlspecialchars($course_name) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/student_forum.css">
   
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-comments"></i> Forum for <?= htmlspecialchars($course_name) ?></h2>
        </div>

        <div class="create-post-section">
            <a href="student_create_post.php?course_id=<?= $course_id ?>" class="create-post-btn"><i class="fas fa-plus-circle"></i> Create New Post</a>
        </div>

        <div class="forum-posts-container">
            <?php if ($posts->num_rows > 0): ?>
                <?php while ($post = $posts->fetch_assoc()): ?>
                    <div class="post-card">
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
                            <a href="student_view_post.php?post_id=<?= $post['id'] ?>" class="post-comments-link"><i class="fas fa-comment"></i> Comment</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-posts-message"><i class="fas fa-exclamation-circle"></i> No posts in this forum yet. Be the first to create one!</p>
            <?php endif; ?>
        </div>

        <div class="back-button-container">
            <a href="student_forum_courses.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Courses</a>
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