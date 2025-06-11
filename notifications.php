<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];
// Hiển thị system notifications
$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC");

// Lấy thông báo của user
$stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? AND type = 'teacher_notification' ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Đánh dấu đã đọc khi vào trang
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Notifications | BTEC FPT</title>
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
            width: 100%;
        }
        .main-content {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .notifications-header {
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
        .notifications-header h2 {
            font-size: 2em;
            color: #fff;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(251,191,36,0.18);
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
        .system-announcements {
            background-color: #e9ecef;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1.5px solid #fde68a;
        }
        .system-announcements h3 {
            font-size: 1.3em;
            color: #2563eb;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .system-announcements ul {
            list-style: none;
            padding-left: 0;
        }
        .system-announcements li {
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .system-announcements li:last-child {
            border-bottom: none;
        }
        .system-announcements li strong {
            color: #555;
            font-weight: bold;
            margin-right: 5px;
        }
        .user-notifications {
            margin-bottom: 30px;
        }
        .user-notifications h3 {
            font-size: 1.3em;
            color: #28a745;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .user-notifications ul {
            list-style: none;
            padding-left: 0;
        }
        .user-notifications li {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border: 1.5px solid #fde68a;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notification-info {
            flex-grow: 1;
        }
        .notification-date {
            font-size: 0.98em;
            color: #777;
            margin-bottom: 5px;
        }
        .notification-message {
            color: #333;
        }
        .notification-status {
            color: #eab308;
            font-weight: bold;
            margin-left: 15px;
        }
        .no-notifications {
            font-style: italic;
            color: #777;
        }
        .navigation-links {
            margin-top: 30px;
            font-size: 1.1em;
            display: flex;
            gap: 18px;
        }
        .navigation-links a {
            color: #fff;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.18s;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
        }
        .navigation-links a:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
            transform: translateY(-3px) scale(1.06);
            text-decoration: none;
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
        .dark-mode .notifications-header h2 {
            color: #fbbf24;
        }
        .dark-mode .notifications-header {
            background: linear-gradient(90deg, #2563eb 0%, #1e293b 100%);
        }
        .dark-mode .system-announcements {
            background-color: #222;
            border-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .system-announcements h3 {
            color: #fbbf24;
        }
        .dark-mode .system-announcements li {
            border-bottom-color: #444;
        }
        .dark-mode .system-announcements li strong {
            color: #ccc;
        }
        .dark-mode .user-notifications h3 {
            color: #a7f3d0;
        }
        .dark-mode .user-notifications ul li {
            background-color: #222;
            border-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .user-notifications ul li .notification-date {
            color: #ccc;
        }
        .dark-mode .user-notifications ul li .notification-status {
            color: #fbbf24;
        }
        .dark-mode .no-notifications {
            color: #fde68a;
        }
        .dark-mode .navigation-links a {
            color: #fbbf24;
            background: #1e293b;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .navigation-links a:hover {
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
            .notifications-header h2 { font-size: 1.8em; }
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
            .notifications-header { flex-direction: column; align-items: flex-start; }
            .notifications-header h2 { margin-bottom: 10px; font-size: 1.8em; }
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
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
            <li><a href="notifications.php" class="active"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <div class="notifications-header">
                <h2>Your Notifications</h2>
            </div>
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="system-announcements">
                <h3>Important Announcements</h3>
                <ul>
                    <?php while ($notif = $sys_notif_result->fetch_assoc()): ?>
                        <li>
                            <strong>[<?= date('Y-m-d H:i', strtotime($notif['created_at'])) ?>]</strong>
                            <?= htmlspecialchars($notif['message']) ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <div class="user-notifications">
                <h3>Your Recent Notifications</h3>
                <?php if ($result->num_rows > 0): ?>
                    <ul>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li>
                                <div class="notification-info">
                                    <p class="notification-date"><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></p>
                                    <p class="notification-message"><?= htmlspecialchars($row['message']) ?></p>
                                </div>
                                <?php if ($row['is_read'] == 0): ?>
                                    <span class="notification-status">(New)</span>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-notifications">No new notifications.</p>
                <?php endif; ?>
            </div>
            <div class="navigation-links">
                <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
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