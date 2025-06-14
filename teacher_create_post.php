<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$course_id = $_GET['course_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    // Upload file
    $media_url = null;
    $attachment = null;

    if (!empty($_FILES['media']['name'])) {
        $media_path = "uploads/" . time() . "_" . $_FILES['media']['name'];
        move_uploaded_file($_FILES['media']['tmp_name'], $media_path);
        $media_url = $media_path;
    }

    if (!empty($_FILES['attachment']['name'])) {
        $file_path = "uploads/" . time() . "_" . $_FILES['attachment']['name'];
        move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path);
        $attachment = $file_path;
    }

    $stmt = $conn->prepare("INSERT INTO posts (course_id, user_id, content, media_url, attachment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $course_id, $user_id, $content, $media_url, $attachment);
    $stmt->execute();

    header("Location: teacher_forum.php?course_id=$course_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Post (Teacher)</title>
</head>
<body>
<h2>Create New Post for Course <?= $course_id ?></h2>
<form method="POST" enctype="multipart/form-data">
    <textarea name="content" rows="5" cols="60" placeholder="Write your post here..." required></textarea><br><br>
    Upload image/video: <input type="file" name="media"><br><br>
    Attach file: <input type="file" name="attachment"><br><br>
    <button type="submit">Submit Post</button>
    <a href="teacher_forum.php?course_id=<?= $course_id ?>">Cancel</a>
</form>
</body>
</html>
