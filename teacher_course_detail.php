<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['course_id'])) {
    $error_message = "Course ID missing.";
} else {
    $course_id = intval($_GET['course_id']);
    $teacher_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT title, description, department FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $course_id, $teacher_id);
    $stmt->execute();
    $stmt->bind_result($title, $description, $department);
    if (!$stmt->fetch()) {
        $error_message = "You are not allowed to view this course.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Details (Teacher) | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif; /* Changed to Roboto for consistency */
            background-color: #f0f2f5; /* Light grey background for the whole page */
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        /* Sidebar (Fixed Width and Style) */
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
            margin-bottom: 12px;
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

        /* Main Content Wrapper */
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px; /* Match sidebar width */
            padding: 30px;
            background-color: #f0f2f5; /* Match body background */
        }

        .main-content {
            background: rgba(255,255,255,0.97);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
            padding: 40px 32px 32px 32px;
            margin-top: 32px;
            margin-bottom: 32px;
            min-height: 80vh;
            position: relative;
            overflow: hidden;
            transition: background 0.3s, box-shadow 0.3s;
        }

        .main-section-header {
            background: linear-gradient(90deg, #f5af19 0%, #f39c12 100%);
            border-radius: 20px 20px 0 0;
            padding: 28px 40px 22px 40px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            min-height: 70px;
            position: relative;
        }

        .main-section-header h2 {
            color: #2c3e50;
            font-size: 2.2em;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 18px;
            letter-spacing: 1px;
        }

        .main-section-header h2 i {
            color: #f39c12;
            font-size: 1.2em;
        }

        @media (max-width: 768px) {
            .main-section-header {
                padding: 18px 16px 14px 16px;
                min-height: 50px;
            }
            .main-section-header h2 {
                font-size: 1.3em;
                gap: 10px;
            }
        }

        .error-message {
            background-color: #fff3cd; /* Light yellow background */
            color: #856404; /* Dark yellow text */
            padding: 20px; /* More padding */
            border-radius: 8px;
            margin-bottom: 25px; /* More margin */
            border-left: 5px solid #ffc107; /* Orange-yellow border */
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Subtle shadow */
        }

        .error-message h2 {
            font-size: 1.6em;
            margin-top: 0;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            color: #856404; /* Match text color */
        }

        .error-message h2 i {
            margin-right: 10px;
            color: #ffc107; /* Orange-yellow icon */
        }

        .error-message p {
            margin-bottom: 10px;
        }

        .course-info-section,
        .manage-course-section {
            background-color: #fff;
            padding: 25px; /* More padding */
            margin-bottom: 25px; /* More margin */
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); /* Slightly stronger shadow */
            border: 1px solid #e0e0e0; /* Subtle border */
        }
        .course-info-section {
             border-left: 5px solid #3498db; /* Blue section border */
        }
        .manage-course-section {
            border-left: 5px solid #28a745; /* Green section border */
        }


        .course-info-section h3,
        .manage-course-section h3 {
            font-size: 1.6em; /* Consistent size */
            color: #2c3e50; /* Darker heading color */
            margin-top: 0;
            margin-bottom: 20px; /* More margin */
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee; /* Consistent border */
            padding-bottom: 10px; /* More padding */
            font-weight: 500;
        }

        .course-info-section h3 i {
            margin-right: 12px;
            color: #3498db; /* Blue icon */
        }
        .manage-course-section h3 i {
            margin-right: 12px;
            color: #28a745; /* Green icon */
        }

        .course-details-list {
            list-style: none;
            padding: 0; /* Remove default padding */
            margin-bottom: 15px;
        }

        .course-details-list li {
            padding: 10px 0; /* More padding */
            color: #555;
            display: flex; /* Align strong and text */
            gap: 10px; /* Space between label and value */
        }

        .course-details-list li strong {
            font-weight: 500; /* Medium weight */
            color: #333;
            min-width: 100px; /* Ensure labels align */
        }

        .course-description {
            margin-top: 15px; /* More margin */
            padding: 20px; /* More padding */
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0; /* Subtle border */
            border-radius: 6px;
            color: #555;
            line-height: 1.7;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.03); /* Inner shadow */
        }

        .manage-links {
            list-style: none;
            padding: 0;
            display: grid; /* Use grid for better layout */
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Responsive grid */
            gap: 15px; /* Space between grid items */
        }

        .manage-links li {
            margin-bottom: 0; /* Remove default list item margin */
        }

        .manage-links li a {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 20px;
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(243,156,18,0.10);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            gap: 10px;
            border: none;
            outline: none;
        }
        .manage-links li a:hover {
            background: linear-gradient(90deg, #2980b9 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .manage-links li a i {
            margin-right: 10px;
            font-size: 1.1em;
            color: #fff700;
            transition: color 0.2s;
        }
        .manage-links li a:hover i {
            color: #fff;
        }

        .back-link {
            margin-top: 30px; /* More space */
            text-align: center;
        }

        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            font-size: 1em;
            transition: color 0.2s ease, text-decoration 0.2s ease;
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 5px;
            background-color: #ecf0f1;
            border: 1px solid #dcdcdc;
        }

        .back-link a i {
            margin-right: 8px;
        }

        .back-link a:hover {
            color: #2980b9;
            text-decoration: none;
            background-color: #e0e6eb;
            border-color: #c0c6cb;
        }

        /* Footer */
        hr {
            margin-top: 30px;
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
        }

        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px; /* Increased margin-top to separate from content */
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

        footer p {
            margin: 5px 0;
        }

        .contact-info {
            margin-top: 15px;
        }

        .contact-info p {
            margin: 3px 0;
        }

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
            background: #222;
            box-shadow: 0 0 32px rgba(0,0,0,0.25);
        }

        .dark-mode .course-details-header h2 {
            color: #f8f9fa;
        }

        .dark-mode .course-details-header h2 i {
            color: #f39c12;
        }

        .dark-mode .error-message {
            background-color: #3e3300; /* Darker yellow for dark mode */
            color: #ffda6a; /* Lighter yellow text */
            border-left-color: #f39c12; /* Accent color */
        }

        .dark-mode .error-message h2 {
            color: #ffda6a;
        }

        .dark-mode .error-message h2 i {
            color: #f39c12;
        }

        .dark-mode .course-info-section,
        .dark-mode .manage-course-section {
            background-color: #2a2a2a;
            border-color: #444;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .dark-mode .course-info-section {
            border-left-color: #f39c12; /* Accent color for course info */
        }
        .dark-mode .manage-course-section {
            border-left-color: #28a745; /* Green for manage section */
        }

        .dark-mode .course-info-section h3,
        .dark-mode .manage-course-section h3 {
            color: #f8f9fa;
            border-bottom-color: #555;
        }

        .dark-mode .course-info-section h3 i {
            color: #f39c12; /* Accent color */
        }
        .dark-mode .manage-course-section h3 i {
            color: #28a745; /* Green */
        }

        .dark-mode .course-details-list li {
            color: #ccc;
        }
        .dark-mode .course-details-list li strong {
            color: #eee;
        }

        .dark-mode .course-description {
            background-color: #333;
            color: #ccc;
            border-color: #444;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        .dark-mode .manage-links li a {
            background-color: #f39c12; /* Accent color for buttons */
            color: #222; /* Dark text for accent buttons */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .dark-mode .manage-links li a:hover {
            background-color: #e08e0b;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
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
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
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
                padding: 25px;
            }
            .course-details-header h2 {
                font-size: 1.8em;
            }
            .course-info-section h3, .manage-course-section h3 {
                font-size: 1.4em;
            }
            .manage-links {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
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
                margin-bottom: 15px;
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
                justify-content: center;
                padding: 10px;
                text-align: center;
                flex-direction: column; /* Stack icon and text */
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span { /* Ensure span for text to stack */
                display: block;
                font-size: 0.8em;
            }

            .main-wrapper {
                margin-left: 0;
                padding: 20px;
            }

            .course-details-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 20px;
            }
            .course-details-header h2 {
                margin-bottom: 10px;
                font-size: 1.8em;
            }

            .error-message {
                padding: 15px;
            }
            .error-message h2 {
                font-size: 1.4em;
            }

            .course-info-section, .manage-course-section {
                padding: 20px;
                margin-bottom: 20px;
            }
            .course-info-section h3, .manage-course-section h3 {
                font-size: 1.4em;
                margin-bottom: 15px;
            }
            .course-details-list li {
                flex-direction: column; /* Stack label and value */
                gap: 5px;
            }
            .course-details-list li strong {
                min-width: unset;
            }
            .course-description {
                padding: 15px;
            }

            .manage-links {
                grid-template-columns: 1fr; /* Single column layout */
                gap: 10px;
            }
            .manage-links li a {
                padding: 12px 15px;
                font-size: 0.95em;
            }

            .back-link {
                margin-top: 25px;
            }
            .back-link a {
                width: 100%;
                justify-content: center;
            }
            footer {
                margin-top: 25px;
            }
        }

        @media (max-width: 480px) {
            .sidebar ul li {
                width: 95%; /* One item per row */
            }
            .sidebar ul li a {
                justify-content: flex-start; /* Align text to start */
                flex-direction: row; /* Back to row for icon and text */
            }
            .sidebar ul li a i {
                margin-right: 10px;
                margin-bottom: 0;
            }
            .main-wrapper {
                padding: 15px;
            }
            .main-content {
                padding: 20px;
            }
            .course-details-header h2 {
                font-size: 1.6em;
            }
            .course-info-section h3, .manage-course_section h3 {
                font-size: 1.2em;
            }
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

        @media (min-width: 1200px) {
            .main-content {
                max-width: 1400px;
                margin-left: auto;
                margin-right: auto;
            }
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

        /* Responsive cho main-content */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 8px 20px 8px;
                margin-top: 16px;
                margin-bottom: 16px;
            }
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
    </style>
</head>
<body>

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
            <div class="main-section-header">
                <h2><i class="fas fa-info-circle"></i> Course Details</h2>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <h2><i class="fas fa-exclamation-triangle"></i> Error</h2>
                    <p><?= $error_message ?></p>
                    <p class="back-link"><a href="teacher_courses.php"><i class="fas fa-arrow-left"></i> Back to My Courses</a></p>
                </div>
            <?php else: ?>
                <div class="course-info-section">
                    <h3><i class="fas fa-book-open"></i> Course Information</h3>
                    <ul class="course-details-list">
                        <li><strong>Title:</strong> <?= htmlspecialchars($title) ?></li>
                        <li><strong>Department:</strong> <?= htmlspecialchars($department) ?></li>
                    </ul>
                    <?php if ($description): ?>
                        <h3><i class="fas fa-file-alt"></i> Description</h3>
                        <p class="course-description"><?= nl2br(htmlspecialchars($description)) ?></p>
                    <?php endif; ?>
                </div>

                <div class="manage-course-section">
                    <h3><i class="fas fa-cog"></i> Manage Course</h3>
                    <ul class="manage-links">
                        <li><a href="teacher_lessons.php?course_id=<?= $course_id ?>"><i class="fas fa-list-ol"></i> Manage Lessons</a></li>
                        <li><a href="teacher_quizzes.php?course_id=<?= $course_id ?>"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
                        <li><a href="teacher_enrollments.php?course_id=<?= $course_id ?>"><i class="fas fa-users"></i> View Enrollments</a></li>
                        <li><a href="teacher_analytics.php?course_id=<?= $course_id ?>"><i class="fas fa-chart-bar"></i> View Analytics</a></li>
                        <li><a href="teacher_progress.php?course_id=<?= $course_id ?>"><i class="fas fa-chart-line"></i> Track Student Progress</a></li>
                        <li><a href="teacher_assignments.php?course_id=<?= $course_id ?>"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="navigation-links">
                <a href="teacher_courses.php"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
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
        // Sidebar highlight tab hiện tại
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
                    currentPath.startsWith('teacher_analytics.php') ||
                    currentPath.startsWith('teacher_progress.php') ||
                    currentPath.startsWith('teacher_course_detail.php'))) {
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
