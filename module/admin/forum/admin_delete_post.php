<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$post_id = $_GET['post_id'] ?? 0;

// Xóa comment trước
$conn->query("DELETE FROM comments WHERE post_id = $post_id");

// Xóa like
$conn->query("DELETE FROM post_likes WHERE post_id = $post_id");

// Xóa post
$conn->query("DELETE FROM posts WHERE id = $post_id");

echo "success";
exit;
