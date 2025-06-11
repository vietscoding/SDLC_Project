<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";
$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC LIMIT 5");
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #fefce8 0%, #e0e7ff 100%);
            color: #222;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2563eb 60%, #fbbf24 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(37,99,235,0.10);
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
            filter: drop-shadow(0 2px 8px rgba(251,191,36,0.10));
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
            color: #fff;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            border-radius: 12px;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #1e40af;
            border: 2px solid #fbbf24;
        }
        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #fde68a;
            transition: color 0.2s;
        }
        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #2563eb;
        }
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
        }
        .main-content {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #fde68a;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            border-radius: 16px 16px 0 0;
            box-shadow: 0 2px 8px rgba(251,191,36,0.08);
            padding: 20px 30px;
        }
        .dashboard-header h1 {
            font-size: 2em;
            color: #fff;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(251,191,36,0.18);
        }
        .dashboard-header .user-info {
            font-size: 1.1em;
            color: #1e293b; /* Đổi sang màu xanh đậm dễ đọc */
            font-weight: 700;
            text-shadow: 0 1px 4px #fff, 0 1px 8px #fbbf24; /* Thêm bóng chữ cho nổi bật */
        }
        .toggle-mode-btn {
            position: absolute;
            top: 18px;
            right: 30px;
            background: #fde68a;
            color: #2563eb;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }
        .toggle-mode-btn:hover {
            background: #2563eb;
            color: #fde68a;
        }
        .notifications-section {
            background-color: #fef9c3;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(251,191,36,0.04);
            margin-bottom: 30px;
            border: 1px solid #fde68a;
        }
        .notifications-section h3 {
            font-size: 1.3em;
            color: #2563eb;
            margin-bottom: 15px;
            border-bottom: 1px solid #fde68a;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        .notifications-section h3 i {
            color: #fbbf24;
            margin-right: 10px;
        }
        .notifications-section ul li {
            padding: 10px 0;
            border-bottom: 1px dashed #fde68a;
            font-size: 0.95em;
            color: #1e293b;
        }
        .notifications-section ul li strong {
            color: #2563eb;
            font-weight: 600;
            margin-right: 5px;
        }
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 28px;
            margin-top: 35px;
        }
        .module-card {
            background: #fff;
            padding: 32px 24px 24px 24px;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            text-align: left;
            transition: 
                transform 0.25s cubic-bezier(.17,.67,.83,.67), 
                box-shadow 0.25s,
                background 0.25s;
            border: 2px solid #fde68a;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .module-card:hover {
            transform: translateY(-10px) scale(1.06) rotate(-1deg);
            box-shadow: 0 16px 40px rgba(37,99,235,0.18);
            background: linear-gradient(120deg, #fde68a 0%, #2563eb 100%);
        }
        .module-card img {
            width: 54px;
            height: 54px;
            margin-bottom: 18px;
            filter: drop-shadow(0 2px 8px rgba(251,191,36,0.10));
            transition: transform 0.2s;
        }
        .module-card:hover img {
            transform: scale(1.12) rotate(-6deg);
        }
        .module-card h4 {
            font-size: 1.25em;
            color: #2563eb;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .module-card p {
            color: #1e293b;
            font-size: 1em;
            margin-bottom: 18px;
        }
        .module-card a {
            display: inline-block;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            font-size: 1em;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
        }
        .module-card a:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.85em;
            color: #2563eb;
            background-color: #fde68a;
            border-top: 1px solid #fbbf24;
            border-radius: 0 0 12px 12px;
        }
        footer a {
            color: #2563eb;
            text-decoration: none;
            margin: 0 8px;
        }
        footer a:hover {
            text-decoration: underline;
            color: #fbbf24;
        }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }
        /* Dark Mode */
        .dark-mode {
            background-color: #1e293b;
            color: #fde68a;
        }
        .dark-mode .sidebar {
            background: linear-gradient(135deg, #1e293b 60%, #fbbf24 100%);
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }
        .dark-mode .main-wrapper {
            background-color: #1e293b;
        }
        .dark-mode .main-content {
            background-color: #1e293b;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .dark-mode .dashboard-header h1,
        .dark-mode .notifications-section h3,
        .dark-mode .module-card h4 {
            color: #fbbf24;
        }
        .dark-mode .notifications-section,
        .dark-mode .module-card {
            background-color: #1e293b;
            border-color: #fbbf24;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .dark-mode .notifications-section h3 i {
            color: #fbbf24;
        }
        .dark-mode .notifications-section ul li {
            border-bottom-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .notifications-section ul li strong {
            color: #fbbf24;
        }
        .dark-mode .module-card a {
            color: #fbbf24;
            background: #1e293b;
            border: 1px solid #fbbf24;
        }
        .dark-mode .module-card a:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode footer {
            background-color: #1e293b;
            color: #fde68a;
            border-top-color: #fbbf24;
        }
        .dark-mode footer a {
            color: #fbbf24;
        }
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .dashboard-header h1 { font-size: 1.8em; }
            .module-card h4 { font-size: 1.3em; }
        }
        @media (max-width: 768px) {
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
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span { display: block; font-size: 0.8em; }
            .main-wrapper { margin-left: 0; padding: 20px; }
            .dashboard-header { flex-direction: column; align-items: flex-start; }
            .dashboard-header h1 { margin-bottom: 10px; font-size: 1.8em; }
            .dashboard-header .user-info { font-size: 0.9em; }
            .module-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
            .module-card { padding: 20px; }
            .module-card h4 { font-size: 1.2em; }
            .module-card p { font-size: 0.85em; }
            .module-card a { font-size: 0.85em; padding: 6px 12px; }
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; }
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
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <div class="dashboard-header">
                <h1>Welcome, <?= $fullname; ?></h1>
                <div class="user-info">
                    <i class="fas fa-user-graduate"></i> Role: <?= $role; ?>
                </div>
            </div>
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <?php if ($sys_notif_result->num_rows > 0): ?>
                <section class="notifications-section">
                    <h3><i class="fas fa-bullhorn"></i> Announcements</h3>
                    <ul>
                        <?php while ($notif = $sys_notif_result->fetch_assoc()): ?>
                            <li><strong><?= date('M d, Y', strtotime($notif['created_at'])); ?></strong> - <?= htmlspecialchars($notif['message']); ?></li>
                        <?php endwhile; ?>
                    </ul>
                    <?php if ($conn->query("SELECT message FROM system_notifications")->num_rows > 5): ?>
                        <p style="margin-top: 10px; text-align: right;"><a href="notifications.php">View All Announcements</a></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
            <section class="module-grid">
                <div class="module-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Courses">
                    <h4>Explore Courses</h4>
                    <p>Browse and enroll in a wide range of academic courses.</p>
                    <a href="courses.php">View Courses</a>
                </div>
                <div class="module-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/2910/2910768.png" alt="Search">
                    <h4>Find Your Course</h4>
                    <p>Quickly search for specific courses by title or instructor.</p>
                    <a href="student_search_courses.php">Search Now</a>
                </div>
                <div class="module-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/953/953773.png" alt="Progress">
                    <h4>Track Your Progress</h4>
                    <p>Monitor your academic performance and course completion.</p>
                    <a href="progress.php">View Progress</a>
                </div>
                <div class="module-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/870/870687.png" alt="Assignments">
                    <h4>Manage Assignments</h4>
                    <p>View upcoming deadlines and submit your work.</p>
                    <a href="student_assignments.php">View Assignments</a>
                </div>
                <div class="module-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/2712/2712200.png" alt="Results">
                    <h4>See Your Grades</h4>
                    <p>Check your grades and feedback for completed assignments.</p>
                    <a href="student_view_assignments.php">View Results</a>
                </div>
                <div class="module-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/107/107827.png" alt="Notifications">
                    <h4>Stay Informed</h4>
                    <p>Receive important updates and announcements from the system.</p>
                    <a href="notifications.php">View Notifications</a>
                </div>
                <div class="module-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="Profile">
                    <h4>My Profile</h4>
                    <p>View your account.</p>
                    <a href="student_profile.php">My Profile</a>
                </div>
                <div class="module-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/3361/3361953.png" alt="Logout">
                    <h4>Logout</h4>
                    <p>Securely exit your student account.</p>
                    <a href="logout.php">Logout</a>
                </div>
            </section>
        </div>
        <hr style ="margin-top:30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));">
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