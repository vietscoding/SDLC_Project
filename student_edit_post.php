<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$post_id = $_GET['post_id'];

// Fetch the post to be edited
$stmt = $conn->prepare("SELECT content, media_url, attachment, course_id FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    echo "Post not found or you don't have permission to edit it.";
    exit;
}

// Fetch course name for the header
$course_id = $post['course_id'];
$course_result = $conn->query("SELECT title FROM courses WHERE id = $course_id");
$course = $course_result->fetch_assoc();
$course_name = $course ? $course['title'] : 'Unknown Course';

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
    $stmt->bind_param("sssii", $content, $current_media_url, $current_attachment, $post_id, $user_id);
    $stmt->execute();

    header("Location: student_my_posts.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Post - <?= htmlspecialchars($course_name) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/student/student_edit_post.css">
   
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-edit"></i> Edit Post for <?= htmlspecialchars($course_name) ?></h2>
        </div>

        <div class="post-form-container">
            <form id="editPostForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="content">Post Content:</label>
                    <textarea name="content" id="content" rows="8" placeholder="Write your post here..." required><?= htmlspecialchars($post['content']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="media">Upload Image/Video (Optional):</label>
                    <?php if ($post['media_url']): ?>
                        <div class="current-file-info">
                            <i class="fas fa-image"></i> Current Media: <a href="<?= htmlspecialchars($post['media_url']) ?>" target="_blank">View Current File</a>
                        </div>
                        <div class="remove-checkbox-group">
                            <input type="checkbox" name="remove_media" id="remove_media" value="true">
                            <label for="remove_media">Remove current media</label>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="media" id="media" accept="image/*,video/*">
                </div>

                <div class="form-group">
                    <label for="attachment">Attach File (Optional):</label>
                    <?php if ($post['attachment']): ?>
                        <div class="current-file-info">
                            <i class="fas fa-paperclip"></i> Current Attachment: <a href="<?= htmlspecialchars($post['attachment']) ?>" target="_blank">Download Current File</a>
                        </div>
                        <div class="remove-checkbox-group">
                            <input type="checkbox" name="remove_attachment" id="remove_attachment" value="true">
                            <label for="remove_attachment">Remove current attachment</label>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="attachment" id="attachment">
                </div>

                <div class="button-group">
                    <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Update Post</button>
                    <a href="student_my_posts.php" class="cancel-btn"><i class="fas fa-times-circle"></i> Cancel</a>
                </div>
            </form>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>

    <script src="js/student_sidebar.js"></script>
    <script>
        document.getElementById('editPostForm').addEventListener('submit', function(event) {
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