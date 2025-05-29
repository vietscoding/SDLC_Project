<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Lấy danh sách khóa học học viên đã đăng ký
$sql = "
SELECT c.id, c.title,
    (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS total_lessons,
    (SELECT COUNT(*) FROM progress p
        WHERE p.course_id = c.id AND p.user_id = ? AND p.is_completed = 1) AS completed_lessons
FROM courses c
JOIN enrollments e ON c.id = e.course_id
WHERE e.user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Learning Progress | [Your University Name]</title>
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
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.7;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            background-color: #0056b3;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .sidebar .logo img {
            display: block;
            width: 80%;
            height: auto;
            margin:auto;
        }

        .sidebar ul {
            list-style: none;
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
            border-left-color: #fbc531;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.2em;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            flex-grow: 1;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        .progress-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .progress-header h2 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .progress-header h3 {
            font-size: 1.5em;
            color: #555;
            margin-top: 10px;
            font-weight: 400;
        }

        .progress-list {
            list-style: none;
            padding-left: 0;
        }

        .progress-item {
            background-color: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .course-info {
            flex-grow: 1;
            margin-right: 20px;
        }

        .course-title {
            font-size: 1.2em;
            color: #0056b3;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .lessons-progress {
            color: #555;
            margin-bottom: 8px;
        }

        .progress-bar-container {
            background-color: #e0e0e0;
            border-radius: 5px;
            height: 10px;
            width: 100%;
            overflow: hidden;
        }

        .progress-bar {
            background-color: #28a745;
            height: 100%;
            border-radius: 5px;
        }

        .progress-percentage {
            min-width: 50px; /* Increased minimum width to accommodate spacing */
            text-align: right;
            color: #555;
            font-weight: bold;
            margin-left: 15px; /* Add some left margin to push it away from the progress bar */
            margin-right: 15px; /* Add some right margin to create space from the button */
        }

        .view-details-button {
            background-color: #0056b3;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .view-details-button:hover {
            background-color: #004080;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .no-courses {
            font-style: italic;
            color: #777;
        }

        .navigation-links {
            margin-top: 30px;
            font-size: 1.1em;
        }

        .navigation-links a {
            color: #0056b3;
            text-decoration: none;
            margin-right: 20px;
            font-weight: 600;
        }

        .navigation-links a:hover {
            text-decoration: underline;
            color: #004080;
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
            background-color: #222;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-content {
            background-color: #333;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .progress-header h2,
        .dark-mode .progress-header h3 {
            color: #f8f9fa;
        }

        .dark-mode .progress-list .progress-item {
            background-color: #444;
            border-color: #555;
            color: #eee;
        }

        .dark-mode .progress-list .course-title {
            color: #fbc531;
        }

        .dark-mode .progress-list .lessons-progress {
            color: #ccc;
        }

        .dark-mode .progress-list .progress-bar-container {
            background-color: #555;
        }

        .dark-mode .progress-list .progress-bar {
            background-color: #a7f3d0;
        }

        .dark-mode .progress-list .progress-percentage {
            color: #eee;
        }

        .dark-mode .progress-list .view-details-button {
            background-color: #fbc531;
            color: #222;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .progress-list .view-details-button:hover {
            background-color: #fbb003;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .no-courses {
            color: #ccc;
        }

        .dark-mode .navigation-links a {
            color: #fbc531;
        }

        .dark-mode .navigation-links a:hover {
            color: #fbb003;
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
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="progress.php" class="active"><i class="fas fa-chart-line"></i> Academic Progress</a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> Grades & Results</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="progress-header">
            <h2>My Learning Progress</h2>
            <h3>Track your progress across your enrolled courses.</h3>
        </div>

        <ul class="progress-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        $progress_percent = ($row['total_lessons'] > 0) ? round(($row['completed_lessons'] / $row['total_lessons']) * 100) : 0;
                    ?>
                    <li class="progress-item">
                        <div class="course-info">
                            <h4 class="course-title"><?= htmlspecialchars($row['title']) ?></h4>
                            <p class="lessons-progress"><?= $row['completed_lessons'] ?> / <?= $row['total_lessons'] ?> Lessons Completed</p>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?= $progress_percent ?>%;"></div>
                            </div>
                        </div>
                        <span class="progress-percentage"><?= $progress_percent ?>%</span>
                        <a href="course_detail.php?course_id=<?= $row['id'] ?>" class="view-details-button">View Course</a>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-courses">You are not enrolled in any courses yet.</p>
            <?php endif; ?>
        </ul>

        <div class="navigation-links">
            <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
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