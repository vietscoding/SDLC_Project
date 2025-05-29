<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC LIMIT 5"); // Limit to a few recent notifications
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | [Your University Name]</title>
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
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .dashboard-header h1 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
        }

        .dashboard-header .user-info {
            font-size: 0.9em;
            color: #777;
        }

        .notifications-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .notifications-section h3 {
            font-size: 1.4em;
            color: #2c3e50; /* Teacher-specific heading color */
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .notifications-section h3 i {
            margin-right: 10px;
        }

        .notifications-section ul {
            list-style: none;
            padding-left: 0;
        }

        .notifications-section ul li {
            padding: 10px 0;
            border-bottom: 1px solid #f2f2f2;
        }

        .notifications-section ul li:last-child {
            border-bottom: none;
        }

        .notifications-section ul li strong {
            color: #555;
            font-weight: bold;
            margin-right: 5px;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Slightly wider cards */
            gap: 25px;
        }

        .module-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-align: left; /* Align text to the left for a more modern look */
            transition: transform 0.2s ease-in-out;
            border-left: 5px solid #2c3e50; /* Teacher-specific accent border */
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        }

        .module-card img {
            width: 50px; /* Slightly smaller icons */
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .module-card h4 {
            font-size: 1.6em;
            color: #333;
            margin-bottom: 10px;
        }

        .module-card p {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 15px;
        }

        .module-card a {
            display: inline-block;
            text-decoration: none;
            color: #2c3e50; /* Teacher-specific link color */
            font-weight: 600;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #2c3e50; /* Teacher-specific border color */
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .module-card a:hover {
            background-color: #2c3e50; /* Teacher-specific hover background */
            color: white;
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

        .dark-mode .dashboard-header h1,
        .dark-mode .notifications-section h3,
        .dark-mode .module-card h4 {
            color: #f8f9fa;
        }

        .dark-mode .notifications-section,
        .dark-mode .module-card,
        .dark-mode footer {
            background-color: #444;
            border-color: #555;
        }

        .dark-mode .notifications-section ul li {
            border-bottom-color: #555;
        }

        .dark-mode .module-card a {
            color: #f39c12; /* Teacher-specific dark mode accent */
            border-color: #f39c12;
        }

        .dark-mode .module-card a:hover {
            background-color: #f39c12;
            color: #222;
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
            <li><a href="teacher_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
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
        <div class="dashboard-header">
            <h1>Welcome, <?= $fullname; ?></h1>
            <div class="user-info">
                <i class="fas fa-user-tie"></i> Role: <?= $role; ?>
            </div>
        </div>

        <?php if ($sys_notif_result->num_rows > 0): ?>
            <section class="notifications-section">
                <h3><i class="fas fa-bullhorn"></i> Announcements</h3>
                <ul>
                    <?php while ($notif = $sys_notif_result->fetch_assoc()): ?>
                        <li><strong><?= date('M d, Y', strtotime($notif['created_at'])); ?></strong> - <?= htmlspecialchars($notif['message']); ?></li>
                    <?php endwhile; ?>
                </ul>
                <?php if ($conn->query("SELECT message FROM system_notifications")->num_rows > 5): ?>
                    <p style="margin-top: 10px; text-align: right;"><a href="teacher_view_notifications.php">View All Announcements</a></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="module-grid">
            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Courses">
                <h4>Manage My Courses</h4>
                <p>View and manage the courses you are teaching.</p>
                <a href="teacher_courses.php">Go to Courses</a>
            </div>

            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/2910/2910768.png" alt="Search">
                <h4>Search Courses</h4>
                <p>Find other available courses in the system.</p>
                <a href="teacher_search_courses.php">Search Courses</a>
            </div>

            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/2712/2712200.png" alt="Results">
                <h4>View Quiz Results</h4>
                <p>See how your students performed on quizzes.</p>
                <a href="teacher_quiz_results.php">View Results</a>
            </div>

            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/870/870687.png" alt="Assignments">
                <h4>Manage Assignments</h4>
                <p>Create, edit, and grade assignments for your courses.</p>
                <a href="teacher_assignments.php">Manage Assignments</a>
            </div>

            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/107/107827.png" alt="Notifications">
                <h4>Send Notifications</h4>
                <p>Communicate important information to your students.</p>
                <a href="teacher_notifications.php">Send Notification</a>
            </div>

            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/2782/2782291.png" alt="View Notifications">
                <h4>View Notifications</h4>
                <p>See the history of notifications you've sent.</p>
                <a href="teacher_view_notifications.php">View Notifications</a>
            </div>

            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/3248/3248341.png" alt="Quizzes">
                <h4>Manage Quizzes</h4>
                <p>Create and edit quizzes for your courses.</p>
                <a href="teacher_quizzes.php">Manage Quizzes</a>
            </div>

            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="Profile">
                <h4>My Profile</h4>
                <p>View your account.</p>
                <a href="teacher_profile.php">My Profile</a>
            </div>

            <div class="module-card">
                <img src="https://cdn-icons-png.flaticon.com/512/3361/3361953.png" alt="Logout">
                <h4>Logout</h4>
                <p>Securely exit your teacher account.</p>
                <a href="logout.php">Logout</a>
            </div>
        </section>
        

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