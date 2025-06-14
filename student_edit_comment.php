<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$comment_id = $_GET['comment_id'];
$post_id = $_GET['post_id'];

// Kiểm tra quyền
$check = $conn->query("SELECT * FROM comments WHERE id=$comment_id AND user_id={$_SESSION['user_id']}");
if ($check->num_rows === 0) {
    echo "Permission denied.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_content = $_POST['new_content'];
    $conn->query("UPDATE comments SET content='$new_content' WHERE id=$comment_id");
    header("Location: student_view_post.php?post_id=$post_id");
    exit;
}

$comment = $check->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head><title>Edit Comment</title></head>
<body>

<h2>Edit Comment</h2>
<form method="POST">
    <textarea name="new_content" rows="3" cols="60" required><?= htmlspecialchars($comment['content']) ?></textarea><br>
    <button type="submit">Save</button>
</form>

<p><a href="student_view_post.php?post_id=<?= $post_id ?>">← Back to Post</a></p>

</body>
</html>
