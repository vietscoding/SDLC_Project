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
    <title>Course Details (Teacher) | [Your University Name]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f6f8; /* Light background */
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        /* Sidebar (Fixed Width and Style) */
        .sidebar {
            width: 280px; /* Match previous sidebars */
            background-color: #2c3e50; /* Teacher-specific dark blue */
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px; /* More margin to match */
        }

        .sidebar .logo img {
            display: block;
            width: 80%; /* Match previous logos */
            height: auto;
            margin: 0 auto;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: flex; /* Use flex to align icon and text */
            align-items: center; /* Vertically align icon and text */
            padding: 15px 20px; /* Match previous padding */
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent; /* Indicator */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #f39c12; /* Teacher-specific accent */
        }

        .sidebar ul li a i {
            margin-right: 15px; /* Spacing for the icon */
            font-size: 1.2em; /* Icon size */
        }

        /* Main Content */
        .main-content {
            margin-left: 280px; /* Match sidebar width */
            padding: 30px;
            flex-grow: 1; /* Allow main content to take up remaining vertical space */
        }

        .course-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .course-details-header h2 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .course-details-header h2 i {
            margin-right: 10px;
            color: #007bff; /* Blue icon */
        }

        .error-message {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #d32f2f;
        }

        .error-message h2 {
            font-size: 1.6em;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .course-info-section {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #007bff; /* Blue section border */
        }

        .course-info-section h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }

        .course-info-section h3 i {
            margin-right: 10px;
            color: #007bff; /* Blue icon */
        }

        .course-details-list {
            list-style: none;
            padding-left: 20px;
        }

        .course-details-list li {
            padding: 8px 0;
            color: #666;
        }

        .course-details-list li strong {
            font-weight: bold;
            color: #444;
            margin-right: 5px;
        }

        .course-description {
            margin-top: 15px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 6px;
            color: #555;
            line-height: 1.7;
        }

        .manage-course-section {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #28a745; /* Green section border */
        }

        .manage-course-section h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }

        .manage-course-section h3 i {
            margin-right: 10px;
            color: #28a745; /* Green icon */
        }

        .manage-links {
            list-style: none;
            padding-left: 20px;
        }

        .manage-links li {
            margin-bottom: 10px;
        }

        .manage-links li a {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff; /* Blue button */
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }

        .manage-links li a:hover {
            background-color: #0056b3;
        }

        .back-link {
            margin-top: 20px;
        }

        .back-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
            color: #0056b3;
            text-decoration: underline;
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


        /* Dark Mode (Optional) */
        .dark-mode {
            background-color: #212529;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #343a40;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .sidebar ul li a {
            color: #ddd;
        }

        .dark-mode .sidebar ul li a:hover,
        .dark-mode .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #ffc107;
        }

        .dark-mode .course-details-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .error-message {
            background-color: #424242;
            color: #ef9a9a;
            border-left-color: #ef5350;
        }

        .dark-mode .course-info-section,
        .dark-mode .manage-course-section {
            background-color: #343a40;
            border-left-color: #007bff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .course-info-section h3,
        .dark-mode .manage-course-section h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .course-details-list li,
        .dark-mode .course-description {
            color: #ccc;
            background-color: #495057;
            border-color: #555;
        }

        .dark-mode .course-details-list li strong {
            color: #eee;
        }

        .dark-mode .manage-links li a {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .back-link a {
            color: #007bff;
        }

        .dark-mode footer {
            color: #ccc;
            border-top-color: #555;
            background-color: #343a40;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_courses.php" class=""><i class="fas fa-book"></i> My Courses</a></li>
            <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> View Quiz Results</a></li>
            <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
            <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> View Notifications</a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="teacher_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="course-details-header">
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


</body>
</html>
