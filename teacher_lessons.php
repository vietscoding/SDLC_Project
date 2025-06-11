<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

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
    $conn->query("DELETE FROM lessons WHERE id = $remove_id AND course_id = $course_id");
    header("Location: teacher_lessons.php?course_id=$course_id");
    exit;
}

// Lấy danh sách bài giảng
$result = $conn->query("SELECT id, title FROM lessons WHERE course_id = $course_id ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lessons - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC FPT</title>
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
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5; /* Lighter grey background for the whole page */
            color: #333;
            line-height: 1.6;
            min-height: 10vh; /* Changed to 10vh to test if footer pushes up - this is a test. Revert to 100vh for production*/
            display: flex;
            overflow-x: hidden; /* Prevent horizontal scroll */
            flex-direction: column; /* Ensure main-wrapper and footer can stack */
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50 60%, #2980b9 100%);
            color: white;
            position: fixed;
            height: 100vh;
            max-height: 100vh;
            overflow-y: auto; /* Thêm dòng này để sidebar cuộn được */
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

        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px; /* Match sidebar width */
            padding: 30px;
            background-color: #f0f2f5; /* Match body background */
            display: flex;
            flex-direction: column; /* Allows content and footer to stack vertically */
            min-height: calc(100vh - 0px); /* Adjust based on sidebar height/body padding if any */
        }

        .main-content {
            background-color: #fff; /* White main content background */
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* More subtle shadow */
            padding: 30px;
            flex-grow: 1; /* Allow content to grow and push footer down */
        }

        .lessons-header { /* Renamed from .courses-header to be specific */
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px; /* Consistent margin */
            padding-bottom: 15px; /* Consistent padding */
            border-bottom: 1px solid #eee; /* Consistent border */
        }

        .lessons-header h2 {
            font-size: 2em; /* Consistent font size */
            color: #2c3e50; /* Consistent color */
            margin: 0;
            font-weight: 500; /* Consistent font weight */
        }

        .lessons-header h2 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for icon */
        }

        /* Styling for the "Back to My Courses" button */
        .lessons-header .button {
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

        .lessons-header .button:hover {
            background-color: #2980b9; /* Darker blue on hover */
        }

        .lessons-header .button i {
            margin-right: 8px;
        }

        .add-lesson-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px; /* Consistent border-radius */
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); /* Consistent shadow */
            margin-bottom: 30px;
            border: 1px solid #e0e0e0; /* Subtle border */
        }

        .add-lesson-section h3 {
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

        .add-lesson-section h3 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for icon */
        }

        .add-lesson-section label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500; /* Consistent font weight */
            font-size: 0.95em;
        }

        .add-lesson-section input[type="text"],
        .add-lesson-section textarea,
        .add-lesson-section input[type="file"] {
            width: 100%; /* Full width */
            padding: 12px; /* Increased padding */
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
        }

        .add-lesson-section input[type="text"]:focus,
        .add-lesson-section textarea:focus {
            border-color: #3498db; /* Blue focus border */
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); /* Blue shadow on focus */
        }

        .add-lesson-section button[type="submit"] {
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

        .add-lesson-section button[type="submit"]:hover {
            background-color: #219b56;
        }

        .add-lesson-section button[type="submit"] i {
            margin-right: 8px;
        }

        .lesson-list-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px; /* Consistent border-radius */
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); /* Consistent shadow */
            margin-bottom: 30px;
            border: 1px solid #e0e0e0; /* Subtle border */
        }

        .lesson-list-section h3 {
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

        .lesson-list-section h3 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for icon */
        }

        .lesson-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-top: 10px;
        }

        .lesson-item {
            background: rgba(255,255,255,0.98);
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
            display: flex;
            align-items: center;
            padding: 18px 22px;
            border: none;
            position: relative;
            transition: transform 0.18s cubic-bezier(.17,.67,.83,.67), box-shadow 0.18s;
            cursor: pointer;
        }

        .lesson-item:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 16px 40px rgba(243,156,18,0.18);
            background: linear-gradient(120deg, #f1c40f 0%, #fffbe6 100%);
        }

        .lesson-thumb {
            width: 48px;
            height: 48px;
            background: #f1c40f;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6em;
            margin-right: 18px;
            box-shadow: 0 2px 8px rgba(243,156,18,0.10);
            flex-shrink: 0;
        }

        .lesson-title {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.1em;
            flex-grow: 1;
            margin-right: 15px;
        }

        .lesson-item > div {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lesson-actions a {
            color: #3498db;
            text-decoration: none;
            font-size: 1em;
            transition: color 0.2s, background-color 0.2s;
            padding: 7px 10px;
            border-radius: 4px;
        }

        .lesson-actions a:hover {
            color: #fff;
            background: #3498db;
        }

        .lesson-actions .delete-btn {
            color: #e74c3c;
        }

        .lesson-actions .delete-btn:hover {
            background: #e74c3c;
            color: #fff;
        }

        .file-link {
            color: #27ae60;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            font-size: 1em;
            transition: color 0.2s, background-color 0.2s;
            padding: 7px 10px;
            border-radius: 4px;
        }

        .file-link:hover {
            color: #fff;
            background: #27ae60;
        }

        .file-link i {
            margin-right: 5px;
        }

        /* Responsive chỉnh lại cho mobile */
        @media (max-width: 768px) {
            .lesson-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 14px 10px;
            }
            .lesson-thumb {
                margin-bottom: 8px;
                margin-right: 0;
            }
            .lesson-title {
                margin-right: 0;
            }
            .lesson-item > div {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
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

        .dark-mode .lessons-header h2,
        .dark-mode .add-lesson-section h3,
        .dark-mode .lesson-list-section h3 {
            color: #f8f9fa;
        }
        
        .dark-mode .lessons-header h2 i,
        .dark-mode .add-lesson-section h3 i,
        .dark-mode .lesson-list-section h3 i {
            color: #f39c12;
        }

        .dark-mode .add-lesson-section,
        .dark-mode .lesson-list-section {
            background-color: #2a2a2a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border-color: #444;
        }

        .dark-mode .add-lesson-section label,
        .dark-mode .lesson-title {
            color: #eee;
        }

        .dark-mode .add-lesson-section input[type="text"],
        .dark-mode .add-lesson-section textarea,
        .dark-mode .add-lesson-section input[type="file"] {
            border-color: #555;
            background-color: #333;
            color: #eee;
        }

        .dark-mode .add-lesson-section input[type="text"]:focus,
        .dark-mode .add-lesson-section textarea:focus {
            border-color: #f39c12;
            box-shadow: 0 0 5px rgba(243, 156, 18, 0.3);
        }

        .dark-mode .add-lesson-section button[type="submit"] {
            background-color: #f39c12; /* Accent color for submit */
            color: #222;
        }

        .dark-mode .add-lesson-section button[type="submit"]:hover {
            background-color: #e68a00;
        }

        .dark-mode .lesson-item {
            background-color: #333;
            border-color: #444;
            box-shadow: 0 1px 5px rgba(0,0,0,0.2);
        }

        .dark-mode .lesson-actions a {
            color: #fbc531; /* Lighter accent for action links */
        }

        .dark-mode .lesson-actions a:hover {
            background-color: #4a4a4a;
        }
        
        .dark-mode .lesson-actions .delete-btn {
            color: #e74c3c;
        }
        .dark-mode .lesson-actions .delete-btn:hover {
            background-color: #4a4a4a;
            color: #c0392b;
        }


        .dark-mode .lesson-item .file-link {
            color: #2ecc71; /* Green for file link in dark mode */
        }
        .dark-mode .lesson-item .file-link:hover {
            background-color: #4a4a4a;
        }


        .dark-mode .no-lessons {
            background-color: #333;
            color: #ccc;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border-color: #444;
        }

        .dark-mode .no-lessons i {
            color: #f39c12;
        }

        .dark-mode .navigation-links a {
            color: #fbc531;
        }

        .dark-mode .navigation-links a:hover {
            color: #f39c12;
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

        /* Responsive adjustments (similar to My Courses) */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            .main-wrapper {
                margin-left: 220px;
            }
            .lessons-header h2 {
                font-size: 1.8em;
            }
            .add-lesson-section h3,
            .lesson-list-section h3 {
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

            .lessons-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .lessons-header h2 {
                margin-bottom: 10px;
                font-size: 1.8em;
            }
            .lessons-header .button {
                width: 100%; /* Full width button */
                justify-content: center; /* Center button content */
            }
            .add-lesson-section,
            .lesson-list-section {
                padding: 20px;
            }
            .add-lesson-section h3,
            .lesson-list-section h3 {
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
            .lessons-header h2 {
                font-size: 1.5em;
            }
            .add-lesson-section h3,
            .lesson-list-section h3 {
                font-size: 1.1em;
            }
            .lesson-item {
                flex-direction: column; /* Stack title and actions */
                align-items: flex-start;
                gap: 10px;
            }
            .lesson-item > div { /* Container for file link and actions */
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                gap: 5px;
            }
            .lesson-item .file-link,
            .lesson-actions {
                width: 100%; /* Full width for stacked elements */
            }
            .lesson-actions a {
                flex-grow: 1; /* Make action buttons fill width */
                text-align: center;
                justify-content: center;
            }
            footer {
                padding: 15px;
                font-size: 0.8em;
            }
            footer .contact-info p {
                font-size: 0.75em;
            }
        }

        .navigation-links {
            margin-top: 40px;
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
    </style>
</head>
<body>
    <?php
    // KHÔNG THAY ĐỔI BẤT KỲ LOGIC PHP NÀO Ở ĐÂY.
    // PHẦN NÀY CHỈ ĐỂ ĐẢM BẢO CÁC BIẾN CẦN THIẾT TỒN TẠI ĐỂ TRÁNH LỖI "UNDEFINED VARIABLE" 
    // KHI BẠN CHƯA KẾT NỐI DB THẬT VÀ CHẠY THỬ RIÊNG FILE NÀY.
    // TRONG MÔI TRƯỜNG PHP THẬT CỦA BẠN, CÁC BIẾN NÀY SẼ ĐƯỢC LẤY TỪ DATABASE HOẶC URL.
    if (!isset($course_title)) {
        $course_title = "Web Development Fundamentals (Placeholder)"; // Tiêu đề mặc định nếu chưa được set
    }
    if (!isset($message)) {
        $message = ''; // Mặc định không có thông báo
    }
    if (!isset($course_id)) {
        $course_id = 999; // ID mặc định nếu chưa được set
    }
    // QUAN TRỌNG: Biến $result PHẢI được lấy từ truy vấn database của bạn.
    // Nếu bạn đang chạy thử file này mà không có database, nó sẽ báo lỗi.
    // Bạn cần đảm bảo $result là một mysqli_result object hợp lệ.
    // Ví dụ (bạn sẽ có đoạn này trong file PHP của mình, không phải ở đây):
    // $sql = "SELECT id, title, content, video_link, file_path FROM lessons WHERE course_id = ?";
    // $stmt = $conn->prepare($sql);
    // $stmt->bind_param("i", $course_id);
    // $stmt->execute();
    // $result = $stmt->get_result();
    
    // Nếu bạn muốn test mà không có DB, bạn có thể tạo một mock object TẠM THỜI (chỉ để test giao diện):
    /*
    if (!isset($result)) {
        class MockMySQLiResultTemp {
            private $data;
            private $position = 0;
            public function __construct($array_data) { $this->data = $array_data; }
            public function num_rows() { return count($this->data); }
            public function fetch_assoc() {
                if ($this->position < count($this->data)) { return $this->data[$this->position++]; }
                return null;
            }
        }
        $dummy_lessons_temp = [
            ['id' => 101, 'title' => 'HTML Basics (Mock)', 'content' => 'HTML content.', 'video_link' => 'https://youtube.com/mock1', 'file_path' => 'uploads/mock_html.pdf'],
            ['id' => 102, 'title' => 'CSS Styling (Mock)', 'content' => 'CSS content.', 'video_link' => '', 'file_path' => 'uploads/mock_css.pptx'],
        ];
        $result = new MockMySQLiResultTemp($dummy_lessons_temp);
    }
    */
    // XÓA BỎ PHẦN MOCK NÀY KHI BẠN KẾT NỐI VỚI DB THẬT CỦA MÌNH!
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
            <div class="lessons-header">
                <h2><i class="fas fa-clipboard-list"></i> Manage Lessons for Course: <?= htmlspecialchars($course_title) ?></h2>
                <div>
                    <a href="teacher_courses.php" class="button"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <?= $message ?>
            <?php endif; ?>

            <div class="add-lesson-section">
                <h3><i class="fas fa-plus-circle"></i> Add New Lesson</h3>
                <form method="post" enctype="multipart/form-data">
                    <label for="title">Lesson Title:</label><br>
                    <input type="text" name="title" id="title" placeholder="Enter lesson title" required><br><br>

                    <label for="content">Lesson Content:</label><br>
                    <textarea name="content" id="content" rows="5" cols="60" placeholder="Enter lesson content" required></textarea><br><br>

                    <label for="video_link">Video Link (optional):</label><br>
                    <input type="text" name="video_link" id="video_link" placeholder="http://example.com/video"><br><br>
                    <label for="lesson_file">Upload File (PDF, DOCX, PPTX):</label><br>
                    <input type="file" name="lesson_file" id="lesson_file" accept=".pdf,.doc,.docx,.ppt,.pptx"><br><br>
                    <button type="submit"><i class="fas fa-plus"></i> Add Lesson</button>
                </form>
            </div>

            <div class="lesson-list-section">
                <h3><i class="fas fa-list-ul"></i> Lesson List</h3>
                <?php if (isset($result) && $result->num_rows > 0): // Sử dụng $result từ PHP thật của bạn ?>
                    <ul class="lesson-list">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li class="lesson-item">
                                <span class="lesson-thumb">
                                    <i class="fas fa-book-open"></i>
                                </span>
                                <span class="lesson-title"><?= htmlspecialchars($row['title']) ?></span>
                                <div>
                                    <?php if (!empty($row['file_path'])): ?>
                                        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="file-link" title="View Lesson File"><i class="fas fa-file-alt"></i> File</a>
                                    <?php endif; ?>
                                    <div class="lesson-actions">
                                        <a href="edit_lesson.php?course_id=<?= htmlspecialchars($course_id) ?>&lesson_id=<?= htmlspecialchars($row['id']) ?>" class="edit-btn" title="Edit Lesson"><i class="fas fa-edit"></i></a>
                                        <a href="teacher_lessons.php?course_id=<?= htmlspecialchars($course_id) ?>&remove_id=<?= htmlspecialchars($row['id']) ?>" onclick="return confirm('Remove this lesson?')" class="delete-btn" title="Remove Lesson"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-lessons"><i class="fas fa-info-circle"></i> No lessons have been added yet.</p>
                <?php endif; ?>
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
            <p>© <?= date('Y'); ?> BTEC FPT - Learning Management System.</p>
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

                    // Logic để kích hoạt 'active' cho "My Courses" khi đang ở teacher_courses.php hoặc teacher_lessons.php
                    if (linkFileName === 'teacher_courses.php' && 
                       (currentPath === 'teacher_courses.php' || currentPath.startsWith('teacher_lessons.php'))) {
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