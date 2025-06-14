<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$action = $_GET['action'] ?? 'add';  // 'add' hoặc 'edit'
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$title = '';
$description = '';
$due_date = '';
$course_id = 0;
$error = '';
$file_path = '';
$current_file = '';

// Lấy danh sách tất cả khóa học
$courses_result = $conn->query("SELECT id, title FROM courses");

// Nếu là sửa, lấy dữ liệu bài tập hiện tại
if ($action === 'edit' && $assignment_id > 0) {
    $stmt = $conn->prepare("SELECT title, description, due_date, course_id, file_path FROM assignments WHERE id = ?");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $stmt->bind_result($title, $description, $due_date, $course_id, $file_path);
    if (!$stmt->fetch()) {
        echo "Assignment not found.";
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

        move_uploaded_file($file_tmp, $target_file);
        $upload_path = $target_file;
    }

    if (empty($title) || empty($description) || $course_id <= 0) {
        $error = "Please fill all required fields.";
    } else {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, due_date, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $course_id, $title, $description, $due_date, $upload_path);
            $stmt->execute();
            $stmt->close();
        } else if ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ?, course_id = ?, file_path = ? WHERE id = ?");
            $stmt->bind_param("sssisi", $title, $description, $due_date, $course_id, $upload_path, $assignment_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: admin_assignments.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= ($action === 'add') ? 'Add New Assignment' : 'Edit Assignment' ?> | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f6f8;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 200px;
            background-color: #34495e;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 40px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }
        .sidebar .logo {
            text-align: center;
            padding: 10px 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .sidebar .logo img {
            width: 70%;
            max-width: 110px;
            height: auto;
            margin: 0 auto;
            display: block;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 10px 10px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 4px solid transparent;
            font-size: 0.95em;
            white-space: normal;
            word-break: break-word;
            min-height: 38px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #e74c3c;
        }
        .sidebar ul li a i {
            margin-right: 10px;
            font-size: 1em;
        }
        .main-content {
            margin-left: 200px;
            padding: 40px 30px 30px 30px;
            flex-grow: 1;
            min-height: 100vh;
            background: #f4f6f8;
        }
        .edit-assignment-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
            justify-content: flex-start;
            gap: 16px;
        }
        .edit-assignment-header h2 {
            font-size: 2em;
            color: #333;
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        .edit-assignment-header h2 i {
            margin-right: 10px;
            color: #e74c3c;
        }
        .error-message {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #d32f2f;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-container {
            background-color: #fff;
            padding: 30px 24px 24px 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(44,62,80,0.07);
            margin-bottom: 20px;
            max-width: 800px;      /* tăng từ 600px lên 800px */
            margin-left: auto;
            margin-right: auto;
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        .form-container input[type="text"],
        .form-container input[type="date"],
        .form-container select,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f9f9f9;
        }
        .form-container input[type="text"]:focus,
        .form-container input[type="date"]:focus,
        .form-container select:focus,
        .form-container textarea:focus {
            border-color: #e67e22;
            box-shadow: 0 0 0 0.2rem rgba(230, 126, 34, 0.13);
            outline: none;
        }
        .form-container textarea {
            resize: vertical;
        }
        .form-container button[type="submit"] {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .form-container button[type="submit"]:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            box-shadow: 0 4px 16px rgba(243,156,18,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .form-container .current-file {
            margin-bottom: 15px;
            color: #2980b9;
            font-size: 0.98em;
        }
        .navigation-links {
            margin-top: 30px;
        }
        .navigation-links a {
            display: inline-flex;
            align-items: center;
            color: #fff;
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            border-radius: 6px;
            padding: 12px 26px;
            box-shadow: 0 2px 8px rgba(243,156,18,0.10);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            border: none;
            outline: none;
            gap: 8px;
        }
        .navigation-links a:hover {
            background: linear-gradient(90deg, #2980b9 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .navigation-links a i {
            margin-right: 8px;
            font-size: 1.1em;
        }
        hr { margin-top: 30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0)); }
        footer {
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
        footer a:hover { text-decoration: underline; }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 20px; }
            .sidebar { position: static; width: 100%; height: auto; }
        }
        @media (max-width: 600px) {
            .main-content { padding: 10px; }
            .form-container { padding: 10px; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo">
        <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
    </div>
    <ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_courses.php"><i class="fas fa-book"></i> Manage Courses</a></li>
        <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="admin_approve_teachers.php"><i class="fas fa-user-check"></i> User authorization</a></li>
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_assignments.php" class="active"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>
<div class="main-content">
    <div class="edit-assignment-header">
        <h2><i class="fas fa-edit"></i> <?= ($action === 'add') ? 'Add New Assignment' : 'Edit Assignment' ?></h2>
    </div>
    <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <div class="form-container">
        <form method="post" enctype="multipart/form-data">
            <label for="title">Assignment Title:</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($description) ?></textarea>
            <label for="due_date">Due Date:</label>
            <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($due_date) ?>">
            <label for="course_id">Select Course:</label>
            <select id="course_id" name="course_id" required>
                <option value="">-- Select a Course --</option>
                <?php
                // Reset pointer nếu đã fetch ở trên
                $courses_result->data_seek(0);
                while ($course = $courses_result->fetch_assoc()): ?>
                    <option value="<?= $course['id'] ?>" <?= ($course['id'] == $course_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['title']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="file">Attachment (optional, PDF/DOCX/PPT...):</label>
            <input type="file" id="file" name="file"><br>
            <?php if (!empty($current_file)): ?>
                <div class="current-file">
                    📎 Current file: <a href="<?= htmlspecialchars($current_file) ?>" target="_blank">Download</a>
                </div>
            <?php endif; ?>
            <button type="submit"><i class="fas fa-save"></i> <?= ($action === 'add') ? 'Add Assignment' : 'Save Changes' ?></button>
        </form>
    </div>
    <div class="navigation-links">
        <a href="admin_assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments List</a>
    </div>
    <hr>
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