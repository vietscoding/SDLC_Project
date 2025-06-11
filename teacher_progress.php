<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$stmt->bind_result($course_title);
if (!$stmt->fetch()) {
    echo "You do not have permission to view progress for this course.";
    exit;
}
$stmt->close();

// Lấy tổng số bài học trong khóa
$result = $conn->query("SELECT COUNT(*) AS total_lessons FROM lessons WHERE course_id = $course_id");
$total_lessons = $result->fetch_assoc()['total_lessons'];

// Lấy danh sách học viên đã enroll
$stmt = $conn->prepare("
    SELECT u.id, u.fullname
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    WHERE e.course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$students_result = $stmt->get_result();

// Lấy điểm trung bình quiz từng học viên
$avg_scores = [];
$quiz_scores_res = $conn->query("
    SELECT qs.user_id, AVG(qs.score) AS avg_score
    FROM quiz_submissions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    WHERE q.course_id = $course_id
    GROUP BY qs.user_id
");
while ($row = $quiz_scores_res->fetch_assoc()) {
    $avg_scores[$row['user_id']] = round($row['avg_score'], 2);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Tracking - <?= htmlspecialchars($course_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Reset some default styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e7ef 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50 60%, #2980b9 100%);
            color: white;
            position: fixed;
            height: 100vh;
            max-height: 100vh;
            overflow-y: auto;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(44,62,80,0.15);
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            scrollbar-width: thin;
            scrollbar-color: #f1c40f33 #2c3e5000;
        }
        .sidebar::-webkit-scrollbar {
            width: 6px;
            background: transparent;
            transition: opacity 0.2s;
            opacity: 0;
        }
        .sidebar:hover::-webkit-scrollbar {
            opacity: 1;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #f1c40f99 60%, #f39c1299 100%);
            border-radius: 6px;
            min-height: 30px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #f39c12cc 60%, #f1c40fcc 100%);
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%;
        }

        .sidebar .logo img {
            display: block;
            width: 70%; /* Smaller logo */
            max-width: 150px; /* Max size for logo */
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
            margin-bottom: 12px;
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

        .sidebar ul li a span {
            white-space: nowrap;
        }

        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px; /* Match sidebar width */
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
        }

        .main-content {
            background: rgba(255,255,255,0.97);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .progress-header h2 {
            font-size: 2em; /* Consistent with My Courses header */
            color: #2c3e50;
            margin: 0;
            font-weight: 500;
        }

        .progress-header h2 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for the icon */
        }

        .progress-table-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); /* Subtle shadow */
            margin-bottom: 30px;
            border: 1px solid #e0e0e0; /* Subtle border */
            border-left: 5px solid #3498db; /* Info blue accent */
        }

        .progress-table-section h3 {
            font-size: 1.6em;
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .progress-table-section h3 i {
            margin-right: 10px;
        }

        .progress-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .progress-table th, .progress-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .progress-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }

        .progress-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .progress-table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .back-to-courses {
            margin-top: 20px;
            text-align: center; /* Center the back link */
        }

        .back-to-courses a {
            color: #3498db; /* Consistent link color */
            text-decoration: none;
            font-weight: 500; /* Medium weight */
            font-size: 1em;
            transition: color 0.2s ease, text-decoration 0.2s ease;
            display: inline-flex; /* Use flex for alignment of icon and text */
            align-items: center;
            padding: 10px 15px; /* Add some padding */
            border-radius: 5px; /* Rounded corners */
            background-color: #ecf0f1; /* Light background for the button-like link */
            border: 1px solid #dcdcdc;
        }

        .back-to-courses a i {
            margin-right: 8px;
        }

        .back-to-courses a:hover {
            color: #2980b9;
            text-decoration: none; /* Remove underline on hover for button-like style */
            background-color: #e0e6eb;
            border-color: #c0c6cb;
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
            color: #3498db; /* Consistent link color */
            text-decoration: none;
            margin: 0 8px;
        }

        footer a:hover {
            text-decoration: underline;
        }

        footer p {
            margin: 5px 0;
        }

        .contact-info {
            margin-top: 15px;
        }

        .contact-info p {
            margin: 3px 0;
        }

        /* Dark Mode */
        .dark-mode {
            background: linear-gradient(135deg, #232526 0%, #414345 100%);
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #333;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-wrapper {
            background: transparent;
        }

        .dark-mode .main-content {
            background-color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .progress-header h2 {
            color: #f8f9fa;
        }

        .dark-mode .progress-header h2 i {
            color: #f39c12;
        }

        .dark-mode .progress-table-section {
            background-color: #2a2a2a;
            border-color: #444;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-left-color: #3498db;
        }

        .dark-mode .progress-table-section h3 {
            color: #f8f9fa;
            border-bottom-color: #555;
        }

        .dark-mode .progress-table th {
            background-color: #333;
            color: #eee;
        }

        .dark-mode .progress-table td {
            border-bottom-color: #555;
        }

        .dark-mode .progress-table tbody tr:nth-child(even) {
            background-color: #2a2a2a;
        }

        .dark-mode .progress-table tbody tr:hover {
            background-color: #383838;
        }

        .dark-mode .back-to-courses a {
            color: #f39c12;
            background-color: #3a3a3a;
            border-color: #555;
        }

        .dark-mode .back-to-courses a:hover {
            background-color: #4a4a4a;
            border-color: #666;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }

        .dark-mode footer a {
            color: #fbc531;
        }

        /* Navigation Links */
        .navigation-links {
            margin-top: 40px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
            padding-bottom: 20px;
        }
        .navigation-links a {
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
        .navigation-links a:hover {
            background: linear-gradient(90deg, #2980b9 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .navigation-links a i {
            margin-right: 8px;
            font-size: 1.1em;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            .main-wrapper {
                margin-left: 220px;
                padding: 25px;
            }
            .progress-header h2 {
                font-size: 1.8em;
            }
            .progress-table-section {
                padding: 20px;
            }
            .progress-table th, .progress-table td {
                padding: 10px 12px;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
                padding-top: 0;
            }
            .sidebar .logo {
                padding: 15px 0;
            }
            .sidebar .logo img {
                width: 50%;
                max-width: 120px;
            }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar ul li {
                width: 48%; /* Two items per row */
                margin-bottom: 5px;
            }
            .sidebar ul li a {
                justify-content: center;
                padding: 10px;
                text-align: center;
                flex-direction: column; /* Stack icon and text */
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span { /* Ensure span for text to stack */
                display: block;
                font-size: 0.8em;
            }

            .main-wrapper {
                margin-left: 0;
                padding: 20px;
            }

            .progress-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 20px;
            }
            .progress-header h2 {
                margin-bottom: 10px;
                font-size: 1.8em;
            }

            .progress-table-section {
                padding: 15px;
            }
            .progress-table th, .progress-table td {
                padding: 8px 10px;
                font-size: 0.9em;
            }
            .back-to-courses a {
                width: 100%;
                justify-content: center;
            }
            footer {
                margin-top: 25px;
            }
        }

        @media (max-width: 480px) {
            .sidebar ul li {
                width: 95%; /* One item per row */
            }
            .sidebar ul li a {
                justify-content: flex-start; /* Align text to start */
                flex-direction: row; /* Back to row for icon and text */
            }
            .sidebar ul li a i {
                margin-right: 10px;
                margin-bottom: 0;
            }
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="teacher_courses.php" class="sidebar-link"><i class="fas fa-book"></i> <span>My Courses</span></a></li>
            <li><a href="teacher_search_courses.php" class="sidebar-link"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="teacher_quiz_results.php" class="sidebar-link"><i class="fas fa-chart-bar"></i> <span>View Quiz Results</span></a></li>
            <li><a href="teacher_assignments.php" class="sidebar-link"><i class="fas fa-tasks"></i> <span>Manage Assignments</span></a></li>
            <li><a href="teacher_notifications.php" class="sidebar-link"><i class="fas fa-bell"></i> <span>Send Notifications</span></a></li>
            <li><a href="teacher_view_notifications.php" class="sidebar-link"><i class="fas fa-envelope-open-text"></i> <span>View Notifications</span></a></li>
            <li><a href="teacher_quizzes.php" class="sidebar-link"><i class="fas fa-question-circle"></i> <span>Manage Quizzes</span></a></li>
            <li><a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> <span>Log out</span></a></li>
        </ul>
    </div>

    <div class="main-wrapper">
        <div class="main-content">
            <div class="progress-header">
                <h2><i class="fas fa-tasks"></i> Progress Tracking for: <?= htmlspecialchars($course_title) ?></h2>
            </div>

            <div class="progress-table-section">
                <h3><i class="fas fa-list-alt"></i> Student Progress</h3>
                <table class="progress-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Lesson Completion</th>
                            <th>Average Quiz Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <?php
                                $student_id = $student['id'];
                                // Đếm số bài học đã hoàn thành
                                $stmt2 = $conn->prepare("SELECT COUNT(*) FROM progress WHERE user_id = ? AND course_id = ? AND is_completed = 1");
                                $stmt2->bind_param("ii", $student_id, $course_id);
                                $stmt2->execute();
                                $stmt2->bind_result($completed_lessons);
                                $stmt2->fetch();
                                $stmt2->close();

                                $completion_rate = ($total_lessons > 0) ? round(($completed_lessons / $total_lessons) * 100, 2) : 0;
                                $avg_quiz_score = $avg_scores[$student_id] ?? 'N/A';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($student['fullname']) ?></td>
                                <td><?= $completion_rate ?>%</td>
                                <td><?= $avg_quiz_score ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="navigation-links">
                <a href="teacher_courses.php"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
            </div>

           

            <hr style="margin-top:30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));">
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

    <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
        <i class="fas fa-moon"></i>
    </button>

    <script>
        // Toggle dark/light mode và lưu trạng thái vào localStorage
        const btn = document.getElementById('toggleModeBtn');
        function setDarkMode(on) {
            document.body.classList.toggle('dark-mode', on);
            btn.innerHTML = on
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
            localStorage.setItem('darkMode', on ? '1' : '0');
        }
        document.addEventListener('DOMContentLoaded', function() {
            setDarkMode(localStorage.getItem('darkMode') === '1');
            btn.onclick = function() {
                setDarkMode(!document.body.classList.contains('dark-mode'));
            };

            // Sidebar highlight tab hiện tại
            const currentPath = window.location.pathname.split('/').pop();
            const sidebarLinks = document.querySelectorAll('.sidebar ul li a.sidebar-link');
            sidebarLinks.forEach(link => {
                link.classList.remove('active');
                const linkHref = link.getAttribute('href');
                if (linkHref) {
                    const linkFileName = linkHref.split('/').pop();
                    if (linkFileName === 'teacher_courses.php' && 
                   (currentPath === 'teacher_courses.php' || 
                    currentPath.startsWith('teacher_lessons.php') || 
                    currentPath.startsWith('edit_lesson.php') ||
                    currentPath.startsWith('teacher_enrollments.php') ||
                    currentPath.startsWith('teacher_analytics.php') ||
                    currentPath.startsWith('teacher_progress.php'))) {
                    link.classList.add('active');
                } else if (linkFileName === currentPath) {
                    link.classList.add('active');
                }
            }
        });
        });
    </script>
</body>
</html>
