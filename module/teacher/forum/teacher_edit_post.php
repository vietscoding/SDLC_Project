<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

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

$course_id = $post['course_id']; // Get course_id from the fetched post

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $_POST['content'];
    $current_media_url = $post['media_url'];
    $current_attachment = $post['attachment'];

    // Handle media upload
    if (!empty($_FILES['media']['name'])) {
        // Ensure the uploads directory exists and is writable
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
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
        // Ensure the uploads directory exists and is writable
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_edit_post.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-edit"></i> Edit Post for Course <?= htmlspecialchars($course_id) ?></h2>
        </div>

        <div class="post-create-container">
            <form method="POST" enctype="multipart/form-data">
                <label for="content">Post Content:</label>
                <textarea name="content" id="content" rows="8" placeholder="Write your post here..." required><?= htmlspecialchars($post['content']) ?></textarea>

                <label for="media">Upload Image/Video:</label>
                <?php if ($post['media_url']): ?>
                    <div class="post-media-preview">
                        <?php
                        $ext = strtolower(pathinfo($post['media_url'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                        ?>
                            <img src="../../../<?= htmlspecialchars($post['media_url']) ?>" alt="Current Media">
                        <?php elseif (in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                            <video src="../../../<?= htmlspecialchars($post['media_url']) ?>" controls></video>
                        <?php else: ?>
                            <p>Current Media: <a href="../../../<?= htmlspecialchars($post['media_url']) ?>" target="_blank">View Current Media</a></p>
                        <?php endif; ?>
                    </div>
                    <div class="current-file-info">
                        <span class="remove-checkbox">
                            <input type="checkbox" name="remove_media" value="true" id="remove_media">
                            <label for="remove_media">Remove Current Media</label>
                        </span>
                    </div>
                <?php endif; ?>
                <input type="file" name="media" id="media" accept="image/*,video/*">

                <label for="attachment">Attach File:</label>
                <?php if ($post['attachment']): ?>
                    <div class="current-file-info">
                        Current Attachment: <a href="<?= htmlspecialchars($post['attachment']) ?>" target="_blank">Download Current Attachment</a>
                        <span class="remove-checkbox">
                            <input type="checkbox" name="remove_attachment" value="true" id="remove_attachment">
                            <label for="remove_attachment">Remove Current Attachment</label>
                        </span>
                    </div>
                <?php endif; ?>
                <input type="file" name="attachment" id="attachment">

                <div class="button-group">
                    <button type="submit"><i class="fas fa-save"></i> Update Post</button>
                    <a href="teacher_my_posts.php" class="cancel-button"><i class="fas fa-arrow-left"></i> Back to My Posts</a>
                </div>
            </form>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>