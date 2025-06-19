<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

// Lấy course_id từ URL
if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "You are not allowed to manage lessons for this course.";
    exit;
}
$stmt->bind_result($course_title);
$stmt->fetch();
$stmt->close();

// Thêm bài giảng mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $video = trim($_POST['video_link']);
    $file_path = null;

    // Xử lý file upload nếu có
    if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/lessons/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Tạo thư mục nếu chưa có
        }

        $file_name = basename($_FILES['lesson_file']['name']);
        $target_file = $upload_dir . time() . '_' . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['lesson_file']['tmp_name'], $target_file)) {
                $file_path = $target_file;
            }
        }
    }

    // Lưu bài giảng
    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content, video_link, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $course_id, $title, $content, $video, $file_path);
        $stmt->execute();
        $stmt->close();
        header("Location: teacher_lessons.php?course_id=$course_id");
        exit;
    }
}


// Xóa bài giảng
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    // Ensure the lesson belongs to the current course and teacher
    $delete_stmt = $conn->prepare("DELETE FROM lessons WHERE id = ? AND course_id = ?");
    $delete_stmt->bind_param("ii", $remove_id, $course_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: teacher_lessons.php?course_id=$course_id");
    exit;
}


// Lấy danh sách bài giảng
$result = $conn->query("SELECT id, title, content, video_link, file_path FROM lessons WHERE course_id = $course_id ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lessons - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_lessons.css">
</head>
<body>
    <?php

    if (!isset($course_title)) {
        $course_title = "Web Development Fundamentals (Placeholder)"; // Tiêu đề mặc định nếu chưa được set
    }
    if (!isset($message)) {
        $message = ''; // Mặc định không có thông báo
    }
    if (!isset($course_id)) {
        $course_id = 999; // ID mặc định nếu chưa được set
    }

    ?>

    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-clipboard-list"></i> Manage Lessons for Course: <?= htmlspecialchars($course_title) ?></h2>
            </div>

        <div class="lessons-overview">
            <h3><i class="fas fa-plus-circle"></i> Add New Lesson</h3>
            <div class="lessons-content">
                <form method="post" enctype="multipart/form-data" class="add-lesson-form">
                    <label for="title">Lesson Title:</label>
                    <input type="text" name="title" id="title" placeholder="Enter lesson title" required>

                    <label for="content">Lesson Content:</label>
                    <textarea name="content" id="content" rows="5" placeholder="Enter lesson content" required></textarea>

                    <label for="video_link">Video Link (optional):</label>
                    <input type="text" name="video_link" id="video_link" placeholder="e.g., https://youtube.com/watch?v=video_id">

                    <label for="lesson_file">Upload File (PDF, DOCX, PPTX):</label>
                    <input type="file" name="lesson_file" id="lesson_file" accept=".pdf,.doc,.docx,.ppt,.pptx">
                    
                    <button type="submit"><i class="fas fa-plus"></i> Add Lesson</button>
                </form>
            </div>
        </div>

        <div class="lessons-overview">
            <h3><i class="fas fa-list-ul"></i> Lesson List</h3>
            <div class="lessons-content">
                <?php if (isset($result) && $result->num_rows > 0): ?>
                    <ul class="lesson-list">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li class="lesson-item">
                                <div class="lesson-item-info">
                                    <span class="lesson-icon"><i class="fas fa-book-open"></i></span>
                                    <div class="lesson-details">
                                        <h4><?= htmlspecialchars($row['title']) ?></h4>
                                        <p><?= htmlspecialchars(substr($row['content'], 0, 100)) . (strlen($row['content']) > 100 ? '...' : '') ?></p>
                                    </div>
                                </div>
                                <div class="lesson-actions">
                                    <?php if (!empty($row['video_link'])): ?>
                                        <a href="<?= htmlspecialchars($row['video_link']) ?>" target="_blank" class="view-link" title="View Video"><i class="fas fa-video"></i> Video</a>
                                    <?php endif; ?>
                                    <?php if (!empty($row['file_path'])): ?>
                                        <a href="../../../<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="file-link" title="View File"><i class="fas fa-file-alt"></i> File</a>
                                    <?php endif; ?>
                                    <a href="edit_lesson.php?course_id=<?= htmlspecialchars($course_id) ?>&lesson_id=<?= htmlspecialchars($row['id']) ?>" class="edit-link" title="Edit Lesson"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="teacher_lessons.php?course_id=<?= htmlspecialchars($course_id) ?>&remove_id=<?= htmlspecialchars($row['id']) ?>" onclick="return confirm('Are you sure you want to remove this lesson?')" class="delete-link" title="Remove Lesson"><i class="fas fa-trash-alt"></i> Delete</a>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-lessons"><i class="fas fa-info-circle"></i> No lessons have been added to this course yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-buttons">
            <a href="teacher_courses.php"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
            <a href="../dashboard/teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>