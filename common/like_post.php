<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // A date in the past
header('Content-Type: application/json'); // Đảm bảo phản hồi là JSON

include "../includes/db_connect.php"; // Thay đổi đường dẫn nếu cần

$response = ['success' => false, 'message' => '', 'like_count' => 0, 'action' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = "Bạn cần đăng nhập để thực hiện hành động này.";
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;

if (!$post_id) {
    $response['message'] = "ID bài viết không hợp lệ.";
    echo json_encode($response);
    exit;
}

// Kiểm tra xem bài viết có tồn tại không
$stmt_check_post = $conn->prepare("SELECT id FROM posts WHERE id = ?");
if (!$stmt_check_post) {
    $response['message'] = "Lỗi chuẩn bị truy vấn kiểm tra bài viết: " . $conn->error;
    echo json_encode($response);
    exit;
}
$stmt_check_post->bind_param("i", $post_id);
$stmt_check_post->execute();
$result_check_post = $stmt_check_post->get_result();

if ($result_check_post->num_rows === 0) {
    $response['message'] = "Bài viết không tồn tại.";
    echo json_encode($response);
    exit;
}
$stmt_check_post->close();

// Kiểm tra xem người dùng đã like bài viết này chưa
$stmt = $conn->prepare("SELECT id FROM post_likes WHERE user_id = ? AND post_id = ?");
if (!$stmt) {
    $response['message'] = "Lỗi chuẩn bị truy vấn kiểm tra like: " . $conn->error;
    echo json_encode($response);
    exit;
}
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Người dùng đã like, thực hiện unlike
    $stmt_delete = $conn->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?");
    if (!$stmt_delete) {
        $response['message'] = "Lỗi chuẩn bị truy vấn xóa like: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt_delete->bind_param("ii", $user_id, $post_id);
    if ($stmt_delete->execute()) {
        $response['success'] = true;
        $response['action'] = 'unliked'; // <-- Đã thêm trường action
    } else {
        $response['message'] = "Lỗi khi unlike bài viết: " . $stmt_delete->error;
    }
    $stmt_delete->close();
} else {
    // Người dùng chưa like, thực hiện like
    $stmt_insert = $conn->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)");
    if (!$stmt_insert) {
        $response['message'] = "Lỗi chuẩn bị truy vấn thêm like: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt_insert->bind_param("ii", $user_id, $post_id);
    if ($stmt_insert->execute()) {
        $response['success'] = true;
        $response['action'] = 'liked'; // <-- Đã thêm trường action
    } else {
        $response['message'] = "Lỗi khi like bài viết: " . $stmt_insert->error;
    }
    $stmt_insert->close();
}
$stmt->close();

// Lấy lại tổng số like sau khi cập nhật
$stmt_count = $conn->prepare("SELECT COUNT(*) AS total_likes FROM post_likes WHERE post_id = ?");
if (!$stmt_count) {
    $response['message'] = "Lỗi chuẩn bị truy vấn đếm like: " . $conn->error;
    echo json_encode($response);
    exit;
}
$stmt_count->bind_param("i", $post_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$like_data = $result_count->fetch_assoc();
$response['like_count'] = $like_data['total_likes'];
$stmt_count->close();

echo json_encode($response);
$conn->close();
