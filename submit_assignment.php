<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

if (!isset($_GET['assignment_id'])) {
    echo "Assignment ID missing.";
    exit;
}

$assignment_id = intval($_GET['assignment_id']);

// Lấy thông tin assignment
$stmt = $conn->prepare("
    SELECT a.title, a.description, a.due_date, c.title AS course_title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON e.course_id = c.id
    WHERE a.id = ? AND e.user_id = ?
");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$stmt->bind_result($title, $description, $due_date, $course_title);
if (!$stmt->fetch()) {
    echo "You are not enrolled in this course or assignment does not exist.";
    exit;
}
$stmt->close();
$is_late = (strtotime($due_date) < time());
// Kiểm tra nếu học viên đã nộp bài
$submitted_text = '';
$submitted_file = '';

$stmt = $conn->prepare("SELECT submitted_text, submitted_file FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($submitted_text, $submitted_file);
    $stmt->fetch();
}
$stmt->close();

$error = '';
$success = '';

// Xử lý nộp bài
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_text = trim($_POST['submitted_text'] ?? '');
    $uploaded_file = '';

    // Xử lý upload file (nếu có)
    if (isset($_FILES['submitted_file']) && $_FILES['submitted_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/assignments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $tmp_name = $_FILES['submitted_file']['tmp_name'];
        $name = basename($_FILES['submitted_file']['name']);
        $target_file = $upload_dir . time() . '_' . $name;

        if (move_uploaded_file($tmp_name, $target_file)) {
            $uploaded_file = $target_file;
        } else {
            $error = "Failed to upload file.";
        }
    }

    if (empty($submitted_text) && empty($uploaded_file)) {
        $error = "Please submit text or upload a file.";
    }

    if (empty($error)) {
        // Kiểm tra xem học viên đã nộp chưa (cập nhật nếu có)
        $check_stmt = $conn->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $assignment_id, $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            // Update bài nộp
            $update_stmt = $conn->prepare("UPDATE assignment_submissions SET submitted_text = ?, submitted_file = ?, submitted_at = NOW() WHERE assignment_id = ? AND user_id = ?");
            $update_stmt->bind_param("ssii", $submitted_text, $uploaded_file, $assignment_id, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert bài nộp mới
            $insert_stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, user_id, submitted_text, submitted_file) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("iiss", $assignment_id, $user_id, $submitted_text, $uploaded_file);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        $check_stmt->close();

        $success = "Submission successful!";
        // Tìm giáo viên quản lý khóa học
        $result_teacher = $conn->query("
            SELECT c.teacher_id, c.title AS course_title
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            WHERE a.id = $assignment_id
        ");
        $info = $result_teacher->fetch_assoc();
        $teacher_id = $info['teacher_id'];
        $course_title = $info['course_title'];
        $result_teacher->close();

        // Gửi notification cho giáo viên
        $notif_msg = $_SESSION['fullname'] . " has submitted an assignment for your course: " . $course_title;
        $stmt_notify_teacher = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt_notify_teacher->bind_param("is", $teacher_id, $notif_msg);
        $stmt_notify_teacher->execute();
        $stmt_notify_teacher->close();
    }

}

// Get user fullname and role for the header (assuming these are in $_SESSION from login)
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignment: <?= htmlspecialchars($title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/student/submit_assignment.css">
 
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="submit-header">
            <h2><i class="fas fa-upload"></i> Submit Assignment: <?= htmlspecialchars($title) ?></h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>

        

        <div class="assignment-info">
            <p><strong>Course:</strong> <?= htmlspecialchars($course_title) ?></p>
            <p><strong>Due Date:</strong> <?= date('Y-m-d H:i', strtotime($due_date)) ?></p>
            <p><strong>Description:</strong> <span class="assignment-description"><?= nl2br(htmlspecialchars($description)) ?></span></p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($is_late): ?>
            <div class="error-message"><i class="fas fa-hourglass-end"></i>Sorry, the submission deadline has passed. You can no longer submit this assignment.</div>
            <?php if (!empty($submitted_text) || !empty($submitted_file)): ?>
                <div class="submission-form-container">
                    <h3>Your Submitted Work:</h3>
                    <?php if (!empty($submitted_text)): ?>
                        <p><strong>Text:</strong></p>
                        <p style="white-space: pre-line;"><?= htmlspecialchars($submitted_text) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($submitted_file)): ?>
                        <p><strong>File:</strong> <a href="<?= htmlspecialchars($submitted_file) ?>" target="_blank">View File</a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="submission-form-container">
                <form method="post" enctype="multipart/form-data" class="submission-form">
                    <label for="submitted_text">Text Submission (optional):</label>
                    <textarea id="submitted_text" name="submitted_text" rows="6" placeholder="Type your submission here..."><?= htmlspecialchars($submitted_text) ?></textarea>
                    
                    <label for="submitted_file">Upload File (optional):</label>
                    <?php if (!empty($submitted_file)): ?>
                        <p style="margin-bottom: 10px;">Current File: <a href="<?= htmlspecialchars($submitted_file) ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">View Submitted File</a></p>
                    <?php endif; ?>
                    <div class="file-upload-wrapper">
                        <div class="file-upload-button"><i class="fas fa-upload"></i> Choose File</div>
                        <span class="file-upload-text"><?= !empty($submitted_file) ? basename($submitted_file) : 'No file chosen' ?></span>
                        <input type="file" id="submitted_file" name="submitted_file" accept=".pdf,.doc,.docx,.txt,.zip" onchange="this.parentNode.querySelector('.file-upload-text').innerText = this.value.split('\\').pop();">
                    </div>
                    <button type="submit"><i class="fas fa-paper-plane"></i> Submit Assignment</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="student_assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
            <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>

    <script src="js/student_sidebar.js"></script>
 
</body>
</html>