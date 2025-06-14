<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$comment_id = $_GET['comment_id'] ?? 0;
$post_id = $_GET['post_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Lấy comment về để kiểm tra quyền sở hữu
$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Comment not found or permission denied.");
}
$comment = $result->fetch_assoc();

// Xử lý update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];
    $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ?");
    $stmt->bind_param("si", $content, $comment_id);
    $stmt->execute();
    header("Location: teacher_view_post.php?post_id=$post_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Comment</title>
</head>
<body>
<h2>Edit Your Comment</h2>

<form method="POST">
    <textarea name="content" rows="5" cols="60" required><?= htmlspecialchars($comment['content']) ?></textarea><br><br>
    <button type="submit">Update Comment</button>
    <a href="teacher_view_post.php?post_id=<?= $post_id ?>">Cancel</a>
</form>

</body>
</html>
