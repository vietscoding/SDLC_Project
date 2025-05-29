<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Lấy danh sách khóa học giáo viên phụ trách
$stmt = $conn->prepare("SELECT id, title, description FROM courses WHERE teacher_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Courses | BTEC FPT</title>
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
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            background-color: #2c3e50; /* Teacher-specific dark blue */
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
            overflow-y: auto; /* Enable vertical scrolling for long menus */
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .sidebar .logo img {
            display: block;
            width: 50%;
            height: auto;
            margin-left: auto;
            margin-right: auto;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
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
            border-left-color: #f39c12; /* Teacher accent color */
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

        .courses-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px; /* Reduced margin */
            padding-bottom: 10px; /* Reduced padding */
            border-bottom: 1px solid #eee;
        }

        .courses-header h2 {
            font-size: 2.2em; /* Reduced font size */
            color: #2c3e50;
            margin: 0;
        }

        .course-list {
            list-style: none;
            padding: 0;
            display: flex; /* Change to flex layout */
            flex-direction: column; /* Arrange items vertically */
            gap: 15px; /* Reduced gap between course items */
        }

        .course-item {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08); /* Slightly reduced shadow */
            overflow: hidden;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            display: flex; /* Flex layout for content inside course item */
            flex-direction: column; /* Stack image and content vertically */
        }

        .course-item:hover {
            transform: translateY(-3px); /* Reduced hover effect */
            box-shadow: 0 5px 14px rgba(0,0,0,0.12); /* Reduced hover shadow */
        }

        .course-item-image {
            height: 150px; /* Slightly reduced image height */
            background-size: cover;
            background-position: center;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .course-item-content {
            padding: 15px; /* Reduced padding */
        }

        .course-item-title {
            font-size: 1.4em; /* Reduced font size */
            color: #2c3e50;
            margin-bottom: 8px; /* Reduced margin */
        }

        .course-item-description {
            color: #666;
            margin-bottom: 10px; /* Reduced margin */
            font-size: 0.9em; /* Reduced font size */
            line-height: 1.4; /* Slightly reduced line height */
        }

        .course-item-actions {
            display: flex;
            flex-wrap: wrap; /* Allow buttons to wrap to the next line */
            gap: 8px; /* Reduced gap between buttons */
            margin-top: 10px; /* Reduced margin */
        }

        .course-item-actions a {
            display: inline-block;
            text-decoration: none;
            color: #fff;
            background-color: #3498db; /* Action button color */
            padding: 8px 12px; /* Reduced padding */
            border-radius: 5px;
            font-size: 0.85em; /* Reduced font size */
            transition: background-color 0.2s ease;
        }

        .course-item-actions a:hover {
            background-color: #2980b9;
        }

        .no-courses {
            background-color: #f9f9f9;
            padding: 15px; /* Reduced padding */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* Reduced shadow */
            text-align: center;
            color: #777;
            font-style: italic;
            font-size: 0.9em; /* Reduced font size */
        }

        .navigation-links {
            margin-top: 20px; /* Reduced margin */
            text-align: center;
        }

        .navigation-links a {
            color: #3498db;
            text-decoration: none;
            margin: 0 10px; /* Reduced margin */
            font-weight: bold;
            font-size: 0.9em; /* Reduced font size */
            transition: color 0.2s ease;
        }

        .navigation-links a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        hr {
            margin-top: 20px; /* Reduced margin */
            border: 0;
            border-top: 1px solid #eee;
        }

        footer {
            text-align: center;
            padding: 20px; /* Reduced padding */
            margin-top: 15px; /* Reduced margin */
            font-size: 0.85em; /* Reduced font size */
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
            margin: 3px 0; /* Reduced margin */
        }

        footer .contact-info {
            margin-top: 8px; /* Reduced margin */
        }

        footer .contact-info p {
            margin: 2px 0; /* Reduced margin */
            font-size: 0.8em; /* Reduced font size */
        }

        /* Dark Mode (Optional) */
        .dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #333;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-content {
            background-color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .courses-header h2 {
            color: #eee;
        }

        .dark-mode .course-item {
            background-color: #444;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .dark-mode .course-item-title {
            color: #eee;
        }

        .dark-mode .course-item-description {
            color: #ccc;
        }

        .dark-mode .course-item-actions a {
            background-color: #6dd5ed;
            color: #222;
        }

        .dark-mode .course-item-actions a:hover {
            background-color: #4bc0c8;
        }

        .dark-mode .no-courses {
            background-color: #333;
            color: #ccc;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .dark-mode .navigation-links a {
            color: #6dd5ed;
        }

        .dark-mode .navigation-links a:hover {
            color: #4bc0c8;
            text-decoration: underline;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="teacher_courses.php" class="active"><i class="fas fa-book"></i> My Courses</a></li>
            <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> View Quiz Results</a></li>
            <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
            <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> View Notifications</a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="teacher_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="courses-header">
            <h2><i class="fas fa-graduation-cap"></i> My Courses</h2>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <ul class="course-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <li class="course-item">
                        <div class="course-item-image" style="background-image: url('https://source.unsplash.com/random/800x400?education&sig=<?= $row['id'] ?>');"></div>
                        <div class="course-item-content">
                            <h3 class="course-item-title"><?= htmlspecialchars($row['title']) ?></h3>
                            <p class="course-item-description"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                            <div class="course-item-actions">
                                <a href="teacher_lessons.php?course_id=<?= $row['id'] ?>"><i class="fas fa-list-ul"></i> Manage Lessons</a>
                                <a href="teacher_enrollments.php?course_id=<?= $row['id'] ?>"><i class="fas fa-users"></i> View Enrollments</a>
                                <a href="teacher_analytics.php?course_id=<?= $row['id'] ?>"><i class="fas fa-chart-pie"></i> View Analytics</a>
                                <a href="teacher_progress.php?course_id=<?= $row['id'] ?>"><i class="fas fa-chart-line"></i> View Progress</a>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-courses"><i class="fas fa-exclamation-triangle"></i> You are not assigned to any courses yet.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <hr style ="margin-top:20px; ">
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