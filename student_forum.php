<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$course_id = $_GET['course_id'] ?? 0;

// Lấy tên khóa học theo id
$course_result = $conn->query("SELECT title FROM courses WHERE id = $course_id");
$course = $course_result->fetch_assoc();
$course_name = $course ? $course['title'] : 'Unknown Course';

// Câu query lấy post + like count
$sql = "
    SELECT p.*, u.fullname,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.course_id = $course_id AND p.status = 'approved'
    ORDER BY p.created_at DESC
";

$posts = $conn->query($sql);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Forum - Course <?= $course_id ?></title>
</head>
<body>
    <h2>Forum for <?= htmlspecialchars($course_name) ?></h2>
    <a href="student_create_post.php?course_id=<?= $course_id ?>">+ Create New Post</a>
    <hr>

    <?php while ($post = $posts->fetch_assoc()): ?>
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:15px">
            <p><strong><?= htmlspecialchars($post['fullname']) ?></strong> (<?= $post['created_at'] ?>)</p>
            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            <?php if ($post['media_url']): ?>
    <?php
    $ext = strtolower(pathinfo($post['media_url'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
    ?>
        <p><img src="<?= $post['media_url'] ?>" style="max-width:300px"></p>
    <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
        <p><video src="<?= $post['media_url'] ?>" controls style="max-width:300px"></video></p>
    <?php endif; ?>
<?php endif; ?>
            <?php if ($post['attachment']): ?>
                <p><a href="<?= $post['attachment'] ?>" download>Download Attachment</a></p>
            <?php endif; ?>
            <!-- Like/Unlike button -->
<button class="like-btn" data-post-id="<?= $post['id'] ?>">
    👍 Like (<span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?></span>)
</button>

            <p><a href="student_view_post.php?post_id=<?= $post['id'] ?>">View & Comment</a></p>
        </div>
    <?php endwhile; ?>
    <a href="student_forum_courses.php">← Back to Courses</a>
<script src="js/like.js"></script>
</body>
</html>
