<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$pending_posts = $conn->query("
    SELECT p.*, u.fullname, c.title AS course_title
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Forum Posts</title>
</head>
<body>
<h2>Pending Forum Posts</h2>

<?php while ($post = $pending_posts->fetch_assoc()): ?>
    <div style="border:1px solid #ccc; padding:10px; margin:10px">
        <strong>Course:</strong> <?= htmlspecialchars($post['course_title']) ?><br>
        <strong>By:</strong> <?= htmlspecialchars($post['fullname']) ?><br>
        <strong>At:</strong> <?= $post['created_at'] ?><br><br>
        <div><?= nl2br(htmlspecialchars($post['content'])) ?></div>
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
        <a href="admin_approve_post.php?post_id=<?= $post['id'] ?>">✔️ Approve</a> |
        <a href="admin_reject_post.php?post_id=<?= $post['id'] ?>">❌ Reject</a> |
        <a href="#" class="delete-post" data-post-id="<?= $post['id'] ?>">🗑️ Delete</a>
    </div>
<?php endwhile; ?>
<a href="admin_manage_forum.php">View All Posts (Approved & Rejected)</a><br>
<a href="admin_dashboard.php">← Back to Dashboard</a>
<script src="js/delete_post.js"></script>
</body>
</html>
