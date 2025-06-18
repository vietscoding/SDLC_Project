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
        // Ensure the uploads directory exists and is writable
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        move_uploaded_file($_FILES['media']['tmp_name'], $media_path);
        $media_url = $media_path;
    }

    if (!empty($_FILES['attachment']['name'])) {
        $file_path = "uploads/" . time() . "_" . $_FILES['attachment']['name'];
        // Ensure the uploads directory exists and is writable
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_create_post.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-plus-circle"></i> Create New Post for Course <?= htmlspecialchars($course_id) ?></h2>
        </div>

        <div class="post-create-container">
            <form id="createPostForm" method="POST" enctype="multipart/form-data">
                <label for="content">Post Content:</label>
                <textarea name="content" id="content" rows="8" placeholder="Write your post here..." required></textarea>

                <label for="media">Upload Image/Video:</label>
                <input type="file" name="media" id="media" accept="image/*,video/*">

                <label for="attachment">Attach File:</label>
                <input type="file" name="attachment" id="attachment">

                <div class="button-group">
                    <button type="submit"><i class="fas fa-paper-plane"></i> Submit Post</button>
                    <a href="teacher_forum.php?course_id=<?= htmlspecialchars($course_id) ?>" class="cancel-button"><i class="fas fa-arrow-left"></i> Cancel</a>
                </div>
            </form>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>
    <script>
        document.getElementById('createPostForm').addEventListener('submit', function(event) {
            const maxFileSizeMB = 50; // Max size for individual files in MB
            const maxTotalPostSizeMB = 100; // Max total post size in MB (consider content + files)
            const maxFileSizeBytes = maxFileSizeMB * 1024 * 1024;
            const maxTotalPostSizeBytes = maxTotalPostSizeMB * 1024 * 1024;

            let totalFilesSize = 0;

            const mediaInput = document.getElementById('media');
            const attachmentInput = document.getElementById('attachment');

            // Check media file size
            if (mediaInput.files.length > 0) {
                const mediaFile = mediaInput.files[0];
                if (mediaFile.size > maxFileSizeBytes) {
                    alert(`The uploaded media file "${mediaFile.name}" exceeds the maximum allowed size of ${maxFileSizeMB}MB.`);
                    event.preventDefault(); // Prevent form submission
                    return;
                }
                totalFilesSize += mediaFile.size;
            }

            // Check attachment file size
            if (attachmentInput.files.length > 0) {
                const attachmentFile = attachmentInput.files[0];
                if (attachmentFile.size > maxFileSizeBytes) {
                    alert(`The attached file "${attachmentFile.name}" exceeds the maximum allowed size of ${maxFileSizeMB}MB.`);
                    event.preventDefault(); // Prevent form submission
                    return;
                }
                totalFilesSize += attachmentFile.size;
            }

            // Estimate content size (rough estimation, can be more accurate if needed)
            const contentInput = document.getElementById('content');
            const contentSize = new TextEncoder().encode(contentInput.value).length;
            let totalPostSize = contentSize + totalFilesSize;

            // Check total post size
            if (totalPostSize > maxTotalPostSizeBytes) {
                alert(`The total size of your post (including content and files) exceeds the maximum allowed size of ${maxTotalPostSizeMB}MB.`);
                event.preventDefault(); // Prevent form submission
                return;
            }
        });
    </script>
</body>
</html>