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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignment: <?= htmlspecialchars($title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #fefce8 0%, #e0e7ff 100%);
            color: #222;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2563eb 60%, #fbbf24 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(37,99,235,0.10);
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: background 0.4s;
        }
        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%;
        }
        .sidebar .logo img {
            display: block;
            width: 70%;
            max-width: 150px;
            height: auto;
            margin: auto;
            filter: drop-shadow(0 2px 8px rgba(251,191,36,0.10));
        }
        .sidebar ul {
            list-style: none;
            width: 100%;
            padding: 0 15px;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            border-radius: 12px;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #1e40af;
            border: 2px solid #fbbf24;
        }
        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #fde68a;
            transition: color 0.2s;
        }
        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #2563eb;
        }
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
            width: 100%;
        }
        .main-content {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .submit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #fde68a;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            border-radius: 16px 16px 0 0;
            box-shadow: 0 2px 8px rgba(251,191,36,0.08);
            padding: 20px 30px;
        }
        .submit-header h2 {
            font-size: 2em;
            color: #fff;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(251,191,36,0.18);
        }
        .toggle-mode-btn {
            position: absolute;
            top: 18px;
            right: 30px;
            background: #fde68a;
            color: #2563eb;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }
        .toggle-mode-btn:hover {
            background: #2563eb;
            color: #fde68a;
        }
        .assignment-info {
            background-color: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border: 1.5px solid #fde68a;
            border-radius: 12px;
        }
        .assignment-info p {
            margin-bottom: 10px;
            color: #555;
        }
        .assignment-info strong {
            font-weight: bold;
            color: #2563eb;
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
            border: 1.5px solid #fde68a;
            border-radius: 12px;
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
            font-family: 'Roboto', sans-serif;
            font-size: 1em;
            line-height: 1.6;
        }
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
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.2s;
        }
        .file-upload-button:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
        }
        .file-upload-text {
            display: inline-block;
            margin-left: 10px;
            color: #555;
            font-size: 1em;
        }
        .submission-form button[type="submit"] {
            background: linear-gradient(90deg, #22c55e 0%, #a3e635 100%);
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(34,197,94,0.18);
            font-weight: 700;
            letter-spacing: 0.5px;
            border: 2px solid #22c55e;
        }
        .submission-form button[type="submit"]:hover {
            background: linear-gradient(90deg, #a3e635 0%, #22c55e 100%);
            color: #22c55e;
            box-shadow: 0 4px 16px rgba(34,197,94,0.22);
            border-color: #a3e635;
        }
        .navigation-links {
            margin-top: 30px;
            font-size: 1.1em;
            display: flex;
            gap: 18px;
        }
        .navigation-links a {
            color: #fff;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.18s;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
        }
        .navigation-links a:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
            transform: translateY(-3px) scale(1.06);
            text-decoration: none;
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.85em;
            color: #2563eb;
            background-color: #fde68a;
            border-top: 1px solid #fbbf24;
            border-radius: 0 0 12px 12px;
        }
        footer a {
            color: #2563eb;
            text-decoration: none;
            margin: 0 8px;
        }
        footer a:hover {
            text-decoration: underline;
            color: #fbbf24;
        }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }
        /* Dark Mode */
        .dark-mode {
            background-color: #1e293b;
            color: #fde68a;
        }
        .dark-mode .sidebar {
            background: linear-gradient(135deg, #1e293b 60%, #fbbf24 100%);
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }
        .dark-mode .main-wrapper {
            background-color: #1e293b;
        }
        .dark-mode .main-content {
            background-color: #1e293b;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .dark-mode .submit-header h2 {
            color: #fbbf24;
        }
        .dark-mode .submit-header {
            background: linear-gradient(90deg, #2563eb 0%, #1e293b 100%);
        }
        .dark-mode .assignment-info {
            background-color: #222;
            border-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .assignment-info strong {
            color: #fbbf24;
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
            background-color: #222;
            border-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .submission-form label {
            color: #fde68a;
        }
        .dark-mode .submission-form textarea,
        .dark-mode .submission-form input[type="file"] {
            background-color: #333;
            color: #fde68a;
            border-color: #fbbf24;
        }
        .dark-mode .file-upload-wrapper {
            background-color: #222;
            border-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .file-upload-button {
            background: #1e293b;
            color: #fbbf24;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .file-upload-button:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode .file-upload-text {
            color: #fde68a;
        }
        .dark-mode .navigation-links a {
            color: #fbbf24;
            background: #1e293b;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .navigation-links a:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode footer {
            background-color: #1e293b;
            color: #fde68a;
            border-top-color: #fbbf24;
        }
        .dark-mode footer a {
            color: #fbbf24;
        }
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .submit-header h2 { font-size: 1.8em; }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
                padding-top: 0;
            }
            .sidebar .logo { padding: 15px 0; }
            .sidebar .logo img { width: 50%; max-width: 120px; }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar ul li { width: 48%; margin-bottom: 5px; }
            .sidebar ul li a {
                justify-content: center;
                padding: 10px;
                text-align: center;
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span { display: block; font-size: 0.8em; }
            .main-wrapper { margin-left: 0; padding: 20px; }
            .submit-header { flex-direction: column; align-items: flex-start; }
            .submit-header h2 { margin-bottom: 10px; font-size: 1.8em; }
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; }
            .sidebar ul li a i { margin-right: 10px; margin-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
            <li><a href="student_assignments.php" class="active"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <div class="submit-header">
                <h2>Submit Assignment: <?= htmlspecialchars($title) ?></h2>
            </div>
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
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
            <?php if ($is_late): ?>
                <div class="error-message">Sorry, the submission deadline has passed. You can no longer submit this assignment.</div>
                <?php if (!empty($submitted_text) || !empty($submitted_file)): ?>
                    <div class="submission-form">
                        <h3>Your Submitted Work:</h3>
                        <?php if (!empty($submitted_text)): ?>
                            <p><strong>Text:</strong></p>
                            <p><?= nl2br(htmlspecialchars($submitted_text)) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($submitted_file)): ?>
                            <p><strong>File:</strong> <a href="<?= htmlspecialchars($submitted_file) ?>" target="_blank">View File</a></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="submission-form">
                    <form method="post" enctype="multipart/form-data">
                        <label for="submitted_text">Text Submission (optional):</label><br>
                        <textarea id="submitted_text" name="submitted_text" rows="6"><?= htmlspecialchars($submitted_text) ?></textarea>
                        <label for="submitted_file">Upload File (optional):</label><br>
                        <?php if (!empty($submitted_file)): ?>
                            <p>Current File: <a href="<?= htmlspecialchars($submitted_file) ?>" target="_blank">View Submitted File</a></p>
                        <?php endif; ?>
                        <div class="file-upload-wrapper">
                            <div class="file-upload-button"><i class="fas fa-upload"></i> Choose File</div>
                            <span class="file-upload-text">No file chosen</span>
                            <input type="file" id="submitted_file" name="submitted_file" accept=".pdf,.doc,.docx,.txt,.zip" onchange="this.parentNode.querySelector('.file-upload-text').innerText = this.value.split('\\').pop();">
                        </div><br>
                        <button type="submit"><i class="fas fa-upload"></i> Submit Assignment</button>
                    </form>
                </div>
            <?php endif; ?>
            <div class="navigation-links">
                <a href="student_assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
                <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
            </div>
            <hr style ="margin-top:30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));">
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
    </div>
    <script>
        // Toggle dark/light mode
        const btn = document.getElementById('toggleModeBtn');
        btn.onclick = function() {
            document.body.classList.toggle('dark-mode');
            btn.innerHTML = document.body.classList.contains('dark-mode')
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
        };
        // File upload button click
        document.querySelectorAll('.file-upload-button').forEach(function(btn){
            btn.onclick = function() {
                this.parentNode.querySelector('input[type=file]').click();
            }
        });
    </script>
</body>
</html>