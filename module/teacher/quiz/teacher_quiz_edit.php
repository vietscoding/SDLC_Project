<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$action = $_GET['action'] ?? 'add';
$quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

$quiz_title = '';
$course_id = 0;
$deadline = ''; // Thêm biến deadline
$error = '';
$message = ''; // Added for consistency with teacher_assignment_edit.php

// Lấy danh sách khóa học giáo viên quản lý để chọn
$courses_result = $conn->query("SELECT id, title FROM courses WHERE teacher_id = $user_id");

// Nếu sửa, lấy dữ liệu quiz hiện tại
if ($action === 'edit' && $quiz_id > 0) {
    $stmt = $conn->prepare("
        SELECT q.title, q.course_id, q.deadline
        FROM quizzes q
        JOIN courses c ON q.course_id = c.id
        WHERE q.id = ? AND c.teacher_id = ?
    ");
    $stmt->bind_param("ii", $quiz_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($quiz_title, $course_id, $deadline);
    if (!$stmt->fetch()) {
        echo "Quiz not found or you don't have permission.";
        exit;
    }
    $stmt->close();
    // Định dạng deadline cho input type="datetime-local"
    if ($deadline) {
        $deadline = date('Y-m-d\TH:i', strtotime($deadline));
    }
}

// Xử lý submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_title = trim($_POST['title']);
    $course_id = intval($_POST['course_id']);
    $deadline = $_POST['deadline'] ?? '';

    if (empty($quiz_title) || $course_id <= 0 || empty($deadline)) {
        $error = "Please enter quiz title, select a course and set a deadline.";
        $message = '<div class="error-message"><i class="fas fa-times-circle"></i> ' . htmlspecialchars($error) . '</div>';
    } else {
        // Định dạng lại deadline về dạng Y-m-d H:i:s cho MySQL
        $deadline_mysql = date('Y-m-d H:i:s', strtotime($deadline));
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title, deadline) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $course_id, $quiz_title, $deadline_mysql);
            if ($stmt->execute()) {
                // Set the success message for display on the current page
                $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Quiz added successfully!</div>';
                // No redirect here, stay on the same page
                // Update action to 'edit' and quiz_id if a new quiz was just added, to allow immediate editing
                $action = 'edit';
                $quiz_id = $stmt->insert_id;
            } else {
                $error = "Error adding quiz: " . $stmt->error;
                $message = '<div class="error-message"><i class="fas fa-times-circle"></i> ' . htmlspecialchars($error) . '</div>';
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE quizzes SET title = ?, course_id = ?, deadline = ? WHERE id = ?");
            $stmt->bind_param("sisi", $quiz_title, $course_id, $deadline_mysql, $quiz_id);
            if ($stmt->execute()) {
                // Set the success message for display on the current page
                $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Quiz updated successfully!</div>';
                // No redirect here, stay on the same page
            } else {
                $error = "Error updating quiz: " . $stmt->error;
                $message = '<div class="error-message"><i class="fas fa-times-circle"></i> ' . htmlspecialchars($error) . '</div>';
            }
            $stmt->close();
        }
    }
    // Re-fetch quiz data after successful update/add to ensure form fields are updated
    // This is especially important for 'add' action if it transitions to 'edit' mode
    if ($action === 'edit' && $quiz_id > 0) {
        $stmt = $conn->prepare("
            SELECT q.title, q.course_id, q.deadline
            FROM quizzes q
            JOIN courses c ON q.course_id = c.id
            WHERE q.id = ? AND c.teacher_id = ?
        ");
        $stmt->bind_param("ii", $quiz_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($quiz_title, $course_id, $deadline);
        $stmt->fetch();
        $stmt->close();
        if ($deadline) {
            $deadline = date('Y-m-d\TH:i', strtotime($deadline));
        }
    }
}

// Check for session messages (e.g., from a redirect after successful operation)
// This part is now less critical for success messages from POST, but useful for other redirects
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
    <title><?= ($action === 'add') ? 'Add New Quiz' : 'Edit Quiz' ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_quiz_edit.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-edit"></i> <?= ($action === 'add') ? 'Add New Quiz' : 'Edit Quiz: ' . htmlspecialchars($quiz_title) ?></h2>

        </div>

        <?php if (!empty($message)): ?>
            <?= $message ?>
        <?php endif; ?>

        <div class="form-overview">
            <h3><i class="fas fa-info-circle"></i> Quiz Details</h3>
            <div class="form-content">
                <form method="post" class="edit-quiz-form-style">
                    <label for="title">Quiz Title:</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($quiz_title) ?>" required>

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

                    <label for="deadline">Deadline:</label>
                    <input type="datetime-local" id="deadline" name="deadline" value="<?= htmlspecialchars($deadline) ?>" required>

                    <button type="submit"><i class="fas fa-save"></i> <?= ($action === 'add') ? 'Add Quiz' : 'Save Changes' ?></button>
                </form>
            </div>
        </div>

        <div class="back-buttons">
            <a href="teacher_quizzes.php" class="primary-button"><i class="fas fa-arrow-left"></i> Back to Quiz List</a>
            <a href="teacher_dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>

</body>
</html>