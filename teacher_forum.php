<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? 0;

// Lấy tên khóa học
$course = $conn->query("SELECT title FROM courses WHERE id = $course_id")->fetch_assoc();
if (!$course) {
    echo "Invalid Course ID";
    exit;
}

// Lấy danh sách bài post kèm like_count
$posts = $conn->query("
    SELECT p.*, u.fullname, 
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .post-card {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .post-card img, .post-card video {
            max-width: 300px;
            margin: 10px 0;
        }
        button.like-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<h2>Forum: <?= htmlspecialchars($course['title']) ?></h2>
<p><a href="teacher_create_post.php?course_id=<?= $course_id ?>">+ Create New Post</a></p>
<hr>

<?php while ($post = $posts->fetch_assoc()): ?>
    <div class="post-card">
        <p><strong><?= htmlspecialchars($post['fullname']) ?></strong> (<?= $post['created_at'] ?>)</p>
        <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>

        <?php if ($post['media_url']): ?>
            <?php
            $ext = strtolower(pathinfo($post['media_url'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
            ?>
                <img src="<?= $post['media_url'] ?>" alt="Image">
            <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                <video src="<?= $post['media_url'] ?>" controls></video>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($post['attachment']): ?>
            <p><a href="<?= $post['attachment'] ?>" download>📎 Download Attachment</a></p>
        <?php endif; ?>

        <button class="like-btn" data-post-id="<?= $post['id'] ?>">
            👍 Like (<span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?></span>)
        </button>

        <p><a href="teacher_view_post.php?post_id=<?= $post['id'] ?>">View & Comment</a></p>
    </div>
    
<?php endwhile; ?>
<a href="teacher_forum_courses.php">← Back to Course Forum</a>
<script src="js/like.js"></script>

</body>
</html>
