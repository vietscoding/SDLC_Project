<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Profile | BTEC FPT</title>
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
            padding: 0;
            margin: 0;
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
            flex-direction: column; /* Arrange header and content vertically */
            position: relative; /* Create positioning context for children if needed */
            min-width: 0; /* Prevent content overflow if width is smaller than content */
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee; /* Thicker border for header */
        }

        .profile-header h1 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .profile-header h1 i {
            margin-right: 10px;
            color: #2c3e50; /* Match sidebar color */
        }

        .profile-info-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); /* Lighter shadow */
            margin-bottom: 20px;
        }

        .profile-info-section h3 {
            font-size: 1.4em;
            color: #2c3e50; /* Match sidebar color */
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .profile-info-section h3 i {
            margin-right: 10px;
        }

        .profile-info-section p {
            padding: 8px 0;
            border-bottom: 1px solid #f2f2f2;
        }

        .profile-info-section p:last-child {
            border-bottom: none;
        }

        .profile-info-section strong {
            font-weight: bold;
            color: #555;
            margin-right: 5px;
        }

        .profile-actions-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            text-align: center; /* Center buttons */
        }

        .profile-actions-section h3 {
            font-size: 1.4em;
            color: #2c3e50; /* Match sidebar color */
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center; /* Center heading text */
        }

        .profile-actions-section h3 i {
            margin-right: 10px;
        }

        .profile-actions-section a {
            display: inline-block; /* Make links behave like block elements but allow them to sit side-by-side */
            text-decoration: none;
            color: #fff;
            background-color: #007bff; /* Blue for actions */
            font-weight: 600;
            padding: 10px 20px; /* Slightly more padding */
            border-radius: 5px;
            margin: 0 10px 10px 0; /* Add margin-right and margin-bottom */
            transition: background-color 0.2s ease, transform 0.2s ease; /* Add transform transition */
            display: inline-flex; /* Use flex for icon alignment */
            align-items: center;
            gap: 8px; /* Space between icon and text */
        }

        .profile-actions-section a:hover {
            background-color: #0056b3;
            transform: translateY(-2px); /* Subtle lift on hover */
        }

        .back-to-dashboard {
            margin-top: 20px;
            text-align: center;
        }

        .back-to-dashboard a {
            color: #6c757d;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-to-dashboard a:hover {
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 30px;
            margin-top: auto; /* Push footer to the bottom */
            font-size: 0.9em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            /* Remove border-radius to match full width footer */
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

        .dark-mode .main-content {
            background-color: #343a40;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .profile-header h1 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .profile-info-section,
        .dark-mode .profile-actions-section {
            background-color: #495057;
            color: #eee;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .dark-mode .profile-info-section h3,
        .dark-mode .profile-actions-section h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .profile-info-section p {
            color: #ccc;
            border-bottom-color: #666;
        }

        .dark-mode .profile-info-section strong {
            color: #eee;
        }

        .dark-mode .profile-actions-section a {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .back-to-dashboard a {
            color: #a7b1b8;
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
            <li><a href="teacher_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
            <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
            <li><a href="teacher_grades.php"><i class="fas fa-graduation-cap"></i> Grade Submissions</a></li>
            <li><a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="teacher_profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="profile-header">
            <h1><i class="fas fa-user"></i> Your Profile</h1>
        </div>

        <section class="profile-info-section">
            <h3><i class="fas fa-info-circle"></i> Profile Information</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($fullname) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
        </section>

        <section class="profile-actions-section">
            <h3><i class="fas fa-wrench"></i> Account Actions</h3>
            <a href="teacher_change_email.php"><i class="fas fa-envelope"></i> Change Email</a>
            <a href="teacher_change_password.php"><i class="fas fa-key"></i> Change Password</a>
        </section>

        <div class="back-to-dashboard">
            <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
<hr style="margin-top:30px;">
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
        <p>&copy; 2025 BTEC FPT - Learning Management System.</p>
        <small>Powered by Innovation in Education</small>
    </footer>
    </div>



</body>
</html>
