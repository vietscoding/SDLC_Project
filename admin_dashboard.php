<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Thống kê số liệu
$total_courses = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];
$total_students = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->fetch_assoc()['total'];
$total_teachers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'")->fetch_assoc()['total'];
$total_quizzes  = $conn->query("SELECT COUNT(*) AS total FROM quizzes")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | BTEC</title>
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

        .admin-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .admin-dashboard-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .admin-dashboard-header h2 i {
            margin-right: 10px;
            color: #e74c3c; /* Red icon for admin */
        }

        .system-overview {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .overview-item {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease-in-out;
        }

        .overview-item:hover {
            transform: scale(1.05);
        }

        .overview-item i {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #e74c3c; /* Màu đỏ cho icon */
        }

        .overview-item span {
            display: block;
            font-size: 1.2em;
            color: #555;
            margin-bottom: 5px;
        }

        .overview-item strong {
            font-size: 1.5em;
            color: #333;
        }

        .system-overview h3 {
            font-size: 1.8em;
            color: #555;
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            text-align: center;
        }

        .system-overview h3 i {
            margin-right: 10px;
            color: #e74c3c;
        }

        .admin-actions {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .admin-actions h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .admin-actions h3 i {
            margin-right: 10px;
            color: #f39c12; /* Orange icon for actions */
        }

        .admin-actions ul {
            list-style: none;
            padding-left: 20px;
            margin: 0;
        }

        .admin-actions li {
            padding: 8px 0;
        }

        .admin-actions li a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .admin-actions li a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .logout-link {
            margin-top: 20px;
        }

        .logout-link a {
            color: #e74c3c; /* Red color for logout */
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .logout-link a:hover {
            color: #c0392b;
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

        .dark-mode .admin-dashboard-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .system-overview,
        .dark-mode .admin-actions {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .system-overview h3,
        .dark-mode .admin-actions h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .overview-item {
            background-color: #495057;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .overview-item span {
            color: #ccc;
        }

        .dark-mode .system-overview li,
        .dark-mode .admin-actions li {
            color: #ccc;
        }

        .dark-mode .admin-actions li a {
            color: #007bff;
        }

        .dark-mode .logout-link a {
            color: #e74c3c;
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
        <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_courses.php"><i class="fas fa-book"></i> Manage Courses</a></li>
        <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="admin-dashboard-header">
        <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
    </div>

    <div class="system-overview">
        <h3><i class="fas fa-chart-pie"></i> System Overview</h3>
        <div class="overview-item">
            <i class="fas fa-book"></i>
            <span>Total Courses</span>
            <strong><?= $total_courses ?></strong>
        </div>
        <div class="overview-item">
            <i class="fas fa-user-graduate"></i>
            <span>Total Students</span>
            <strong><?= $total_students ?></strong>
        </div>
        <div class="overview-item">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Total Teachers</span>
            <strong><?= $total_teachers ?></strong>
        </div>
        <div class="overview-item">
            <i class="fas fa-question-circle"></i>
            <span>Total Quizzes</span>
            <strong><?= $total_quizzes ?></strong>
        </div>
    </div>

    <div class="admin-actions">
        <h3><i class="fas fa-tools"></i> Admin Actions</h3>
        <ul>
            <li><a href="admin_courses.php"><i class="fas fa-book"></i> Manage Courses</a></li>
            <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
            <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> View Reports</a></li>
            <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
            <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        </ul>
    </div>

    <div class="logout-link">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
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
