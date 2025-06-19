<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? 0;

// Lấy tên khóa học
$course = $conn->query("SELECT title FROM courses WHERE id = $course_id")->fetch_assoc();
if (!$course) {
    echo "Invalid Course ID";
    exit;
}

// Lấy danh sách bài post kèm like_count và trạng thái like của người dùng hiện tại
$posts = $conn->query("
    SELECT p.*, u.fullname,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = {$teacher_id}) AS is_liked_by_user
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.course_id = $course_id AND p.status = 'approved'
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forum - <?= htmlspecialchars($course['title']) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/teacher/teacher_forum.css">
   
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-comments"></i> Forum: <?= htmlspecialchars($course['title']) ?></h2>
        </div>

        <div class="create-post-section">
            <a href="teacher_create_post.php?course_id=<?= $course_id ?>" class="create-post-btn">
                <i class="fas fa-plus-circle"></i> Create New Post
            </a>
        </div>

        <div class="forum-posts-container">
            <?php if ($posts->num_rows > 0): ?>
                <?php while ($post = $posts->fetch_assoc()): ?>
                    <div class="post-card">
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
                                    <img src="../../../<?= $post['media_url'] ?>" alt="Image">
                                <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                                    <video src="../../../<?= $post['media_url'] ?>" controls></video>
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
                            <a href="teacher_view_post.php?post_id=<?= $post['id'] ?>" class="post-comments-link">
                                <i class="fas fa-comment"></i> View & Comment
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-posts-message"><i class="fas fa-exclamation-circle"></i> No posts found for this course yet. Be the first to create one!</p>
            <?php endif; ?>
        </div>

        <div class="back-button-container">
            <a href="teacher_forum_courses.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Course Forums
            </a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
    <script src="../../../js/like.js"></script>
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