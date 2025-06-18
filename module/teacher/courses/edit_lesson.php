<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Lấy lesson_id và course_id từ URL
if (!isset($_GET['lesson_id']) || !isset($_GET['course_id'])) {
    echo "Missing lesson or course ID.";
    exit;
}

$lesson_id = intval($_GET['lesson_id']);
$course_id = intval($_GET['course_id']);

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "You are not allowed to edit lessons for this course.";
    exit;
}
$stmt->close();

// Lấy thông tin bài giảng cần sửa
$stmt = $conn->prepare("SELECT title, content, video_link, file_path FROM lessons WHERE id = ? AND course_id = ?");
$stmt->bind_param("ii", $lesson_id, $course_id);
$stmt->execute();
$stmt->bind_result($title, $content, $video, $file_path);
if (!$stmt->fetch()) {
    echo "Lesson not found.";
    exit;
}
$stmt->close();


// Cập nhật bài giảng khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = trim($_POST['title']);
    $new_content = trim($_POST['content']);
    $new_video = trim($_POST['video_link']);
    $file_path_to_save = $file_path; // Giữ nguyên file cũ nếu không upload mới

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/lessons/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_name = time() . '_' . basename($_FILES['file']['name']);
    $target_file = $upload_dir . $file_name;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        $file_path_to_save = $target_file;
    }
}

    if (!empty($new_title) && !empty($new_content)) {
        $stmt = $conn->prepare("UPDATE lessons SET title = ?, content = ?, video_link = ?, file_path = ? WHERE id = ? AND course_id = ?");
        $stmt->bind_param("ssssii", $new_title, $new_content, $new_video, $file_path_to_save, $lesson_id, $course_id);

        $stmt->execute();
        $stmt->close();

        header("Location: teacher_lessons.php?course_id=$course_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lesson - <?= htmlspecialchars($title ?? 'Lesson') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/teacher/edit_lesson.css"
</head>
<body>
    <?php
    // KHÔNG THAY ĐỔI BẤT KỲ LOGIC PHP NÀO Ở ĐÂY.
    // PHẦN NÀY CHỈ ĐỂ ĐẢM BẢO CÁC BIẾN CẦN THIẾT TỒN TẠI ĐỂ TRÁNH LỖI "UNDEFINED VARIABLE"
    // KHI BẠN CHƯA KẾT NỐI DB THẬT VÀ CHẠY THỬ RIÊNG FILE NÀY.
    // TRONG MÔI TRƯỜM PHP THẬT CỦA BẠN, CÁC BIẾN NÀY SẼ ĐƯỢC LẤY TỪ DATABASE HOẶC URL.
    if (!isset($title)) {
        $title = "Sample Lesson Title (Placeholder)";
    }
    if (!isset($content)) {
        $content = "This is placeholder lesson content. It should be replaced with actual lesson data from your database.";
    }
    if (!isset($video)) {
        $video = "http://example.com/placeholder_video";
    }
    if (!isset($course_id)) {
        $course_id = 999; // Placeholder course ID
    }
    // Nếu bạn có biến $message cho thông báo thành công/lỗi, hãy thêm vào đây
    if (!isset($message)) {
        $message = '';
    }
    // Ví dụ về cách bạn sẽ thiết lập $message trong file PHP của mình:
    // $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Bài học đã được cập nhật thành công!</div>';
    // $message = '<div class="error-message"><i class="fas fa-times-circle"></i> Lỗi khi cập nhật bài học.</div>';
    ?>

    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-edit"></i> Edit Lesson: <?= htmlspecialchars($title) ?></h2>
            
        </div>

        <?php if (!empty($message)): ?>
            <?= $message ?>
        <?php endif; ?>

        <div class="form-overview">
            <h3><i class="fas fa-info-circle"></i> Lesson Details</h3>
            <div class="form-content">
                <form method="post" enctype="multipart/form-data" class="edit-lesson-form-style">
                    <label for="title">Lesson Title:</label>
                    <input type="text" name="title" id="title" value="<?= htmlspecialchars($title) ?>" required>

                    <label for="content">Lesson Content:</label>
                    <textarea name="content" id="content" rows="7" placeholder="Enter lesson content" required><?= htmlspecialchars($content) ?></textarea>

                    <label for="video_link">Video Link (optional):</label>
                    <input type="text" name="video_link" id="video_link" value="<?= htmlspecialchars($video) ?>" placeholder="e.g., https://youtube.com/watch?v=video_id">
                    
                    <label for="file">Upload File (PDF, DOCX, PPTX):</label>
                    <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.ppt,.pptx">
                    <?php if (!empty($file_path)): ?>
                        <p style="font-size: 0.9em; color: var(--text-medium); margin-top: -10px; margin-bottom: 20px;">Current file: <a href="<?= htmlspecialchars($file_path) ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;"><?= basename($file_path) ?></a> (Upload new to replace)</p>
                    <?php endif; ?>
                    
                    <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <div class="back-buttons">
            <a href="teacher_lessons.php?course_id=<?= htmlspecialchars($course_id) ?>" class="primary-button"><i class="fas fa-arrow-left"></i> Back to Lessons</a>
            <a href="teacher_dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>
    
</body>
</html>