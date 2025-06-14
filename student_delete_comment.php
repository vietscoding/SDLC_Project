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

// Xóa comment
$conn->query("DELETE FROM comments WHERE id=$comment_id");

header("Location: student_view_post.php?post_id=$post_id");
exit;
?>
