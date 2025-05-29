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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignment: <?= htmlspecialchars($title) ?> | [Your University Name]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset some default styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.7;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            background-color: #0056b3;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .sidebar .logo img {
            display: block;
            width: 80%;
            height: auto;
            margin:auto;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #fbc531;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.2em;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            flex-grow: 1;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        .submit-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .submit-header h2 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .assignment-info {
            background-color: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .assignment-info p {
            margin-bottom: 10px;
            color: #555;
        }

        .assignment-info strong {
            font-weight: bold;
            color: #0056b3;
        }

        .assignment-description {
            white-space: pre-line;
        }

        .error-message {
            color: #c0392b;
            background-color: #fdecea;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #e74c3c;
        }

        .success-message {
            color: #27ae60;
            background-color: #e6ffe9;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #2ecc71;
        }

        .submission-form {
            background-color: white;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .submission-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .submission-form textarea {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Open Sans', sans-serif;
            font-size: 1em;
            line-height: 1.6;
        }

        .submission-form input[type="file"] {
            margin-bottom: 15px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .submission-form button[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .submission-form button[type="submit"]:hover {
            background-color: #218838;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .navigation-links {
            margin-top: 30px;
            font-size: 1.1em;
        }

        .navigation-links a {
            color: #0056b3;
            text-decoration: none;
            margin-right: 20px;
            font-weight: 600;
        }

        .navigation-links a:hover {
            text-decoration: underline;
            color: #004080;
        }

        footer{
        text-align: center;
            padding: 30px;
            margin-top: 40px;
            font-size: 0.9em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px;
        }

        footer a {
            color: #0056b3;
            text-decoration: none;
            margin: 0 5px;
        }

        footer a:hover {
            text-decoration: underline;
        }

        footer p {
            margin: 5px 0;
        }

        footer .contact-info {
            margin-top: 15px;
        }

        footer .contact-info p {
            margin: 3px 0;
        }

        /* Dark Mode Footer */
        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }

        .dark-mode footer a {
            color: #fbc531;
        }


        /* Dark Mode (Optional - Add a class 'dark-mode' to the body) */
        .dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #222;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-content {
            background-color: #333;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .submit-header h2 {
            color: #f8f9fa;
        }

        .dark-mode .assignment-info {
            background-color: #444;
            border-color: #555;
            color: #eee;
        }

        .dark-mode .assignment-info strong {
            color: #fbc531;
        }

        .dark-mode .error-message {
            color: #e74c3c;
            background-color: #553939;
            border-color: #c0392b;
        }

        .dark-mode .success-message {
            color: #2ecc71;
            background-color: #385442;
            border-color: #27ae60;
        }

        .dark-mode .submission-form {
            background-color: #333;
            border-color: #555;
        }

        .dark-mode .submission-form label {
            color: #eee;
        }

        .dark-mode .submission-form textarea,
        .dark-mode .submission-form input[type="file"] {
            background-color: #444;
            color: #eee;
            border-color: #555;
        }

        .dark-mode .submission-form button[type="submit"] {
            background-color: #a7f3d0;
            color: #222;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .submission-form button[type="submit"]:hover {
            background-color: #86efac;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .navigation-links a {
            color: #fbc531;
        }

        .dark-mode .navigation-links a:hover {
            color: #fbb003;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }
        /* ... other styles ... */

        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            padding: 8px 12px;
            margin-bottom: 15px;
        }

        .file-upload-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
        }

        .file-upload-button {
            background-color: #0056b3;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s ease;
        }

        .file-upload-button:hover {
            background-color: #004080;
        }

        .file-upload-text {
            display: inline-block;
            margin-left: 10px;
            color: #555;
            font-size: 1em;
        }

        /* Dark Mode adjustments */
        .dark-mode .file-upload-wrapper {
            background-color: #333;
            border-color: #555;
            color: #eee;
        }

        .dark-mode .file-upload-button {
            background-color: #fbc531;
            color: #222;
        }

        .dark-mode .file-upload-button:hover {
            background-color: #fbb003;
        }

        .dark-mode .file-upload-text {
            color: #ccc;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> Academic Progress</a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="student_assignments.php" class="active"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> Grades & Results</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="submit-header">
            <h2>Submit Assignment: <?= htmlspecialchars($title) ?></h2>
            </div>

        <div class="assignment-info">
            <p><strong>Course:</strong> <?= htmlspecialchars($course_title) ?></p>
            <p><strong>Due Date:</strong> <?= date('Y-m-d H:i', strtotime($due_date)) ?></p>
            <p><strong>Description:</strong> <span class="assignment-description"><?= nl2br(htmlspecialchars($description)) ?></span></p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="submission-form">
            <form method="post" enctype="multipart/form-data">
                <label for="submitted_text">Text Submission (optional):</label><br>
                <textarea id="submitted_text" name="submitted_text" rows="6"></textarea><br><br>

                <label for="submitted_file">Upload File (optional):</label><br>
                <div class="file-upload-wrapper">
                    <div class="file-upload-button"><i class="fas fa-upload"></i> Choose File</div>
                    <span class="file-upload-text">No file chosen</span>
                    <input type="file" id="submitted_file" name="submitted_file" accept=".pdf,.doc,.docx,.txt,.zip" onchange="this.parentNode.querySelector('.file-upload-text').innerText = this.value.split('\\').pop();">
                </div><br>

                <button type="submit"><i class="fas fa-upload"></i> Submit Assignment</button>
            </form>
        </div>

        <div class="navigation-links">
            <a href="student_assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
            <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <hr style ="margin-top:30px; ">
            <footer>
            <a href="https://www.facebook.com/btecfptdn/?locale=vi_VN" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
            |
            <a href="https://international.fpt.edu.vn/" target="_blank"><i class="fas fa-globe"></i> Website</a>
            |
            <a href="tel:02473099588"><i class="fas fa-phone"></i> 024 730 99 588</a>
<br>
            <p>Address: 66 Võ Văn Tần, Quận Thanh Khê, Đà Nẵng</p>
            <div class="contact-info">
                <p>Email:</p>
                <p>Academic Department: <a href="mailto:Academic.btec.dn@fe.edu.vn">Academic.btec.dn@fe.edu.vn</a></p>
                <p>SRO Department: <a href="mailto:sro.btec.dn@fe.edu.vn">sro.btec.dn@fe.edu.vn</a></p>
                <p>Finance Department: <a href="mailto:accounting.btec.dn@fe.edu.vn">accounting.btec.dn@fe.edu.vn</a></p>
            </div>
            <p>&copy; <?= date('Y'); ?> BTEC FPT - Learning Management System.</p>
            <small>Powered by Innovation in Education</small>
        </footer>

    </div>
</body>
</html>