<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current_password, $hashed_password)) {
        $error = "Incorrect current password.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
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

        .change-password-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .change-password-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 20px;
            display: flex;
            align-items: center;
        }

        .change-password-header h2 i {
            margin-right: 10px;
            color: #e74c3c; /* Red icon for admin */
        }

        .change-password-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin:auto;
            width: 100%;
            max-width: 500px;
        }

        .change-password-container h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .change-password-container h3 i {
            margin-right: 10px;
            color: #007bff; /* Blue icon */
        }

        .change-password-container form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .change-password-container form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .change-password-container form button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .change-password-container form button[type="submit"]:hover {
            background-color: #0056b3;
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

        .back-link {
            margin-top: 20px;
        }

        .back-link a {
            color: #6c757d;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
            color: #495057;
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

        .dark-mode .change-password-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .change-password-container {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .change-password-container h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .change-password-container form label {
            color: #eee;
        }

        .dark-mode .change-password-container form input[type="password"] {
            background-color: #495057;
            color: #eee;
            border-color: #6c757d;
        }

        .dark-mode .change-password-container form button[type="submit"] {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .success-message {
            color: #28a745;
        }

        .dark-mode .error-message {
            color: #dc3545;
        }

        .dark-mode .back-link a {
            color: #a7b1b8;
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
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_courses.php"><i class="fas fa-book"></i> Manage Courses</a></li>
        <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="change-password-header">
        <h2><i class="fas fa-key"></i> Change Password</h2>
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

    <div class="back-link">
        <a href="admin_profile.php"><i class="fas fa-arrow-left"></i> Back to Profile</a>
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