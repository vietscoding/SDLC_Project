<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['user_id'])) {
    echo "User ID missing.";
    exit;
}

$user_id = intval($_GET['user_id']);

// Không cho phép admin tự xóa chính mình
if ($user_id == $_SESSION['user_id']) {
    echo "You cannot delete your own account!";
    exit;
}

// Xóa user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    header("Location: admin_users.php");
    exit;
} else {
    echo "Failed to delete user.";
}
$stmt->close();
?>
