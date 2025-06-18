<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$post_id = $_GET['post_id'] ?? 0;
$conn->query("UPDATE posts SET status = 'approved' WHERE id = $post_id");
header("Location: admin_forum.php");
exit;
