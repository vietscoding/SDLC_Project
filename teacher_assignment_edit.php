<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$action = $_GET['action'] ?? 'add';  // 'add' hoặc 'edit'
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

$title = '';
$description = '';
$due_date = '';
$course_id = 0;
$error = '';
$file_path = '';
$current_file = '';
$message = ''; // Added for consistency with edit_lesson.php

// Lấy danh sách khóa học do giáo viên quản lý
$courses_result = $conn->query("SELECT id, title FROM courses WHERE teacher_id = $user_id");

// Nếu là sửa, lấy dữ liệu bài tập hiện tại
if ($action === 'edit' && $assignment_id > 0) {
    $stmt = $conn->prepare("SELECT a.title, a.description, a.due_date, a.course_id, a.file_path
FROM assignments a JOIN courses c ON a.course_id = c.id
WHERE a.id = ? AND c.teacher_id = ?");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$stmt->bind_result($title, $description, $due_date, $course_id, $file_path);
if (!$stmt->fetch()) {
    echo "Assignment not found or you don't have permission.";
    exit;
}
$current_file = $file_path;
$stmt->close();


}

// Xử lý submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $course_id = intval($_POST['course_id']);
    $upload_path = $current_file;

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/assignments/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_tmp = $_FILES['file']['tmp_name'];
    $file_name = time() . '_' . basename($_FILES['file']['name']);
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($file_tmp, $target_file)) {
        $upload_path = $target_file;
    } else {
        $error = "Failed to upload file.";
    }
}

    if (empty($title) || empty($description) || $course_id <= 0) {
        $error = "Please fill all required fields.";
        $message = '<div class="error-message"><i class="fas fa-times-circle"></i> ' . htmlspecialchars($error) . '</div>';
    } else {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, due_date, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $course_id, $title, $description, $due_date, $upload_path);
            if ($stmt->execute()) {
                $_SESSION['message'] = '<div class="success-message"><i class="fas fa-check-circle"></i> Assignment added successfully!</div>';
                header("Location: teacher_assignments.php");
                exit;
            } else {
                $error = "Error adding assignment: " . $stmt->error;
                $message = '<div class="error-message"><i class="fas fa-times-circle"></i> ' . htmlspecialchars($error) . '</div>';
            }
            $stmt->close();
        } else if ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ?, course_id = ?, file_path = ? WHERE id = ?");
            $stmt->bind_param("sssisi", $title, $description, $due_date, $course_id, $upload_path, $assignment_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = '<div class="success-message"><i class="fas fa-check-circle"></i> Assignment updated successfully!</div>';
                header("Location: teacher_assignments.php");
                exit;
            } else {
                $error = "Error updating assignment: " . $stmt->error;
                $message = '<div class="error-message"><i class="fas fa-times-circle"></i> ' . htmlspecialchars($error) . '</div>';
            }
            $stmt->close();
        }
    }
}

// Check for session messages (e.g., from a redirect after successful operation)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($action === 'add') ? 'Add New Assignment' : 'Edit Assignment' ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_assignment_edit.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-edit"></i> <?= ($action === 'add') ? 'Add New Assignment' : 'Edit Assignment: ' . htmlspecialchars($title) ?></h2>
        </div>

        <?php if (!empty($message)): ?>
            <?= $message ?>
        <?php endif; ?>

        <div class="form-overview">
            <h3><i class="fas fa-info-circle"></i> Assignment Details</h3>
            <div class="form-content">
                <form method="post" enctype="multipart/form-data" class="edit-assignment-form-style">
                    <label for="title">Assignment Title:</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>

                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="7" placeholder="Enter assignment description" required><?= htmlspecialchars($description) ?></textarea>

                    <label for="due_date">Due Date:</label>
                    <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($due_date) ?>">

                    <label for="course_id">Select Course:</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">-- Select a Course --</option>
                        <?php
                        // Reset pointer for courses_result if it was already fetched
                        if ($courses_result->num_rows > 0) {
                            $courses_result->data_seek(0);
                        }
                        while ($course = $courses_result->fetch_assoc()): ?>
                            <option value="<?= $course['id'] ?>" <?= ($course['id'] == $course_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="file">Upload File (PDF, DOCX, PPTX):</label>
                    <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.ppt,.pptx">
                    <?php if (!empty($current_file)): ?>
                        <p style="font-size: 0.9em; color: var(--text-medium); margin-top: -10px; margin-bottom: 20px;">Current file: <a href="<?= htmlspecialchars($current_file) ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;"><?= basename($current_file) ?></a> (Upload new to replace)</p>
                    <?php endif; ?>

                    <button type="submit"><i class="fas fa-save"></i> <?= ($action === 'add') ? 'Add Assignment' : 'Save Changes' ?></button>
                </form>
            </div>
        </div>

        <div class="back-buttons">
            <a href="teacher_assignments.php" class="primary-button"><i class="fas fa-arrow-left"></i> Back to Assignments List</a>
            <a href="teacher_dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>

</body>
</html>