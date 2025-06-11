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
    <title>Edit Lesson - <?= htmlspecialchars($title ?? 'Lesson') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Reset some default styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif; /* Changed to Roboto */
            background-color: #f0f2f5; /* Lighter grey background for the whole page */
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden; /* Prevent horizontal scroll */
            flex-direction: column; /* Allows main-wrapper and footer to stack */
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50 60%, #2980b9 100%);
            color: white;
            position: fixed;
            height: 100vh;
            max-height: 100vh;
            overflow-y: auto;
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
        .sidebar ul li a span {
            white-space: nowrap;
        }

        /* Nút đổi dark/light mode */
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

        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px; /* Match sidebar width */
            padding: 30px;
            background-color: #f0f2f5; /* Match body background */
            display: flex;
            flex-direction: column; /* Allows content and footer to stack vertically */
            min-height: 100vh; /* Ensure it takes full height */
        }

        .main-content {
            background-color: #fff; /* White main content background */
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* More subtle shadow */
            padding: 30px;
            flex-grow: 1; /* Allow content to grow and push footer down */
        }

        .edit-lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .edit-lesson-header h2 {
            font-size: 2em; /* Consistent font size */
            color: #2c3e50; /* Consistent color */
            margin: 0;
            font-weight: 500; /* Consistent font weight */
        }

        .edit-lesson-header h2 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for icon */
        }

        /* Styling for the "Back to Lessons" button */
        .edit-lesson-header .button {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background-color: #3498db; /* A blue color */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            font-weight: 500;
        }

        .edit-lesson-header .button:hover {
            background-color: #2980b9; /* Darker blue on hover */
        }

        .edit-lesson-header .button i {
            margin-right: 8px;
        }

        .edit-lesson-form {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px; /* Consistent border-radius */
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); /* Consistent shadow */
            margin-bottom: 30px;
            border: 1px solid #e0e0e0; /* Subtle border */
        }

        .edit-lesson-form h3 {
            font-size: 1.6em; /* Consistent font size */
            color: #2c3e50; /* Consistent heading color */
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee; /* Consistent border */
            padding-bottom: 10px;
            font-weight: 500;
        }

        .edit-lesson-form h3 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for icon */
        }

        .edit-lesson-form label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500; /* Consistent font weight */
            font-size: 0.95em;
        }

        .edit-lesson-form input[type="text"],
        .edit-lesson-form textarea,
        .edit-lesson-form input[type="file"] {
            width: 100%; /* Full width */
            padding: 12px; /* Increased padding */
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
        }

        .edit-lesson-form input[type="text"]:focus,
        .edit-lesson-form textarea:focus {
            border-color: #3498db; /* Blue focus border */
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); /* Blue shadow on focus */
        }

        .edit-lesson-form button[type="submit"] {
            background-color: #27ae60; /* Success green */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .edit-lesson-form button[type="submit"]:hover {
            background-color: #219b56;
        }

        .edit-lesson-form button[type="submit"] i {
            margin-right: 8px;
        }

        .navigation-links {
            margin-top: 40px; /* Consistent margin */
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
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
        @media (max-width: 768px) {
            .navigation-links {
                flex-direction: column;
                gap: 15px;
            }
            .navigation-links a {
                width: 100%;
                justify-content: center;
                font-size: 0.95em;
                padding: 10px 0;
            }
        }

        /* Message Box Styling (from My Courses) */
        .success-message, .error-message, .warning-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            border: 1px solid transparent; /* Default transparent border */
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .warning-message {
            background-color: #fff3cd;
            color: #85640c;
            border-color: #ffeeba;
        }

        .success-message i, .error-message i, .warning-message i {
            margin-right: 8px;
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
            margin-top: 40px; /* Adjusted margin-top to match your My Courses footer */
            font-size: 0.85em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px; /* Match border-radius of main-content */
            flex-shrink: 0; /* Prevent footer from shrinking */
        }

        footer a {
            color: #3498db; /* Consistent link color */
            text-decoration: none;
            margin: 0 8px;
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
            font-size: 0.85em;
        }

        /* Dark Mode */
        body.dark-mode {
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

        .dark-mode .edit-lesson-header h2,
        .dark-mode .edit-lesson-form h3 {
            color: #f8f9fa;
        }
        
        .dark-mode .edit-lesson-header h2 i,
        .dark-mode .edit-lesson-form h3 i {
            color: #f39c12;
        }

        .dark-mode .edit-lesson-form {
            background-color: #2a2a2a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border-color: #444;
        }

        .dark-mode .edit-lesson-form label {
            color: #eee;
        }

        .dark-mode .edit-lesson-form input[type="text"],
        .dark-mode .edit-lesson-form textarea,
        .dark-mode .edit-lesson-form input[type="file"] {
            border-color: #555;
            background-color: #333;
            color: #eee;
        }

        .dark-mode .edit-lesson-form input[type="text"]:focus,
        .dark-mode .edit-lesson-form textarea:focus {
            border-color: #f39c12;
            box-shadow: 0 0 5px rgba(243, 156, 18, 0.3);
        }

        .dark-mode .edit-lesson-form button[type="submit"] {
            background-color: #f39c12; /* Accent color for submit */
            color: #222;
        }

        .dark-mode .edit-lesson-form button[type="submit"]:hover {
            background-color: #e68a00;
        }

        .dark-mode .navigation-links a {
            background: linear-gradient(90deg, #222 0%, #444 100%);
            color: #fbc531;
        }

        .dark-mode .navigation-links a:hover {
            background: linear-gradient(90deg, #3498db 0%, #6dd5fa 100%);
            color: #fff;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
            border-radius: 0 0 8px 8px;
        }
        
        .dark-mode footer a {
            color: #fbc531;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            .main-wrapper {
                margin-left: 220px;
            }
            .edit-lesson-header h2 {
                font-size: 1.8em;
            }
            .edit-lesson-form h3 {
                font-size: 1.4em;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column; /* Stack sidebar and main content */
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
                padding-top: 0;
            }
            .sidebar .logo {
                padding: 15px 0;
                margin-bottom: 0;
            }
            .sidebar .logo img {
                width: 50%;
                max-width: 120px;
            }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar ul li {
                width: 48%; /* Two items per row */
                margin-bottom: 5px;
            }
            .sidebar ul li a {
                flex-direction: column; /* Stack icon and text */
                justify-content: center;
                padding: 10px;
                text-align: center;
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span {
                display: block; /* Ensure text is on a new line */
                font-size: 0.8em;
            }

            .main-wrapper {
                margin-left: 0;
                padding: 20px;
            }

            .edit-lesson-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .edit-lesson-header h2 {
                margin-bottom: 10px;
                font-size: 1.8em;
            }
            .edit-lesson-header .button {
                width: 100%; /* Full width button */
                justify-content: center; /* Center button content */
            }
            .edit-lesson-form {
                padding: 20px;
            }
            .edit-lesson-form h3 {
                font-size: 1.3em;
            }
            .navigation-links {
                margin-top: 30px;
            }
            .navigation-links a {
                font-size: 0.9em;
            }
        }

        @media (max-width: 480px) {
            .sidebar ul li {
                width: 95%; /* One item per row */
            }
            .sidebar ul li a {
                flex-direction: row; /* Back to row for very small screens if desired, or keep column */
                justify-content: flex-start;
            }
            .sidebar ul li a i {
                margin-right: 10px;
                margin-bottom: 0;
            }
            .sidebar ul li a span {
                display: inline; /* Display inline again */
            }
            .main-content {
                padding: 15px;
            }
            .edit-lesson-header h2 {
                font-size: 1.5em;
            }
            .edit-lesson-form h3 {
                font-size: 1.1em;
            }
            footer {
                padding: 15px;
                font-size: 0.8em;
            }
            footer .contact-info p {
                font-size: 0.75em;
            }
        }
    </style>
</head>
<body>

    <?php
    // KHÔNG THAY ĐỔI BẤT KỲ LOGIC PHP NÀO Ở ĐÂY.
    // PHẦN NÀY CHỈ ĐỂ ĐẢM BẢO CÁC BIẾN CẦN THIẾT TỒN TẠI ĐỂ TRÁNH LỖI "UNDEFINED VARIABLE"
    // KHI BẠN CHƯA KẾT NỐI DB THẬT VÀ CHẠY THỬ RIÊNG FILE NÀY.
    // TRONG MÔI TRƯỜNG PHP THẬT CỦA BẠN, CÁC BIẾN NÀY SẼ ĐƯỢC LẤY TỪ DATABASE HOẶC URL.
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

    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="teacher_courses.php" class="sidebar-link"><i class="fas fa-book"></i> <span>My Courses</span></a></li>
            <li><a href="teacher_search_courses.php" class="sidebar-link"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="teacher_quiz_results.php" class="sidebar-link"><i class="fas fa-chart-bar"></i> <span>View Quiz Results</span></a></li>
            <li><a href="teacher_assignments.php" class="sidebar-link"><i class="fas fa-tasks"></i> <span>Manage Assignments</span></a></li>
            <li><a href="teacher_notifications.php" class="sidebar-link"><i class="fas fa-bell"></i> <span>Send Notifications</span></a></li>
            <li><a href="teacher_view_notifications.php" class="sidebar-link"><i class="fas fa-envelope-open-text"></i> <span>View Notifications</span></a></li>
            <li><a href="teacher_quizzes.php" class="sidebar-link"><i class="fas fa-question-circle"></i> <span>Manage Quizzes</span></a></li>
            <li><a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> <span>Log out</span></a></li>
        </ul>
    </div>

    <div class="main-wrapper">
        <div class="main-content">
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="edit-lesson-header">
                <h2><i class="fas fa-edit"></i> Edit Lesson</h2>
                <a href="teacher_lessons.php?course_id=<?= htmlspecialchars($course_id) ?>" class="button"><i class="fas fa-arrow-left"></i> Back to Lessons</a>
            </div>

            <?php if (!empty($message)): ?>
                <?= $message ?>
            <?php endif; ?>

            <div class="edit-lesson-form">
                <h3><i class="fas fa-info-circle"></i> Editing Lesson</h3>
                <form method="post" enctype="multipart/form-data">
                    <label for="title">Lesson Title:</label><br>
                    <input type="text" name="title" id="title" value="<?= htmlspecialchars($title) ?>" required><br><br>

                    <label for="content">Lesson Content:</label><br>
                    <textarea name="content" id="content" rows="7" cols="60" required><?= htmlspecialchars($content) ?></textarea><br><br>

                    <label for="video_link">Video Link (optional):</label><br>
                    <input type="text" name="video_link" id="video_link" value="<?= htmlspecialchars($video) ?>"><br><br>
                    
                    <label for="file">Lesson Document (optional, PDF/DOCX/PPT...):</label><br>
                    <input type="file" name="file" id="file"><br><br>

                    <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>

            <div class="navigation-links">
                <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
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
        // JavaScript để highlight link sidebar tương ứng
        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname.split('/').pop();
            const sidebarLinks = document.querySelectorAll('.sidebar ul li a.sidebar-link');

            sidebarLinks.forEach(link => {
                // Xóa active class cũ nếu có
                link.classList.remove('active');

                const linkHref = link.getAttribute('href');
                if (linkHref) {
                    const linkFileName = linkHref.split('/').pop();

                    // Logic để kích hoạt 'active' cho "My Courses" khi đang ở teacher_courses.php hoặc teacher_lessons.php hoặc edit_lesson.php
                    if (linkFileName === 'teacher_courses.php' && 
                       (currentPath === 'teacher_courses.php' || 
                        currentPath.startsWith('teacher_lessons.php') || 
                        currentPath.startsWith('edit_lesson.php'))) {
                        link.classList.add('active');
                    } else if (linkFileName === currentPath) {
                        link.classList.add('active');
                    }
                }
            });
        });

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