<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Thống kê số lượng học viên
$total_students = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='student'")->fetch_assoc()['total'];

// Thống kê tổng số khóa học
$total_courses = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];

// Thống kê tổng số quiz
$total_quizzes = $conn->query("SELECT COUNT(*) AS total FROM quizzes")->fetch_assoc()['total'];

// Thống kê tổng số bài nộp quiz
$total_submissions = $conn->query("SELECT COUNT(*) AS total FROM quiz_submissions")->fetch_assoc()['total'];

// Thống kê tổng số assignments
$total_assignments = $conn->query("SELECT COUNT(*) AS total FROM assignments")->fetch_assoc()['total'];

// Tổng số bài nộp assignment
$total_assignment_submissions = $conn->query("SELECT COUNT(*) AS total FROM assignment_submissions")->fetch_assoc()['total'];

// Thống kê trung bình điểm từng assignment
$assignment_stats = $conn->query("
    SELECT a.title, AVG(s.grade) AS avg_grade, COUNT(s.id) AS submission_count
    FROM assignments a
    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
    GROUP BY a.id
    ORDER BY a.id DESC
");

// Thống kê trung bình điểm quiz từng quiz
$quiz_stats = $conn->query("
    SELECT q.title, AVG(s.score) AS avg_score, COUNT(s.id) AS submission_count
    FROM quizzes q
    LEFT JOIN quiz_submissions s ON q.id = s.quiz_id
    GROUP BY q.id
    ORDER BY q.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports (Admin) | BTEC</title>
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
            background-color: #f4f6f8;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: #34495e; /* Darker blue for admin */
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
            margin-bottom: 30px;
        }

        .sidebar .logo img {
            width: 70%;
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
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #e74c3c; /* Red accent for admin */
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.1em;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .reports-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .reports-header h2 i {
            margin-right: 10px;
            color: #2ecc71; /* Green for reports */
        }

        .summary-statistics {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .summary-statistics h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .summary-statistics h3 i {
            margin-right: 10px;
            color: #3498db; /* Blue for summary */
        }

        .summary-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .summary-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item i {
            margin-right: 10px;
            color: #e67e22; /* Orange for icons */
            font-size: 1.2em;
        }

        .performance-statistics {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .performance-statistics h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .performance-statistics h3 i {
            margin-right: 10px;
            color: #f39c12; /* Yellow/Orange for performance */
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: #f9f9f9;
            border-radius: 6px;
            overflow: hidden;
        }

        .stats-table thead th {
            background-color: #3498db; /* Blue for table header */
            color: white;
            padding: 12px 15px;
            text-align: left;
        }

        .stats-table tbody td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }

        .stats-table tbody tr:last-child td {
            border-bottom: none;
        }

        .stats-table tbody tr:nth-child(even) {
            background-color: #ecf0f1; /* Light gray for even rows */
        }

        .no-records {
            color: #777;
            font-style: italic;
        }

        .back-to-dashboard {
            margin-top: 30px;
        }

        .back-to-dashboard a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .back-to-dashboard a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Keyframes for subtle animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Apply animation to content sections */
        .reports-header, .summary-statistics, .performance-statistics, .back-to-dashboard {
            animation: fadeInUp 0.5s ease-out;
        }

        .summary-item {
            animation: fadeInUp 0.7s ease-out;
        }

        .stats-table {
            animation: fadeInUp 0.9s ease-out;
        }

        /* Dark Mode (Optional) */
        .dark-mode {
            background-color: #212529;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #2c3e50;
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

        .dark-mode .reports-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .summary-statistics,
        .dark-mode .performance-statistics {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .summary-statistics h3,
        .dark-mode .performance-statistics h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .summary-item {
            border-bottom-color: #495057;
            color: #ccc;
        }

        .dark-mode .stats-table {
            background-color: #495057;
            color: #eee;
        }

        .dark-mode .stats-table thead th {
            background-color: #2c3e50;
            color: #ddd;
        }

        .dark-mode .stats-table tbody td {
            border-bottom-color: #343a40;
        }

        .dark-mode .stats-table tbody tr:nth-child(even) {
            background-color: #343a40;
        }

        .dark-mode .no-records {
            color: #bbb;
        }

        .dark-mode .back-to-dashboard a {
            color: #007bff;
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
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_reports.php" class="active"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="reports-header">
        <h2><i class="fas fa-chart-line"></i> System Reports</h2>
    </div>

    <div class="summary-statistics">
        <h3><i class="fas fa-chart-pie"></i> Summary Statistics</h3>
        <ul class="summary-list">
            <li class="summary-item"><i class="fas fa-user-graduate"></i> Total Students: <strong><?= $total_students ?></strong></li>
            <li class="summary-item"><i class="fas fa-book"></i> Total Courses: <strong><?= $total_courses ?></strong></li>
            <li class="summary-item"><i class="fas fa-question-circle"></i> Total Quizzes: <strong><?= $total_quizzes ?></strong></li>
            <li class="summary-item"><i class="fas fa-file-alt"></i> Total Quiz Submissions: <strong><?= $total_submissions ?></strong></li>
            <li class="summary-item"><i class="fas fa-tasks"></i> Total Assignments: <strong><?= $total_assignments ?></strong></li>
            <li class="summary-item"><i class="fas fa-upload"></i> Total Assignment Submissions: <strong><?= $total_assignment_submissions ?></strong></li>
        </ul>
    </div>

    <div class="performance-statistics">
        <h3><i class="fas fa-chart-bar"></i> Quiz Performance Statistics</h3>
        <?php if ($quiz_stats->num_rows > 0): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Quiz Title</th>
                        <th>Average Score</th>
                        <th>Submissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $quiz_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['avg_score'] !== null ? round($row['avg_score'], 2) : 'N/A' ?></td>
                            <td><?= $row['submission_count'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-records"><i class="fas fa-exclamation-circle"></i> No quiz records found.</p>
        <?php endif; ?>
    </div>

    <div class="performance-statistics">
        <h3><i class="fas fa-clipboard-check"></i> Assignment Performance Statistics</h3>
        <?php if ($assignment_stats->num_rows > 0): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Assignment Title</th>
                        <th>Average Grade</th>
                        <th>Submissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $assignment_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['avg_grade'] !== null ? round($row['avg_grade'], 2) : 'N/A' ?></td>
                            <td><?= $row['submission_count'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-records"><i class="fas fa-exclamation-circle"></i> No assignment records found.</p>
        <?php endif; ?>
    </div>

    <div class="back-to-dashboard">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
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
