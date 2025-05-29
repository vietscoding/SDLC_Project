<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$success = $error = "";

// Xử lý khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Lấy password cũ từ DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Kiểm tra mật khẩu cũ đúng không
    if (!password_verify($current_password, $hashed_password)) {
        $error = "Incorrect current password.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Cập nhật mật khẩu mới
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hashed, $user_id);
        $stmt->execute();
        $stmt->close();

        $success = "Password changed successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password | BTEC FPT</title>
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

        .change-password-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .change-password-header h1 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
        }

        .change-password-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin:auto;
            max-width: 500px; /* Constrain width for forms */
        }

        .change-password-container h3 {
            font-size: 1.4em;
            color: #0056b3;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .change-password-container h3 i {
            margin-right: 10px;
        }

        .success-message {
            color: green;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .change-password-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .change-password-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .change-password-container button[type="submit"] {
            background-color: #0056b3;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .change-password-container button[type="submit"]:hover {
            background-color: #004494;
        }

        .back-to-profile {
            margin-top: 20px;
        }

        .back-to-profile a {
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
        }

        .back-to-profile a:hover {
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

        .dark-mode .change-password-header h1,
        .dark-mode .change-password-container h3 {
            color: #f8f9fa;
        }

        .dark-mode .change-password-container {
            background-color: #444;
            color: #eee;
            border-color: #555;
        }

        .dark-mode .change-password-container label {
            color: #eee;
        }

        .dark-mode .change-password-container input[type="password"] {
            background-color: #555;
            color: #eee;
            border-color: #666;
        }

        .dark-mode .change-password-container button[type="submit"] {
            background-color: #fbc531;
            color: #222;
        }

        .dark-mode .back-to-profile a {
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
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> Academic Progress</a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> Grades & Results</a></li>
            <li><a href="student_profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="change-password-header">
            <h1><i class="fas fa-key"></i> Change Password</h1>
        </div>

        <div class="change-password-container">
            <h3><i class="fas fa-lock"></i> Update Your Password</h3>
            <?php if ($success) echo "<p class='success-message'>$success</p>"; ?>
            <?php if ($error) echo "<p class='error-message'>$error</p>"; ?>
            <form method="post">
                <label for="current_password">Current Password:</label><br>
                <input type="password" id="current_password" name="current_password" required><br><br>

                <label for="new_password">New Password:</label><br>
                <input type="password" id="new_password" name="new_password" required><br><br>

                <label for="confirm_password">Confirm New Password:</label><br>
                <input type="password" id="confirm_password" name="confirm_password" required><br><br>

                <button type="submit"><i class="fas fa-save"></i> Change Password</button>
            </form>
        </div>

        <div class="back-to-profile">
            <a href="student_profile.php"><i class="fas fa-arrow-left"></i> Back to Profile</a>
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
