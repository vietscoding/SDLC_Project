<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->close();

        $success = "Email updated successfully.";
    } else {
        $error = "Email cannot be empty.";
    }
}

$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($current_email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Email | BTEC FPT</title>
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

        .change-email-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee; /* Thicker border for header */
        }

        .change-email-header h1 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .change-email-header h1 i {
            margin-right: 10px;
            color: #2c3e50; /* Match sidebar color */
        }

        .change-email-container {
            background-color: #fff;
            padding: 25px; /* Increased padding */
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); /* Lighter shadow */
            margin-bottom: 20px;
            max-width: 550px; /* Max width for the form container */
            margin-left: auto; /* Center the form */
            margin-right: auto; /* Center the form */
        }

        .change-email-container h3 {
            font-size: 1.5em; /* Slightly larger heading */
            color: #2c3e50; /* Match sidebar color */
            margin-bottom: 20px; /* Increased margin */
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .change-email-container h3 i {
            margin-right: 10px;
        }

        .success-message {
            color: green;
            margin-bottom: 15px;
            font-weight: bold;
            background-color: #e6ffe6;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #a3e6a3;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
            font-weight: bold;
            background-color: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e6a3a3;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .change-email-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .change-email-container input[type="email"] {
            width: 100%;
            padding: 12px; /* Increased padding */
            margin-bottom: 20px; /* Increased margin */
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1.1em; /* Slightly larger font */
        }

        .change-email-container button[type="submit"] {
            background-color: #007bff; /* Blue submit button */
            color: white;
            padding: 12px 25px; /* Increased padding */
            border: none;
            border-radius: 5px;
            font-size: 1.1em; /* Slightly larger font */
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .change-email-container button[type="submit"]:hover {
            background-color: #0056b3;
            transform: translateY(-2px); /* Subtle lift on hover */
        }

        .back-to-profile {
            margin-top: 30px; /* More margin */
            text-align: center;
        }

        .back-to-profile a {
            color: #6c757d;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-to-profile a:hover {
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

        .dark-mode .change-email-header h1 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .change-email-container {
            background-color: #495057;
            color: #eee;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .dark-mode .change-email-container h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .success-message {
            background-color: #28a745;
            border-color: #1a6f2b;
            color: #fff;
        }

        .dark-mode .error-message {
            background-color: #dc3545;
            border-color: #a71d2a;
            color: #fff;
        }

        .dark-mode .change-email-container label {
            color: #ccc;
        }

        .dark-mode .change-email-container input[type="email"] {
            background-color: #5a6268;
            color: #eee;
            border-color: #6c757d;
        }

        .dark-mode .change-email-container button[type="submit"] {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .back-to-profile a {
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
        <div class="change-email-header">
            <h1><i class="fas fa-envelope"></i> Change Email</h1>
        </div>

        <div class="change-email-container">
            <h3><i class="fas fa-edit"></i> Update Your Email Address</h3>
            <?php if ($success) echo "<p class='success-message'><i class='fas fa-check-circle'></i> $success</p>"; ?>
            <?php if ($error) echo "<p class='error-message'><i class='fas fa-exclamation-triangle'></i> $error</p>"; ?>
            <form method="post">
                <label for="email">New Email:</label><br>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($current_email) ?>" required><br>
                <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>

        <div class="back-to-profile">
            <a href="teacher_profile.php"><i class="fas fa-arrow-left"></i> Back to Profile</a>
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
