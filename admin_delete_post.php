<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['post_id'])) {
    echo "Post ID missing.";
    exit;
}

$post_id = intval($_GET['post_id']);

// Xóa post
$stmt = $conn->prepare("DELETE FROM forum_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
if ($stmt->execute()) {
    header("Location: admin_forum.php");
    exit;
} else {
    echo "Failed to delete post.";
}
$stmt->close();
?>
