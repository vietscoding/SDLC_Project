<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$course_id = $_GET['course_id'];

// Fetch course name for the header
$course_result = $conn->query("SELECT title FROM courses WHERE id = $course_id");
$course = $course_result->fetch_assoc();
$course_name = $course ? $course['title'] : 'Unknown Course';

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

    header("Location: student_forum.php?course_id=$course_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Create Post - <?= htmlspecialchars($course_name) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/student_create_post.css">
  
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-edit"></i> Create New Post for <?= htmlspecialchars($course_name) ?></h2>
        </div>

        <div class="post-form-container">
            <form id="createPostForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="content">Post Content:</label>
                    <textarea name="content" id="content" rows="8" placeholder="Write your post here..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="media">Upload Image/Video (Optional):</label>
                    <input type="file" name="media" id="media" accept="image/*,video/*">
                </div>

                <div class="form-group">
                    <label for="attachment">Attach File (Optional):</label>
                    <input type="file" name="attachment" id="attachment">
                </div>

                <div class="button-group">
                    <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit Post</button>
                    <a href="student_forum.php?course_id=<?= $course_id ?>" class="cancel-btn"><i class="fas fa-times-circle"></i> Cancel</a>
                </div>
            </form>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/student_sidebar.js"></script>
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