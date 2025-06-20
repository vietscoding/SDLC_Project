<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit;
}

include "../../../includes/db_connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_id']) || !isset($_POST['csrf_token'])) {
    echo "Invalid request.";
    exit;
}

// Kiểm tra CSRF token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo "Invalid CSRF token.";
    exit;
}

$course_id = intval($_POST['course_id']);

// Kiểm tra xem khóa học có tồn tại không (optional nhưng tốt)
$check_stmt = $conn->prepare("SELECT id FROM courses WHERE id = ?");
$check_stmt->bind_param("i", $course_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows === 0) {
    echo "Course not found.";
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// Xóa khóa học
$stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);

if ($stmt->execute()) {
    // Regenerate CSRF token để tránh reuse token sau thao tác nguy hiểm
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: admin_courses.php?delete=success");
    exit;
} else {
    echo "Failed to delete course.";
}

$stmt->close();
$conn->close();
?>
