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
        header("Location: teacher_assignments.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= ($action === 'add') ? 'Add New Assignment' : 'Edit Assignment' ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e7ef 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50 60%, #2980b9 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(44,62,80,0.15);
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
            color: white;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            border-radius: 8px;
            margin-bottom: 10px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            transform: translateX(8px) scale(1.05);
            box-shadow: 0 2px 8px rgba(243,156,18,0.15);
        }
        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #f1c40f;
            transition: color 0.2s;
        }
        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #2c3e50;
        }
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
        }
        .main-content {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .toggle-mode-btn {
            position: absolute;
            top: 18px;
            right: 30px;
            background: #fff;
            color: #2c3e50;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }
        .toggle-mode-btn:hover {
            background: #f1c40f;
            color: #fff;
        }
        .edit-assignment-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            background: linear-gradient(90deg, #f1c40f 0%, #f39c12 100%);
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 8px rgba(243,156,18,0.08);
            padding: 20px 30px;
        }
        .edit-assignment-header h2 {
            font-size: 2em;
            color: #2c3e50;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(241,196,15,0.08);
            display: flex;
            align-items: center;
        }
        .edit-assignment-header h2 i {
            margin-right: 10px;
            color: #f39c12;
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
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            flex-grow: 1;
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-container input[type="text"],
        .form-container input[type="date"],
        .form-container select,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-container input[type="text"]:focus,
        .form-container input[type="date"]:focus,
        .form-container select:focus,
        .form-container textarea:focus {
            border-color: #f1c40f;
            box-shadow: 0 0 0 0.2rem rgba(241, 196, 15, 0.15);
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-container button[type="submit"]:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            box-shadow: 0 4px 16px rgba(243,156,18,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 5px;
            background-color: #ecf0f1;
            border: 1px solid #dcdcdc;
            transition: background 0.2s, color 0.2s;
        }
        .back-link a i {
            margin-right: 8px;
        }
        .back-link a:hover {
            color: #2980b9;
            background-color: #e0e6eb;
            border-color: #c0c6cb;
        }
        hr {
            margin-top: 30px;
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.85em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px;
        }
        footer a {
            color: #3498db;
            text-decoration: none;
            margin: 0 8px;
        }
        footer a:hover {
            text-decoration: underline;
        }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }
        /* Dark Mode */
        .dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }
        .dark-mode .sidebar {
            background-color: #333;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }
        .dark-mode .main-wrapper {
            background-color: #1a1a1a;
        }
        .dark-mode .main-content {
            background-color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .dark-mode .edit-assignment-header {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
        }
        .dark-mode .edit-assignment-header h2,
        .dark-mode .edit-assignment-header h2 i {
            color: #181e29;
        }
        .dark-mode .form-container {
            background-color: #2a2a2a;
            color: #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        .dark-mode .form-container label {
            color: #ccc;
        }
        .dark-mode .form-container input[type="text"],
        .dark-mode .form-container input[type="date"],
        .dark-mode .form-container select,
        .dark-mode .form-container textarea {
            background-color: #3a3a3a;
            color: #eee;
            border-color: #555;
        }
        .dark-mode .form-container input[type="text"]:focus,
        .dark-mode .form-container input[type="date"]:focus,
        .dark-mode .form-container select:focus,
        .dark-mode .form-container textarea:focus {
            border-color: #f39c12;
            box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
        }
        .dark-mode .form-container button[type="submit"] {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #181e29;
        }
        .dark-mode .form-container button[type="submit"]:hover {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
        }
        .dark-mode .back-link a {
            color: #f39c12;
            background-color: #3a3a3a;
            border-color: #555;
        }
        .dark-mode .back-link a:hover {
            background-color: #4a4a4a;
            border-color: #666;
        }
        .dark-mode footer {
            background: #23272f;
            color: #aaa;
        }
        .dark-mode footer a {
            color: #ffe082;
        }
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .edit-assignment-header h2 { font-size: 1.8em; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
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
                flex-direction: column;
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span {
                display: block;
                font-size: 0.8em;
            }
            .main-wrapper { margin-left: 0; padding: 20px; }
            .main-content { padding: 20px; }
            .edit-assignment-header { flex-direction: column; align-items: flex-start; margin-bottom: 20px; }
            .edit-assignment-header h2 { margin-bottom: 10px; font-size: 1.8em; }
            .form-container { padding: 15px; }
            .back-link { margin-top: 25px; }
            .back-link a { width: 100%; justify-content: center; }
            footer { margin-top: 25px; }
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; flex-direction: row; }
            .sidebar ul li a i { margin-right: 10px; margin-bottom: 0; }
            .main-wrapper { padding: 15px; }
            .main-content { padding: 15px; }
            .edit-assignment-header h2 { font-size: 1.6em; }
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="teacher_courses.php"><i class="fas fa-book"></i> <span>My Courses</span></a></li>
            <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> <span>View Quiz Results</span></a></li>
            <li><a href="teacher_assignments.php" class="active"><i class="fas fa-tasks"></i> <span>Manage Assignments</span></a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> <span>Send Notifications</span></a></li>
            <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> <span>View Notifications</span></a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> <span>Manage Quizzes</span></a></li>
            <li><a href="teacher_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
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
                        <?php while ($course = $courses_result->fetch_assoc()): ?>
                            <option value="<?= $course['id'] ?>" <?= ($course['id'] == $course_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label for="file">Attachment (optional, PDF/DOCX/PPT...):</label>
                    <input type="file" id="file" name="file"><br><br>
                    <?php if (!empty($current_file)): ?>
                    <p>📎 Current file: <a href="<?= htmlspecialchars($current_file) ?>" target="_blank">Download</a></p>
                    <?php endif; ?>
                    <button type="submit"><i class="fas fa-save"></i> <?= ($action === 'add') ? 'Add Assignment' : 'Save Changes' ?></button>
                </form>
            </div>
            <div class="navigation-links" style="margin-top:30px;">
    <a href="teacher_assignments.php" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(90deg,#f39c12 0%,#f1c40f 100%);color:#fff;padding:12px 26px;border-radius:6px;text-decoration:none;font-weight:600;font-size:1em;box-shadow:0 2px 8px rgba(243,156,18,0.10);transition:background 0.2s,color 0.2s,box-shadow 0.2s,transform 0.15s;border:none;outline:none;">
        <i class="fas fa-arrow-left"></i> Back to Assignments List
    </a>
</div>
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
    <script>
        // Toggle dark/light mode
        const btn = document.getElementById('toggleModeBtn');
        btn.onclick = function() {
            document.body.classList.toggle('dark-mode');
            btn.innerHTML = document.body.classList.contains('dark-mode')
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
        };
    </script>
</body>
</html>