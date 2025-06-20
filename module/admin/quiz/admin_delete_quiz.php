<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

if (!isset($_GET['quiz_id'])) {
    echo "Quiz ID missing.";
    exit;
}

$quiz_id = intval($_GET['quiz_id']);

// Xóa quiz
$stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quiz_id);
if ($stmt->execute()) {
    header("Location: admin_quizzes.php");
    exit;
} else {
    echo "Failed to delete quiz.";
}
$stmt->close();
?>
