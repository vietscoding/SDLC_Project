<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$post_id = $_GET['post_id'];

// Lấy bài post
$post_stmt = $conn->prepare("
    SELECT p.*, u.fullname, c.title AS course_name
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    JOIN courses c ON p.course_id = c.id
    WHERE p.id = ?
");
$post_stmt->bind_param("i", $post_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result();
if ($post_result->num_rows === 0) {
    die("Post not found.");
}
$post = $post_result->fetch_assoc();

// Thêm comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $comment = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    $stmt->execute();
    header("Location: teacher_view_post.php?post_id=$post_id");
    exit;
}

// Xóa comment
if (isset($_GET['delete_comment'])) {
    $comment_id = $_GET['delete_comment'];
    $user_id = $_SESSION['user_id'];
    $conn->query("DELETE FROM comments WHERE id=$comment_id AND user_id=$user_id");
    header("Location: teacher_view_post.php?post_id=$post_id");
    exit;
}

// Load comments
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
<h2>Course: <?= htmlspecialchars($post['course_name']) ?></h2>
<div style="border:1px solid #ccc; padding:10px; margin-bottom:20px">
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
</div>

<h3>Comments</h3>
<?php while ($cmt = $comments->fetch_assoc()): ?>
    <div style="border:1px solid #eee; padding:8px; margin-bottom:8px">
        <strong><?= htmlspecialchars($cmt['fullname']) ?></strong> (<?= $cmt['created_at'] ?>)
        <p><?= nl2br(htmlspecialchars($cmt['content'])) ?></p>
        <?php if ($cmt['user_id'] == $_SESSION['user_id']): ?>
            <a href="teacher_edit_comment.php?post_id=<?= $post_id ?>&comment_id=<?= $cmt['id'] ?>">Edit</a>
            <a href="teacher_view_post.php?post_id=<?= $post_id ?>&delete_comment=<?= $cmt['id'] ?>" 
               onclick="return confirm('Delete this comment?')">Delete</a>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

<h4>Add Comment</h4>
<form method="POST">
    <textarea name="content" rows="4" cols="60" required></textarea><br>
    <button type="submit">Post Comment</button>
</form>

<br>
<a href="teacher_forum.php?course_id=<?= $post['course_id'] ?>">← Back to Forum</a>
</body>
</html>
