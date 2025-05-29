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
    <title>Analytics - <?= htmlspecialchars($course_title) ?> | [Your University Name]</title>
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
            background-color: #f8f9fa; /* Light grey background */
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px; /* Slightly wider sidebar */
            background-color: #2c3e50; /* Teacher-specific dark blue */
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px; /* More top padding */
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100; /* Ensure it's above other content */
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        /* Logo image spanning the width */
        .sidebar .logo img {
            display: block;
            width: 80%; /* Make it span the width */
            height: auto; /* Maintain aspect ratio */
            margin:auto;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px; /* Adjust padding */
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent; /* Indicator for active/hover */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active { /* You'd need JavaScript to add 'active' class */
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #f39c12; /* Teacher-specific accent color */
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.2em;
        }

        .main-content {
            margin-left: 280px; /* Match sidebar width */
            padding: 30px; /* Adjust padding */
            flex-grow: 1;
            background-color: #fff; /* White main content background */
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
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
            font-size: 2.2em;
            color: #333;
            margin: 0;
        }

        .analytics-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border-left: 5px solid #27ae60; /* Success green accent */
        }

        .analytics-section h3 {
            font-size: 1.6em;
            color: #2c3e50; /* Teacher-specific heading color */
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .analytics-section h3 i {
            margin-right: 10px;
        }

        .analytics-list {
            list-style: none;
            padding-left: 20px;
        }

        .analytics-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f2f2f2;
            color: #555;
            font-size: 1em;
        }

        .analytics-list li strong {
            font-weight: bold;
            color: #333;
            margin-right: 5px;
        }

        .analytics-list li:last-child {
            border-bottom: none;
        }

        .back-to-courses {
            margin-top: 20px;
        }

        .back-to-courses a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .back-to-courses a:hover {
            color: #1a252f;
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


        /* Dark Mode (Optional - Add a class 'dark-mode' to the body) */
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

        .dark-mode .analytics-header h2,
        .dark-mode .analytics-section h3 {
            color: #f8f9fa;
        }

        .dark-mode .analytics-section {
            background-color: #444;
            border-left-color: #2ecc71; /* Dark mode success green */
            color: #eee;
        }

        .dark-mode .analytics-list li {
            color: #eee;
            border-bottom-color: #555;
        }

        .dark-mode .analytics-list li strong {
            color: #f8f9fa;
        }

        .dark-mode .back-to-courses a {
            color: #f39c12;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
            border-radius: 0 0 8px 8px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_courses.php" class="active"><i class="fas fa-book"></i> My Courses</a></li>
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
        <div class="analytics-header">
            <h2><i class="fas fa-chart-line"></i> Analytics for: <?= htmlspecialchars($course_title) ?></h2>
        </div>

        <div class="analytics-section">
            <h3><i class="fas fa-graduation-cap"></i> Student Engagement</h3>
            <ul class="analytics-list">
                <li><strong>Total students enrolled:</strong> <?= $total_students ?></li>
                <li><strong>Average lesson completion rate:</strong> <?= $avg_progress ?>%</li>
            </ul>
        </div>

        <div class="analytics-section">
            <h3><i class="fas fa-book-open"></i> Course Content</h3>
            <ul class="analytics-list">
                <li><strong>Total lessons:</strong> <?= $total_lessons ?></li>
                <li><strong>Total assignments:</strong> <?= $total_assignments ?></li>
            </ul>
        </div>

        <div class="analytics-section">
            <h3><i class="fas fa-star"></i> Performance Overview</h3>
            <ul class="analytics-list">
                <li><strong>Average quiz score:</strong> <?= $avg_score ?></li>
                <li><strong>Average assignment grade:</strong> <?= $avg_grade ?></li>
                <li><strong>Total assignment submissions:</strong> <?= $total_submissions ?></li>
            </ul>
        </div>

        <div class="back-to-courses">
            <a href="teacher_courses.php"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
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

    </div>

</body>
</html>
