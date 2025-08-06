<?php

session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
include "../../../includes/db_connect.php";

$post_id = $_POST['post_id'] ?? 0;

if ($post_id) {
    $conn->query("DELETE FROM comments WHERE post_id = $post_id");
    $conn->query("DELETE FROM post_likes WHERE post_id = $post_id");
    $conn->query("DELETE FROM posts WHERE id = $post_id");
    echo json_encode(['status' => 'success', 'message' => 'Post deleted']);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid post ID']);
    exit;
}
