<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Chưa đăng nhập"]);
    exit;
}

include "includes/db_connect.php";

$post_id = $_POST['post_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Kiểm tra post tồn tại
$check = $conn->query("SELECT id FROM posts WHERE id = $post_id");
if ($check->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Post không tồn tại"]);
    exit;
}

// Kiểm tra like hay chưa
$like_check = $conn->query("SELECT id FROM post_likes WHERE post_id = $post_id AND user_id = $user_id");
if ($like_check->num_rows > 0) {
    // Unlike
    $conn->query("DELETE FROM post_likes WHERE post_id = $post_id AND user_id = $user_id");
} else {
    // Like
    $conn->query("INSERT INTO post_likes (post_id, user_id) VALUES ($post_id, $user_id)");
}

// Đếm lại số lượt like
$result = $conn->query("SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = $post_id");
$like_count = $result->fetch_assoc()['like_count'] ?? 0;

echo json_encode(["success" => true, "like_count" => $like_count]);
?>
