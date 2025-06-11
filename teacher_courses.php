<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Lấy danh sách khóa học giáo viên phụ trách
$stmt = $conn->prepare("SELECT id, title, description FROM courses WHERE teacher_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Courses | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Reset */
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

        .courses-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(90deg, #f1c40f 0%, #f39c12 100%);
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 8px rgba(243,156,18,0.08);
            padding: 20px 30px;
        }
        .courses-header h2 {
            font-size: 2em;
            color: #2c3e50;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(241,196,15,0.08);
        }
        .courses-header h2 i {
            margin-right: 10px;
            color: #f39c12;
        }

        .course-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-direction: column; /* Hiển thị dọc */
            gap: 28px;
            margin-top: 35px;
        }
        .course-item {
            background: rgba(255,255,255,0.98);
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
            overflow: hidden;
            transition: transform 0.25s cubic-bezier(.17,.67,.83,.67), box-shadow 0.25s;
            border: none;
            position: relative;
            cursor: pointer;
            display: flex;
            flex-direction: row; /* Ảnh và nội dung nằm ngang */
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }
        .course-item:hover {
            transform: translateY(-8px) scale(1.03) rotate(-1deg);
            box-shadow: 0 16px 40px rgba(243,156,18,0.18);
            background: linear-gradient(120deg, #f1c40f 0%, #fffbe6 100%);
        }
        .course-item-image {
            position: relative;
            width: 260px;
            min-width: 180px;
            height: 180px;
            background-size: cover;
            background-position: center;
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
            border-top-right-radius: 0;
            overflow: hidden;
        }
        .course-item-image::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg,rgba(44,62,80,0.15) 60%,rgba(241,196,15,0.13) 100%);
            z-index: 1;
        }
        .course-icon {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 2;
            background: rgba(255,255,255,0.85);
            color: #f39c12;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6em;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            border: 2px solid #fffbe6;
        }
        .course-item-content {
            padding: 24px 28px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .course-item-title {
            font-size: 1.25em;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .course-item-description {
            color: #555;
            margin-bottom: 15px;
            font-size: 1em;
            line-height: 1.5;
            flex-grow: 1;
        }
        .course-item-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .course-item-actions a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(90deg, #3498db 0%, #6dd5fa 100%);
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 0.95em;
            font-weight: 600;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(41,128,185,0.10);
        }
        .course-item-actions a i {
            margin-right: 8px;
            font-size: 1em;
        }
        .course-item-actions a:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            box-shadow: 0 4px 16px rgba(243,156,18,0.13);
        }

        .no-courses {
            background-color: #fdfdfd;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            text-align: center;
            color: #777;
            font-style: italic;
            font-size: 1em;
            border: 1px solid #e0e0e0;
            max-width: 700px;
            margin: 0 auto;
        }
        .no-courses i {
            margin-right: 8px;
            color: #f39c12;
        }

        .navigation-links {
            margin-top: 40px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .navigation-links a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            font-size: 1em;
            transition: color 0.2s, text-decoration 0.2s;
            display: flex;
            align-items: center;
        }
        .navigation-links a i {
            margin-right: 8px;
        }
        .navigation-links a:hover {
            color: #2980b9;
            text-decoration: underline;
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
        .dark-mode .courses-header h2 {
            color: #f8f9fa;
        }
        .dark-mode .courses-header h2 i {
            color: #f39c12;
        }
        .dark-mode .course-item {
            background-color: #2a2a2a;
            border-color: #444;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .dark-mode .course-item-title {
            color: #f8f9fa;
        }
        .dark-mode .course-item-description {
            color: #ccc;
        }
        .dark-mode .course-item-actions a {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #222;
        }
        .dark-mode .course-item-actions a:hover {
            background: linear-gradient(90deg, #3498db 0%, #6dd5fa 100%);
            color: #fff;
        }
        .dark-mode .no-courses {
            background-color: #333;
            color: #ccc;
            border-color: #444;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .dark-mode .no-courses i {
            color: #f39c12;
        }
        .dark-mode .navigation-links a {
            color: #fbc531;
        }
        .dark-mode .navigation-links a:hover {
            color: #e08e0b;
            text-decoration: underline;
        }
        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }
        .dark-mode footer a {
            color: #fbc531;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .courses-header h2 { font-size: 1.8em; }
            .course-item-title { font-size: 1.3em; }
            .course-item-image { height: 150px; }
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
            .courses-header { flex-direction: column; align-items: flex-start; margin-bottom: 20px; }
            .courses-header h2 { margin-bottom: 10px; font-size: 1.8em; }
            .course-list { gap: 20px; }
            .course-item { max-width: 100%; }
            .course-item-content { padding: 15px; }
            .course-item-title { font-size: 1.4em; }
            .course-item-description { font-size: 0.9em; }
            .course-item-actions { flex-direction: column; gap: 8px; }
            .course-item-actions a { width: 100%; justify-content: center; }
            .navigation-links { flex-direction: column; gap: 15px; }
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
            <li><a href="teacher_courses.php" class="active"><i class="fas fa-book"></i> <span>My Courses</span></a></li>
            <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> <span>View Quiz Results</span></a></li>
            <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> <span>Manage Assignments</span></a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> <span>Send Notifications</span></a></li>
            <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> <span>View Notifications</span></a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> <span>Manage Quizzes</span></a></li>
            <li><a href="teacher_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log out</span></a></li>
        </ul>
    </div>

    <div class="main-wrapper">
        <div class="main-content">
            <!-- Nút chuyển dark/light mode -->
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="courses-header">
                <h2><i class="fas fa-graduation-cap"></i> My Courses</h2>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <ul class="course-list">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <li class="course-item">
                            <div class="course-item-image" style="background-image: url('https://source.unsplash.com/random/800x400?education&sig=<?= $row['id'] ?>');">
                                <span class="course-icon"><i class="fas fa-book-open"></i></span>
                            </div>
                            <div class="course-item-content">
                                <h3 class="course-item-title"><?= htmlspecialchars($row['title']) ?></h3>
                                <p class="course-item-description"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                                <div class="course-item-actions">
                                    <a href="teacher_lessons.php?course_id=<?= $row['id'] ?>"><i class="fas fa-list-ul"></i> Manage Lessons</a>
                                    <a href="teacher_enrollments.php?course_id=<?= $row['id'] ?>"><i class="fas fa-users"></i> View Enrollments</a>
                                    <a href="teacher_analytics.php?course_id=<?= $row['id'] ?>"><i class="fas fa-chart-pie"></i> View Analytics</a>
                                    <a href="teacher_progress.php?course_id=<?= $row['id'] ?>"><i class="fas fa-chart-line"></i> View Progress</a>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-courses"><i class="fas fa-exclamation-triangle"></i> You are not assigned to any courses yet.</p>
            <?php endif; ?>

            <div class="navigation-links">
                <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
            </div>
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