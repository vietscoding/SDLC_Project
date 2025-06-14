<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$post_id = $_GET['post_id'];

// Lấy thông tin post
$post_stmt = $conn->prepare("SELECT p.*, u.fullname FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id=? AND p.status='approved'");
$post_stmt->bind_param("i", $post_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result();
$post = $post_result->fetch_assoc();

if (!$post) {
    echo "Post not found or not approved.";
    exit;
}

// Thêm bình luận mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $comment_content = $_POST['comment_content'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment_content);
    $stmt->execute();
}

// Lấy danh sách bình luận
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
    <title>View Post</title>
</head>
<body>

<h2>Post by <?= htmlspecialchars($post['fullname']) ?></h2>
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

<hr>
<h3>Comments</h3>

<?php while ($c = $comments->fetch_assoc()): ?>
    <div style="border:1px solid #ccc; padding:8px; margin-bottom:8px">
        <p><strong><?= htmlspecialchars($c['fullname']) ?></strong> (<?= $c['created_at'] ?>)</p>
        <p><?= nl2br(htmlspecialchars($c['content'])) ?></p>

        <?php if ($c['user_id'] == $_SESSION['user_id']): ?>
            <a href="student_edit_comment.php?comment_id=<?= $c['id'] ?>&post_id=<?= $post_id ?>">Edit</a> |
            <a href="student_delete_comment.php?comment_id=<?= $c['id'] ?>&post_id=<?= $post_id ?>" onclick="return confirm('Delete this comment?');">Delete</a>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

<hr>
<h3>Add Comment</h3>
<form method="POST">
    <textarea name="comment_content" rows="3" cols="60" required></textarea><br>
    <button type="submit">Post Comment</button>
</form>

<p><a href="student_forum.php?course_id=<?= $post['course_id'] ?>">← Back to Forum</a></p>

</body>
</html>
