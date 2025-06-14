<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$course_id = $_GET['course_id'];
$course = $conn->query("SELECT title FROM courses WHERE id = $course_id")->fetch_assoc();

$posts = $conn->query("
    SELECT p.*, u.fullname 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.course_id = $course_id
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Forum - <?= htmlspecialchars($course['title']) ?></title>
</head>
<body>
<h2>Forum Posts - <?= htmlspecialchars($course['title']) ?></h2>

<?php while ($post = $posts->fetch_assoc()): ?>
    <div style="border:1px solid #ccc; padding:10px; margin:10px">
        <strong>By:</strong> <?= htmlspecialchars($post['fullname']) ?><br>
        <strong>At:</strong> <?= $post['created_at'] ?><br>
        <strong>Status:</strong> <?= strtoupper($post['status']) ?><br>
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

        <a href="#" class="delete-post" data-post-id="<?= $post['id'] ?>">🗑️ Delete</a>
    </div>
<?php endwhile; ?>

<a href="admin_manage_forum.php">← Back to Courses</a>
<script src="js/delete_post.js"></script>
</body>
</html>
