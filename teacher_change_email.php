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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "Email updated successfully.";
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e7ef 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50 60%, #2980b9 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(44,62,80,0.15);
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: background 0.4s;
        }
        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%;
        }
        .sidebar .logo img {
            display: block;
            width: 70%;
            max-width: 150px;
            height: auto;
            margin: auto;
        }
        .sidebar ul {
            list-style: none;
            width: 100%;
            padding: 0 15px;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            border-radius: 8px;
            margin-bottom: 10px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            transform: translateX(8px) scale(1.05);
            box-shadow: 0 2px 8px rgba(243,156,18,0.15);
        }
        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #f1c40f;
            transition: color 0.2s;
        }
        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #2c3e50;
        }
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
        }
        .main-content {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .toggle-mode-btn {
            position: absolute;
            top: 18px;
            right: 30px;
            background: #fff;
            color: #2c3e50;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }
        .toggle-mode-btn:hover {
            background: #f1c40f;
            color: #fff;
        }
        .change-email-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            background: linear-gradient(90deg, #f1c40f 0%, #f39c12 100%);
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 8px rgba(243,156,18,0.08);
            padding: 20px 30px;
            justify-content: flex-start;
        }
        .change-email-header h2 {
            font-size: 2em;
            color: #2c3e50;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(241,196,15,0.08);
            display: flex;
            align-items: center;
        }
        .change-email-header h2 i {
            margin-right: 10px;
            color: #f39c12;
        }
        .change-email-container {
            background: #fff;
            padding: 32px 28px 24px 28px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.06);
            margin-bottom: 20px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        .change-email-container h3 {
            font-size: 1.3em;
            color: #2c3e50;
            margin-bottom: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        .change-email-container h3 i {
            margin-right: 10px;
            color: #f39c12;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 18px;
            border-left: 5px solid #28a745;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 18px;
            border-left: 5px solid #dc3545;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .change-email-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .change-email-container input[type="email"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .change-email-container input[type="email"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.18);
            outline: none;
        }
        .change-email-container button[type="submit"] {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
            padding: 12px 0;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            width: 60%;              /* Thêm dòng này để nút không sát hai bên */
            margin: 0 auto;          /* Thêm dòng này để căn giữa nút */
        }
        .change-email-container button[type="submit"]:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            box-shadow: 0 4px 16px rgba(243,156,18,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .back-links {
            margin-top: 28px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .back-links a {
            display: inline-flex;
            align-items: center;
            color: #fff;
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            border-radius: 6px;
            padding: 12px 26px;
            box-shadow: 0 2px 8px rgba(243,156,18,0.10);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            border: none;
            outline: none;
            gap: 8px;
        }
        .back-links a:hover {
            background: linear-gradient(90deg, #2980b9 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .back-links a i {
            margin-right: 8px;
            font-size: 1.1em;
        }
        hr {
            margin-top: 30px;
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.85em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px;
        }
        footer a {
            color: #3498db;
            text-decoration: none;
            margin: 0 8px;
        }
        footer a:hover {
            text-decoration: underline;
        }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }

        /* Dark Mode */
        .dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }
        .dark-mode .sidebar {
            background-color: #333;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }
        .dark-mode .main-wrapper {
            background-color: #1a1a1a;
        }
        .dark-mode .main-content {
            background-color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .dark-mode .change-email-header {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
        }
        .dark-mode .change-email-header h2,
        .dark-mode .change-email-header h2 i {
            color: #181e29;
        }
        .dark-mode .change-email-container {
            background: #23272f;
            color: #eee;
        }
        .dark-mode .change-email-container h3 {
            color: #ffe082;
            border-bottom-color: #555;
        }
        .dark-mode .success-message {
            background: #384d3d;
            color: #a3d4a6;
            border-left-color: #28a745;
        }
        .dark-mode .error-message {
            background: #5e3237;
            color: #f0a3aa;
            border-left-color: #dc3545;
        }
        .dark-mode .change-email-container label {
            color: #ccc;
        }
        .dark-mode .change-email-container input[type="email"] {
            background-color: #3a3a3a;
            color: #eee;
            border-color: #555;
        }
        .dark-mode .change-email-container input[type="email"]:focus {
            border-color: #f39c12;
            box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
        }
        .dark-mode .change-email-container button[type="submit"] {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #181e29;
        }
        .dark-mode .change-email-container button[type="submit"]:hover {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
        }
        .dark-mode .back-links a {
            background: linear-gradient(90deg, #23272f 0%, #22304a 100%);
            color: #ffe082;
        }
        .dark-mode .back-links a:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #181e29;
        }
        .dark-mode footer {
            background: #23272f;
            color: #aaa;
        }
        .dark-mode footer a {
            color: #ffe082;
        }
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .change-email-header h2 { font-size: 1.8em; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
                padding-top: 0;
            }
            .sidebar .logo { padding: 15px 0; }
            .sidebar .logo img { width: 50%; max-width: 120px; }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar ul li { width: 48%; margin-bottom: 5px; }
            .sidebar ul li a {
                justify-content: center;
                padding: 10px;
                text-align: center;
                flex-direction: column;
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span {
                display: block;
                font-size: 0.8em;
            }
            .main-wrapper { margin-left: 0; padding: 20px; }
            .main-content { padding: 20px; min-height: unset; }
            .change-email-header { flex-direction: column; align-items: flex-start; margin-bottom: 20px; }
            .change-email-header h2 { margin-bottom: 10px; font-size: 1.8em; }
            .change-email-container { padding: 15px; }
            .back-links { flex-direction: column; gap: 15px; }
            footer { margin-top: 25px; }
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; flex-direction: row; }
            .sidebar ul li a i { margin-right: 10px; margin-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="teacher_courses.php"><i class="fas fa-book"></i> <span>My Courses</span></a></li>
            <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> <span>Manage Assignments</span></a></li>
            <li><a href="teacher_grades.php"><i class="fas fa-graduation-cap"></i> <span>Grade Submissions</span></a></li>
            <li><a href="teacher_students.php"><i class="fas fa-users"></i> <span>My Students</span></a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> <span>Send Notifications</span></a></li>
            <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> <span>View Notifications</span></a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> <span>Manage Quizzes</span></a></li>
            <li><a href="teacher_profile.php" class="active"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log out</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="change-email-header">
                <h2><i class="fas fa-envelope"></i> Change Email</h2>
            </div>
            <div class="change-email-container">
                <h3><i class="fas fa-edit"></i> Update Your Email Address</h3>
                <?php if (!empty($success)): ?>
                    <div class="success-message"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <label for="email">New Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($current_email ?? '') ?>" required>
                    <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
            <div class="back-links">
                <a href="teacher_profile.php"><i class="fas fa-arrow-left"></i> Back to Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
            </div>
        </div>
        <hr>
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
    <script>
        // Toggle dark/light mode
        const btn = document.getElementById('toggleModeBtn');
        btn.onclick = function() {
            document.body.classList.toggle('dark-mode');
            btn.innerHTML = document.body.classList.contains('dark-mode')
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
        };
    </script>
</body>
</html>