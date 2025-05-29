<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Fetch system notifications
$sys_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC");

// Fetch personal notifications
$personal_result = $conn->query("SELECT message, created_at FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications | [Your University Name]</title>
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
            display: flex;
            flex-direction: column;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .notifications-header h2 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .notifications-header h2 i {
            margin-right: 10px;
            color: #007bff; /* Blue icon */
        }

        .announcements-section,
        .personal-notifications-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            border-left: 4px solid #f39c12; /* Teacher accent color */
        }

        .announcements-section h3,
        .personal-notifications-section h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }

        .announcements-section h3 i {
            margin-right: 10px;
            color: #f39c12; /* Teacher accent icon */
        }

        .personal-notifications-section h3 i {
            margin-right: 10px;
            color: #007bff; /* Primary color icon */
        }

        .announcements-section ul,
        .personal-notifications-section ul {
            list-style: none;
            padding-left: 20px;
            margin: 0;
        }

        .announcements-section li,
        .personal-notifications-section li {
            padding: 8px 0;
            color: #666;
            word-break: break-word;
        }

        .announcements-section li strong,
        .personal-notifications-section li strong {
            font-weight: bold;
            color: #444;
            margin-right: 5px;
        }

        .personal-notifications-section p {
            color: #777;
            font-style: italic;
        }

        .back-links {
            margin-top: 20px;
        }

        .back-links a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            margin-right: 15px;
            transition: color 0.2s ease;
        }

        .back-links a:hover {
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

        .dark-mode .notifications-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .announcements-section,
        .dark-mode .personal-notifications-section {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            border-left-color: #ffc107;
        }

        .dark-mode .announcements-section h3,
        .dark-mode .personal-notifications-section h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .announcements-section ul,
        .dark-mode .personal-notifications-section ul {
            color: #ccc;
        }

        .dark-mode .announcements-section li,
        .dark-mode .personal-notifications-section li {
            color: #ccc;
        }

        .dark-mode .announcements-section li strong,
        .dark-mode .personal-notifications-section li strong {
            color: #eee;
        }

        .dark-mode .personal-notifications-section p {
            color: #bbb;
        }

        .dark-mode .back-links a {
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
            <li><a href="teacher_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
            <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> View Quiz Results</a></li>
            <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
            <li><a href="teacher_view_notifications.php" class="active"><i class="fas fa-envelope-open-text"></i> View Notifications</a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="teacher_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="notifications-header">
            <h2><i class="fas fa-envelope-open-text"></i> My Notifications</h2>
        </div>

        <?php if ($sys_result && $sys_result->num_rows > 0): ?>
            <div class="announcements-section">
                <h3><i class="fas fa-bullhorn"></i> Important Announcements</h3>
                <ul>
                    <?php while ($sys = $sys_result->fetch_assoc()): ?>
                        <li><strong>[<?= $sys['created_at'] ?>]</strong> <?= htmlspecialchars($sys['message']) ?></li>
                    <?php endwhile; ?>
                </ul>
                <hr>
            </div>
        <?php endif; ?>

        <div class="personal-notifications-section">
            <h3><i class="fas fa-bell"></i> Personal Notifications</h3>
            <?php if ($personal_result && $personal_result->num_rows > 0): ?>
                <ul>
                    <?php while ($notif = $personal_result->fetch_assoc()): ?>
                        <li><strong>[<?= $notif['created_at'] ?>]</strong> <?= htmlspecialchars($notif['message']) ?></li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No personal notifications.</p>
            <?php endif; ?>
        </div>

        <div class="back-links">
            <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
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