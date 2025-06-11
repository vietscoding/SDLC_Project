<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];
$keyword = $_GET['keyword'] ?? "";

// Query lấy các courses mà teacher này đang dạy
if (!empty($keyword)) {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS instructor 
                            FROM courses c 
                            JOIN users u ON c.teacher_id = u.id 
                            WHERE c.teacher_id = ? 
                              AND (c.title LIKE ? OR c.department LIKE ?) 
                            ORDER BY c.id DESC");
    $like = "%" . $keyword . "%";
    $stmt->bind_param("iss", $teacher_id, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS instructor 
                            FROM courses c 
                            JOIN users u ON c.teacher_id = u.id 
                            WHERE c.teacher_id = ? 
                            ORDER BY c.id DESC");
    $stmt->bind_param("i", $teacher_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search My Courses | BTEC FPT</title>
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
        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%;
        }

        .sidebar .logo img {
            display: block;
            width: 70%; /* Smaller logo */
            max-width: 150px; /* Max size for logo */
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
            background-color: #fff; /* White main content background */
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* More subtle shadow */
            padding: 30px;
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

        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px; /* Consistent margin-bottom */
            padding-bottom: 15px;
            border-bottom: 1px solid #eee; /* Consistent border */
        }

        .search-header h2 {
            font-size: 2em; /* Consistent with other headers */
            color: #2c3e50; /* Consistent header color */
            margin: 0;
            font-weight: 500;
        }

        .search-header h2 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for the icon */
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 25px; /* More space before results */
            background-color: #f9f9f9; /* Light background for form */
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .search-form input[type="text"] {
            padding: 12px; /* Larger padding */
            border: 1px solid #ccc;
            border-radius: 6px; /* Slightly more rounded */
            flex-grow: 1;
            font-size: 1em;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .search-form input[type="text"]:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .search-form button[type="submit"] {
            background-color: #3498db; /* Blue button */
            color: #fff;
            padding: 12px 20px; /* Larger padding */
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .search-form button[type="submit"]:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        .search-form a {
            color: #6c757d; /* Grey reset link */
            text-decoration: none;
            font-size: 0.95em;
            display: inline-flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 6px;
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .search-form a:hover {
            background-color: #dfe4e8;
            border-color: #c9d0d6;
            text-decoration: none;
        }

        .search-form a i {
            margin-right: 8px;
        }

        .course-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 28px;
            margin-top: 35px;
        }

        .course-item {
            background: rgba(255,255,255,0.98);
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
            overflow: hidden;
            transition: transform 0.25s cubic-bezier(.17,.67,.83,.67), box-shadow 0.25s;
            border: none;
            position: relative;
            cursor: pointer;
            display: flex;
            flex-direction: row;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }
        .course-item:hover {
            transform: translateY(-8px) scale(1.03) rotate(-1deg);
            box-shadow: 0 16px 40px rgba(243,156,18,0.18);
            background: linear-gradient(120deg, #f1c40f 0%, #fffbe6 100%);
        }
        .course-item-image {
            position: relative;
            width: 260px;
            min-width: 180px;
            height: 180px;
            background-size: cover;
            background-position: center;
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
            border-top-right-radius: 0;
            overflow: hidden;
        }
        .course-item-image::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg,rgba(44,62,80,0.15) 60%,rgba(241,196,15,0.13) 100%);
            z-index: 1;
        }
        .course-icon {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 2;
            background: rgba(255,255,255,0.85);
            color: #f39c12;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6em;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            border: 2px solid #fffbe6;
        }
        .course-item-content {
            padding: 24px 28px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .course-item-title {
            font-size: 1.25em;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .course-item-description {
            color: #555;
            margin-bottom: 15px;
            font-size: 1em;
            line-height: 1.5;
            flex-grow: 1;
        }
        .course-item-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .course-item-actions a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(90deg, #3498db 0%, #6dd5fa 100%);
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 0.95em;
            font-weight: 600;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(41,128,185,0.10);
        }
        .course-item-actions a i {
            margin-right: 8px;
            font-size: 1em;
        }
        .course-item-actions a:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            box-shadow: 0 4px 16px rgba(243,156,18,0.13);
        }
        @media (max-width: 768px) {
            .course-list { gap: 20px; }
            .course-item { max-width: 100%; flex-direction: column; }
            .course-item-image { width: 100%; min-width: 100%; height: 150px; border-radius: 14px 14px 0 0; }
            .course-item-content { padding: 15px; }
            .course-item-title { font-size: 1.4em; }
            .course-item-description { font-size: 0.9em; }
            .course-item-actions { flex-direction: column; gap: 8px; }
            .course-item-actions a { width: 100%; justify-content: center; }
        }

        .no-courses {
            font-style: italic;
            color: #777;
            background-color: #fdfdfd;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        .no-courses i {
            margin-right: 8px;
            color: #f39c12;
        }

        .back-to-dashboard {
            margin-top: 30px;
            text-align: center;
        }

        .back-to-dashboard a {
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

        .back-to-dashboard a i {
            margin-right: 8px;
        }

        .back-to-dashboard a:hover {
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
            background-color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .search-header h2 {
            color: #f8f9fa;
        }

        .dark-mode .search-header h2 i {
            color: #f39c12;
        }

        .dark-mode .search-form {
            background-color: #333;
            border-color: #444;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .dark-mode .search-form input[type="text"] {
            background-color: #444;
            color: #eee;
            border-color: #555;
        }
        .dark-mode .search-form input[type="text"]:focus {
            border-color: #f39c12;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.2);
        }


        .dark-mode .search-form button[type="submit"] {
            background-color: #f39c12;
            color: #222;
        }

        .dark-mode .search-form button[type="submit"]:hover {
            background-color: #e08e0b;
        }

        .dark-mode .search-form a {
            background-color: #3a3a3a;
            border-color: #555;
            color: #ccc;
        }

        .dark-mode .search-form a:hover {
            background-color: #4a4a4a;
            border-color: #666;
        }

        .dark-mode .course-list li {
            background-color: #2a2a2a;
            color: #eee;
            border-color: #444;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-left-color: #28a745;
        }

        .dark-mode .course-list li:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .dark-mode .course-info strong {
            color: #f8f9fa;
        }
        .dark-mode .course-info span {
            color: #ccc;
        }

        .dark-mode .course-details-link a {
            color: #f39c12;
            background-color: #3a3a3a;
            border-color: #555;
        }
        .dark-mode .course-details-link a:hover {
            background-color: #4a4a4a;
            border-color: #666;
        }

        .dark-mode .no-courses {
            background-color: #333;
            color: #ccc;
            border-color: #444;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .dark-mode .no-courses i {
            color: #f39c12;
        }

        .dark-mode .back-to-dashboard a {
            color: #f39c12;
            background-color: #3a3a3a;
            border-color: #555;
        }

        .dark-mode .back-to-dashboard a:hover {
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
            .search-header h2 {
                font-size: 1.8em;
            }
            .search-form {
                flex-wrap: wrap; /* Allow items to wrap */
            }
            .search-form input[type="text"] {
                flex-basis: 100%; /* Take full width */
                margin-bottom: 10px;
            }
            .search-form button, .search-form a {
                flex-basis: calc(50% - 7.5px); /* Two items per row */
            }
            .search-form button {
                order: 1; /* Place search button first */
            }
            .search-form a {
                order: 2; /* Place reset button second */
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

            .search-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 20px;
            }
            .search-header h2 {
                margin-bottom: 10px;
                font-size: 1.8em;
            }

            .search-form {
                flex-direction: column;
                gap: 10px;
                padding: 15px;
            }
            .search-form input[type="text"],
            .search-form button[type="submit"],
            .search-form a {
                width: 100%; /* Full width for all form elements */
                margin: 0; /* Remove specific margins */
            }
            .search-form button[type="submit"],
            .search-form a {
                padding: 10px 15px; /* Adjust padding for smaller buttons */
            }

            .course-list li {
                flex-direction: column; /* Stack info and link vertically */
                align-items: flex-start;
                padding: 15px;
            }
            .course-info {
                padding-right: 0;
                margin-bottom: 10px;
            }
            .course-details-link {
                width: 100%; /* Full width for the link */
                text-align: center;
            }
            .course-details-link a {
                width: 100%;
                justify-content: center;
            }
            .no-courses {
                padding: 15px;
            }
            .back-to-dashboard a {
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
                <h2><i class="fas fa-search"></i> Search My Courses</h2>
            </div>

            <form class="search-form" method="get">
                <input type="text" name="keyword" placeholder="Search by title or department..." value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <a href="teacher_courses.php"><i class="fas fa-undo"></i> Reset</a>
            </form>

            <?php if ($result->num_rows > 0): ?>
                <ul class="course-list">
                    <?php while ($course = $result->fetch_assoc()): ?>
                        <li class="course-item">
                            <div class="course-item-image" style="background-image: url('https://source.unsplash.com/random/800x400?education&sig=<?= $course['id'] ?>');">
                                <span class="course-icon"><i class="fas fa-book-open"></i></span>
                            </div>
                            <div class="course-item-content">
                                <h3 class="course-item-title"><?= htmlspecialchars($course['title']) ?></h3>
                                <p class="course-item-description"><?= htmlspecialchars($course['department']) ?> - Instructor: <?= htmlspecialchars($course['instructor']) ?></p>
                                <div class="course-item-actions">
                                    <a href="teacher_course_detail.php?course_id=<?= $course['id'] ?>"><i class="fas fa-info-circle"></i> View Details</a>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-courses"><i class="fas fa-exclamation-circle"></i> No courses found matching your search criteria.</p>
            <?php endif; ?>

            <p class="back-to-dashboard"><a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></p>

            <div class="navigation-links">
                <a href="teacher_courses.php"><i class="fas fa-book"></i> My Courses</a>
                <a href="teacher_search_courses.php"><i class="fas fa-search"></i> Search Courses</a>
                <a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> Quiz Results</a>
                <a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
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

            // Sidebar highlight tab hiện tại
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
                    currentPath.startsWith('teacher_progress.php'))) {
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