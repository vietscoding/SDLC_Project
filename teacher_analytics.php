<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($course_title);
if (!$stmt->fetch()) {
    echo "You are not allowed to access this course analytics.";
    exit;
}
$stmt->close();

// Tổng số học viên đã enroll
$result1 = $conn->query("SELECT COUNT(*) AS total_students FROM enrollments WHERE course_id = $course_id");
$total_students = $result1->fetch_assoc()['total_students'];

// Tổng số bài học
$result2 = $conn->query("SELECT COUNT(*) AS total_lessons FROM lessons WHERE course_id = $course_id");
$total_lessons = $result2->fetch_assoc()['total_lessons'];

// Tỉ lệ hoàn thành bài học trung bình (%)
$avg_progress = 0;
if ($total_lessons > 0 && $total_students > 0) {
    $result3 = $conn->query("
        SELECT AVG(lesson_count / $total_lessons) * 100 AS avg_completion
        FROM (
            SELECT COUNT(DISTINCT lesson_id) AS lesson_count 
            FROM progress 
            WHERE course_id = $course_id AND is_completed = 1
            GROUP BY user_id
        ) AS temp
    ");
    $avg_progress = round($result3->fetch_assoc()['avg_completion'], 2);
}

// Điểm quiz trung bình
$result4 = $conn->query("
    SELECT AVG(score) AS avg_score 
    FROM quiz_submissions 
    WHERE quiz_id IN (SELECT id FROM quizzes WHERE course_id = $course_id)
");
$avg_score = round($result4->fetch_assoc()['avg_score'], 2);

// --- MỚI: Thống kê bài tập (assignments) ---
// Tổng số assignments trong khóa
$result5 = $conn->query("SELECT COUNT(*) AS total_assignments FROM assignments WHERE course_id = $course_id");
$total_assignments = $result5->fetch_assoc()['total_assignments'];

// Tổng số bài nộp assignments
$result6 = $conn->query("
    SELECT COUNT(*) AS total_submissions FROM assignment_submissions 
    WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = $course_id)
");
$total_submissions = $result6->fetch_assoc()['total_submissions'];

// Điểm trung bình assignments
$result7 = $conn->query("
    SELECT AVG(grade) AS avg_grade FROM assignment_submissions 
    WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = $course_id) AND grade IS NOT NULL
");
$avg_grade_row = $result7->fetch_assoc();
$avg_grade = $avg_grade_row['avg_grade'] !== null ? round($avg_grade_row['avg_grade'], 2) : 'N/A';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC FPT</title>
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
            width: 250px; /* Adjusted width to match other pages */
            background-color: #2c3e50; /* Dark blue/grey as in the My Courses image */
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px; /* Adjusted padding */
            box-shadow: 2px 0 10px rgba(0,0,0,0.1); /* Adjusted shadow */
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-y: auto; /* Enable vertical scrolling for long menus */
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%; /* Ensure logo area takes full width */
        }

        .sidebar .logo img {
            display: block;
            width: 70%; /* Smaller logo to match other pages */
            max-width: 150px; /* Max size for logo */
            height: auto;
            margin: auto;
        }

        .sidebar ul {
            list-style: none;
            width: 100%;
            padding: 0 15px; /* Padding for list items */
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px; /* Adjusted padding */
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease;
            border-radius: 6px; /* Slightly rounded corners */
            margin-bottom: 8px; /* Space between items */
            border-left: 5px solid transparent; /* Added border for active/hover */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #34495e; /* Slightly lighter dark blue on hover */
            color: #f39c12; /* Accent color for active/hover */
            border-left-color: #f39c12; /* Accent border color */
        }

        .sidebar ul li a i {
            margin-right: 12px; /* Adjusted icon spacing */
            font-size: 1.1em;
            color: #ecf0f1; /* Light grey for icons */
        }

        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #f39c12; /* Accent color for icons on hover/active */
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
            min-height: 100vh; /* Ensure it takes full height */
        }

        .main-content {
            background-color: #fff; /* White main content background */
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* More subtle shadow */
            padding: 30px;
            flex-grow: 1; /* Allow content to grow and push footer down */
        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .analytics-header h2 {
            font-size: 2em; /* Consistent font size */
            color: #2c3e50; /* Consistent color */
            margin: 0;
            font-weight: 500; /* Consistent font weight */
        }

        .analytics-header h2 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for icon */
        }

        /* Styling for the "Back to My Courses" button */
        .analytics-header .button {
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

        .analytics-header .button:hover {
            background-color: #2980b9; /* Darker blue on hover */
        }

        .analytics-header .button i {
            margin-right: 8px;
        }

        /* Analytics Sections as Cards */
        .analytics-sections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive grid */
            gap: 20px; /* Space between cards */
            margin-bottom: 30px;
        }

        .analytics-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px; /* Consistent border-radius */
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); /* More prominent shadow for cards */
            border-left: 5px solid; /* Dynamic border color */
            transition: transform 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .analytics-section:hover {
            transform: translateY(-5px); /* Lift effect on hover */
        }

        .analytics-section.engagement { border-left-color: #27ae60; } /* Green */
        .analytics-section.content { border-left-color: #f39c12; } /* Orange */
        .analytics-section.performance { border-left-color: #3498db; } /* Blue */
        /* Add more classes for different sections if needed */

        .analytics-section h3 {
            font-size: 1.4em; /* Slightly smaller for card titles */
            color: #2c3e50; /* Consistent heading color */
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee; /* Consistent border */
            padding-bottom: 10px;
            font-weight: 500;
        }

        .analytics-section h3 i {
            margin-right: 10px;
            color: inherit; /* Inherit color from border-left for icon */
        }
        .analytics-section.engagement h3 i { color: #27ae60; }
        .analytics-section.content h3 i { color: #f39c12; }
        .analytics-section.performance h3 i { color: #3498db; }


        .analytics-list {
            list-style: none;
            padding-left: 0; /* Remove default list padding */
        }

        .analytics-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f2f2f2;
            color: #555;
            font-size: 1em;
            display: flex;
            justify-content: space-between; /* Align key and value */
            align-items: center;
        }

        .analytics-list li strong {
            font-weight: 500; /* Less bold, more refined */
            color: #333;
            margin-right: 10px;
        }
        .analytics-list li span.value {
            font-weight: 600;
            color: #2c3e50;
        }


        .analytics-list li:last-child {
            border-bottom: none;
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

        .navigation-links {
            margin-top: 40px; /* Consistent margin */
            text-align: center;
            padding-bottom: 20px;
        }

        .navigation-links a {
            color: #3498db; /* Consistent blue */
            text-decoration: none;
            margin: 0 15px; /* Consistent margin */
            font-weight: 500; /* Consistent font weight */
            font-size: 1em; /* Consistent font size */
            transition: color 0.2s ease, text-decoration 0.2s ease;
        }

        .navigation-links a i {
            margin-right: 8px;
        }

        .navigation-links a:hover {
            color: #2980b9;
            text-decoration: underline;
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

        .dark-mode .analytics-header h2 {
            color: #f8f9fa;
        }
        
        .dark-mode .analytics-header h2 i {
            color: #f39c12;
        }

        .dark-mode .analytics-section {
            background-color: #2a2a2a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border: 1px solid #444; /* Darker border for cards */
        }
        .dark-mode .analytics-section.engagement { border-left-color: #2ecc71; }
        .dark-mode .analytics-section.content { border-left-color: #f39c12; }
        .dark-mode .analytics-section.performance { border-left-color: #3498db; }


        .dark-mode .analytics-section h3 {
            color: #f8f9fa;
            border-bottom-color: #555;
        }
        .dark-mode .analytics-section.engagement h3 i { color: #2ecc71; }
        .dark-mode .analytics-section.content h3 i { color: #f39c12; }
        .dark-mode .analytics-section.performance h3 i { color: #3498db; }


        .dark-mode .analytics-list li {
            color: #ddd;
            border-bottom-color: #444;
        }

        .dark-mode .analytics-list li strong {
            color: #f8f9fa;
        }
        .dark-mode .analytics-list li span.value {
            color: #eee;
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

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            .main-wrapper {
                margin-left: 220px;
            }
            .analytics-header h2 {
                font-size: 1.8em;
            }
            .analytics-section h3 {
                font-size: 1.3em;
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

            .analytics-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .analytics-header h2 {
                margin-bottom: 10px;
                font-size: 1.8em;
            }
            .analytics-header .button {
                width: 100%; /* Full width button */
                justify-content: center; /* Center button content */
            }

            .analytics-sections-grid {
                grid-template-columns: 1fr; /* Stack cards vertically on small screens */
            }

            .analytics-section {
                padding: 20px;
            }
            .analytics-section h3 {
                font-size: 1.2em;
            }
            .analytics-list li {
                font-size: 0.9em;
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
            .analytics-header h2 {
                font-size: 1.5em;
            }
            .analytics-section h3 {
                font-size: 1.1em;
            }
            .analytics-list li {
                font-size: 0.8em;
            }
            footer {
                padding: 15px;
                font-size: 0.8em;
            }
            footer .contact-info p {
                font-size: 0.75em;
            }
        }

        /* Button to toggle dark/light mode */
        .toggle-mode-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 200;
            transition: background-color 0.3s ease;
        }

        .toggle-mode-btn:hover {
            background-color: #2980b9;
        }

        .toggle-mode-btn i {
            font-size: 1.2em;
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
.navigation-links {
    margin-top: 40px;
    text-align: center;
    display: flex;
    justify-content: center;
    gap: 20px;
    padding-bottom: 20px;
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
/* Sidebar gradient & scrollbar đẹp */
.sidebar {
    background: linear-gradient(135deg, #2c3e50 60%, #2980b9 100%);
    box-shadow: 2px 0 20px rgba(44,62,80,0.15);
    scrollbar-width: thin;
    scrollbar-color: #f1c40f33 #2c3e5000;
}
.sidebar::-webkit-scrollbar {
    width: 6px;
    background: transparent;
    transition: opacity 0.2s;
    opacity: 0;
}
.sidebar:hover::-webkit-scrollbar {
    opacity: 1;
}
.sidebar::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #f1c40f99 60%, #f39c1299 100%);
    border-radius: 6px;
    min-height: 30px;
    border: 2px solid transparent;
    background-clip: padding-box;
}
.sidebar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #f39c12cc 60%, #f1c40fcc 100%);
}
.sidebar::-webkit-scrollbar-track {
    background: transparent;
}
    </style>
</head>
<body>

    <?php
    // KHÔNG THAY ĐỔI BẤT KỲ LOGIC PHP NÀO Ở ĐÂY.
    // PHẦN NÀY CHỈ ĐỂ ĐẢM BẢO CÁC BIẾN CẦN THIẾT TỒN TẠI ĐỂ TRÁNH LỖI "UNDEFINED VARIABLE"
    // KHI BẠN CHƯA KẾT NỐI DB THẬT VÀ CHẠY THỬ RIÊNG FILE NÀY.
    // TRONG MÔI TRƯỜNG PHP THẬT CỦA BẠN, CÁC BIẾN NÀY SẼ ĐƯỢC LẤY TỪ DATABASE HOẶC URL.

    if (!isset($course_id)) {
        $course_id = 123; // Placeholder course ID
    }
    if (!isset($course_title)) {
        $course_title = "Data Structures & Algorithms";
    }

    // Giả lập dữ liệu analytics nếu chưa có
    if (!isset($total_students)) {
        $total_students = 45;
    }
    if (!isset($avg_progress)) {
        $avg_progress = 78.5; // Example percentage
    }
    if (!isset($total_lessons)) {
        $total_lessons = 15;
    }
    if (!isset($total_assignments)) {
        $total_assignments = 8;
    }
    if (!isset($avg_score)) {
        $avg_score = "85/100"; // Can be string or number
    }
    if (!isset($avg_grade)) {
        $avg_grade = "B+"; // Can be string or number
    }
    if (!isset($total_submissions)) {
        $total_submissions = 120;
    }
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
            
            <div class="analytics-header">
                <h2><i class="fas fa-chart-line"></i> Analytics for: <?= htmlspecialchars($course_title) ?></h2>
                <a href="teacher_courses.php" class="button"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
            </div>

            <div class="analytics-sections-grid">
                <div class="analytics-section engagement">
                    <h3><i class="fas fa-graduation-cap"></i> Student Engagement</h3>
                    <ul class="analytics-list">
                        <li><strong>Total students enrolled:</strong> <span class="value"><?= htmlspecialchars($total_students) ?></span></li>
                        <li><strong>Average lesson completion rate:</strong> <span class="value"><?= htmlspecialchars($avg_progress) ?>%</span></li>
                    </ul>
                </div>

                <div class="analytics-section content">
                    <h3><i class="fas fa-book-open"></i> Course Content</h3>
                    <ul class="analytics-list">
                        <li><strong>Total lessons:</strong> <span class="value"><?= htmlspecialchars($total_lessons) ?></span></li>
                        <li><strong>Total assignments:</strong> <span class="value"><?= htmlspecialchars($total_assignments) ?></span></li>
                    </ul>
                </div>

                <div class="analytics-section performance">
                    <h3><i class="fas fa-star"></i> Performance Overview</h3>
                    <ul class="analytics-list">
                        <li><strong>Average quiz score:</strong> <span class="value"><?= htmlspecialchars($avg_score) ?></span></li>
                        <li><strong>Average assignment grade:</strong> <span class="value"><?= htmlspecialchars($avg_grade) ?></span></li>
                        <li><strong>Total assignment submissions:</strong> <span class="value"><?= htmlspecialchars($total_submissions) ?></span></li>
                    </ul>
                </div>
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
    <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
        <i class="fas fa-moon"></i>
    </button>
    <script>
    // Toggle dark/light mode và lưu trạng thái vào localStorage
    const btn = document.getElementById('toggleModeBtn');
    function setDarkMode(on) {
        document.body.classList.toggle('dark-mode', on);
        btn.innerHTML = on
            ? '<i class="fas fa-sun"></i>'
            : '<i class="fas fa-moon"></i>';
        localStorage.setItem('darkMode', on ? '1' : '0');
    }
    document.addEventListener('DOMContentLoaded', function() {
        setDarkMode(localStorage.getItem('darkMode') === '1');
        btn.onclick = function() {
            setDarkMode(!document.body.classList.contains('dark-mode'));
        };
    });

    // JavaScript để highlight link sidebar tương ứng
    document.addEventListener('DOMContentLoaded', () => {
        const currentPath = window.location.pathname.split('/').pop();
        const sidebarLinks = document.querySelectorAll('.sidebar ul li a.sidebar-link');

        sidebarLinks.forEach(link => {
            link.classList.remove('active');
            const linkHref = link.getAttribute('href');
            if (linkHref) {
                const linkFileName = linkHref.split('/').pop();
                if (linkFileName === 'teacher_courses.php' && 
                   (currentPath === 'teacher_courses.php' || 
                    currentPath.startsWith('teacher_lessons.php') || 
                    currentPath.startsWith('edit_lesson.php') ||
                    currentPath.startsWith('teacher_enrollments.php') ||
                    currentPath.startsWith('teacher_analytics.php'))) {
                    link.classList.add('active');
                } else if (linkFileName === currentPath) {
                    link.classList.add('active');
                }
            }
        });
    });
</script>
</body>
</html>