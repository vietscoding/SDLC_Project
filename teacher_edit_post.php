<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];
$post_id = $_GET['post_id'];

// Fetch the post to be edited, ensuring it belongs to the logged-in teacher
$stmt = $conn->prepare("SELECT content, media_url, attachment, course_id FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    die("Post not found or you don't have permission to edit it.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $_POST['content'];
    $current_media_url = $post['media_url'];
    $current_attachment = $post['attachment'];

    // Handle media upload
    if (!empty($_FILES['media']['name'])) {
        // Delete old media if it exists
        if ($current_media_url && file_exists($current_media_url)) {
            unlink($current_media_url);
        }
        $media_path = "uploads/" . time() . "_" . $_FILES['media']['name'];
        move_uploaded_file($_FILES['media']['tmp_name'], $media_path);
        $current_media_url = $media_path;
    } else if (isset($_POST['remove_media']) && $_POST['remove_media'] == 'true') {
        if ($current_media_url && file_exists($current_media_url)) {
            unlink($current_media_url);
        }
        $current_media_url = null;
    }

    // Handle attachment upload
    if (!empty($_FILES['attachment']['name'])) {
        // Delete old attachment if it exists
        if ($current_attachment && file_exists($current_attachment)) {
            unlink($current_attachment);
        }
        $file_path = "uploads/" . time() . "_" . $_FILES['attachment']['name'];
        move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path);
        $current_attachment = $file_path;
    } else if (isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == 'true') {
        if ($current_attachment && file_exists($current_attachment)) {
            unlink($current_attachment);
        }
        $current_attachment = null;
    }

    $stmt = $conn->prepare("UPDATE posts SET content = ?, media_url = ?, attachment = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssii", $content, $current_media_url, $current_attachment, $post_id, $teacher_id);
    $stmt->execute();

    header("Location: teacher_my_posts.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Post (Teacher)</title>
</head>
<body>
<h2>Edit Post</h2>
<form method="POST" enctype="multipart/form-data">
    <textarea name="content" rows="5" cols="60" required><?= htmlspecialchars($post['content']) ?></textarea><br><br>

    <?php if ($post['media_url']): ?>
        <p>Current Media: <a href="<?= htmlspecialchars($post['media_url']) ?>" target="_blank">View</a></p>
        <input type="checkbox" name="remove_media" value="true"> Remove current media<br><br>
    <?php endif; ?>
    Upload new image/video: <input type="file" name="media"><br><br>

    <?php if ($post['attachment']): ?>
        <p>Current Attachment: <a href="<?= htmlspecialchars($post['attachment']) ?>" target="_blank">Download</a></p>
        <input type="checkbox" name="remove_attachment" value="true"> Remove current attachment<br><br>
    <?php endif; ?>
    Attach new file: <input type="file" name="attachment"><br><br>

    <button type="submit">Update Post</button>
</form>
<a href="teacher_my_posts.php">Cancel</a>
</body>
</html>