<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Lấy thông tin user hiện tại
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
            background-color: #0056b3; /* University blue (example) */
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
            border-left-color: #fbc531; /* Accent color */
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
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .profile-header h1 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
        }

        .profile-info-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin:auto;
        }

        .profile-info-section h3 {
            font-size: 1.4em;
            color: #0056b3;
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            text-align: center;
        }

        .profile-actions-section h3 {
            font-size: 1.4em;
            color: #0056b3;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-actions-section h3 i {
            margin-right: 10px;
        }

        .profile-actions-section a {
            display: inline-block;
            text-decoration: none;
            color: #0056b3;
            font-weight: 600;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #0056b3;
            margin: 0 10px 10px 0;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .profile-actions-section a:hover {
            background-color: #0056b3;
            color: white;
        }

        .back-to-dashboard {
            margin-top: 20px;
            text-align: center;
        }

        .back-to-dashboard a {
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
        }

        .back-to-dashboard a:hover {
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

        /* Dark Mode (Optional) */
        .dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #222;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-content {
            background-color: #333;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .profile-header h1,
        .dark-mode .profile-info-section h3,
        .dark-mode .profile-actions-section h3 {
            color: #f8f9fa;
        }

        .dark-mode .profile-info-section,
        .dark-mode .profile-actions-section,
        .dark-mode footer {
            background-color: #444;
            border-color: #555;
        }

        .dark-mode .profile-info-section p {
            border-bottom-color: #555;
        }

        .dark-mode .profile-info-section strong {
            color: #eee;
        }

        .dark-mode .profile-actions-section a {
            color: #fbc531;
            border-color: #fbc531;
        }

        .dark-mode .profile-actions-section a:hover {
            background-color: #fbc531;
            color: #222;
        }

        .dark-mode .back-to-dashboard a {
            color: #fbc531;
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
            <li><a href="student_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> Academic Progress</a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> Grades & Results</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
            <h3><i class="fas fa-wrench"></i> Actions</h3>
            <a href="student_change_email.php"><i class="fas fa-envelope"></i> Change Email</a>
            <a href="student_change_password.php"><i class="fas fa-key"></i> Change Password</a>
        </section>

        <div class="back-to-dashboard">
            <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
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